<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $now = now();
        $hour = (int) $now->format('G');

        // Greeting based on the current time
        if ($hour < 12) {
            $greeting = 'Good morning';
        } elseif ($hour < 18) {
            $greeting = 'Good afternoon';
        } else {
            $greeting = 'Good evening';
        }

        // Get the logged-in user's ID
        $userId = Auth::id();

        // Get tasks belonging to the logged-in user
        $allTasks = DB::table('tasks')
            ->where('user_id', $userId);

        // Calculate task statistics
        $totalTasks = (clone $allTasks)->count();

        $completedTasks = (clone $allTasks)
            ->where('is_completed', true)
            ->count();

        // Get today's tasks
        $todayTasks = DB::table('tasks')
            ->where('user_id', $userId)
            ->whereDate('due_date', $now->toDateString())
            ->orderBy('due_date')
            ->get()
            ->map(function ($task) {
                return [
                    'title' => $task->title,
                    'subject' => $task->location ?: ucfirst($task->difficulty),
                    'priority' => $task->priority,
                    'time' => $task->due_date
                        ? \Carbon\Carbon::parse($task->due_date)->format('g:i A')
                        : 'No time set',
                    'done' => (bool) $task->is_completed,
                ];
            })
            ->toArray();
            

        // Dashboard data
        $d = [
        'userName' => explode(' ', trim(Auth::user()->name))[0],
        
        // Dynamic task statistics
            'tasks' => [
                'done' => $completedTasks,
                'total' => $totalTasks,
                'change' => '+12%',
            ],

            // Existing sample data: Streak
            'streak' => [
                'current' => 7,
                'best' => 12,
            ],

            // Existing data: Focus time
           'focus' => [
    'total' => '3h 42m',
    'week' => [
        ['day' => 'Mon', 'height' => 40],
        ['day' => 'Tue', 'height' => 60],
        ['day' => 'Wed', 'height' => 75],
        ['day' => 'Thu', 'height' => 100],
        ['day' => 'Fri', 'height' => 65],
        ['day' => 'Sat', 'height' => 35],
        ['day' => 'Sun', 'height' => 20],
    ],
    'highlight' => 'Thu',
],

            // Existing sample data: Productivity score
            'score' => [
                'value' => 86,
                'label' => 'Excellent',
            ],

            // Dynamic today's tasks
            'todayTasks' => $todayTasks,

            // Existing sample data: Next class
            'nextClass' => [
                'title' => 'Advanced Calculus',
                'room' => 'Room 204',
                'detail' => 'Mathematics',
                'sampleSecondsUntilStart' => 1800,
                'windowMinutes' => 60,
            ],

            // Existing sample data: Later today
            'laterToday' => [
    [
        'title' => 'AP Biology',
        'time' => '2:00 PM',
        'room' => 'Lab 3',
        'length' => '90 min',
    ],
],
        ];

        return view('dashboard', [
            'dateLabel' => $now->format('l, F j'),
            'greeting' => $greeting,
            'd' => $d,
        ]);
    }
}
