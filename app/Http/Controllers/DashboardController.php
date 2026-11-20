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
        // Due dates are entered and saved as Philippine local time, so the
        // "today" range is built in Manila time with no timezone conversion.
        $fmt = 'Y-m-d H:i:s';
        $todayStart = $now->copy()->startOfDay()->format($fmt);
        $todayEnd = $now->copy()->endOfDay()->format($fmt);

        $taskBase = fn () => DB::table('tasks')->where('user_id', $userId);
        $pendingQuery = fn () => $taskBase()->where(function ($q) {
            $q->where('is_completed', false)->orWhereNull('is_completed');
        });

        $totalTasks = $taskBase()->count();
        $completedTasks = $taskBase()->where('is_completed', true)->count();
        $pendingTasks = max(0, $totalTasks - $completedTasks);

        // Guard against DivisionByZeroError
        $percent = $totalTasks > 0 ? (int) round(($completedTasks / $totalTasks) * 100) : 0;

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

        $dueTodayPending = count(array_filter($todayTasks, fn ($t) => ! $t['done']));

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

        $taskNote = $this->taskNote($totalTasks, $completedTasks, $percent);

        // ---------------------------------------------------------------
        // CALENDAR (calendar_schedules)
        // ---------------------------------------------------------------
        $schedule = $this->buildSchedule($userId, $now);

        // Productivity score: real formula from task data
        [$scoreValue, $scoreLabel] = $this->productivityScore($totalTasks, $completedTasks, $overdueCount);

        // Subtitle
        $parts = [];
        if ($dueTodayPending > 0) {
            $parts[] = $dueTodayPending . ($dueTodayPending === 1 ? ' task' : ' tasks') . ' due today';
        }
        if ($schedule['classesLeft'] > 0) {
            $parts[] = $schedule['classesLeft'] . ($schedule['classesLeft'] === 1 ? ' class' : ' classes') . ' left today';
        }
        $subtitle = $parts ? implode(' and ', $parts) . '.' : "You're all clear for now. Nice work.";

        $d = [
            'userName' => explode(' ', trim(Auth::user()->name))[0],
            'subtitle' => $subtitle,
            'tasks' => [
                'done' => $completedTasks,
                'total' => $totalTasks,
                'pending' => $pendingTasks,
                'percent' => $percent,
                'note' => $taskNote,
            ],
            'classTime' => $schedule['classTime'],
            'score' => ['value' => $scoreValue, 'label' => $scoreLabel],
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
    // Helpers
    // =====================================================================

    /**
     * Save a task's completed state from the dashboard checkbox.
     * Only the logged-in user's own tasks can be changed.
     * Returns fresh statistics so the page can update without reloading.
     */
    public function toggleTask(Request $request, int $id)
    {
        $request->validate(['completed' => ['required', 'boolean']]);

        $userId = Auth::id();
        $completed = $request->boolean('completed');

        $task = DB::table('tasks')->where('id', $id)->where('user_id', $userId)->first();
        if (! $task) {
            return response()->json(['message' => 'Task not found.'], 404);
        }

        $changes = [
            'is_completed' => $completed ? 1 : 0,
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('tasks', 'completed_at')) {
            $changes['completed_at'] = $completed ? now() : null;
        }

        DB::table('tasks')->where('id', $id)->where('user_id', $userId)->update($changes);

        // Fresh statistics (same rules as the dashboard page)
        $now = Carbon::now(self::TZ);
        $todayStart = $now->copy()->startOfDay()->format('Y-m-d H:i:s');
        $base = fn () => DB::table('tasks')->where('user_id', $userId);

        $total = $base()->count();
        $done = $base()->where('is_completed', true)->count();
        $overdue = $base()
            ->where(function ($q) {
                $q->where('is_completed', false)->orWhereNull('is_completed');
            })
            ->whereNotNull('due_date')
            ->where('due_date', '<', $todayStart)
            ->count();
        $percent = $total > 0 ? (int) round(($done / $total) * 100) : 0;
        [$scoreValue, $scoreLabel] = $this->productivityScore($total, $done, $overdue);

        return response()->json([
            'id' => $id,
            'completed' => $completed,
            'done' => $done,
            'total' => $total,
            'pending' => max(0, $total - $done),
            'percent' => $percent,
            'note' => $this->taskNote($total, $done, $percent),
            'score' => ['value' => $scoreValue, 'label' => $scoreLabel],
        ]);
    }

    private function taskNote(int $total, int $done, int $percent): string
    {
        return match (true) {
            $total === 0 => 'No tasks yet. Add one to get started.',
            $done === $total => 'All tasks completed. Great work!',
            $percent >= 50 => "Keep going, you're over halfway there.",
            default => "{$percent}% done. One task at a time.",
        };
    }

    /** 70% completion rate + 30% "not overdue" rate. Returns [value, label]. */
    private function productivityScore(int $total, int $done, int $overdue): array
    {
        if ($total === 0) {
            return [0, 'No data'];
        }

        $value = (int) round(($done / $total) * 70 + (1 - min(1, $overdue / $total)) * 30);
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

    /** Parse a stored due date as Philippine local time (no conversion). */
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
            'subject' => $location ?: ($difficulty ? ucfirst((string) $difficulty) : 'No details'),
            'priority' => $priority ? ucfirst(strtolower((string) $priority)) : 'Normal',
            'time' => ($due && $due->format('H:i:s') !== '00:00:00') ? $due->format('g:i A') : 'All day',
            'done' => (bool) ($task->is_completed ?? false),
            'due' => $due,
        ];
    }

    private function whenLabel(?Carbon $due, Carbon $now): string
    {
        if (! $due) {
            return 'No due date';
        }
        $days = (int) $now->copy()->startOfDay()->diffInDays($due->copy()->startOfDay(), false);
        $day = match (true) {
            $days === 0 => 'Today',
            $days === 1 => 'Tomorrow',
            default => $due->format('M j'),
        };
        return $due->format('H:i:s') !== '00:00:00' ? $day . ', ' . $due->format('g:i A') : $day;
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

    /**
     * Study streak. The tasks table has no completion timestamp in the files
     * provided, so this only works if a `completed_at` column exists.
     * Returns null when streak tracking is not available.
     */
    private function studyStreak(int $userId, Carbon $now): ?array
    {
        if (! Schema::hasColumn('tasks', 'completed_at')) {
            return null;
        }

        $appTz = config('app.timezone');
        $days = DB::table('tasks')
            ->where('user_id', $userId)
            ->whereNotNull('completed_at')
            ->pluck('completed_at')
            ->map(fn ($v) => Carbon::parse($v, $appTz)->setTimezone(self::TZ)->toDateString())
            ->unique()
            ->sort()
            ->values();

        $last7 = [];
        $set = $days->flip();
        for ($i = 6; $i >= 0; $i--) {
            $day = $now->copy()->startOfDay()->subDays($i);
            $last7[] = ['day' => $day->format('D'), 'on' => $set->has($day->toDateString())];
        }

        $todayDone = $set->has($now->copy()->startOfDay()->toDateString());

        if ($days->isEmpty()) {
            return ['current' => 0, 'best' => 0, 'last7' => $last7, 'todayDone' => false];
        }

        $best = 1;
        $run = 1;
        for ($i = 1; $i < $days->count(); $i++) {
            $prev = Carbon::parse($days[$i - 1], self::TZ);
            $cur = Carbon::parse($days[$i], self::TZ);
            $run = $prev->copy()->addDay()->isSameDay($cur) ? $run + 1 : 1;
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

        return ['current' => $current, 'best' => $best, 'last7' => $last7, 'todayDone' => $todayDone];
    }

    /**
     * Read calendar_schedules for the authenticated user and work out
     * ongoing / up next / later today. Event fields used (unchanged):
     * id, title, subject, room, startTime, endTime.
     */
    private function buildSchedule(int $userId, Carbon $now): array
    {
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

        $record = DB::table('calendar_schedules')->where('user_id', $userId)->first();
        if (! $record) {
            return $result;
        }

        $decoded = is_string($record->schedule ?? null) ? json_decode($record->schedule, true) : null;
        if (! is_array($decoded) || $decoded === []) {
            return $result;
        }

        // Normalise events; skip anything malformed.
        $events = [];
        foreach ($decoded as $e) {
            if (! is_array($e) || empty($e['startTime']) || empty($e['endTime'])) {
                continue;
            }
            try {
                // No timezone suffix in the saved strings, so they are read as Manila time.
                $start = Carbon::parse($e['startTime'], self::TZ)->setTimezone(self::TZ);
                $end = Carbon::parse($e['endTime'], self::TZ)->setTimezone(self::TZ);
            } catch (\Throwable $ex) {
                continue;
            }
            if ($end->lessThanOrEqualTo($start)) {
                continue;
            }
            $events[] = [
                'title' => trim((string) ($e['title'] ?? '')) ?: (trim((string) ($e['subject'] ?? '')) ?: 'Untitled class'),
                'subject' => trim((string) ($e['subject'] ?? '')),
                'room' => trim((string) ($e['room'] ?? '')),
                'start' => $start,
                'end' => $end,
                'minutes' => (int) $start->diffInMinutes($end),
            ];
        }

        if ($events === []) {
            return $result;
        }

        usort($events, fn ($a, $b) => $a['start']->getTimestamp() <=> $b['start']->getTimestamp());

        $result['hasSchedule'] = true;

        // Is anything scheduled in the current week?
        $weekStart = $now->copy()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->addDays(7);
        $inThisWeek = array_filter($events, fn ($e) => $e['start']->greaterThanOrEqualTo($weekStart) && $e['start']->lessThan($weekEnd));
        if ($inThisWeek === []) {
            $result['stale'] = true;
            $first = $record->week_start ? Carbon::parse($record->week_start, self::TZ) : $events[0]['start'];
            $result['weekLabel'] = $first->format('M j');
        }

        // Class time this week (Mon..Sun)
        $minutesByDay = array_fill(0, 7, 0);
        foreach ($inThisWeek as $e) {
            $idx = (int) $weekStart->diffInDays($e['start']->copy()->startOfDay(), false);
            if ($idx >= 0 && $idx < 7) {
                $minutesByDay[$idx] += $e['minutes'];
            }
        }
        $result['classTime'] = $this->classTimeFromMinutes($minutesByDay, $now);

        // Today's classes
        $today = array_values(array_filter($events, fn ($e) => $e['start']->isSameDay($now)));

        $ongoing = null;
        $upcoming = [];
        foreach ($today as $e) {
            if ($e['start']->lessThanOrEqualTo($now) && $e['end']->greaterThan($now)) {
                $ongoing = $ongoing ?? $e;
            } elseif ($e['start']->greaterThan($now)) {
                $upcoming[] = $e;
            }
            // Classes that already ended are intentionally ignored.
        }

        $heroEvent = $ongoing ?? ($upcoming[0] ?? null);
        if ($heroEvent) {
            $mode = $ongoing ? 'ongoing' : 'upcoming';
            $minsToStart = (int) $now->diffInMinutes($heroEvent['start'], false);
            $result['hero'] = [
                'mode' => $mode,
                'title' => $heroEvent['title'],
                'subject' => $heroEvent['subject'] !== '' && $heroEvent['subject'] !== $heroEvent['title'] ? $heroEvent['subject'] : '',
                'room' => $heroEvent['room'] ?: 'Room not set',
                'timeRange' => $heroEvent['start']->format('g:i A') . ' - ' . $heroEvent['end']->format('g:i A'),
                'startIso' => $heroEvent['start']->toIso8601String(),
                'endIso' => $heroEvent['end']->toIso8601String(),
                'status' => $mode === 'ongoing' ? 'In progress' : ($minsToStart <= 60 ? 'Starting soon' : 'Up next'),
                'label' => $mode === 'ongoing' ? 'Ends in' : 'Starts in',
            ];
        }

        // Later today: remaining upcoming classes, excluding the one in the hero card
        $later = $ongoing ? $upcoming : array_slice($upcoming, 1);
        foreach ($later as $e) {
            $result['laterToday'][] = [
                'title' => $e['title'],
                'time' => $e['start']->format('g:i A') . ' - ' . $e['end']->format('g:i A'),
                'room' => $e['room'] ?: 'Room not set',
                'length' => $this->durationLabel($e['minutes']),
            ];
        }

        $result['classesLeft'] = ($ongoing ? 1 : 0) + count($upcoming);

        // Next class on another day (for the "no more classes today" state)
        $endOfToday = $now->copy()->endOfDay();
        foreach ($events as $e) {
            if ($e['start']->greaterThan($endOfToday)) {
                $result['nextOtherDay'] = [
                    'title' => $e['title'],
                    'when' => $e['start']->format('D, M j') . ' at ' . $e['start']->format('g:i A'),
                ];
                break;
            }
        }

        return $result;
    }

    private function emptyClassTime(Carbon $now): array
    {
        return $this->classTimeFromMinutes(array_fill(0, 7, 0), $now);
    }

    private function classTimeFromMinutes(array $minutesByDay, Carbon $now): array
    {
        $names = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $total = array_sum($minutesByDay);
        $max = max($minutesByDay) ?: 1;

        $week = [];
        foreach ($names as $i => $name) {
            $week[] = [
                'day' => $name,
                'height' => $total > 0 ? max(8, (int) round(($minutesByDay[$i] / $max) * 100)) : 8,
            ];
        }

        return [
            'total' => $total > 0 ? $this->durationLabel($total) : '0h',
            'week' => $week,
            'highlight' => $now->format('D'),
            'hasData' => $total > 0,
        ];
    }
}
