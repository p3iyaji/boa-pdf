# BOA PDF (Powerhouse) — Application breakdown

This document describes **what the application does**, **what it depends on**, and **how major pieces fit together**. It reflects the codebase as of the repository’s current state.

---

## 1. Purpose

A **Laravel 13** web app for **authenticated users** to:

- Upload and browse a **personal PDF library**
- **Merge**, **compress**, **convert**, and **visually sign** PDFs
- **Download** or **inline-view** documents they own

Branding in `composer.json`: **BOA PDF** — document management, merge, compress, convert, and sign.

---

## 2. Technology stack

| Layer | Technology |
|--------|------------|
| Runtime | PHP **^8.3** (project targets **8.4** in team guidelines) |
| Framework | Laravel **13** |
| HTTP / MVC | Controllers, Form Requests, Blade |
| Database | Eloquent; typical deploy uses SQLite or MySQL (see `.env`) |
| Frontend assets | **Vite 8**, **Tailwind CSS 4** (`@tailwindcss/vite`) |
| PDF manipulation | **setasign/fpdi**, **setasign/fpdf** |
| PDF text extraction | **smalot/pdfparser** |
| Raster handling | **intervention/image** **^3** |
| Tests | **Pest 4** + `pestphp/pest-plugin-laravel` |

---

## 3. System requirements

### 3.1 Required (application runs)

- **PHP** with extensions commonly needed by Laravel: `openssl`, `pdo`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `zip` (DOCX fallback uses `ZipArchive`)
- **Composer** dependencies installed
- **Node** (for `npm run dev` / `npm run build` when changing frontend)
- Writable **`storage/`** and **`bootstrap/cache/`**
- Database migrated (`php artisan migrate`)

### 3.2 Strongly recommended (feature quality)

| Capability | Typical setup | Used for |
|------------|----------------|----------|
| **Ghostscript** (`gs`) | e.g. `brew install ghostscript` | PDF **compression** (real image re-encoding); Imagick often needs GS for PDF |
| **qpdf** (11+) | e.g. `brew install qpdf` | Optional **second pass** after Ghostscript (`--optimize-images`) |
| **ImageMagick + Imagick PHP** | ImageMagick with PDF policy + `imagick` extension | PDF → **JPG/PNG** in `PdfConversionService` |
| **LibreOffice** (`soffice`) | e.g. `brew install --cask libreoffice` | High-fidelity **PDF → DOCX** (and can be used for other conversions via headless mode) |

Paths can be overridden in `.env`: `GHOSTSCRIPT_PATH`, `QPDF_PATH`, `LIBREOFFICE_PATH` (see `config/pdf.php`).

### 3.3 Optional / not wired in code

- **`ILOVEPDF_PUBLIC_KEY` / `ILOVEPDF_SECRET_KEY`** appear in `config/pdf.php` only; **no service class currently consumes them** — reserved for a possible future integration.

---

## 4. Configuration (high level)

Relevant keys (see `.env.example` and `config/pdf.php`):

| Setting | Role |
|---------|------|
| `APP_URL`, `APP_KEY` | Standard Laravel |
| `DB_*` | Database connection |
| `FILESYSTEM_DISK` | Default disk (`local` uses `storage/app/private`) |
| `PDF_MAX_FILE_SIZE` | Upload max in **kilobytes** (default `51200` ≈ 50 MB) |
| `PDF_TEMP_CLEANUP_HOURS` | Age threshold for `pdf:cleanup` artifact prune |
| `PDF_UPLOAD_RETENTION_DAYS` | Library document retention for `pdf:prune-expired-documents` (`0` = skip pruning) |
| `PDF_DEFAULT_COMPRESSION` | Default preset: `low`, `medium`, `recommended`, `maximum` |
| Mail settings | Password reset emails (`MAIL_*`) |

---

## 5. Data model

### 5.1 `users`

Standard Laravel user: `name`, `email`, `password`, etc. Implements **password reset** via `CanResetPassword` + notification (`password_reset_tokens` table from default migration).

### 5.2 `documents`

Central row for **every file** in the library (uploads and derived outputs).

