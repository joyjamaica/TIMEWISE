<?php

namespace App\Http\Controllers\Collaborate;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use App\Models\TeamTask;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    private function rules(Team $team): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(array_keys(TeamTask::STATUSES))],
            'due_date' => ['nullable', 'date'],
            // The assignee must belong to this team.
            'assignee_id' => ['nullable', Rule::exists('team_members', 'user_id')->where('team_id', $team->id)],
        ];
    }

    public function store(Request $request, Team $team)
    {
        Gate::authorize('view', $team);
        $data = $request->validate($this->rules($team));

        $team->tasks()->create($data + ['created_by' => $request->user()->id]);

        return back()->with('status', 'Task added.');
    }

    public function update(Request $request, Team $team, TeamTask $task)
    {
        Gate::authorize('view', $team);
        $task->update($request->validate($this->rules($team)));

        return back()->with('status', 'Task updated.');
    }

    public function updateStatus(Request $request, Team $team, TeamTask $task)
    {
        Gate::authorize('view', $team);
        $data = $request->validate(['status' => ['required', Rule::in(array_keys(TeamTask::STATUSES))]]);
        $task->update($data);

        return back()->with('status', 'Moved to '.TeamTask::STATUSES[$data['status']].'.');
    }

    public function destroy(Request $request, Team $team, TeamTask $task)
    {
        Gate::authorize('view', $team);
        abort_unless(
            $request->user()->id === $team->owner_id || $request->user()->id === $task->created_by,
            403,
            'Only the team owner or the task’s creator can delete it.'
        );
        $task->delete();

        return back()->with('status', 'Task deleted.');
    }
}
