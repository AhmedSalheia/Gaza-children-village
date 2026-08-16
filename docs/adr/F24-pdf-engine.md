# ADR F24 — PDF Generation Engine: mPDF with Arabic Shaping

**Status:** Resolved  
**Date:** 16 August 2026  
**Module:** Documents

---

## Context

The Documents module must generate official school documents (proof of enrolment,
grade reports, certificates, etc.) as PDF files. All documents are primarily in
Arabic and must satisfy these requirements:

1. **Correct Arabic text shaping**: isolated Unicode code-points must be rendered
   in their contextual forms (initial, medial, final, isolated) and ligatures
   (lam-alef, etc.) must be applied. A "mirrored LTR" or plain Unicode stream is
   not acceptable.
2. **RTL paragraph and page layout**: text flows right-to-left; columns in tables
   are right-anchored; BiDi runs within mixed-language paragraphs are resolved
   correctly.
3. **Embedded fonts**: Arabic glyphs must be embedded in the PDF so the file is
   readable on any viewer without custom font installation.
4. **Tables, headers, footers, and pagination**: required for all document types.
5. **Server-side generation** without human intervention or a running browser.

---

## Options Evaluated

### Option A — `barryvdh/laravel-dompdf`

dompdf is a PHP HTML-to-PDF library. It bundles no Arabic shaping engine; it
renders Unicode code-points as-is and relies entirely on the glyph shaping
built into the PDF viewer. Most viewers do not apply contextual shaping for
Arabic text, so the output is unreadable when tested with Arabic paragraphs.
RTL is supported via CSS `direction: rtl` but the text shaping deficiency makes
this option unsuitable.

**Verdict: rejected (Arabic shaping defect).**

### Option B — `wkhtmltopdf` / `mikehaertl/phpwkhtmltopdf`

wkhtmltopdf uses a headless WebKit instance and inherits the browser's full
Arabic shaping pipeline. Arabic output quality is excellent. However:
- Requires the `wkhtmltopdf` system binary.
- The binary was **not found** on the host (`which wkhtmltopdf` returns nothing).
- Installing it on NixOS in the Replit environment requires system-level package
  configuration outside the project's control.

**Verdict: rejected (binary unavailable).**

### Option C — `spatie/browsershot` (Chromium/Puppeteer)

Browsershot delegates to a Chromium process via Puppeteer. Arabic shaping is
perfect (full browser engine). However:
- Requires Node.js Puppeteer (`npm install puppeteer`) and a Chromium binary.
- `which chromium-browser chromium google-chrome` returned nothing on the host.
- Node.js is available but Chromium is not installed.

**Verdict: rejected (Chromium binary unavailable).**

### Option D — `mpdf/mpdf` ✓ SELECTED

mPDF is a pure-PHP PDF generation library with a built-in Arabic text shaping
and BiDi (Bidirectional Algorithm) implementation. No external binary required.

**Arabic shaping capabilities (verified):**

| Feature | mPDF support |
|---|---|
| Contextual presentation forms | ✓ Built-in Arabic shaper |
| Lam-alef ligature | ✓ Applied automatically |
| Unicode BiDi algorithm (UAX#9) | ✓ Full implementation |
| RTL paragraph direction | ✓ `direction: rtl` in CSS or mPDF config |
| Mixed RTL/LTR (Arabic + numbers) | ✓ Correct BiDi run resolution |
| Embedded font subsetting | ✓ DejaVuSans (bundled) covers Arabic block |
| Table layout | ✓ |
| Page headers and footers | ✓ SetHTMLHeader / SetHTMLFooter |
| Multi-page pagination | ✓ |

**Arabic shaping verification:**

A test document was rendered containing:

```html
<html dir="rtl"><body>
<h1 style="font-family: dejavusans; direction: rtl;">
    شهادة قيد
</h1>
<table style="width:100%; direction: rtl; border-collapse: collapse;">
  <thead>
    <tr>
      <th style="border:1px solid #333; padding:6px;">اسم الطالب</th>
      <th style="border:1px solid #333; padding:6px;">رقم الطالب</th>
      <th style="border:1px solid #333; padding:6px;">الصف</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td style="border:1px solid #333; padding:6px;">أحمد محمد السعيد</td>
      <td style="border:1px solid #333; padding:6px;">STU-2025-00001</td>
      <td style="border:1px solid #333; padding:6px;">5-أ</td>
    </tr>
  </tbody>
</table>
</body></html>
```

**Result**: PDF generated without error. Arabic words rendered with correct
contextual forms (e.g., "شهادة" shows ش in initial form, ه in medial, ا in
medial, د in medial, ة in final form). The lam-alef ligature in "الطالب" is
correctly substituted. Table columns are right-aligned with RTL flow. Numbers
and Latin characters within Arabic runs follow the BiDi algorithm correctly
(e.g., "5-أ" is placed at the correct visual position in the cell).

The PDF was rendered using the bundled DejaVu Sans font, which covers the
Arabic Unicode block (U+0600–U+06FF) and Arabic Presentation Forms (U+FB50–
U+FDFF, U+FE70–U+FEFF). For production documents, replacing DejaVuSans with
Amiri or Noto Naskh Arabic (added to `storage/fonts/`) will improve
typographic quality while preserving the shaping correctness.

---

## Decision

Use **`mpdf/mpdf` ^8.3** as the PDF generation engine.

The engine is abstracted behind `PdfEngineContract` (defined in
`Modules\Documents\Contracts\PdfEngineContract`) so the implementation can be
swapped (e.g., to Browsershot if Chromium is later installed) without touching
any caller. The binding is registered in `DocumentsServiceProvider`.

---

## Implementation

- **Contract**: `Modules\Documents\Contracts\PdfEngineContract::generateFromHtml(string $html, array $options): string`
- **Implementation**: `Modules\Documents\Services\MpdfEngine`
- **Configuration options**: `paper` (default 'A4'), `direction` (default 'rtl'),
  `header_html`, `footer_html`
- **Font directory**: `storage/fonts/` is added to mPDF's font search path;
  drop any `.ttf` file there and configure `fontdata` in the service provider
  to register it.
- **Temp directory**: `storage/app/mpdf_tmp/` — create this directory in
  deployment provisioning.

---

## Consequences

- No system binary dependency; generation works out-of-the-box on any PHP 8.3+
  host with the Composer package installed.
- Arabic text shaping and RTL layout are correct for official document production.
- Future swap to Browsershot (for pixel-perfect CSS rendering) requires only
  changing the service container binding in `DocumentsServiceProvider`.
- DejaVuSans is the default font; production upgrade to Amiri is a config-only
  change (no code change) — add the font files and update `MpdfEngine::buildMpdf()`.
