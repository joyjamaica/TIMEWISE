<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use RuntimeException;
use Smalot\PdfParser\Parser;
use Throwable;

class NoteFileExtractor
{
    private const MAX_TEXT_LENGTH = 6000;

    public function extract(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, ['pdf', 'txt', 'md', 'csv'], true)) {
            throw new RuntimeException('Please upload a PDF, TXT, Markdown, or CSV file.');
        }

        try {
            $text = match ($extension) {
                'pdf' => (new Parser())->parseFile($file->getRealPath())->getText(),
                'txt', 'md', 'csv' => file_get_contents($file->getRealPath()),
            };
        } catch (Throwable $e) {
            throw new RuntimeException('PulseBot could not read that file. Try a text-based PDF or a TXT, Markdown, or CSV file.', 0, $e);
        }

        if (!is_string($text)) {
            throw new RuntimeException('PulseBot could not read that file. Please try another file.');
        }

        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', ' ', $text);
        $text = trim((string) $text);

        if ($text === '') {
            throw new RuntimeException('No readable text was found. Scanned PDFs need OCR before they can be summarized.');
        }

        if (mb_strlen($text) > self::MAX_TEXT_LENGTH) {
            return mb_substr($text, 0, self::MAX_TEXT_LENGTH)
                ."\n\n[Attachment text truncated after ".self::MAX_TEXT_LENGTH.' characters.]';
        }

        return $text;
    }
}
