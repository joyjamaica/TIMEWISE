<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index()
    {
        $userId = auth()->id();
        $tasks = DB::table('tasks')->where('user_id', $userId)->get();
        $completed = $tasks->where('is_completed', true)->count();
        $total = $tasks->count();
        $completionRate = $total ? (int) round(($completed / $total) * 100) : 0;

        $focusSeconds = (int) DB::table('focus_sessions')
            ->where('user_id', $userId)->sum('duration_seconds');
        $activeSession = DB::table('focus_sessions')
            ->where('user_id', $userId)->whereNull('ended_at')->latest('started_at')->first();

        $logs = DB::table('task_completion_logs')->where('user_id', $userId)->get();
        $completionDates = $logs->map(fn ($log) => Carbon::parse($log->completed_at)->toDateString())->unique()->sort()->values();
        $currentStreak = $this->currentStreak($completionDates->all());
        $bestStreak = $this->bestStreak($completionDates->all());

        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();
        $weeklyLogs = DB::table('task_completion_logs')->where('user_id', $userId)
            ->whereBetween('completed_at', [$weekStart, $weekEnd])->get();
        $dailyCompletions = [];
        $dailyFocus = [];
        for ($i = 0; $i < 7; $i++) {
            $day = $weekStart->copy()->addDays($i);
            $dailyCompletions[] = [
                'label' => $day->format('D'),
                'count' => $weeklyLogs->filter(fn ($log) => Carbon::parse($log->completed_at)->isSameDay($day))->count(),
            ];
            $dailyFocus[] = (int) DB::table('focus_sessions')->where('user_id', $userId)
                ->whereBetween('started_at', [$day->copy()->startOfDay(), $day->copy()->endOfDay()])
                ->sum('duration_seconds');
        }

        $scheduleRecord = DB::table('calendar_schedules')->where('user_id', $userId)->first();
        $schedule = $scheduleRecord ? json_decode($scheduleRecord->schedule, true) : [];
        $subjectSeconds = [];
        foreach ((array) $schedule as $event) {
            $subject = trim($event['subject'] ?? $event['title'] ?? 'Uncategorized');
            $start = isset($event['startTime']) ? strtotime($event['startTime']) : false;
            $end = isset($event['endTime']) ? strtotime($event['endTime']) : false;
            if ($start && $end && $end > $start) {
                $subjectSeconds[$subject] = ($subjectSeconds[$subject] ?? 0) + ($end - $start);
            }
        }
        arsort($subjectSeconds);
        $subjectEffort = collect($subjectSeconds)->take(5)->map(fn ($seconds, $subject) => [
            'subject' => $subject,
            'hours' => round($seconds / 3600, 1),
        ])->values();

        $milestones = [
            ['title' => 'First Step', 'description' => 'Complete your first task', 'unlocked' => $completed >= 1, 'icon' => '✦'],
            ['title' => '3-Day Streak', 'description' => 'Stay consistent for 3 days', 'unlocked' => $bestStreak >= 3, 'icon' => '3'],
            ['title' => '10 Tasks', 'description' => 'Complete 10 tasks', 'unlocked' => $completed >= 10, 'icon' => '10'],
            ['title' => 'Focus Starter', 'description' => 'Record 5 focus hours', 'unlocked' => $focusSeconds >= 18000, 'icon' => '◷'],
        ];

        return view('analytics.index', compact(
            'total', 'completed', 'completionRate', 'focusSeconds', 'activeSession',
            'currentStreak', 'bestStreak', 'dailyCompletions', 'dailyFocus',
            'subjectEffort', 'milestones'
        ));
    }

    public function startFocus(Request $request)
    {
        $userId = $request->user()->id;
        $existing = DB::table('focus_sessions')->where('user_id', $userId)->whereNull('ended_at')->exists();
        if ($existing) {
            return response()->json(['message' => 'You already have an active focus session.'], 409);
        }

        $id = DB::table('focus_sessions')->insertGetId([
            'user_id' => $userId,
            'label' => $request->input('label', 'Study session'),
            'started_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Focus session started.', 'id' => $id, 'started_at' => now()->toIso8601String()]);
    }

    public function stopFocus(Request $request)
    {
        $session = DB::table('focus_sessions')->where('user_id', $request->user()->id)
            ->whereNull('ended_at')->latest('started_at')->first();
        if (!$session) {
            return response()->json(['message' => 'There is no active focus session.'], 404);
        }

        $seconds = max(0, Carbon::parse($session->started_at)->diffInSeconds(now()));
        DB::table('focus_sessions')->where('id', $session->id)->update([
            'ended_at' => now(), 'duration_seconds' => $seconds, 'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Focus session saved.', 'duration_seconds' => $seconds]);
    }

    private function currentStreak(array $dates): int
    {
        if (!$dates) return 0;
        $set = array_flip($dates);
        $day = now()->startOfDay();
        if (!isset($set[$day->toDateString()])) $day->subDay();
        $streak = 0;
        while (isset($set[$day->toDateString()])) { $streak++; $day->subDay(); }
        return $streak;
    }

    private function bestStreak(array $dates): int
    {
        $best = $run = 0; $previous = null;
        foreach ($dates as $date) {
            $current = Carbon::parse($date);
            $run = $previous && $previous->copy()->addDay()->isSameDay($current) ? $run + 1 : 1;
            $best = max($best, $run); $previous = $current;
        }
        return $best;
    }
}
