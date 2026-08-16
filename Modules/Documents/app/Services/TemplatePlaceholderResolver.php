<?php

declare(strict_types=1);

namespace Modules\Documents\Services;

use Modules\Documents\Data\DocumentDataContext;
use Modules\Documents\Exceptions\UnknownPlaceholderException;

/**
 * Safe template placeholder resolver.
 *
 * Resolves `{{ dot.key }}` tokens in a template body against a typed
 * DocumentDataContext. Only keys that appear in DocumentDataContext::toFlatArray()
 * are permitted — anything else causes an UnknownPlaceholderException.
 *
 * Security contract:
 *   - No eval(), no Blade rendering, no shell execution.
 *   - The replacement loop operates on pre-validated, whitelisted keys only.
 *   - Template bodies pass through sanitizeHtml() before storage and before
 *     rendering; sanitizeHtml() uses DOMDocument so all HTML entity encodings
 *     (e.g. java&#x73;cript:) are decoded before attribute-value checks, making
 *     entity-encoding XSS bypasses impossible.
 *
 * Placeholder format: `{{ key }}` (any amount of interior whitespace).
 */
final class TemplatePlaceholderResolver
{
    /** @var string Regex that matches {{ any.key }} with optional interior spaces. */
    private const PLACEHOLDER_PATTERN = '/\{\{\s*([\w.]+)\s*\}\}/';

    /**
     * Tags removed along with ALL their descendants and text content.
     * Any tag whose content should never appear in the output lives here.
     *
     * @var list<string>
     */
    private const DANGEROUS_TAGS = [
        'script', 'noscript', 'iframe', 'frame', 'frameset',
        'object', 'embed', 'applet',
        'form', 'input', 'button', 'select', 'textarea',
        'base', 'link', 'meta',
        // <style> blocks are removed entirely (including their CSS text content)
        // because mPDF resolves @import url(…) and background: url(…) values in
        // style blocks by making outbound HTTP requests — an SSRF vector.
        // Inline style="" attributes are still permitted and sanitized below.
        'style',
    ];

    /**
     * Tags permitted in the output.
     * Any tag NOT in this list is removed (its children are unwrapped into the parent).
     *
     * @var list<string>
     */
    private const ALLOWED_TAGS = [
        'p', 'div', 'span',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td',
        'ul', 'ol', 'li',
        'br', 'hr',
        'b', 'i', 'em', 'strong', 'u', 's', 'del', 'ins',
        'sub', 'sup',
        'img', 'a',
        // NOTE: <style> is intentionally excluded.
        // mPDF fetches remote stylesheets referenced via @import url(…) inside <style>
        // blocks, creating an SSRF path. Inline style="" attributes are allowed
        // (and sanitized below). Top-level <style> block injection is rejected here.
        'colgroup', 'col', 'caption',
        'blockquote', 'pre', 'code',
        'html', 'head', 'body', 'title', '!doctype', '#text', '#document',
    ];

    /**
     * URL-bearing attributes whose values are decoded and checked for dangerous schemes.
     *
     * @var list<string>
     */
    private const URL_ATTRIBUTES = ['href', 'src', 'action', 'formaction', 'data', 'poster', 'background'];

    /**
     * URI schemes that may never appear in any URL attribute value.
     * Matching is done AFTER html_entity_decode() so &#x73; evasions are caught.
     *
     * @var list<string>
     */
    private const DANGEROUS_SCHEMES = ['javascript', 'vbscript', 'data', 'mhtml'];

    // -----------------------------------------------------------------
    // Public API
    // -----------------------------------------------------------------

    /**
     * Extract every placeholder key found in the template body.
     *
     * @return string[] Unique keys in order of first appearance
     */
    public function extractPlaceholders(string $body): array
    {
        preg_match_all(self::PLACEHOLDER_PATTERN, $body, $matches);

        return array_values(array_unique($matches[1]));
    }

