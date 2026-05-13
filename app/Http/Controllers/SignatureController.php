<?php

namespace App\Http\Controllers;

use App\Http\Requests\SignPdfRequest;
use App\Models\Document;
use App\Models\SignatureRequest;
use App\Services\PdfConversionService;
use App\Services\PdfSignatureService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SignatureController extends Controller
{
    public function __construct(
        private PdfSignatureService $signer,
        private PdfConversionService $conversion,
    ) {}

    public function create(Request $request, Document $document): View
    {
        $this->authorizeDocument($request, $document);

        return view('pdf.sign', ['document' => $document]);
    }

    public function store(SignPdfRequest $request, Document $document): RedirectResponse
    {
        $this->authorizeDocument($request, $document);

        $data = $request->validated();

        $hasDrawing = ! empty($data['signature']);
        $hasTyped = ! empty($data['typed_signature']);
        $hasLogo = ! empty($data['logo']);

        $metadata = [];
        $signaturePosition = [];

        $absolutePath = null;

        try {
            if ($hasDrawing) {
                $drawPosition = [
                    'page' => (int) $data['page'],
                    'x' => (float) $data['x'],
                    'y' => (float) $data['y'],
                    'width' => isset($data['width']) ? (float) $data['width'] : 60.0,
                ];
                $metadata['signature'] = $drawPosition;
                $signaturePosition['signature'] = $drawPosition;

                $image = $this->signer->createSignatureFromDataUrl($data['signature']);
                $absolutePath = $this->signer->addSignature(
                    $absolutePath ?? $document->absolutePath(),
                    $image,
                    $drawPosition,
                );
                @unlink($image);
            }

            if ($hasTyped) {
                $typedPosition = [
                    'page' => (int) $data['typed_page'],
                    'x' => (float) $data['typed_x'],
                    'y' => (float) $data['typed_y'],
                    'width' => isset($data['typed_width']) ? (float) $data['typed_width'] : 60.0,
                ];
                $metadata['typed_text'] = $typedPosition;
                $signaturePosition['typed_text'] = $typedPosition;

                $image = $this->signer->createSignatureFromDataUrl($data['typed_signature']);
                $inputPdf = $absolutePath ?? $document->absolutePath();
                $nextPath = $this->signer->addSignature($inputPdf, $image, $typedPosition);
                @unlink($image);
                if ($absolutePath !== null && $nextPath !== $absolutePath && file_exists($absolutePath)) {
                    @unlink($absolutePath);
                }
                $absolutePath = $nextPath;
            }

            if ($hasLogo) {
                $logoPosition = [
                    'page' => (int) $data['logo_page'],
                    'x' => (float) $data['logo_x'],
                    'y' => (float) $data['logo_y'],
                    'width' => (float) $data['logo_width'],
                ];
                $metadata['logo'] = $logoPosition;
                $signaturePosition['logo'] = $logoPosition;

                $logoImage = $this->signer->createImageFromDataUrl($data['logo']);
                $inputPdf = $absolutePath ?? $document->absolutePath();
                $stampedPath = $this->signer->addSignature($inputPdf, $logoImage, $logoPosition, 800);
                @unlink($logoImage);
                if ($absolutePath !== null && $stampedPath !== $absolutePath && file_exists($absolutePath)) {
                    @unlink($absolutePath);
                }
                $absolutePath = $stampedPath;
            }
        } catch (Throwable $e) {
            if ($absolutePath !== null && file_exists($absolutePath)) {
                @unlink($absolutePath);
            }
            Log::error('PDF signing failed', ['error' => $e->getMessage()]);

            return back()->withErrors(['sign' => 'Could not update PDF: '.$e->getMessage()]);
        }

        if ($absolutePath === null) {
            return back()->withErrors(['sign' => 'Nothing to apply.']);
        }

        $relativePath = 'signed/'.basename($absolutePath);

        $pages = 0;
        try {
            $pages = $this->conversion->countPages($absolutePath);
        } catch (Throwable) {
            // ignore
        }

        $signed = Document::create([
            'user_id' => $request->user()->id,
            'original_name' => pathinfo($document->original_name, PATHINFO_FILENAME).'-signed.pdf',
            'file_path' => $relativePath,
            'file_size' => Storage::disk('local')->size($relativePath),
            'mime_type' => 'application/pdf',
            'pages' => $pages,
            'status' => Document::STATUS_COMPLETED,
            'operation_type' => Document::OP_SIGNED,
            'parent_document_id' => $document->id,
            'metadata' => $metadata,
        ]);

        SignatureRequest::create([
            'document_id' => $signed->id,
            'requester_email' => $request->user()->email,
            'signer_email' => $request->user()->email,
            'signature_position' => $signaturePosition,
            'status' => SignatureRequest::STATUS_SIGNED,
            'signed_file_path' => $relativePath,
        ]);

        return redirect()->route('pdf.show', $signed)
            ->with('success', 'PDF signed successfully.');
    }

    private function authorizeDocument(Request $request, Document $document): void
    {
        abort_unless($document->user_id === $request->user()->id, 403);
    }
}
