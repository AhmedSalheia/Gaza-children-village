# ADR: SR Import Pipeline — Spreadsheet Library Choice

**Status:** Accepted  
**Date:** 2026-08-15  
**Module:** Imports

---

## Context

The Imports module requires parsing both `.xlsx` (Excel Open XML) and `.csv` files that
may contain 1 000–50 000 student data rows. The pipeline is staged (upload → parse →
map → validate → preview → apply), so the library must support:

1. **Streaming/chunked reads** — rows are stored in `import_rows` before any domain
   action is called; the full file must never be held in memory.
2. **Laravel integration** — service provider, facade, and config should drop into the
   existing Laravel 13 / Livewire 4 stack with no custom bridging.
3. **CSV and XLSX in one package** — avoiding two separate dependencies.
4. **PHP 8.3+ compatibility** — the project uses PHP ^8.3.

## Decision

Install **maatwebsite/excel v4** (`composer require maatwebsite/excel`).

### Why maatwebsite/excel v4

| Criterion | maatwebsite/excel v4 | league/csv (CSV only) | phpoffice/phpspreadsheet |
|---|---|---|---|
| XLSX + CSV | ✅ both | ❌ CSV only | ✅ both |
| Streaming large files | ✅ openspout under the hood | ✅ (memory-efficient) | ⚠️ loads full sheet |
| Laravel service provider | ✅ built-in | ❌ manual | ❌ manual |
| Chunked reading API | ✅ `WithChunkReading` | manual | manual |
| PHP 8.3 | ✅ confirmed | ✅ | ✅ |
| Laravel 13 compatibility | ✅ v4 series (installed as `^4.0`) | ✅ | ✅ |

maatwebsite/excel v4 replaces its previous PhpSpreadsheet dependency with
`openspout/openspout`, which is purpose-built for streaming reads of large XLSX and CSV
files. The `WithChunkReading` interface and chunk size option are used internally by the
`ParseImportFile` action so that each 500-row chunk is yielded to a callback that persists
`ImportRow` records, keeping memory usage bounded regardless of file size.

### What is NOT used from maatwebsite/excel

The write/export side of maatwebsite/excel is not used. All result reports are produced
by the `GenerateImportResultReport` action using native PHP `fputcsv`.

## Versioned constraint

```json
"maatwebsite/excel": "^4.0"
```

Installed as `^4.0` at the time of this ADR. The `^` constraint allows patch and minor
upgrades but not a major version bump.

## Consequences

- A `config/excel.php` publish is available via `vendor:publish --tag=excel-config` if
  fine-grained chunk, memory, or temporary-file settings are needed in production.
- The `Modules/Imports/app/Services/SpreadsheetParser.php` service wraps the
  `Maatwebsite\Excel` reader behind a thin interface so tests can inject a CSV fixture
  without needing actual XLSX files in the test suite.
