<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MatrixController extends Controller
{
    // 1. Display the Matrix Page and fetch all tasks
    public function index()
    {
        $tasks = DB::table('tasks')->get();

        return view('matrix', compact('tasks'));
    }

    // 2. Store a new task
    public function store(Request $request)
    {
        DB::table('tasks')->insert([
            'title' => $request->title,
            'priority' => $request->priority,
            'difficulty' => $request->difficulty,
            'description' => $request->description,
            'due_date' => $request->due_date,
            'reminder' => $request->reminder,
            'location' => $request->location,
            'is_completed' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->back();
    }

    // 3. Update an existing task
    public function update(Request $request, $id)
    {
        DB::table('tasks')->where('id', $id)->update([
            'title' => $request->title,
            'priority' => $request->priority,
            'difficulty' => $request->difficulty,
            'description' => $request->description,
            'due_date' => $request->due_date,
            'reminder' => $request->reminder,
            'location' => $request->location,
            'updated_at' => now(),
        ]);

        return redirect()->back();
    }

    // 4. Toggle task completion
    public function toggleComplete($id)
    {
        $task = DB::table('tasks')->where('id', $id)->first();

        if (!$task) {
            abort(404, 'Task not found.');
        }

        DB::table('tasks')->where('id', $id)->update([
            'is_completed' => !$task->is_completed,
            'updated_at' => now(),
        ]);

        return redirect()->back();
    }
}
