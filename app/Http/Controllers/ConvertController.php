<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConvertPdfRequest;
use App\Models\ConversionLog;
use App\Models\Document;
use App\Services\PdfConversionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class ConvertController extends Controller
{
    public function __construct(private PdfConversionService $conversion) {}

    public function create(Request $request): View
    {
        $documents = Document::query()
            ->where('user_id', $request->user()->id)
            ->where('status', Document::STATUS_COMPLETED)
            ->latest()
            ->get();

        return view('pdf.convert', [
            'documents' => $documents,
            'targets' => PdfConversionService::SUPPORTED_TARGETS,
        ]);
    }

    public function store(ConvertPdfRequest $request): BinaryFileResponse|RedirectResponse
    {
        /** @var Document $source */
        $source = Document::where('id', $request->integer('document_id'))
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $target = $request->input('target');
        $start = microtime(true);

        try {
            $result = $this->conversion->convertFromPdf($source->absolutePath(), $target);
        } catch (Throwable $e) {
            Log::error('PDF conversion failed', ['error' => $e->getMessage()]);

            ConversionLog::create([
                'document_id' => $source->id,
                'source_format' => 'pdf',
                'target_format' => $target,
                'processing_time_ms' => (int) ((microtime(true) - $start) * 1000),
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return back()->withErrors(['convert' => $e->getMessage()]);
        }

        ConversionLog::create([
            'document_id' => $source->id,
            'source_format' => 'pdf',
            'target_format' => $target,
            'processing_time_ms' => (int) ((microtime(true) - $start) * 1000),
            'status' => 'success',
        ]);

        $downloadName = pathinfo($source->original_name, PATHINFO_FILENAME).'.'.$target;

        // Persist a Document row for the converted file so it lives in storage history.
        $relativePath = 'converted/'.basename($result['path']);
        Document::create([
            'user_id' => $request->user()->id,
            'original_name' => $downloadName,
            'file_path' => $relativePath,
            'file_size' => Storage::disk('local')->size($relativePath),
            'mime_type' => $this->mimeFor($target),
            'pages' => $result['pages'],
            'status' => Document::STATUS_COMPLETED,
            'operation_type' => Document::OP_CONVERTED,
            'parent_document_id' => $source->id,
            'metadata' => ['target' => $target],
        ]);

        return response()->download($result['path'], $downloadName);
    }

    private function mimeFor(string $target): string
    {
        return match ($target) {
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'html' => 'text/html',
            'txt' => 'text/plain',
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
            default => 'application/octet-stream',
        };
    }
}
