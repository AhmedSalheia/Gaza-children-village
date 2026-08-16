<?php

declare(strict_types=1);

use Modules\Documents\Services\TemplatePlaceholderResolver;

/**
 * SSRF protection tests for the document template HTML sanitizer.
 *
 * mPDF fetches remote resources (images, stylesheets) during PDF generation
 * via libcurl. An absolute URL pointing to an internal IP or cloud-metadata
 * endpoint (169.254.169.254, 10.x, etc.) could be used to probe the internal
 * network from the server rendering the PDF.
 *
 * sanitizeHtml() restricts src/href/action attributes to relative paths only.
 * Absolute URLs (containing :// or starting with //) are removed before the
 * template body reaches the database or the PDF engine.
 *
 * <style> blocks are also rejected entirely because mPDF resolves @import url(…)
 * in style blocks by making outbound HTTP requests.
 */
describe('sanitizeHtml(): SSRF protection', function (): void {

    it('strips external http:// URL from img src', function (): void {
        $resolver = new TemplatePlaceholderResolver;
        $body = '<img src="http://169.254.169.254/latest/meta-data/" alt="logo">';
        $sanitized = $resolver->sanitizeHtml($body);

        expect($sanitized)->not->toContain('169.254.169.254');
        expect($sanitized)->not->toContain('http://');
    });

    it('strips external https:// URL from img src', function (): void {
        $resolver = new TemplatePlaceholderResolver;
        $body = '<img src="https://evil.example.com/pixel.png" alt="">';
        $sanitized = $resolver->sanitizeHtml($body);

        expect($sanitized)->not->toContain('evil.example.com');
        expect($sanitized)->not->toContain('https://');
    });

    it('strips protocol-relative // URL from img src', function (): void {
        $resolver = new TemplatePlaceholderResolver;
        $body = '<img src="//internal.corp/secret.png" alt="">';
        $sanitized = $resolver->sanitizeHtml($body);

        expect($sanitized)->not->toContain('internal.corp');
    });

    it('strips external URL from a href', function (): void {
        $resolver = new TemplatePlaceholderResolver;
        $body = '<a href="https://attacker.example.com/steal">click</a>';
        $sanitized = $resolver->sanitizeHtml($body);

        expect($sanitized)->not->toContain('attacker.example.com');
        expect($sanitized)->not->toContain('https://');
    });

    it('strips cloud-metadata URL from src (AWS EC2 instance metadata)', function (): void {
        $resolver = new TemplatePlaceholderResolver;
        $body = '<img src="http://169.254.169.254/latest/user-data" alt="">';
        $sanitized = $resolver->sanitizeHtml($body);

        expect($sanitized)->not->toContain('169.254.169.254');
    });

    it('strips internal network URL from src (RFC 1918)', function (): void {
        $resolver = new TemplatePlaceholderResolver;
        $body = '<img src="http://10.0.0.1/admin" alt="">';
        $sanitized = $resolver->sanitizeHtml($body);

        expect($sanitized)->not->toContain('10.0.0.1');
    });

    it('strips ALL src values including pure relative paths (LFI via fopen)', function (): void {
        // mPDF resolves relative paths via fopen() from the working directory.
        // `src="storage/app/private/secret.png"` is a valid fopen() path.
        // DenyingHttpClient does not intercept fopen(). All resource URLs are removed.
        $resolver = new TemplatePlaceholderResolver;
        $body = '<img src="images/logo.png" alt="Logo">';
        $sanitized = $resolver->sanitizeHtml($body);

        expect($sanitized)->not->toContain('images/logo.png');
    });

    it('strips absolute server path /storage/… from src (local file access via fopen)', function (): void {
        // mPDF resolves paths starting with / as local filesystem paths via fopen().
        // This is a local-file-inclusion vector. Only pure relative paths are allowed.
        $resolver = new TemplatePlaceholderResolver;
        $body = '<img src="/storage/logos/school.png" alt="School Logo">';
        $sanitized = $resolver->sanitizeHtml($body);

        expect($sanitized)->not->toContain('/storage/logos/school.png');
    });

    it('strips entire <style> block to prevent @import SSRF', function (): void {
        $resolver = new TemplatePlaceholderResolver;
        $body = '<style>@import url("https://evil.example.com/steal.css");</style><p>text</p>';
        $sanitized = $resolver->sanitizeHtml($body);

        // Both the <style> tag and its content must be gone
        expect($sanitized)->not->toContain('<style');
        expect($sanitized)->not->toContain('@import');
        expect($sanitized)->not->toContain('evil.example.com');
    });

    it('strips <style> block with url() SSRF', function (): void {
        $resolver = new TemplatePlaceholderResolver;
        $body = '<style>body { background: url("http://169.254.169.254/"); }</style>';
        $sanitized = $resolver->sanitizeHtml($body);

        expect($sanitized)->not->toContain('<style');
        expect($sanitized)->not->toContain('169.254.169.254');
    });

    it('strips absolute URL encoded as HTML entities in src (SSRF bypass)', function (): void {
        $resolver = new TemplatePlaceholderResolver;
        // http:// encoded as &#104;ttp:// — DOMDocument decodes this
        $body = '<img src="&#104;ttp://169.254.169.254/meta-data" alt="">';
        $sanitized = $resolver->sanitizeHtml($body);

        // The decoded value contains :// which is an absolute URL — must be stripped
        expect($sanitized)->not->toContain('169.254.169.254');
    });

});

