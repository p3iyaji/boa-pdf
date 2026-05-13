<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadPdfRequest;
use App\Models\Document;
use App\Services\PdfConversionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PdfController extends Controller
{
    public function __construct(private PdfConversionService $conversion) {}

    public function index(Request $request): View
    {
        $documents = Document::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return view('pdf.index', ['documents' => $documents]);
    }

    public function upload(UploadPdfRequest $request): RedirectResponse
    {
        $file = $request->file('file');
        $name = Str::uuid().'.pdf';
        $relativePath = $file->storeAs('uploads', $name, 'local');
        $absolutePath = Storage::disk('local')->path($relativePath);

        $pages = 0;
        try {
            $pages = $this->conversion->countPages($absolutePath);
        } catch (\Throwable) {
            // Ignore - file may be corrupt; status will reflect that.
        }

        $document = Document::create([
            'user_id' => $request->user()->id,
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $relativePath,
            'file_size' => $file->getSize(),
            'mime_type' => 'application/pdf',
            'pages' => $pages,
            'status' => $pages > 0 ? Document::STATUS_COMPLETED : Document::STATUS_FAILED,
            'operation_type' => Document::OP_UPLOAD,
        ]);

        return redirect()->route('pdf.show', $document)
            ->with('success', 'PDF uploaded successfully.');
    }

    public function show(Request $request, Document $document): View
    {
        $this->authorizeDocument($request, $document);

        return view('pdf.show', ['document' => $document]);
    }

    public function stream(Request $request, Document $document): BinaryFileResponse|StreamedResponse
    {
        $this->authorizeDocument($request, $document);

        return response()->file($document->absolutePath(), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.addslashes($document->original_name).'"',
        ]);
    }

    public function download(Request $request, Document $document): BinaryFileResponse
    {
        $this->authorizeDocument($request, $document);

        return response()->download($document->absolutePath(), $document->original_name);
    }

    public function destroy(Request $request, Document $document): RedirectResponse
    {
        $this->authorizeDocument($request, $document);

        if (Storage::disk('local')->exists($document->file_path)) {
            Storage::disk('local')->delete($document->file_path);
        }
        $document->delete();

        return redirect()->route('pdf.index')->with('success', 'Document deleted.');
    }

    private function authorizeDocument(Request $request, Document $document): void
    {
        abort_unless($document->user_id === $request->user()->id, 403);
    }
}
