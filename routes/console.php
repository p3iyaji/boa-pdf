<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Delete generated temp PDF artifacts older than `pdf.temp_cleanup_hours`.
 *
 * Originals (uploads/) are kept; only the on-the-fly output directories are pruned.
 * For aged-out user library documents (including uploads/), see `pdf:prune-expired-documents`.
 */
Artisan::command('pdf:cleanup', function () {
    $cutoff = now()->subHours((int) config('pdf.temp_cleanup_hours', 24))->getTimestamp();
    $disk = Storage::disk('local');
    $removed = 0;

    foreach (['merged', 'compressed', 'signed', 'converted', 'temp'] as $dir) {
        if (! $disk->exists($dir)) {
            continue;
        }
        foreach ($disk->files($dir) as $file) {
            if ($disk->lastModified($file) < $cutoff) {
                $disk->delete($file);
                $removed++;
            }
        }
    }

    $this->info("Pruned {$removed} stale PDF artifact(s).");
})->purpose('Remove generated PDF outputs older than the configured cutoff.');

Schedule::command('pdf:cleanup')->daily();
Schedule::command('pdf:prune-expired-documents')->daily();
