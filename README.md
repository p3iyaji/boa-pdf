# BOA PDF

A Laravel 13 + PHP 8.4 web app for managing PDFs: upload, view in-browser,
merge, compress, convert, and sign.

Stack: Laravel 13 · Blade · Alpine.js · TailwindCSS (CDN) · PDF.js · SQLite.
Auth is a minimal home-rolled flow (no starter kit). All long PDF operations
are synchronous today; queue/horizon hooks can be added later.

## Features

| Feature | Backed by |
|---|---|
| Upload + library | Storage facade, validated `UploadPdfRequest` |
| In-browser view | PDF.js (CDN) with Alpine viewer |
| Merge | `setasign/fpdi` + `setasign/fpdf` |
| Compress | Ghostscript (if installed) with FPDI fallback |
| Convert (PDF → txt/html/docx/jpg/png) | `smalot/pdfparser` (+ Imagick or LibreOffice if available) |
| Sign | HTML canvas drawing → PNG → stamped on PDF via FPDI + `intervention/image` |
| Daily cleanup | `php artisan pdf:cleanup` scheduled via `routes/console.php` |

## First-time setup

The project ships with `composer.json` already declaring the PDF packages, but
the `vendor/` folder needs to be rebuilt so they get installed.

```bash
cd ~/Herd/powerhouse

# Install/refresh dependencies (this will pull setasign/fpdi, fpdf, intervention/image, smalot/pdfparser).
rm -f composer.lock
composer install

# Generate an app key and run migrations against the SQLite db (database/database.sqlite already exists).
php artisan key:generate
php artisan migrate

# (Optional) seed a demo user (demo@boapdf.test / password).
php artisan db:seed
```

Laravel Herd will auto-serve the app at your site URL (e.g. <http://powerhouse.test> if the project folder is `powerhouse`). Visit it,
register an account, and start uploading PDFs.

## Optional system tools

These are detected automatically; install whichever you need:

- **Ghostscript** for high-quality compression: `brew install ghostscript`
- **ImageMagick** with PDF policy + **Imagick PHP extension** for PDF→JPG/PNG
- **LibreOffice** for high-fidelity PDF→DOCX: `brew install --cask libreoffice`

You can also point at custom binaries via `.env`:

```
GHOSTSCRIPT_PATH=/opt/homebrew/bin/gs
LIBREOFFICE_PATH=/Applications/LibreOffice.app/Contents/MacOS/soffice
```

## Running tests

```bash
php artisan test --compact
```

The Pest suite uses `RefreshDatabase` against a SQLite in-memory database and
mocks the PDF services where appropriate, so it runs in <1s on a laptop.

## Daily artifact cleanup

`php artisan pdf:cleanup` removes generated files in `merged/`, `compressed/`,
`signed/`, `converted/`, and `temp/` older than `PDF_TEMP_CLEANUP_HOURS`
(default 24h). It's wired into the Laravel scheduler in `routes/console.php`;
run `php artisan schedule:work` locally if you want it to fire.

`php artisan pdf:prune-expired-documents` removes **library** database rows and
their stored files (under `uploads/`, `merged/`, etc.) when `created_at` is
older than `PDF_UPLOAD_RETENTION_DAYS` (default **30 days**), per user
(each user's documents are evaluated against the same retention window). It
runs daily via the same scheduler. Set `PDF_UPLOAD_RETENTION_DAYS=0` to disable
this pruning while keeping the scheduled task registered.

## Project layout

```
app/
├── Console/Commands/       Scheduled PDF maintenance (library prune)
├── Http/
│   ├── Controllers/        Auth, Pdf, Merge, Compress, Convert, Signature
│   └── Requests/           FormRequest classes for each operation
├── Models/                 Document, SignatureRequest, ConversionLog, User
└── Services/               PdfMerge / PdfCompression / PdfConversion / PdfSignature
config/pdf.php              Tunables (file size, cleanup, compression levels, binary paths)
database/migrations/        documents, signature_requests, conversion_logs
resources/views/
├── auth/                   login, register
├── layouts/app.blade.php   Shared shell (sidebar + flash messages)
├── partials/sidebar.blade.php
└── pdf/                    index, show, merge, compress, convert, sign
routes/
├── web.php                 All HTTP routes
└── console.php             pdf:cleanup, pdf:prune-expired-documents + scheduler
tests/Feature/              AuthTest, PdfLibraryTest, PdfOperationsTest
```

## Notes

- Signature placement happens client-side: you click on the PDF page and the
  view converts pixel coordinates → PDF millimetres before submitting to
  `PdfSignatureService::addSignature()`.
- The compression service tries Ghostscript first (best results) and falls
  back to a streaming FPDI re-pack if `gs` isn't found.
- Convert→DOCX writes a minimal valid DOCX containing the extracted text
  unless LibreOffice is available, in which case it shells out for fidelity.
- All file operations go through Laravel's `Storage` facade so swapping to
  S3 or another disk is a one-line config change.

## What's not in scope (yet)

The original `PROJECT-POWER-HOUSE.md` spec includes several things this build
intentionally left out because they require external services or aren't
strictly needed for the core flows:

- Laravel Horizon / Redis queues (the app uses Laravel's `database` queue).
- `blaspsoft/doxswap` for office-format conversions (LibreOffice handles
  DOCX directly when installed).
- `glenntenorio/laravel-ilovepdf` (needs API keys; Ghostscript covers the
  same need locally).
- Form-fill (AcroForm) editing UI.

These can be added later without changing the existing data model.
