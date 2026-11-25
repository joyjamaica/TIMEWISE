<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Smalot\PdfParser\Parser;

class PulseBotController extends Controller
{
    public function index()
    {
        $history = session()->get('pulsebot_history', []);
        return view('pulsebot', compact('history'));
    }

    public function send(Request $request)
    {
        $request->validate([
            'message' => 'nullable|string|max:1000',
            'file' => 'nullable|file|mimes:pdf,txt,md,csv,ppt,pptx|max:5120',
        ]);

        $userPrompt = $request->input('message') ?? 'Please summarize and analyze this attached document.';
        $fileContent = '';

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $extension = strtolower($file->getClientOriginalExtension());

            if (in_array($extension, ['txt', 'md', 'csv'])) {
                $rawText = @file_get_contents($file->getRealPath());
                $cleanText = preg_replace('/[^\P{C}\n]+/u', '', $rawText);
                $fileContent = "\n\n[File Contents of " . $file->getClientOriginalName() . "]:\n" . trim($cleanText);
            } elseif ($extension === 'pdf') {
                try {
                    $parser = new Parser();
                    $pdf = $parser->parseFile($file->getRealPath());
                    $extractedText = $pdf->getText();

                    $cleanPdfText = preg_replace('/[^\P{C}\n]+/u', '', $extractedText);
                    $fileContent = "\n\n[Extracted PDF Contents of " . $file->getClientOriginalName() . "]:\n" . trim(substr($cleanPdfText, 0, 15000));
                } catch (\Exception $e) {
                    $fileContent = "\n\n[Attached PDF: " . $file->getClientOriginalName() . " (Could not parse text automatically)]";
                }
            } else {
                $fileContent = "\n\n[Attached Document: " . $file->getClientOriginalName() . "]";
            }
        }

        $history = session()->get('pulsebot_history', []);
        $history[] = ['role' => 'user', 'content' => $userPrompt . $fileContent];

        return response()->stream(function () use ($history) {
            $messages = [
                [
                    'role' => 'system',
                    'content' => 'You are PulseBot AI, a warm and helpful student productivity assistant integrated into TimeWise.

CRITICAL FORMATTING RULES:
- NEVER use markdown hash headers (like # or ##), table pipes (|), or divider lines (---).
- Use clean paragraphs, bullet points with bold keywords, and natural spacing.
- Sprinkle in warm, friendly emojis (like 👋, 💡, ✨, 📚, 🎯) naturally to make your responses expressive and pleasant to read.
- Keep your replies encouraging, concise, and structured.'
                ]
            ];

            foreach ($history as $h) {
                $messages[] = [
                    'role' => $h['role'] === 'assistant' ? 'assistant' : 'user',
                    'content' => $h['content']
                ];
            }

            try {
                $response = Http::withoutVerifying()
                    ->withToken(env('GROQ_API_KEY'))
                    ->timeout(45)
                    ->post('https://api.groq.com/openai/v1/chat/completions', [
                        'model' => env('GROQ_MODEL', 'llama-3.1-8b-instant'),
                        'messages' => $messages,
                        'temperature' => 0.7,
                    ]);

                if ($response->successful()) {
                    $reply = $response->json('choices.0.message.content') ?? 'No response generated.';

                    $history[] = ['role' => 'assistant', 'content' => $reply];
                    session()->put('pulsebot_history', $history);

                    echo "data: " . json_encode(['token' => $reply]) . "\n\n";
                    echo "data: " . json_encode(['done' => true, 'reply' => $reply]) . "\n\n";
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                } else {
                    $errorMsg = $response->json('error.message') ?? 'Groq API connection error.';
                    echo "data: " . json_encode(['error' => $errorMsg]) . "\n\n";
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                }
            } catch (\Exception $e) {
                echo "data: " . json_encode(['error' => 'Connection failed: ' . $e->getMessage()]) . "\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function clear()
    {
        session()->forget('pulsebot_history');
        return response()->json(['success' => true]);
    }
}