<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CalendarController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();

        // First, look for the semester that includes today's date.
        $savedRecord = DB::table('semester_class_schedules')
            ->where('user_id', auth()->id())
            ->whereDate('semester_start', '<=', $today)
            ->whereDate('semester_end', '>=', $today)
            ->orderByDesc('semester_start')
            ->first();

        // If there is no active semester, show the next upcoming one.
        if (!$savedRecord) {
            $savedRecord = DB::table('semester_class_schedules')
                ->where('user_id', auth()->id())
                ->whereDate('semester_start', '>', $today)
                ->orderBy('semester_start')
                ->first();
        }

        // If there is no upcoming semester, show the most recently saved one.
        if (!$savedRecord) {
            $savedRecord = DB::table('semester_class_schedules')
                ->where('user_id', auth()->id())
                ->orderByDesc('semester_start')
                ->first();
        }

        $savedSchedule = $savedRecord
            ? json_decode($savedRecord->classes, true)
            : [];

        $semesterStart = $savedRecord?->semester_start;
        $semesterEnd = $savedRecord?->semester_end;

        return view('calendar', compact(
            'savedSchedule',
            'semesterStart',
            'semesterEnd'
        ));
    }

    public function organize(Request $request)
    {
        $request->validate([
            'input_text' => ['nullable', 'string', 'max:20000'],
            'semester_start' => ['required', 'date'],
            'semester_end' => ['required', 'date', 'after_or_equal:semester_start'],
            'schedule_file' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp,pdf',
                'max:5120'
            ],
        ]);

        $inputText = trim((string) $request->input('input_text', ''));
        $file = $request->file('schedule_file');

        if ($inputText === '' && !$file) {
            return response()->json([
                'error' => 'Please provide your study load as text or upload a schedule file.',
            ], 422);
        }

        $apiKey = config('services.gemini.api_key');

        if (!$apiKey) {
            return response()->json([
                'error' => 'Gemini API key is missing. Add GEMINI_API_KEY to your .env file, then restart the Laravel server.',
            ], 500);
        }

        $semesterStart = $request->input('semester_start');
        $semesterEnd = $request->input('semester_end');

        $parts = [];

        if ($inputText !== '') {
            $parts[] = [
                'text' => "Extract all recurring college classes from this study load. The semester starts on {$semesterStart} and ends on {$semesterEnd}.\n\n{$inputText}"
            ];
        }

        if ($file) {
            $mimeType = $file->getMimeType();

            $parts[] = [
                'text' => "Analyze this study-load document thoroughly. Extract every recurring class, its exact subject, days, start time, end time, and room. The semester starts on {$semesterStart} and ends on {$semesterEnd}. Do not include assignments, tasks, or deadlines."
            ];

            $parts[] = [
                'inlineData' => [
                    'mimeType' => $mimeType,
                    'data' => base64_encode(
                        file_get_contents($file->getRealPath())
                    ),
                ],
            ];
        }

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => $parts,
                ],
            ],

            'systemInstruction' => [
                'parts' => [
                    [
                        'text' => <<<'PROMPT'
You are a precise university study-load parser.

Your task is to extract recurring college class schedules from the provided study load.

IMPORTANT:
Extract each class only ONCE. Do not generate separate events for every week or every weekday. The calendar system will automatically repeat each class on its specified days throughout the semester.

COLUMN RULES:
- Subject: Use ONLY the value in the Subject column.
- Days: Use the Days column to determine the recurring weekdays.
- Time: Use the exact start and end times from the Time column.
- Room: Use the Room column exactly as printed.
- BSIT is a course name, NOT a subject.
- EDP Code, Units, and Status are not subject names.

DAY RULES:
- M means Monday.
- T means Tuesday.
- W means Wednesday.
- TH means Thursday.
- F means Friday.
- S means Saturday.
- SU means Sunday.
- MW means Monday and Wednesday.
- TTH means Tuesday and Thursday.
- MWF means Monday, Wednesday, and Friday.
- TTHS means Tuesday, Thursday, and Saturday.
- Return the full weekday names in the days array.
- Include only the weekdays listed in the document.
- Do not invent additional class days.

TIME RULES:
- Preserve exact hours and minutes.
- Interpret AM and PM carefully.
- Include evening classes.
- Do not round times or invent missing times.
- Return times using 24-hour HH:mm format.
- For example, 8:00 AM becomes 08:00 and 5:30 PM becomes 17:30.

ACCURACY RULES:
- Extract every class row exactly once.
- Do not create a separate class object for each weekday.
- Do not include assignments, projects, exams, tasks, or deadlines.
- Do not invent subjects, rooms, days, or times.
- If a value cannot be read confidently, do not guess.
- Use the actual Subject column value for both title and subject.
- If the room is missing or unreadable, return an empty string.
- Return only a valid JSON array.

Each class object must contain:
- id
- title
- subject
- room
- days
- startTime
- endTime

The days field must be an array of full weekday names, such as ["Monday", "Wednesday"].

The startTime and endTime fields must use HH:mm format.

PROMPT,
                    ],
                ],
            ],

            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'id' => [
                                'type' => 'STRING'
                            ],
                            'title' => [
                                'type' => 'STRING'
                            ],
                            'subject' => [
                                'type' => 'STRING'
                            ],
                            'room' => [
                                'type' => 'STRING'
                            ],
                            'days' => [
                                'type' => 'ARRAY',
                                'items' => [
                                    'type' => 'STRING',
                                ],
                            ],
                            'startTime' => [
                                'type' => 'STRING'
                            ],
                            'endTime' => [
                                'type' => 'STRING'
                            ],
                        ],
                        'required' => [
                            'id',
                            'title',
                            'subject',
                            'room',
                            'days',
                            'startTime',
                            'endTime'
                        ],
                    ],
                ],
            ],
        ];

        try {
            $response = Http::timeout(90)
                ->withHeaders([
                    'x-goog-api-key' => $apiKey
                ])
                ->post(
                    'https://generativelanguage.googleapis.com/v1beta/models/gemini-3-flash-preview:generateContent',
                    $payload
                );

            if (!$response->successful()) {
                Log::warning('Gemini semester schedule request failed', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);

                $apiMessage = $response->json('error.message');

                return response()->json([
                    'error' => $apiMessage
                        ? 'Gemini API error: ' . $apiMessage
                        : 'Gemini API request failed (HTTP ' . $response->status() . '). Check the API key, model access, and billing/quota settings.',
                ], 502);
            }

            $text = $response->json('candidates.0.content.parts.0.text');
            $classes = is_string($text)
                ? json_decode($text, true)
                : null;

            if (!is_array($classes) || !array_is_list($classes)) {
                return response()->json([
                    'error' => 'Gemini returned an unexpected class schedule format. Please try again.'
                ], 502);
            }

            // Validate and normalize the recurring class information.
            $validDays = [
                'Monday',
                'Tuesday',
                'Wednesday',
                'Thursday',
                'Friday',
                'Saturday',
                'Sunday',
            ];

            $cleanClasses = [];

            foreach ($classes as $class) {
                if (
                    !is_array($class) ||
                    empty($class['subject']) ||
                    empty($class['startTime']) ||
                    empty($class['endTime']) ||
                    !isset($class['days']) ||
                    !is_array($class['days'])
                ) {
                    continue;
                }

                $startTime = $class['startTime'];
                $endTime = $class['endTime'];

                if (
                    !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $startTime) ||
                    !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $endTime)
                ) {
                    continue;
                }

                $days = array_values(array_unique(array_filter(
                    $class['days'],
                    fn ($day) => in_array($day, $validDays, true)
                )));

                if (empty($days)) {
                    continue;
                }

                $subject = trim($class['subject']);

                $cleanClasses[] = [
                    'id' => !empty($class['id'])
                        ? (string) $class['id']
                        : (string) Str::uuid(),

                    'title' => trim($class['title'] ?? $subject),
                    'subject' => $subject,
                    'room' => trim($class['room'] ?? ''),
                    'days' => $days,
                    'startTime' => $startTime,
                    'endTime' => $endTime,
                ];
            }

            if (empty($cleanClasses)) {
                return response()->json([
                    'error' => 'No valid recurring classes were detected. Please check your study load and try again.'
                ], 422);
            }

            // Save one recurring schedule per user per semester start date.
            DB::table('semester_class_schedules')->updateOrInsert(
                [
                    'user_id' => $request->user()->id,
                    'semester_start' => $semesterStart,
                ],
                [
                    'semester_end' => $semesterEnd,
                    'classes' => json_encode($cleanClasses),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            // Return the recurring classes to the calendar JavaScript.
            return response()->json($cleanClasses);

        } catch (\Throwable $e) {
            Log::error('Gemini semester schedule request exception', [
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Could not contact Gemini. Check your internet connection and try again.',
            ], 502);
        }
    }
}
