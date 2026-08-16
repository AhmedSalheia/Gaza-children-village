<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Documents\Models\DocumentTemplateVersion;
use Modules\Documents\Services\DocumentTemplateVersionService;
use Modules\Documents\Services\TemplatePlaceholderResolver;

uses(RefreshDatabase::class);

/**
 * Confirms the HTML sanitization contract enforced at the storage layer.
 *
 * The "no JavaScript" rule must hold even when a TEMPLATE_MANAGE actor tries to
 * embed executable payloads, including entity-encoded bypass attempts.
 *
 * sanitizeHtml() uses DOMDocument which decodes HTML entities before checking
 * attribute values, so java&#x73;cript: is seen as javascript: and rejected.
 *
 * Sanitization runs in createDraft() (storage layer) and in renderPreviewHtml()
 * (render layer) as defence-in-depth.
 */
describe('TemplatePlaceholderResolver: HTML sanitization', function (): void {

    it('strips <script> tags and their content from template body', function (): void {
        $resolver = new TemplatePlaceholderResolver;
        $body = '<p>{{ student.full_name_ar }}</p><script>alert("xss")</script>';

        $sanitized = $resolver->sanitizeHtml($body);

        expect($sanitized)
            ->not->toContain('<script')
            ->not->toContain('alert("xss")');
    });

    it('strips inline event-handler attributes', function (): void {
        $resolver = new TemplatePlaceholderResolver;

        $payloads = [
            ['<p onclick="alert(1)">text</p>', 'onclick'],
            ['<img src="logo.png" onload="steal()">', 'onload'],
            ['<div onmouseover="evil()">hover</div>', 'onmouseover'],
            ['<a href="#" onfocus="bad()">link</a>', 'onfocus'],
        ];

        foreach ($payloads as [$payload, $attr]) {
            $sanitized = $resolver->sanitizeHtml($payload);
            expect($sanitized)->not->toContain($attr);
        }
    });

    it('strips literal javascript: URI scheme from href', function (): void {
        $resolver = new TemplatePlaceholderResolver;
        $body = '<a href="javascript:alert(1)">click</a>';
        $sanitized = $resolver->sanitizeHtml($body);

        expect(strtolower($sanitized))->not->toContain('javascript:');
    });

    it('strips literal javascript: URI scheme from src', function (): void {
        $resolver = new TemplatePlaceholderResolver;
        $body = '<img src="javascript:alert(1)">';
        $sanitized = $resolver->sanitizeHtml($body);

        expect(strtolower($sanitized))->not->toContain('javascript:');
    });

    it('strips <iframe> tags', function (): void {
        $resolver = new TemplatePlaceholderResolver;
        $body = '<iframe src="https://evil.example.com"></iframe>';
        $sanitized = $resolver->sanitizeHtml($body);

        expect($sanitized)->not->toContain('<iframe');
    });

    // ------------------------------------------------------------------
    // Entity-encoding bypass tests (the class the reviewer flagged)
    // ------------------------------------------------------------------

    it('rejects javascript: href encoded as HTML decimal entity (&#106;avascript:)', function (): void {
        $resolver = new TemplatePlaceholderResolver;
        // &#106; decodes to 'j' — browser interprets href as javascript:
        $body = '<a href="&#106;avascript:alert(1)">click</a>';
        $sanitized = $resolver->sanitizeHtml($body);

        // href must be stripped; the visible link text may remain
        expect(strtolower($sanitized))->not->toContain('javascript:');
    });

    it('rejects javascript: href encoded as HTML hex entity (java&#x73;cript:)', function (): void {
        $resolver = new TemplatePlaceholderResolver;
        // &#x73; decodes to 's'
        $body = '<a href="java&#x73;cript:alert(1)">click</a>';
        $sanitized = $resolver->sanitizeHtml($body);

        expect(strtolower($sanitized))->not->toContain('javascript:');
    });

    it('rejects mixed-case javascript: URI scheme (JaVaScRiPt:)', function (): void {
        $resolver = new TemplatePlaceholderResolver;
        $body = '<a href="JaVaScRiPt:alert(1)">click</a>';
        $sanitized = $resolver->sanitizeHtml($body);

        expect(strtolower($sanitized))->not->toContain('javascript:');
    });

    it('rejects javascript: with leading whitespace in href', function (): void {
        $resolver = new TemplatePlaceholderResolver;
        $body = '<a href="   javascript:alert(1)">x</a>';
        $sanitized = $resolver->sanitizeHtml($body);

        expect(strtolower($sanitized))->not->toContain('javascript:');
    });

    it('rejects data: URI in src attribute', function (): void {
        $resolver = new TemplatePlaceholderResolver;
        $body = '<img src="data:text/html,<script>alert(1)</script>">';
        $sanitized = $resolver->sanitizeHtml($body);

        expect(strtolower($sanitized))->not->toContain('data:');
    });

    it('rejects vbscript: URI scheme', function (): void {
        $resolver = new TemplatePlaceholderResolver;
        $body = '<a href="vbscript:msgbox(1)">click</a>';
        $sanitized = $resolver->sanitizeHtml($body);

        expect(strtolower($sanitized))->not->toContain('vbscript:');
    });

    it('strips CSS expression() from style attribute', function (): void {
        $resolver = new TemplatePlaceholderResolver;
        $body = '<div style="color: expression(alert(1))">text</div>';
        $sanitized = $resolver->sanitizeHtml($body);

        expect(strtolower($sanitized))->not->toContain('expression(');
    });

    it('strips CSS url(javascript:) from style attribute', function (): void {
        $resolver = new TemplatePlaceholderResolver;
        $body = '<div style="background: url(javascript:alert(1))">text</div>';
        $sanitized = $resolver->sanitizeHtml($body);

        expect(strtolower($sanitized))->not->toContain('javascript:');
    });

    it('strips HTML comment nodes', function (): void {
        $resolver = new TemplatePlaceholderResolver;
        $body = '<!-- <script>alert(1)</script> --><p>text</p>';
        $sanitized = $resolver->sanitizeHtml($body);

        expect($sanitized)->not->toContain('<!--');
    });

    it('preserves safe structural HTML intact', function (): void {
        $resolver = new TemplatePlaceholderResolver;
        $body = '<p dir="rtl"><strong>{{ student.full_name_ar }}</strong></p><table><tr><td>cell</td></tr></table>';

        $sanitized = $resolver->sanitizeHtml($body);

        expect($sanitized)
            ->toContain('<p')
            ->toContain('<strong>')
            ->toContain('<table>')
            ->toContain('<td>')
            ->toContain('{{ student.full_name_ar }}');
    });

    it('strips script payload via createDraft() before it reaches the database', function (): void {
        $template = makeTemplate();
        $body = '<p>{{ student.full_name_ar }}</p><script>stealData()</script>';

        $version = app(DocumentTemplateVersionService::class)->createDraft(
            template: $template,
            locale: 'ar',
            body: $body,
        );

        expect($version->body)
            ->not->toContain('<script')
            ->not->toContain('stealData()');
    });

    it('strips event-handler payload via createDraft() before it reaches the database', function (): void {
        $template = makeTemplate();
        $body = '<p onclick="alert(document.cookie)">{{ student.full_name_ar }}</p>';

        $version = app(DocumentTemplateVersionService::class)->createDraft(
            template: $template,
            locale: 'ar',
            body: $body,
        );

        expect($version->body)->not->toContain('onclick');
    });

    it('sanitizes header and footer HTML in createDraft()', function (): void {
        $template = makeTemplate();
        $body = '<p>{{ student.full_name_ar }}</p>';
        $headerHtml = '<div onload="bad()">Logo</div>';
        $footerHtml = '<script>evil()</script><p>Footer</p>';

        $version = app(DocumentTemplateVersionService::class)->createDraft(
            template: $template,
            locale: 'ar',
            body: $body,
            headerConfig: ['html' => $headerHtml],
            footerConfig: ['html' => $footerHtml],
        );

        $headerStored = $version->header_config['html'] ?? '';
        $footerStored = $version->footer_config['html'] ?? '';

        expect($headerStored)->not->toContain('onload');
        expect($footerStored)->not->toContain('<script');
        expect($footerStored)->not->toContain('evil()');
    });

    it('strips script payload in renderPreviewHtml() for drafts pre-dating the storage guard', function (): void {
        $template = makeTemplate();

        // Bypass createDraft() to simulate a pre-guard draft with a dangerous body.
        $version = new DocumentTemplateVersion;
        $version->template_id = $template->id;
        $version->version_number = 1;
        $version->locale = 'ar';
        $version->body = '<p>{{ student.full_name_ar }}</p><script>stealSession()</script>';
        $version->status = 'draft';
        $version->save();

        $html = app(DocumentTemplateVersionService::class)->renderPreviewHtml($version);

        expect($html)
            ->not->toContain('<script')
            ->not->toContain('stealSession()');
    });

    it('strips entity-encoded href in renderPreviewHtml() defence-in-depth layer', function (): void {
        $template = makeTemplate();

        // Bypass createDraft() sanitization — simulate a pre-guard stored body.
        $version = new DocumentTemplateVersion;
        $version->template_id = $template->id;
        $version->version_number = 1;
        $version->locale = 'ar';
        $version->body = '<a href="java&#x73;cript:alert(1)">{{ student.full_name_ar }}</a>';
        $version->status = 'draft';
        $version->save();

        $html = app(DocumentTemplateVersionService::class)->renderPreviewHtml($version);

        expect(strtolower($html))->not->toContain('javascript:');
    });

});

