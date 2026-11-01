<?php

namespace Tests\Feature;

use App\Services\NoteFileExtractor;
use Illuminate\Http\UploadedFile;
use RuntimeException;
use Tests\TestCase;

class NoteFileExtractorTest extends TestCase
{
    public function test_it_extracts_plain_text_notes(): void
    {
        $file = UploadedFile::fake()->createWithContent('notes.txt', "Cell division\nMitosis creates two identical cells.");

        $this->assertSame(
            "Cell division\nMitosis creates two identical cells.",
            app(NoteFileExtractor::class)->extract($file)
        );
    }

    public function test_it_limits_extracted_text_length(): void
    {
        $file = UploadedFile::fake()->createWithContent('notes.txt', str_repeat('a', 20000));

        $extracted = app(NoteFileExtractor::class)->extract($file);

        $this->assertSame(6000 + mb_strlen("\n\n[Attachment text truncated after 6000 characters.]"), mb_strlen($extracted));
        $this->assertStringEndsWith('[Attachment text truncated after 6000 characters.]', $extracted);
    }

    public function test_it_rejects_files_without_readable_text(): void
    {
        $file = UploadedFile::fake()->createWithContent('notes.txt', '   ');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No readable text was found.');

        app(NoteFileExtractor::class)->extract($file);
    }
}
