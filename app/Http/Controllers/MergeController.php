<?php

namespace App\Http\Controllers;

use App\Http\Requests\MergePdfRequest;
use App\Models\Document;
use App\Services\PdfConversionService;
use App\Services\PdfMergeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MergeController extends Controller
{
    public function __construct(
        private PdfMergeService $merger,
        private PdfConversionService $conversion,
    ) {}

    public function create(Request $request): View
    {
        $documents = Document::query()
            ->where('user_id', $request->user()->id)
            ->where('status', Document::STATUS_COMPLETED)
            ->latest()
            ->get();

        $mergeDocuments = $documents->map(fn (Document $d): array => [
            'id' => $d->id,
            'name' => $d->original_name,
            'pages' => $d->pages,
            'size' => $d->human_file_size,
        ])->values()->all();

        return view('pdf.merge', [
            'mergeDocuments' => $mergeDocuments,
        ]);
    }

    public function store(MergePdfRequest $request): RedirectResponse
    {
        $documents = $request->documents();
        $paths = array_map(fn (Document $d): string => $d->absolutePath(), $documents);

        try {
            $absolutePath = $this->merger->merge($paths);
        } catch (Throwable $e) {
            Log::error('PDF merge failed', ['error' => $e->getMessage()]);

            return back()->withErrors(['merge' => 'Could not merge PDFs: '.$e->getMessage()]);
        }

        $relativePath = 'merged/'.basename($absolutePath);
        $name = $request->input('output_name') ?: 'merged-'.now()->format('Ymd-His').'.pdf';

        $pages = 0;
        try {
            $pages = $this->conversion->countPages($absolutePath);
        } catch (Throwable) {
            // ignore
        }

        $document = Document::create([
            'user_id' => $request->user()->id,
            'original_name' => str_ends_with($name, '.pdf') ? $name : $name.'.pdf',
            'file_path' => $relativePath,
            'file_size' => Storage::disk('local')->size($relativePath),
            'mime_type' => 'application/pdf',
            'pages' => $pages,
            'status' => Document::STATUS_COMPLETED,
            'operation_type' => Document::OP_MERGED,
            'parent_document_id' => $documents[0]->id ?? null,
            'metadata' => [
                'source_ids' => array_map(fn (Document $d): int => $d->id, $documents),
            ],
        ]);

        return redirect()->route('pdf.show', $document)
            ->with('success', 'Merged '.count($documents).' documents.');
    }
}