describe('sanitizeHtml(): local file inclusion and path traversal protection', function (): void {

    it('strips file:/ scheme from src attribute', function (): void {
        $resolver = new TemplatePlaceholderResolver;
        $body = '<img src="file:/etc/passwd" alt="">';
        $sanitized = $resolver->sanitizeHtml($body);

        expect($sanitized)->not->toContain('file:');
        expect($sanitized)->not->toContain('etc/passwd');
    });

    it('strips file:// scheme from src attribute', function (): void {
        $resolver = new TemplatePlaceholderResolver;
        $body = '<img src="file:///etc/passwd" alt="">';
        $sanitized = $resolver->sanitizeHtml($body);

        expect($sanitized)->not->toContain('file:');
    });

    it('strips absolute server path /etc/passwd from src', function (): void {
        $resolver = new TemplatePlaceholderResolver;
        $body = '<img src="/etc/passwd" alt="">';
        $sanitized = $resolver->sanitizeHtml($body);

        expect($sanitized)->not->toContain('/etc/passwd');
    });

    it('strips directory traversal from src', function (): void {
        $resolver = new TemplatePlaceholderResolver;
        $body = '<img src="../../etc/passwd" alt="">';
        $sanitized = $resolver->sanitizeHtml($body);

        expect($sanitized)->not->toContain('..');
        expect($sanitized)->not->toContain('etc/passwd');
    });

    it('strips file:/ from inline style url()', function (): void {
        $resolver = new TemplatePlaceholderResolver;
        $body = '<p style="background: url(file:/etc/sensitive)">text</p>';
        $sanitized = $resolver->sanitizeHtml($body);

        expect($sanitized)->not->toContain('file:');
    });

    it('strips absolute path from inline style url()', function (): void {
        $resolver = new TemplatePlaceholderResolver;
        $body = '<p style="background: url(/etc/passwd)">text</p>';
        $sanitized = $resolver->sanitizeHtml($body);

        expect($sanitized)->not->toContain('/etc/passwd');
    });

    it('strips directory traversal from inline style url()', function (): void {
        $resolver = new TemplatePlaceholderResolver;
        $body = '<p style="background: url(../../etc/shadow)">text</p>';
        $sanitized = $resolver->sanitizeHtml($body);

        expect($sanitized)->not->toContain('..');
        expect($sanitized)->not->toContain('etc/shadow');
    });

    it('strips ALL src values including pure relative paths (same LFI risk as absolute paths)', function (): void {
        // Pure relative paths like `assets/logo.png` resolve from the working directory
        // via mPDF's fopen()-based local loader. A template-controlled relative path
        // such as `storage/app/private/cert.pem` is a local file read. All src values
        // are rejected. Images must be provided through placeholder resolution.
        $resolver = new TemplatePlaceholderResolver;
        $body = '<img src="assets/logo.png" alt="School Logo">';
        $sanitized = $resolver->sanitizeHtml($body);

        expect($sanitized)->not->toContain('assets/logo.png');
    });

});

describe('sanitizeHtml(): inline style="" url() SSRF protection', function (): void {

    it('strips http:// from inline style background-image url()', function (): void {
        $resolver = new TemplatePlaceholderResolver;
        $body = '<div style="background-image: url(http://169.254.169.254/latest/meta-data/)">text</div>';
        $sanitized = $resolver->sanitizeHtml($body);

        expect($sanitized)->not->toContain('169.254.169.254');
        expect($sanitized)->not->toContain('http://');
        expect($sanitized)->toContain('text');
    });

    it('strips https:// from inline style background url()', function (): void {
        $resolver = new TemplatePlaceholderResolver;
        $body = '<p style="background: url(https://evil.example.com/steal.png)">para</p>';
        $sanitized = $resolver->sanitizeHtml($body);

        expect($sanitized)->not->toContain('evil.example.com');
        expect($sanitized)->not->toContain('https://');
    });

    it('strips protocol-relative // from inline style url()', function (): void {
        $resolver = new TemplatePlaceholderResolver;
        $body = '<p style="background: url(//internal.corp/image.png)">para</p>';
        $sanitized = $resolver->sanitizeHtml($body);

        expect($sanitized)->not->toContain('internal.corp');
    });

    it('strips ALL inline style url() values including pure relative paths (LFI via fopen)', function (): void {
        // mPDF fetches url() values in style attrs via fopen() for local paths.
        // A relative path like `url(storage/app/private/data.png)` reads a local file.
        // DenyingHttpClient does not intercept fopen(). All url() tokens are removed.
        $resolver = new TemplatePlaceholderResolver;
        $body = '<p style="background: url(assets/header.png)">para</p>';
        $sanitized = $resolver->sanitizeHtml($body);

        expect($sanitized)->not->toContain('url(');
        expect($sanitized)->not->toContain('assets/header.png');
    });

    it('strips absolute server path from inline style url() (local file via fopen)', function (): void {
        $resolver = new TemplatePlaceholderResolver;
        $body = '<p style="background: url(/storage/logos/header.png)">para</p>';
        $sanitized = $resolver->sanitizeHtml($body);

        expect($sanitized)->not->toContain('/storage/logos/header.png');
    });

    it('strips cloud-metadata URL from inline style (AWS EC2 instance metadata)', function (): void {
        $resolver = new TemplatePlaceholderResolver;
        $body = '<span style="background-image:url(http://169.254.169.254/user-data)"></span>';
        $sanitized = $resolver->sanitizeHtml($body);

        expect($sanitized)->not->toContain('169.254.169.254');
    });

});
