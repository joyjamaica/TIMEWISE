<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MatrixController extends Controller
{
    // Every query is limited to the logged-in user's own tasks.

    // 1. Display the Matrix Page and fetch the user's tasks
    public function index()
    {
        $tasks = DB::table('tasks')
            ->where('user_id', Auth::id())
            ->get();

        return view('matrix', compact('tasks'));
    }

    // 2. Display the Monthly Calendar View with task/event badges grouped by date
    public function calendar(Request $request)
    {
        $year = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);

        if ($month < 1) {
            $month = 12;
            $year--;
        } elseif ($month > 12) {
            $month = 1;
            $year++;
        }

        $currentDate = Carbon::createFromDate($year, $month, 1);
        $gridStart = $currentDate->copy()->startOfMonth()->startOfWeek(Carbon::SUNDAY);
        $gridEnd = $currentDate->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);

        $tasks = DB::table('tasks')
            ->where('user_id', Auth::id())
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [
                $gridStart->copy()->startOfDay()->toDateTimeString(),
                $gridEnd->copy()->endOfDay()->toDateTimeString(),
            ])
            ->orderBy('due_date', 'asc')
            ->get();

        $tasksByDate = $tasks->groupBy(function ($task) {
            return Carbon::parse($task->due_date)->format('Y-m-d');
        });

        $days = [];
        $cursor = $gridStart->copy();
        while ($cursor->lte($gridEnd)) {
            $dateKey = $cursor->format('Y-m-d');
            $days[] = [
                'date' => $cursor->copy(),
                'dateKey' => $dateKey,
                'dayNumber' => $cursor->day,
                'isCurrentMonth' => $cursor->month === $currentDate->month,
                'isToday' => $cursor->isToday(),
                'tasks' => $tasksByDate->get($dateKey, collect()),
            ];
            $cursor->addDay();
        }

        $prevMonth = $currentDate->copy()->subMonth();
        $nextMonth = $currentDate->copy()->addMonth();

        return view('calendar-month', compact('currentDate', 'days', 'prevMonth', 'nextMonth'));
    }

    // 3. Store a new task under the logged-in user's account
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'priority' => ['required', 'string', 'max:255'],
            'difficulty' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date' => ['required', 'date'],
            'reminder' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        $dueDate = Carbon::parse($validated['due_date'] ?? now()->setTime(17, 0))->toDateTimeString();

        DB::table('tasks')->insert([
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'priority' => $validated['priority'],
            'difficulty' => $validated['difficulty'],
            'description' => $validated['description'] ?? null,
            'due_date' => $dueDate,
            'reminder' => $validated['reminder'] ?? null,
            'location' => $validated['location'] ?? null,
            'is_completed' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->back();
    }

    // 4. Update an existing task (only if it belongs to the user)
    public function update(Request $request, $id)
    {
        // Verify ownership before updating
        $this->findOwnedTask($id);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'priority' => ['required', 'string', 'max:255'],
            'difficulty' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date' => ['required', 'date'],
            'reminder' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        $dueDate = Carbon::parse($validated['due_date'] ?? now()->setTime(17, 0))->toDateTimeString();

        DB::table('tasks')
            ->where('id', $id)
            ->where('user_id', Auth::id())
            ->update([
                'title' => $validated['title'],
                'priority' => $validated['priority'],
                'difficulty' => $validated['difficulty'],
                'description' => $validated['description'] ?? null,
                'due_date' => $dueDate,
                'reminder' => $validated['reminder'] ?? null,
                'location' => $validated['location'] ?? null,
                'updated_at' => now(),
            ]);

        return redirect()->back();
    }

    // 5. Toggle task completion (only if it belongs to the user)
    public function toggleComplete($id)
    {
        $task = $this->findOwnedTask($id);

        DB::table('tasks')
            ->where('id', $id)
            ->where('user_id', Auth::id())
            ->update([
                'is_completed' => !$task->is_completed,
                'updated_at' => now(),
            ]);

        return redirect()->back();
    }

    // 6. Find a task owned by the logged-in user or return a 404
    private function findOwnedTask($id)
    {
        $task = DB::table('tasks')
            ->where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$task) {
            abort(404, 'Task not found.');
        }

        return $task;
    }
}