    /**
     * Validate that all placeholders in the body are in the approved catalogue.
     *
     * @throws UnknownPlaceholderException When any unknown key is found
     */
    public function validateBody(string $body): void
    {
        $found = $this->extractPlaceholders($body);
        $allowed = array_keys(DocumentDataContext::synthetic()->toFlatArray());
        $unknown = array_diff($found, $allowed);

        if ($unknown !== []) {
            throw new UnknownPlaceholderException(array_values($unknown));
        }
    }

    /**
     * Resolve all approved placeholders in the body against the given context.
     *
     * This method trusts that validateBody() was already called at draft/activation
     * time. It still silently skips any unrecognised key rather than executing it,
     * but validated bodies will have no unrecognised keys to skip.
     */
    public function resolve(string $body, DocumentDataContext $context): string
    {
        $values = $context->toFlatArray();

        return preg_replace_callback(
            self::PLACEHOLDER_PATTERN,
            static function (array $matches) use ($values): string {
                $key = $matches[1];

                return array_key_exists($key, $values)
                    ? htmlspecialchars((string) $values[$key], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                    : $matches[0]; // leave unknown placeholders verbatim (should not happen after validation)
            },
            $body,
        ) ?? $body;
    }

    /**
     * Strip dangerous HTML from a template body, header, or footer fragment.
     *
     * The "no JavaScript" contract is enforced at the storage layer AND at the
     * render layer so both paths are safe regardless of when a draft was created.
     *
     * Implementation — DOMDocument-based sanitizer:
     *
     *   1. Parse the HTML fragment through DOMDocument. DOMDocument decodes all
     *      HTML entity references in attribute values, so `java&#x73;cript:` is
     *      seen as `javascript:` by the time we inspect it. Regex-only approaches
     *      cannot reliably handle this encoding bypass.
     *
     *   2. Walk the DOM tree depth-first, collecting nodes to remove into a
     *      separate list (mutating the tree during iteration would skip nodes).
     *
     *   3. DANGEROUS_TAGS: remove the element AND all its descendants. This
     *      prevents `<script>stealData()</script>` from leaving `stealData()` text
     *      behind (the failure mode of raw strip_tags()).
     *
     *   4. Non-allowed, non-dangerous tags: unwrap — remove the element but keep
     *      its text and child nodes in the parent. Preserves visible content for
     *      unknown layout wrappers while neutralising the tag itself.
     *
     *   5. Attribute allowlist: strip any attribute not in the global safe list.
     *      event-handler attributes (on*) are rejected by not being in the list.
     *
     *   6. URL attributes (href, src, etc.): after DOMDocument decoding, decode
     *      any remaining HTML entities and strip control characters, then reject
     *      values that begin with a dangerous scheme (javascript:, data:, etc.).
     *
     *   7. style attributes: strip CSS expression() and any url(javascript:) tokens.
     *
     *   8. Serialize back to HTML and extract the inner content of <body>.
     *
     * Called by DocumentTemplateVersionService::createDraft() (storage layer) and
     * DocumentTemplateVersionService::renderPreviewHtml() (render layer).
     */
    public function sanitizeHtml(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        // ── 1. Parse ────────────────────────────────────────────────
        libxml_use_internal_errors(true);
        $doc = new \DOMDocument('1.0', 'UTF-8');

        // mb_convert_encoding workaround: loadHTML needs the charset hint so
        // DOMDocument does not mangle UTF-8/Arabic multi-byte characters.
        $wrappedHtml = '<?xml encoding="utf-8" ?><html><head><meta charset="utf-8"></head><body>'
            .$html
            .'</body></html>';

        $doc->loadHTML($wrappedHtml, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_COMPACT);
        libxml_clear_errors();

        // ── 2. Walk and collect nodes to act on ─────────────────────
        $body = $doc->getElementsByTagName('body')->item(0);

        if ($body === null) {
            return '';
        }

        $this->sanitizeNode($doc, $body);

        // ── 3. Serialize body content ────────────────────────────────
        $result = '';

        foreach ($body->childNodes as $child) {
            $result .= $doc->saveHTML($child);
        }

        return $result;
    }

    /**
     * Compute the canonical content hash for a template body.
     *
     * The canonical form strips leading/trailing whitespace and normalises
     * Unicode to NFC before hashing. The same hash is stored in
     * `document_template_versions.content_hash` on activation.
     */
    public function contentHash(string $body): string
    {
        $normalized = normalizer_normalize(trim($body), \Normalizer::FORM_C);

        // Fall back to trimmed body if intl extension is unavailable.
        return hash('sha256', $normalized !== false ? $normalized : trim($body));
    }

    // -----------------------------------------------------------------
    // Private DOM sanitization helpers
    // -----------------------------------------------------------------

    /**
     * Recursively sanitize all child nodes of $parent.
     *
     * Works on a collected snapshot of child nodes so that DOM mutations
     * (removeChild / insertBefore) during the walk do not cause skipped nodes.
     */
    private function sanitizeNode(\DOMDocument $doc, \DOMNode $parent): void
    {
        // Snapshot: collect children into an array before mutating the tree.
        $children = [];

        foreach ($parent->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $node) {
            if (! ($node instanceof \DOMElement)) {
                // Text nodes and comments — keep text, remove comment nodes.
                if ($node instanceof \DOMComment) {
                    $parent->removeChild($node);
                }

                continue;
            }

            $tagName = strtolower($node->tagName);

            // Dangerous tag: remove element AND all its descendants.
            if (in_array($tagName, self::DANGEROUS_TAGS, strict: true)) {
                $parent->removeChild($node);

                continue;
            }

            // Allowed tag: sanitize attributes, then recurse into children.
            if (in_array($tagName, self::ALLOWED_TAGS, strict: true)) {
                $this->sanitizeAttributes($node);
                $this->sanitizeNode($doc, $node);

                continue;
            }

            // Unknown tag: sanitize its descendants first, THEN unwrap.
            //
            // Critical ordering: children must be recursively sanitized before
            // they are moved into the parent, otherwise a nested dangerous tag
            // (e.g. <svg><script>alert(1)</script></svg>) would be transplanted
            // into the parent's allowed-tag space without ever being examined.
            // Calling sanitizeNode() on the unknown element processes all of its
            // descendants in-place (removes <script>, strips bad attrs, etc.)
            // before the element shell is removed.
            $this->sanitizeNode($doc, $node);

            $fragment = $doc->createDocumentFragment();

            foreach (iterator_to_array($node->childNodes) as $child) {
                $fragment->appendChild($child);
            }

            $parent->replaceChild($fragment, $node);
        }
    }

    /**
     * Strip disallowed attributes from an element.
     * URL attributes are additionally checked for dangerous schemes.
     * style attributes are checked for CSS expression() and url(javascript:).
     */
    private function sanitizeAttributes(\DOMElement $element): void
    {
        // Collect attribute names first — mutating the attribute list while
        // iterating it is undefined behaviour in some PHP versions.
        $attrNames = [];

        foreach ($element->attributes as $attr) {
            $attrNames[] = $attr->name;
        }

        // Global safe attributes (case-insensitive match via strtolower below)
        $globalAllowed = [
            'dir', 'lang', 'class', 'id', 'title',
            'style',
            // table layout
            'colspan', 'rowspan', 'width', 'height', 'align', 'valign',
            'border', 'cellpadding', 'cellspacing',
            // img
            'alt',
            // a
            'target', 'rel',
            // misc layout
            'bgcolor', 'color', 'size',
        ];

        foreach ($attrNames as $name) {
            $lower = strtolower($name);

            // Reject event-handler attributes (on*)
            if (str_starts_with($lower, 'on')) {
                $element->removeAttribute($name);

                continue;
            }

            // Reject XML-namespace event handlers (xlink:*, xml:*, etc.)
            if (str_contains($lower, ':') && ! in_array($lower, ['xml:lang', 'xml:space'], strict: true)) {
                $element->removeAttribute($name);

                continue;
            }

            // Reject attributes not in the global allowlist, unless they are URL attributes
            $isUrlAttr = in_array($lower, self::URL_ATTRIBUTES, strict: true);

            if (! $isUrlAttr && ! in_array($lower, $globalAllowed, strict: true)) {
                $element->removeAttribute($name);

                continue;
            }

            // Resource URL attributes (src, action, href, …) are ALWAYS removed.
            //
            // Threat model:
            //   - Outbound HTTP: DenyingHttpClient blocks all cURL requests.
            //   - Local file inclusion via fopen(): mPDF's LocalContentLoader resolves
            //     any path that does not contain "://" via fopen() directly. A value of
            //     `src="storage/app/private/key.png"` causes the PDF worker to open that
            //     file, regardless of scheme or traversal. DenyingHttpClient does NOT
            //     intercept fopen()-based reads.
            //   - Scheme-based: any URI scheme (http:, https:, file:, ftp:, javascript:,
            //     data:) enables fetch or execution.
            //   - No safe relative-path subset exists unless mPDF's asset fetcher is
            //     independently sandboxed to a non-sensitive approved root with canonical
            //     path containment. That infrastructure does not exist in this module.
            //
            // Policy:
            //   - Resource attributes (src, action, formaction, data, poster, background)
            //     are unconditionally removed. Template images and assets must be provided
            //     through placeholder resolution (DocumentDataContext), not raw HTML URLs.
            //   - Anchor links (href starting with # or empty) remain for in-document
            //     navigation (no resource fetch involved).
            if ($isUrlAttr) {
                $rawValue = $element->getAttribute($name);
                $decoded = html_entity_decode($rawValue, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $decoded = preg_replace('/[\x00-\x1F\x7F\s]/u', '', $decoded) ?? $decoded;
                $normalized = strtolower(trim($decoded));

                // Allow only empty values and in-document fragment anchors (#section).
                // Everything else — relative paths, absolute paths, all URI schemes —
                // is removed to eliminate both SSRF and local file inclusion paths.
                if ($normalized !== '' && ! str_starts_with($normalized, '#')) {
                    $element->removeAttribute($name);
                }

                continue;
            }

            // Sanitize style attribute: strip CSS expression() and url(dangerous:)
            if ($lower === 'style') {
                $styleValue = $element->getAttribute('style');
                $styleValue = $this->sanitizeStyleAttribute($styleValue);
                $element->setAttribute('style', $styleValue);
            }
        }
    }

    /**
     * Sanitize a CSS `style` attribute value.
     *
     * Strips:
     *   - CSS `expression(...)` (IE-specific JS execution)
     *   - ALL `url(...)` tokens unconditionally
     *   - `-moz-binding` (legacy XBL binding execution)
     *
     * CSS url() policy:
     *   ALL url() tokens are removed unconditionally — no relative-path exception.
     *   mPDF fetches url() values via fopen() for local paths (not intercepted by
     *   DenyingHttpClient) and via cURL for remote paths (blocked by DenyingHttpClient).
     *   Even a relative value such as `url(storage/app/private/data.png)` causes mPDF
     *   to open that file from the working directory via fopen(). No safe subset of
     *   url() values exists without a fully sandboxed asset fetcher with canonical-path
     *   containment, which this module does not provide.
     *
     *   Template images must be provided through placeholder resolution
     *   (DocumentDataContext), not raw CSS resource references.
     */
    private function sanitizeStyleAttribute(string $value): string
    {
        // Remove expression() calls (IE CSS expressions execute JavaScript)
        $value = preg_replace('/expression\s*\(.*?\)/is', '', $value) ?? $value;

        // Remove ALL url() tokens — both remote SSRF and local fopen() LFI vectors.
        // fopen() is not intercepted by DenyingHttpClient, so even relative paths are
        // dangerous. Removing the entire url() token is the only safe approach without
        // a separately sandboxed asset loader.
        $value = preg_replace('/url\s*\(\s*[\'"]?[^)]*?[\'"]?\s*\)/is', '', $value) ?? $value;

        // Remove -moz-binding (XBL execution, Firefox legacy)
        $value = preg_replace('/-moz-binding\s*:[^;]*/i', '', $value) ?? $value;

        return $value;
    }
}