| Concept | Details |
|---------|---------|
| **Statuses** | `pending`, `processing`, `completed`, `failed` (constants on `Document` model) |
| **Operation types** | `upload`, `converted`, `merged`, `compressed`, `signed` |
| **Storage** | `file_path` is relative to the **`local` disk root** (`storage/app/private`) |
| **Lineage** | `parent_document_id` links derived documents to a source where applicable |
| **Metadata** | JSON: e.g. merge source IDs, compression level/method/sizes, signature positions |

**Authorization:** Controllers enforce **ownership** with `user_id` checks or `Rule::exists(..., 'user_id', $this->user()->id)` — there are no separate Policy classes for documents in the current code.

### 5.3 `conversion_logs`

Audit-style log for **conversion** and **compression** attempts: `document_id`, formats, `processing_time_ms`, `status`, `error_message`.

### 5.4 `signature_requests`

Stores metadata when a user completes signing: links to the **new signed** `document_id`, emails, JSON `signature_position`, `signed_file_path`, `status` (`pending` / `signed`). The UI flow currently records a **self-sign** style completion (`requester_email` / `signer_email` = authenticated user).

---

## 6. HTTP routes and features

All PDF features live under **`/pdf`** and require **`auth`**. Guest routes cover login, register, and password reset.

### 6.1 Authentication

| Route | Method | Name | Behavior |
|-------|--------|------|----------|
| `/` | GET | `login` | Login form |
| `/login` | POST | `authenticate` | Session login; password min length **4** (note: registration uses stricter rules) |
| `/register` | GET/POST | `register`, `register.store` | Create user; **password** min **6**, confirmed |
| `/forgot-password` | GET/POST | `password.request`, `password.email` | Send reset link |
| `/reset-password/{token}` | GET | `password.reset` | Reset form |
| `/reset-password` | POST | `password.update` | Set new password |
| `/logout` | POST | `logout` | Session logout |

**Middleware:** Guest routes use Laravel’s `guest` middleware; authenticated users are redirected away from login/register/reset.

### 6.2 Dashboard

| Route | Name | Behavior |
|-------|------|----------|
| `/dashboard` | `dashboard` | Summary stats and shortcuts to PDF tools |

### 6.3 PDF library (`PdfController`)

| Route | Name | Behavior |
|-------|------|----------|
| `GET /pdf` | `pdf.index` | Paginated list (20) of user’s documents |
| `POST /pdf` | `pdf.upload` | Upload **one** PDF (`mimes:pdf`, max `config('pdf.max_file_size')` KB) → `uploads/`; page count via FPDI; `failed` if pages = 0 |
| `GET /pdf/{document}` | `pdf.show` | Document detail |
| `GET /pdf/{document}/stream` | `pdf.stream` | Inline PDF |
| `GET /pdf/{document}/download` | `pdf.download` | Download |
| `DELETE /pdf/{document}` | `pdf.destroy` | Delete DB row and file |

### 6.4 Merge (`MergeController` + `PdfMergeService`)

| Route | Name | Behavior |
|-------|------|----------|
| `GET /pdf/merge` | `pdf.merge.create` | Pick **completed** documents |
| `POST /pdf/merge` | `pdf.merge.store` | **≥ 2** documents, user-owned IDs; FPDI concatenates all pages in order; output in `merged/`; `parent_document_id` = first source |

Optional `output_name` (max 200 chars); `.pdf` appended if missing.

### 6.5 Compress (`CompressController` + `PdfCompressionService`)

| Route | Name | Behavior |
|-------|------|----------|
| `GET /pdf/compress` | `pdf.compress.create` | Choose **completed** PDF and **level** |
| `POST /pdf/compress` | `pdf.compress.store` | Run compression; new document in `compressed/` with metadata (level, method, sizes) |

**Pipeline:** Prefers **Ghostscript** (PATH + common paths + `GHOSTSCRIPT_PATH`), optional **qpdf** optimize pass, retry at **maximum** if size did not decrease; falls back to **FPDI** if GS unavailable (usually **little** size reduction on scans).

### 6.6 Convert (`ConvertController` + `PdfConversionService`)

| Route | Name | Behavior |
|-------|------|----------|
| `GET /pdf/convert` | `pdf.convert.create` | Choose document and **target** format |
| `POST /pdf/convert` | `pdf.convert.store` | Convert; **HTTP download** of result; also creates a **`Document`** in `converted/` for library history |

