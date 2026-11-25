<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    /** Every date shown on the dashboard is computed in Philippine time. */
    private const TZ = 'Asia/Manila';

    public function index()
    {
        $now = Carbon::now(self::TZ);
        $userId = Auth::id();

        // Greeting (Philippine time)
        $greeting = match (true) {
            $now->hour < 12 => 'Good morning',
            $now->hour < 18 => 'Good afternoon',
            default => 'Good evening',
        };

        // ---------------------------------------------------------------
        // TASKS (always filtered by the authenticated user)
        // ---------------------------------------------------------------
        $fmt = 'Y-m-d H:i:s';
        $todayStart = $now->copy()->startOfDay()->format($fmt);
        $todayEnd = $now->copy()->endOfDay()->format($fmt);

        $taskBase = fn () => DB::table('tasks')->where('user_id', $userId);

        $pendingQuery = fn () => $taskBase()->where(function ($q) {
            $q->where('is_completed', false)
                ->orWhereNull('is_completed');
        });

        $totalTasks = $taskBase()->count();
        $completedTasks = $taskBase()->where('is_completed', true)->count();
        $pendingTasks = max(0, $totalTasks - $completedTasks);

        // Guard against DivisionByZeroError
        $percent = $totalTasks > 0
            ? (int) round(($completedTasks / $totalTasks) * 100)
            : 0;

        $overdueCount = $pendingQuery()
            ->whereNotNull('due_date')
            ->where('due_date', '<', $todayStart)
            ->count();

        // Today's Focus
        $todayTasks = $taskBase()
            ->whereBetween('due_date', [$todayStart, $todayEnd])
            ->orderBy('is_completed')
            ->orderBy('due_date')
            ->get()
            ->map(fn ($t) => $this->formatTask($t))
            ->all();

        $dueTodayPending = count(array_filter(
            $todayTasks,
            fn ($t) => ! $t['done']
        ));

        // Upcoming deadlines (pending, due today or later)
        $deadlines = $pendingQuery()
            ->whereNotNull('due_date')
            ->where('due_date', '>=', $todayStart)
            ->orderBy('due_date')
            ->limit(4)
            ->get()
            ->map(function ($t) use ($now) {
                $task = $this->formatTask($t);
                $task['when'] = $this->whenLabel($task['due'], $now);

                return $task;
            })
            ->all();

        $taskNote = $this->taskNote(
            $totalTasks,
            $completedTasks,
            $percent
        );

        // ---------------------------------------------------------------
        // CALENDAR (semester_class_schedules)
        // ---------------------------------------------------------------
        $schedule = $this->buildSchedule($userId, $now);

        // Productivity score
        [$scoreValue, $scoreLabel] = $this->productivityScore(
            $totalTasks,
            $completedTasks,
            $overdueCount
        );

        // Subtitle
        $parts = [];

        if ($dueTodayPending > 0) {
            $parts[] = $dueTodayPending .
                ($dueTodayPending === 1 ? ' task' : ' tasks') .
                ' due today';
        }

        if ($schedule['classesLeft'] > 0) {
            $parts[] = $schedule['classesLeft'] .
                ($schedule['classesLeft'] === 1 ? ' class' : ' classes') .
                ' left today';
        }

        $subtitle = $parts
            ? implode(' and ', $parts) . '.'
            : "You're all clear for now. Nice work.";

        $user = Auth::user();

        $d = [
            'userName' => explode(' ', trim($user->name))[0],
            'subtitle' => $subtitle,

            'tasks' => [
                'done' => $completedTasks,
                'total' => $totalTasks,
                'pending' => $pendingTasks,
                'percent' => $percent,
                'note' => $taskNote,
            ],

            'classTime' => $schedule['classTime'],

            'score' => [
                'value' => $scoreValue,
                'label' => $scoreLabel,
            ],

            'todayTasks' => $todayTasks,
            'deadlines' => $deadlines,
            'overdue' => $overdueCount,
            'streak' => $this->studyStreak($userId, $now),
            'schedule' => $schedule,
        ];

        return view('dashboard', [
            'dateLabel' => $now->format('l, F j'),
            'greeting' => $greeting,
            'd' => $d,
            'serverNow' => $now->toIso8601String(),

            'clock' => [
                'time' => $now->format('h:i:s'),
                'ampm' => $now->format('A'),
                'date' => $now->format('l, F j, Y'),
            ],

            'links' => [
                'tasks' => $this->firstRoute(['tasks.index', 'tasks']),
                'calendar' => $this->firstRoute(['calendar', 'calendar.index']),
            ],
        ]);
    }

    // =====================================================================
    // TASK HELPERS
    // =====================================================================

    /**
     * Save a task's completed state from the dashboard checkbox.
     * Only the logged-in user's own tasks can be changed.
     */
    public function toggleTask(Request $request, int $id)
    {
        $request->validate([
            'completed' => ['required', 'boolean'],
        ]);

        $userId = Auth::id();
        $completed = $request->boolean('completed');

        $task = DB::table('tasks')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (! $task) {
            return response()->json([
                'message' => 'Task not found.',
            ], 404);
        }

        $changes = [
            'is_completed' => $completed ? 1 : 0,
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('tasks', 'completed_at')) {
            $changes['completed_at'] = $completed ? now() : null;
        }

        DB::table('tasks')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->update($changes);

        // Fresh statistics
        $now = Carbon::now(self::TZ);
        $todayStart = $now->copy()
            ->startOfDay()
            ->format('Y-m-d H:i:s');

        $base = fn () => DB::table('tasks')
            ->where('user_id', $userId);

        $total = $base()->count();

        $done = $base()
            ->where('is_completed', true)
            ->count();

        $overdue = $base()
            ->where(function ($q) {
                $q->where('is_completed', false)
                    ->orWhereNull('is_completed');
            })
            ->whereNotNull('due_date')
            ->where('due_date', '<', $todayStart)
            ->count();

        $percent = $total > 0
            ? (int) round(($done / $total) * 100)
            : 0;

        [$scoreValue, $scoreLabel] = $this->productivityScore(
            $total,
            $done,
            $overdue
        );

        return response()->json([
            'id' => $id,
            'completed' => $completed,
            'done' => $done,
            'total' => $total,
            'pending' => max(0, $total - $done),
            'percent' => $percent,
            'note' => $this->taskNote($total, $done, $percent),

            'score' => [
                'value' => $scoreValue,
                'label' => $scoreLabel,
            ],
        ]);
    }

    private function taskNote(
        int $total,
        int $done,
        int $percent
    ): string {
        return match (true) {
            $total === 0 => 'No tasks yet. Add one to get started.',
            $done === $total => 'All tasks completed. Great work!',
            $percent >= 50 => "Keep going, you're over halfway there.",
            default => "{$percent}% done. One task at a time.",
        };
    }

    /** 70% completion rate + 30% not-overdue rate. */
    private function productivityScore(
        int $total,
        int $done,
        int $overdue
    ): array {
        if ($total === 0) {
            return [0, 'No data'];
        }

        $value = (int) round(
            ($done / $total) * 70 +
            (1 - min(1, $overdue / $total)) * 30
        );

        $label = match (true) {
            $value >= 80 => 'Excellent',
            $value >= 60 => 'Good',
            $value >= 40 => 'Fair',
            default => 'Needs work',
        };

        return [$value, $label];
    }

    /** Return the URL of the first route name that exists, or null. */
    private function firstRoute(array $names): ?string
    {
        foreach ($names as $name) {
            if (Route::has($name)) {
                return route($name);
            }
        }

        return null;
    }

    /** Parse a stored due date as Philippine local time. */
    private function parseDue($value): ?Carbon
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Carbon::parse($value, self::TZ);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function formatTask(object $task): array
    {
        $due = $this->parseDue($task->due_date ?? null);

        $location = $task->location ?? null;
        $difficulty = $task->difficulty ?? null;
        $priority = $task->priority ?? null;

        return [
            'id' => $task->id ?? null,
            'title' => $task->title ?? 'Untitled task',

            'subject' => $location
                ?: ($difficulty ? ucfirst((string) $difficulty) : 'No details'),

            'priority' => $priority
                ? ucfirst(strtolower((string) $priority))
                : 'Normal',

            'time' => (
                $due && $due->format('H:i:s') !== '00:00:00'
            )
                ? $due->format('g:i A')
                : 'All day',

            'done' => (bool) ($task->is_completed ?? false),
            'due' => $due,
        ];
    }

    private function whenLabel(?Carbon $due, Carbon $now): string
    {
        if (! $due) {
            return 'No due date';
        }

        $days = (int) $now->copy()
            ->startOfDay()
            ->diffInDays($due->copy()->startOfDay(), false);

        $day = match (true) {
            $days === 0 => 'Today',
            $days === 1 => 'Tomorrow',
            default => $due->format('M j'),
        };

        return $due->format('H:i:s') !== '00:00:00'
            ? $day . ', ' . $due->format('g:i A')
            : $day;
    }

    private function durationLabel(int $minutes): string
    {
        if ($minutes < 60) {
            return $minutes . ' min';
        }

        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        return $m > 0 ? "{$h}h {$m}m" : "{$h}h";
    }

    // =====================================================================
    // STUDY STREAK
    // =====================================================================

    /**
     * Study streak.
     * Returns null if completed_at is not available.
     */
    private function studyStreak(
        int $userId,
        Carbon $now
    ): ?array {
        if (! Schema::hasColumn('tasks', 'completed_at')) {
            return null;
        }

        $appTz = config('app.timezone');

        $days = DB::table('tasks')
            ->where('user_id', $userId)
            ->whereNotNull('completed_at')
            ->pluck('completed_at')
            ->map(fn ($v) => Carbon::parse($v, $appTz)
                ->setTimezone(self::TZ)
                ->toDateString())
            ->unique()
            ->sort()
            ->values();

        $last7 = [];
        $set = $days->flip();

        for ($i = 6; $i >= 0; $i--) {
            $day = $now->copy()
                ->startOfDay()
                ->subDays($i);

            $last7[] = [
                'day' => $day->format('D'),
                'on' => $set->has($day->toDateString()),
            ];
        }

        $todayDone = $set->has(
            $now->copy()->startOfDay()->toDateString()
        );

        if ($days->isEmpty()) {
            return [
                'current' => 0,
                'best' => 0,
                'last7' => $last7,
                'todayDone' => false,
            ];
        }

        $best = 1;
        $run = 1;

        for ($i = 1; $i < $days->count(); $i++) {
            $prev = Carbon::parse($days[$i - 1], self::TZ);
            $cur = Carbon::parse($days[$i], self::TZ);

            $run = $prev->copy()->addDay()->isSameDay($cur)
                ? $run + 1
                : 1;

            $best = max($best, $run);
        }

        $cursor = $now->copy()->startOfDay();

        if (! $set->has($cursor->toDateString())) {
            $cursor->subDay();
        }

        $current = 0;

        while ($set->has($cursor->toDateString())) {
            $current++;
            $cursor->subDay();
        }

        return [
            'current' => $current,
            'best' => $best,
            'last7' => $last7,
            'todayDone' => $todayDone,
        ];
    }

    // =====================================================================
    // SEMESTER CLASS SCHEDULE
    // =====================================================================

    /**
     * Read recurring classes from semester_class_schedules.
     *
     * Expected database columns:
     * - user_id
     * - semester_start
     * - semester_end
     * - classes (JSON)
     *
     * Expected JSON class structure:
     * - id
     * - title
     * - subject
     * - room
     * - days (array of full weekday names)
     * - startTime (HH:mm)
     * - endTime (HH:mm)
     *
     * The dashboard uses the same output keys as the existing Blade view.
     */
    private function buildSchedule(
        int $userId,
        Carbon $now
    ): array {
        $result = [
            'hasSchedule' => false,
            'stale' => false,
            'weekLabel' => null,
            'hero' => null,
            'laterToday' => [],
            'nextOtherDay' => null,
            'classesLeft' => 0,
            'classTime' => $this->emptyClassTime($now),
        ];

        // ---------------------------------------------------------------
        // FIND THE APPLICABLE SEMESTER
        // ---------------------------------------------------------------

        $today = $now->copy()->startOfDay();

        // First: semester currently active.
        $record = DB::table('semester_class_schedules')
            ->where('user_id', $userId)
            ->whereDate('semester_start', '<=', $today->toDateString())
            ->whereDate('semester_end', '>=', $today->toDateString())
            ->orderByDesc('semester_start')
            ->first();

        // Second: next upcoming semester.
        if (! $record) {
            $record = DB::table('semester_class_schedules')
                ->where('user_id', $userId)
                ->whereDate('semester_start', '>', $today->toDateString())
                ->orderBy('semester_start')
                ->first();
        }

        // Third: most recently saved semester.
        if (! $record) {
            $record = DB::table('semester_class_schedules')
                ->where('user_id', $userId)
                ->orderByDesc('semester_end')
                ->first();
        }

        if (! $record) {
            return $result;
        }

        // ---------------------------------------------------------------
        // PARSE SEMESTER DATES
        // ---------------------------------------------------------------

        try {
            $semesterStart = Carbon::parse(
                $record->semester_start,
                self::TZ
            )->startOfDay();

            $semesterEnd = Carbon::parse(
                $record->semester_end,
                self::TZ
            )->endOfDay();
        } catch (\Throwable $e) {
            return $result;
        }

        if ($semesterEnd->lessThan($semesterStart)) {
            return $result;
        }

        // ---------------------------------------------------------------
        // DECODE SAVED CLASSES
        // ---------------------------------------------------------------

        $decoded = is_string($record->classes ?? null)
            ? json_decode($record->classes, true)
            : null;

        if (! is_array($decoded) || empty($decoded)) {
            return $result;
        }

        $validClasses = [];

        $validDays = [
            'Monday',
            'Tuesday',
            'Wednesday',
            'Thursday',
            'Friday',
            'Saturday',
            'Sunday',
        ];

        foreach ($decoded as $class) {
            if (! is_array($class)) {
                continue;
            }

            $title = trim((string) ($class['title'] ?? ''));
            $subject = trim((string) ($class['subject'] ?? ''));
            $room = trim((string) ($class['room'] ?? ''));

            $startTime = trim((string) ($class['startTime'] ?? ''));
            $endTime = trim((string) ($class['endTime'] ?? ''));

            $days = $class['days'] ?? [];

            if (! is_array($days) || empty($days)) {
                continue;
            }

            // Validate 24-hour time values.
            if (
                ! preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $startTime) ||
                ! preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $endTime)
            ) {
                continue;
            }

            // Validate weekday values.
            $days = array_values(array_unique(array_filter(
                $days,
                fn ($day) => is_string($day) &&
                    in_array($day, $validDays, true)
            )));

            if (empty($days)) {
                continue;
            }

            // Overnight classes are not supported.
            if ($endTime <= $startTime) {
                continue;
            }

            $validClasses[] = [
                'id' => $class['id'] ?? null,
                'title' => $title ?: ($subject ?: 'Untitled class'),
                'subject' => $subject,
                'room' => $room,
                'days' => $days,
                'startTime' => $startTime,
                'endTime' => $endTime,
            ];
        }

        if (empty($validClasses)) {
            return $result;
        }

        $result['hasSchedule'] = true;

        // ---------------------------------------------------------------
        // GENERATE RECURRING CLASS OCCURRENCES
        // ---------------------------------------------------------------

        $weekStart = $now->copy()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        // Start from the earlier of the current week or semester start.
        $generationStart = $weekStart->greaterThan($semesterStart)
            ? $weekStart->copy()->startOfDay()
            : $semesterStart->copy()->startOfDay();

        $events = [];
        $minutesByDay = array_fill(0, 7, 0);

        // Generate occurrences through the end of the semester.
        for (
            $date = $generationStart->copy();
            $date->lessThanOrEqualTo($semesterEnd);
            $date->addDay()
        ) {
            $dayName = $date->format('l');

            foreach ($validClasses as $class) {
                if (! in_array($dayName, $class['days'], true)) {
                    continue;
                }

                try {
                    $start = Carbon::createFromFormat(
                        'Y-m-d H:i',
                        $date->format('Y-m-d') . ' ' . $class['startTime'],
                        self::TZ
                    );

                    $end = Carbon::createFromFormat(
                        'Y-m-d H:i',
                        $date->format('Y-m-d') . ' ' . $class['endTime'],
                        self::TZ
                    );
                } catch (\Throwable $e) {
                    continue;
                }

                if (! $start || ! $end || $end->lessThanOrEqualTo($start)) {
                    continue;
                }

                $minutes = (int) $start->diffInMinutes($end);

                $event = [
                    'id' => $class['id'],
                    'title' => $class['title'],
                    'subject' => $class['subject'],
                    'room' => $class['room'],
                    'start' => $start,
                    'end' => $end,
                    'minutes' => $minutes,
                ];

                $events[] = $event;

                // Only count classes within the current Monday-Sunday week.
                if (
                    $date->greaterThanOrEqualTo($weekStart) &&
                    $date->lessThanOrEqualTo($weekEnd)
                ) {
                    $dayIndex = (int) $weekStart->diffInDays(
                        $date,
                        false
                    );

                    if ($dayIndex >= 0 && $dayIndex < 7) {
                        $minutesByDay[$dayIndex] += $minutes;
                    }
                }
            }
        }

        if (empty($events)) {
            $result['classTime'] = $this->classTimeFromMinutes(
                $minutesByDay,
                $now
            );

            return $result;
        }

        // Sort all generated classes by date and time.
        usort($events, function ($a, $b) {
            return $a['start']->getTimestamp()
                <=> $b['start']->getTimestamp();
        });

        $result['classTime'] = $this->classTimeFromMinutes(
            $minutesByDay,
            $now
        );

        // ---------------------------------------------------------------
        // FIND TODAY'S CLASSES
        // ---------------------------------------------------------------

        $todayEvents = array_values(array_filter(
            $events,
            fn ($event) => $event['start']->isSameDay($now)
        ));

        $ongoing = null;
        $upcoming = [];

        foreach ($todayEvents as $event) {
            if (
                $event['start']->lessThanOrEqualTo($now) &&
                $event['end']->greaterThan($now)
            ) {
                // Use the first matching ongoing class.
                $ongoing = $ongoing ?? $event;
            } elseif ($event['start']->greaterThan($now)) {
                $upcoming[] = $event;
            }
        }

        // ---------------------------------------------------------------
        // DASHBOARD HERO CLASS
        // ---------------------------------------------------------------

        $heroEvent = $ongoing ?? ($upcoming[0] ?? null);

        if ($heroEvent) {
            $mode = $ongoing ? 'ongoing' : 'upcoming';

            $minsToStart = (int) $now->diffInMinutes(
                $heroEvent['start'],
                false
            );

            $result['hero'] = [
                'mode' => $mode,
                'title' => $heroEvent['title'],

                'subject' => (
                    $heroEvent['subject'] !== '' &&
                    $heroEvent['subject'] !== $heroEvent['title']
                )
                    ? $heroEvent['subject']
                    : '',

                'room' => $heroEvent['room'] ?: 'Room not set',

                'timeRange' =>
                    $heroEvent['start']->format('g:i A') .
                    ' - ' .
                    $heroEvent['end']->format('g:i A'),

                'startIso' => $heroEvent['start']->toIso8601String(),
                'endIso' => $heroEvent['end']->toIso8601String(),

                'status' => $mode === 'ongoing'
                    ? 'In progress'
                    : ($minsToStart <= 60 ? 'Starting soon' : 'Up next'),

                'label' => $mode === 'ongoing'
                    ? 'Ends in'
                    : 'Starts in',
            ];
        }

        // ---------------------------------------------------------------
        // LATER TODAY
        // ---------------------------------------------------------------

        // If a class is ongoing, show all upcoming classes.
        // Otherwise, exclude the class already shown in the hero card.
        $later = $ongoing
            ? $upcoming
            : array_slice($upcoming, 1);

        foreach ($later as $event) {
            $result['laterToday'][] = [
                'title' => $event['title'],

                'time' =>
                    $event['start']->format('g:i A') .
                    ' - ' .
                    $event['end']->format('g:i A'),

                'room' => $event['room'] ?: 'Room not set',

                'length' => $this->durationLabel(
                    $event['minutes']
                ),
            ];
        }

        // Count ongoing and upcoming classes for today.
        $result['classesLeft'] =
            ($ongoing ? 1 : 0) + count($upcoming);

        // ---------------------------------------------------------------
        // NEXT CLASS ON ANOTHER DAY
        // ---------------------------------------------------------------

        $endOfToday = $now->copy()->endOfDay();

        foreach ($events as $event) {
            if ($event['start']->greaterThan($endOfToday)) {
                $result['nextOtherDay'] = [
                    'title' => $event['title'],

                    'when' =>
                        $event['start']->format('D, M j') .
                        ' at ' .
                        $event['start']->format('g:i A'),
                ];

                break;
            }
        }

        return $result;
    }

    // =====================================================================
    // CLASS TIME HELPERS
    // =====================================================================

    private function emptyClassTime(Carbon $now): array
    {
        return $this->classTimeFromMinutes(
            array_fill(0, 7, 0),
            $now
        );
    }

    private function classTimeFromMinutes(
        array $minutesByDay,
        Carbon $now
    ): array {
        $names = [
            'Mon',
            'Tue',
            'Wed',
            'Thu',
            'Fri',
            'Sat',
            'Sun',
        ];

        $total = array_sum($minutesByDay);
        $max = max($minutesByDay) ?: 1;

        $week = [];

        foreach ($names as $i => $name) {
            $week[] = [
                'day' => $name,

                'height' => $total > 0
                    ? max(
                        8,
                        (int) round(
                            ($minutesByDay[$i] / $max) * 100
                        )
                    )
                    : 8,
            ];
        }

        return [
            'total' => $total > 0
                ? $this->durationLabel($total)
                : '0h',

            'week' => $week,
            'highlight' => $now->format('D'),
            'hasData' => $total > 0,
        ];
    }
}
