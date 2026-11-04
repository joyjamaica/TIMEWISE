<?php

namespace App\Http\Controllers;

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

    // 2. Store a new task under the logged-in user's account
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'priority' => ['required', 'string', 'max:255'],
            'difficulty' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
            'reminder' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        DB::table('tasks')->insert([
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'priority' => $validated['priority'],
            'difficulty' => $validated['difficulty'],
            'description' => $validated['description'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
            'reminder' => $validated['reminder'] ?? null,
            'location' => $validated['location'] ?? null,
            'is_completed' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->back();
    }

    // 3. Update an existing task (only if it belongs to the user)
    public function update(Request $request, $id)
    {
        // Verify ownership before updating
        $this->findOwnedTask($id);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'priority' => ['required', 'string', 'max:255'],
            'difficulty' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
            'reminder' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        DB::table('tasks')
            ->where('id', $id)
            ->where('user_id', Auth::id())
            ->update([
                'title' => $validated['title'],
                'priority' => $validated['priority'],
                'difficulty' => $validated['difficulty'],
                'description' => $validated['description'] ?? null,
                'due_date' => $validated['due_date'] ?? null,
                'reminder' => $validated['reminder'] ?? null,
                'location' => $validated['location'] ?? null,
                'updated_at' => now(),
            ]);

        return redirect()->back();
    }

    // 4. Toggle task completion (only if it belongs to the user)
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

    // 5. Find a task owned by the logged-in user or return a 404
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
