<?php

namespace App\Http\Controllers\Collaborate;

use App\Http\Controllers\Controller;
use App\Models\TeamTask;
use App\Models\Team;
use Illuminate\Http\Request;

class CollaborateController extends Controller
{
    public function index(Request $request)
    {
        $teams = Team::forUser($request->user())
            ->withCount([
                'members',
                'tasks',
                'tasks as completed_tasks_count' => fn ($q) => $q->where('status', TeamTask::COMPLETED),
            ])
            ->with(['members' => fn ($q) => $q->limit(4)])
            ->latest()
            ->get();

        return view('collaborate.index', compact('teams'));
    }
}
