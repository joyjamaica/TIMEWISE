<?php

namespace App\Http\Controllers\Collaborate;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use App\Models\TeamTask;
use App\Models\Team;
use App\Models\TeamMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TeamController extends Controller
{
    public function create()
    {
        return view('collaborate.teams.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'visibility' => ['required', Rule::in(['public', 'private'])],
        ]);

        $team = DB::transaction(function () use ($request, $data) {
            $team = Team::create($data + [
                'owner_id' => $request->user()->id,
                'invite_code' => Team::generateInviteCode(),
            ]);

            TeamMember::create([
                'team_id' => $team->id,
                'user_id' => $request->user()->id,
                'role' => 'owner',
            ]);

            return $team;
        });

        return redirect()->route('collaborate.teams.show', $team)
            ->with('status', 'Team created. Share the invite code to bring people in.');
    }

    public function joinForm()
    {
        return view('collaborate.teams.join');
    }

    public function join(Request $request)
    {
        $request->merge(['invite_code' => strtoupper(trim((string) $request->input('invite_code')))]);
        $request->validate(['invite_code' => ['required', 'string', 'max:12']]);

        $team = Team::where('invite_code', $request->invite_code)->first();

        if (! $team) {
            return back()->withInput()->withErrors([
                'invite_code' => 'That invitation code doesn’t match any team. Check it and try again.',
            ]);
        }

        if ($team->hasMember($request->user())) {
            return redirect()->route('collaborate.teams.show', $team)
                ->with('status', 'You’re already a member of this team.');
        }

        TeamMember::firstOrCreate(
            ['team_id' => $team->id, 'user_id' => $request->user()->id],
            ['role' => 'member']
        );

        return redirect()->route('collaborate.teams.show', $team)
            ->with('status', 'You joined '.$team->name.'.');
    }

    public function show(Request $request, Team $team)
    {
        Gate::authorize('view', $team);

        $team->loadCount([
            'tasks',
            'tasks as completed_tasks_count' => fn ($q) => $q->where('status', TeamTask::COMPLETED),
        ]);

        $members = $team->members()->orderBy('name')->get();
        $tasks = $team->tasks()->with('assignee')->orderBy('due_date')->get()->groupBy('status');
        $resources = $team->resources()->latest()->get();

        return view('collaborate.teams.show', compact('team', 'members', 'tasks', 'resources'));
    }
}
