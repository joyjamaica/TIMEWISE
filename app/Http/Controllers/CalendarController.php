<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CalendarController extends Controller
{
    
    public function index()
    {
        $savedRecord = DB::table('calendar_schedules')
            ->where('user_id', auth()->id())
            ->first();

        $savedSchedule = $savedRecord
            ? json_decode($savedRecord->schedule, true)
            : [];

        return view('calendar', compact('savedSchedule'));
    }


    public function organize(Request $request)
    {
        $request->validate([
            'input_text' => ['nullable', 'string', 'max:20000'],
            'week_start' => ['required', 'date'],
            'schedule_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);

        $inputText = trim((string) $request->input('input_text', ''));
        $file = $request->file('schedule_file');

        if ($inputText === '' && !$file) {
            return response()->json([
                'error' => 'Please provide some text or upload a syllabus/assignment file.',
            ], 422);
        }

        $apiKey = config('services.gemini.api_key');
        if (!$apiKey) {
            return response()->json([
                'error' => 'Gemini API key is missing. Add GEMINI_API_KEY to your .env file, then restart the Laravel server.',
            ], 500);
        }

        $weekStart = $request->input('week_start');
        $parts = [];

        if ($inputText !== '') {
            $parts[] = ['text' => "Schedule ALL classes and items from this study load over the 5 days (Mon-Fri) of the week starting exactly on Monday, {$weekStart}. Ensure evening classes are included with their exact times.\n\n{$inputText}"];
        }

        if ($file) {
            $mimeType = $file->getMimeType();
            $parts[] = ['text' => "Analyze this document thoroughly. Extract ALL classes, tasks, exact times (including evening classes up to 10 PM), subjects, and rooms, and organize them into a complete 5-day schedule for Monday to Friday of the week starting exactly on Monday, {$weekStart}. Do not omit any subjects or time slots."];
            $parts[] = [
                'inlineData' => [
                    'mimeType' => $mimeType,
                    'data' => base64_encode(file_get_contents($file->getRealPath())),
                ],
            ];
        }

        $payload = [
            'contents' => [['role' => 'user', 'parts' => $parts]],
            'systemInstruction' => [
                'parts' => [[
                    'text' => <<<'PROMPT'
                        You are a precise university study-load parser and calendar generator.

                        Extract every class from the uploaded study-load document.
                        Treat the document as a table and read each column separately.

                        COLUMN RULES:
                        - Subject: Use ONLY the value in the Subject column.
                        - Days: Use the Days column to determine which weekdays a class occurs.
                        - Time: Use the exact start and end times from the Time column.
                        - Room: Use the Room column exactly as printed.
                        - Course: BSIT is the course name, NOT a subject.
                        - EDP Code, Units, and Status are not subject names.

                        DAY RULES:
                        - MW means Monday and Wednesday.
                        - TTH means Tuesday and Thursday.
                        - MWF means Monday, Wednesday, and Friday.
                        - Create a separate calendar event for each applicable weekday.
                        - Never assign a class to a weekday not listed in its Days column.

                        TIME RULES:
                        - Preserve exact hours and minutes, including evening classes.
                        - Interpret AM and PM carefully.
                        - Do not round times or invent missing times.
                        - Calculate the end time from the document, not from assumptions.
                        - Return local datetime strings without a timezone suffix.
                        - The week begins on the supplied Monday date.

                        ACCURACY RULES:
                        - Extract every class row exactly once before expanding its weekdays.
                        - Do not invent subjects, rooms, times, or additional classes.
                        - If a value cannot be read confidently, do not guess.
                        - Return only a valid JSON array.
                        - Each event must contain id, title, subject, room,
                        startTime, and endTime.
                        - Set title and subject to the actual Subject column value.
                        - Format startTime and endTime as YYYY-MM-DDTHH:mm:ss.
                        PROMPT,
                ]],
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'id' => ['type' => 'STRING'],
                            'title' => ['type' => 'STRING'],
                            'subject' => ['type' => 'STRING'],
                            'room' => ['type' => 'STRING'],
                            'startTime' => ['type' => 'STRING'],
                            'endTime' => ['type' => 'STRING'],
                        ],
                        'required' => ['id', 'title', 'subject', 'startTime', 'endTime'],
                    ],
                ],
            ],
        ];

        try {
            $response = Http::timeout(90)
                ->withHeaders(['x-goog-api-key' => $apiKey])
                ->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-3-flash-preview:generateContent', $payload);

            if (!$response->successful()) {
                Log::warning('Gemini schedule request failed', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);

                $apiMessage = $response->json('error.message');
                return response()->json([
                    'error' => $apiMessage
                        ? 'Gemini API error: '.$apiMessage
                        : 'Gemini API request failed (HTTP '.$response->status().'). Check the API key, model access, and billing/quota settings.',
                ], 502);
            }

            $text = $response->json('candidates.0.content.parts.0.text');
            $schedule = is_string($text) ? json_decode($text, true) : null;

            if (!is_array($schedule) || !array_is_list($schedule)) {
                return response()->json([
                    'error' => 'Gemini returned an unexpected schedule format. Please try again.'
                ], 502);
            }

            // SAVE ORGANIZED SCHEDULE TO DATABASE
            DB::table('calendar_schedules')->updateOrInsert(
                [
                    'user_id' => $request->user()->id,
                ],
                [
                    'week_start' => $weekStart,
                    'schedule' => json_encode($schedule),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            // Return organized schedule to JavaScript
            return response()->json($schedule);
        } catch (\Throwable $e) {
            Log::error('Gemini schedule request exception', ['message' => $e->getMessage()]);
            return response()->json([
                'error' => 'Could not contact Gemini. Check your internet connection and try again.',
            ], 502);
        }
    }
}