describe('TemplatePlaceholderResolver: nested unknown-tag XSS bypass', function (): void {

    // These tests confirm that sanitizeNode() sanitizes descendants BEFORE
    // unwrapping unknown tags. Without the pre-sanitize step,
    // <svg><script>alert(1)</script></svg> would unwrap to <script>alert(1)</script>.

    it('strips <script> nested inside unknown <svg> tag', function (): void {
        $resolver = new TemplatePlaceholderResolver;
        $body = '<svg><script>alert(1)</script></svg>';
        $sanitized = $resolver->sanitizeHtml($body);

        expect($sanitized)
            ->not->toContain('<script')
            ->not->toContain('alert(1)');
    });

    it('strips event-handler on img nested inside unknown <svg>', function (): void {
        $resolver = new TemplatePlaceholderResolver;
        $body = '<svg><img src="x" onerror="alert(1)"></svg>';
        $sanitized = $resolver->sanitizeHtml($body);

        expect($sanitized)->not->toContain('onerror');
    });

    it('strips javascript: href nested inside unknown <svg>', function (): void {
        $resolver = new TemplatePlaceholderResolver;
        $body = '<svg><a href="javascript:alert(1)">click</a></svg>';
        $sanitized = $resolver->sanitizeHtml($body);

        expect(strtolower($sanitized))->not->toContain('javascript:');
    });

    it('strips absolute src on img nested inside unknown <svg> (SSRF)', function (): void {
        $resolver = new TemplatePlaceholderResolver;
        $body = '<svg><img src="https://evil.example.com/pixel.png" alt=""></svg>';
        $sanitized = $resolver->sanitizeHtml($body);

        expect($sanitized)->not->toContain('evil.example.com');
    });

    it('strips <script> nested inside doubly-wrapped unknown tags', function (): void {
        $resolver = new TemplatePlaceholderResolver;
        $body = '<div><svg><math><script>evil()</script></math></svg></div>';
        $sanitized = $resolver->sanitizeHtml($body);

        expect($sanitized)
            ->not->toContain('<script')
            ->not->toContain('evil()');
    });

});
