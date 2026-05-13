<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompressPdfRequest;
use App\Models\ConversionLog;
use App\Models\Document;
use App\Services\PdfCompressionService;
use App\Services\PdfConversionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CompressController extends Controller
{
    public function __construct(
        private PdfCompressionService $compressor,
        private PdfConversionService $conversion,
    ) {}

    public function create(Request $request): View
    {
        $documents = Document::query()
            ->where('user_id', $request->user()->id)
            ->where('status', Document::STATUS_COMPLETED)
            ->latest()
            ->get();

        return view('pdf.compress', [
            'documents' => $documents,
            'levels' => config('pdf.compression_levels'),
            'default' => config('pdf.default_compression'),
        ]);
    }

    public function store(CompressPdfRequest $request): RedirectResponse
    {
        /** @var Document $source */
        $source = Document::where('id', $request->integer('document_id'))
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $level = $request->input('level', config('pdf.default_compression'));
        $start = microtime(true);

        try {
            $result = $this->compressor->compress($source->absolutePath(), $level);
        } catch (Throwable $e) {
            Log::error('PDF compression failed', ['error' => $e->getMessage()]);

            ConversionLog::create([
                'document_id' => $source->id,
                'source_format' => 'pdf',
                'target_format' => 'pdf',
                'processing_time_ms' => (int) ((microtime(true) - $start) * 1000),
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return back()->withErrors(['compress' => $e->getMessage()]);
        }

        $relativePath = 'compressed/'.basename($result['path']);

        $pages = 0;
        try {
            $pages = $this->conversion->countPages($result['path']);
        } catch (Throwable) {
            // ignore
        }

        $name = pathinfo($source->original_name, PATHINFO_FILENAME).'-compressed.pdf';

        $compressed = Document::create([
            'user_id' => $request->user()->id,
            'original_name' => $name,
            'file_path' => $relativePath,
            'file_size' => Storage::disk('local')->size($relativePath),
            'mime_type' => 'application/pdf',
            'pages' => $pages,
            'status' => Document::STATUS_COMPLETED,
            'operation_type' => Document::OP_COMPRESSED,
            'parent_document_id' => $source->id,
            'metadata' => [
                'level' => $level,
                'method' => $result['method'],
                'original_size' => $result['original_size'],
                'new_size' => $result['new_size'],
            ],
        ]);

        ConversionLog::create([
            'document_id' => $compressed->id,
            'source_format' => 'pdf',
            'target_format' => 'pdf',
            'processing_time_ms' => (int) ((microtime(true) - $start) * 1000),
            'status' => 'success',
        ]);

        $delta = $result['original_size'] > 0
            ? round((1 - $result['new_size'] / $result['original_size']) * 100, 1)
            : 0;

        return redirect()->route('pdf.show', $compressed)
            ->with('success', "Compressed via {$result['method']} ({$delta}% smaller).");
    }
}
