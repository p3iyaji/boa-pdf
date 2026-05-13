<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use setasign\Fpdi\Fpdi;
use Smalot\PdfParser\Parser;

class PdfConversionService
{
    public const SUPPORTED_TARGETS = ['txt', 'html', 'docx', 'jpg', 'png'];

    /**
     * Convert a PDF to another format.
     *
     * - `txt`: extracted via Smalot\PdfParser.
     * - `html`: simple wrapped extraction.
     * - `docx`: built as a plain-text DOCX wrapper if LibreOffice not available.
     * - `jpg`/`png`: requires Imagick (with Ghostscript) or LibreOffice.
     *
     * @return array{path: string, pages: int}
     */
    public function convertFromPdf(string $pdfPath, string $target): array
    {
        $target = strtolower($target);
        if (! in_array($target, self::SUPPORTED_TARGETS, true)) {
            throw new RuntimeException("Unsupported target format: {$target}");
        }
        if (! file_exists($pdfPath)) {
            throw new RuntimeException("PDF not found: {$pdfPath}");
        }

        return match ($target) {
            'txt' => $this->toText($pdfPath),
            'html' => $this->toHtml($pdfPath),
            'docx' => $this->toDocx($pdfPath),
            'jpg', 'png' => $this->toImage($pdfPath, $target),
        };
    }

    public function countPages(string $pdfPath): int
    {
        $fpdi = new Fpdi;

        return $fpdi->setSourceFile($pdfPath);
    }

    /**
     * @return array{path: string, pages: int}
     */
    private function toText(string $pdfPath): array
    {
        $parser = new Parser;
        $document = $parser->parseFile($pdfPath);
        $text = $document->getText();

        $outputPath = $this->ensureDirectory('converted').'/'.Str::uuid().'.txt';
        file_put_contents($outputPath, $text);

        return ['path' => $outputPath, 'pages' => count($document->getPages())];
    }

    /**
     * @return array{path: string, pages: int}
     */
    private function toHtml(string $pdfPath): array
    {
        $parser = new Parser;
        $document = $parser->parseFile($pdfPath);

        $body = '';
        foreach ($document->getPages() as $i => $page) {
            $body .= '<section class="page" data-page="'.($i + 1).'">';
            $body .= '<pre>'.htmlspecialchars($page->getText(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</pre>';
            $body .= '</section>'.PHP_EOL;
        }

        $html = '<!doctype html><html><head><meta charset="utf-8"><title>Converted PDF</title></head><body>'.$body.'</body></html>';

        $outputPath = $this->ensureDirectory('converted').'/'.Str::uuid().'.html';
        file_put_contents($outputPath, $html);

        return ['path' => $outputPath, 'pages' => count($document->getPages())];
    }

    /**
     * @return array{path: string, pages: int}
     */
    private function toDocx(string $pdfPath): array
    {
        $libre = $this->libreOfficeBinary();
        if ($libre !== null) {
            return $this->convertWithLibreOffice($pdfPath, 'docx', $libre);
        }

        $parser = new Parser;
        $document = $parser->parseFile($pdfPath);
        $text = $document->getText();

        $outputPath = $this->ensureDirectory('converted').'/'.Str::uuid().'.docx';
        $this->writeMinimalDocx($outputPath, $text);

        return ['path' => $outputPath, 'pages' => count($document->getPages())];
    }

    /**
     * @return array{path: string, pages: int}
     */
    private function toImage(string $pdfPath, string $format): array
    {
        if (! extension_loaded('imagick')) {
            throw new RuntimeException('PDF→image conversion requires the Imagick PHP extension (with a Ghostscript-enabled ImageMagick).');
        }

        $imagick = new \Imagick;
        $imagick->setResolution(150, 150);
        $imagick->readImage($pdfPath);
        $imagick->setImageFormat($format);

        $outputDir = $this->ensureDirectory('converted');
        $base = Str::uuid();
        $pageCount = $imagick->getNumberImages();

        if ($pageCount === 1) {
            $outputPath = $outputDir.'/'.$base.'.'.$format;
            $imagick->writeImage($outputPath);
        } else {
            $outputPath = $outputDir.'/'.$base.'-%d.'.$format;
            $imagick->writeImages($outputPath, false);
            $outputPath = $outputDir.'/'.$base.'-0.'.$format;
        }

        $imagick->clear();

        return ['path' => $outputPath, 'pages' => $pageCount];
    }

    /**
     * @return array{path: string, pages: int}
     */
    private function convertWithLibreOffice(string $pdfPath, string $target, string $libre): array
    {
        $outputDir = $this->ensureDirectory('converted');
        $cmd = sprintf(
            '%s --headless --convert-to %s --outdir %s %s 2>&1',
            escapeshellcmd($libre),
            escapeshellarg($target),
            escapeshellarg($outputDir),
            escapeshellarg($pdfPath),
        );

        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new RuntimeException('LibreOffice conversion failed: '.implode("\n", $output));
        }

        $generated = $outputDir.'/'.pathinfo($pdfPath, PATHINFO_FILENAME).'.'.$target;
        if (! file_exists($generated)) {
            throw new RuntimeException("LibreOffice did not produce expected file: {$generated}");
        }

        return ['path' => $generated, 'pages' => $this->countPages($pdfPath)];
    }

    private function libreOfficeBinary(): ?string
    {
        $configured = config('pdf.libreoffice_path') ?: env('LIBREOFFICE_PATH');
        if ($configured && is_executable($configured)) {
            return $configured;
        }

        foreach ([
            '/Applications/LibreOffice.app/Contents/MacOS/soffice',
            '/opt/homebrew/bin/soffice',
            '/usr/local/bin/soffice',
            '/usr/bin/libreoffice',
            '/usr/bin/soffice',
        ] as $candidate) {
            if (is_executable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Write a minimal valid .docx wrapping plain text in a single paragraph.
     */
    private function writeMinimalDocx(string $outputPath, string $text): void
    {
        $safe = htmlspecialchars($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $documentXml = <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:body>
    <w:p><w:r><w:t xml:space="preserve">{$safe}</w:t></w:r></w:p>
    <w:sectPr/>
  </w:body>
</w:document>
XML;

        $contentTypes = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
</Types>
XML;

        $rels = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>
XML;

        $zip = new \ZipArchive;
        if ($zip->open($outputPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Unable to create DOCX archive at {$outputPath}.");
        }

        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', $rels);
        $zip->addFromString('word/document.xml', $documentXml);
        $zip->close();
    }

    private function ensureDirectory(string $subPath): string
    {
        $disk = Storage::disk('local');
        if (! $disk->exists($subPath)) {
            $disk->makeDirectory($subPath);
        }

        return $disk->path($subPath);
    }
}
