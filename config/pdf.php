<?php

return [

    /*
    |--------------------------------------------------------------------------
    | File limits & cleanup
    |--------------------------------------------------------------------------
    |
    | Laravel file validation "max" is in kilobytes (51200 ≈ 50 MB).
    */
    'max_file_size' => (int) env('PDF_MAX_FILE_SIZE', 51200),

    'temp_cleanup_hours' => (int) env('PDF_TEMP_CLEANUP_HOURS', 24),

    /*
    | Library documents (all operation types) older than this many days are
    | removed automatically for the owning user, including the file on disk.
    | Set to 0 to disable pruning (scheduled command still runs but skips work).
    */
    'upload_retention_days' => (int) env('PDF_UPLOAD_RETENTION_DAYS', 30),

    'temp_directories' => [
        'uploads',
        'merged',
        'compressed',
        'signed',
        'converted',
        'temp',
    ],

    'allowed_mime_types' => [
        'application/pdf',
    ],

    /*
    |--------------------------------------------------------------------------
    | Compression defaults
    |--------------------------------------------------------------------------
    */

    'default_compression' => env('PDF_DEFAULT_COMPRESSION', 'recommended'),

    'compression_levels' => ['low', 'medium', 'recommended', 'maximum'],

    /*
    |--------------------------------------------------------------------------
    | External tools (optional)
    |--------------------------------------------------------------------------
    */

    'ghostscript_path' => env('GHOSTSCRIPT_PATH'),

    /*
    | Optional second pass after Ghostscript: recompresses images inside the PDF
    | when built with optimize-images support (qpdf 11+). Detected on PATH if unset.
    */
    'qpdf_path' => env('QPDF_PATH'),

    'libreoffice_path' => env('LIBREOFFICE_PATH'),

    /*
    |--------------------------------------------------------------------------
    | iLovePDF API (optional fallback for compression)
    |--------------------------------------------------------------------------
    */

    'ilovepdf' => [
        'public_key' => env('ILOVEPDF_PUBLIC_KEY'),
        'secret_key' => env('ILOVEPDF_SECRET_KEY'),
    ],

];