**Supported targets:** `txt`, `html`, `docx`, `jpg`, `png` (`PdfConversionService::SUPPORTED_TARGETS`).

| Target | Implementation notes |
|--------|----------------------|
| `txt` / `html` | `smalot/pdfparser` text extraction (HTML wraps text in `<pre>` per page) |
| `docx` | LibreOffice headless if available; else **minimal DOCX** with plain extracted text |
| `jpg` / `png` | **Imagick** required; first page or multi-page sequence written under `converted/` |

`ConversionLog` records success/failure (linked to **source** document id on failure; success entry also uses source id in current code).

### 6.7 Sign (`SignatureController` + `PdfSignatureService`)

| Route | Name | Behavior |
|-------|------|----------|
| `GET /pdf/{document}/sign` | `pdf.sign.create` | Interactive signer UI |
| `POST /pdf/{document}/sign` | `pdf.sign.store` | Apply **drawn** PNG (canvas), **typed** PNG, and/or **logo** image at mm coordinates; FPDI rebuilds PDF; output in `signed/` |

Validation (`SignPdfRequest`): data URLs for signatures (PNG base64, max ~700k chars); logo allows PNG/JPEG/WebP/GIF (larger max for base64 inflation). At least one of the three must be present.

Creates **`SignatureRequest`** with status `signed` pointing at the new document.

**Route ordering:** Merge/compress/convert routes are registered **before** `/pdf/{document}` so literal paths are not captured as IDs.

---

## 7. Storage layout (local disk)

Under **`storage/app/private`** (Laravel `local` disk):

| Directory | Contents |
|-----------|----------|
| `uploads/` | Original uploads |
| `merged/` | Merged PDFs |
| `compressed/` | Compressed PDFs |
| `signed/` | Signed PDFs |
| `converted/` | Converted outputs (and sources for download path) |
| `temp/` | Short-lived files (e.g. signature images) |

---

## 8. Console commands and scheduler

| Command | Purpose |
|---------|---------|
| `pdf:cleanup` | Deletes **files** in `merged/`, `compressed/`, `signed/`, `converted/`, `temp/` older than `PDF_TEMP_CLEANUP_HOURS` (does **not** delete `uploads/` by design) |
| `pdf:prune-expired-documents` | Deletes **`documents`** rows (and files) with `created_at` older than retention; `--days` override |

**Scheduler** (`routes/console.php`): both commands run **daily**.

---

## 9. Security and privacy (summary)

- **Session-based** authentication; CSRF on forms.
- Documents scoped by **`user_id`** in queries and validation.
- Files served via controller actions, not public disk by default for library root.
- Password reset uses Laravel’s broker and notifications (configure `MAIL_*` for real delivery).

---

## 10. Testing

Pest suites under `tests/` include:

- **`Feature/AuthTest`** — login, register, guest PDF redirect, password reset flow
- **`Feature/PdfOperationsTest`** — upload, merge, compress, convert (with mocked services where appropriate)
- **`Feature/PdfLibraryTest`** — library/show/download/delete behavior
- **`Feature/PruneExpiredDocumentsTest`** — retention command
- **`Unit/PdfCompressionServiceTest`** — compression service behavior (often forces non-GS path in tests)

Run: `php artisan test --compact`

---

## 11. Frontend UI

- Blade layouts: `resources/views/layouts/app.blade.php`, sidebar, dashboard, auth screens, PDF tool views
- Styling: Tailwind 4 via Vite (`resources/css/app.css`, `vite.config.js`)
- After asset changes: `npm run dev` or `npm run build` (or `composer run dev` for full stack)

---

## 12. Known behavioral notes

- **Login** password rule (`min:4`) is **weaker** than **registration** (`Password::min(6)`).
- **Convert** returns an immediate **download** while also persisting a library `Document` — the browser file and library entry should match for single-page image outputs; multi-page images may produce multiple files on disk while download uses the path returned by the service (see `PdfConversionService::toImage`).
- **Compression** without Ghostscript: expect **minimal** size change for image-heavy PDFs.
- **iLovePDF** env vars: **not used** by application code today.

---

*Generated from the repository structure and PHP sources. Update this file when you add routes, env vars, or external dependencies.*
