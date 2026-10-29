@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/collaborate.css') }}">
<div class="clb">
    <a class="clb-back" href="{{ route('collaborate.index') }}">Back to Collaborate</a>

    <header class="clb-head">
        <div>
            <p class="clb-eyebrow">{{ ucfirst($team->visibility) }} team</p>
            <h1 class="clb-title">{{ $team->name }}<span class="clb-dot">.</span></h1>
            <p class="clb-sub">{{ $team->description ?: 'One shared space for milestones, sources, and group momentum.' }}</p>
        </div>
        <button type="button" class="clb-btn clb-btn--dark clb-btn--lg" onclick="clbNewTask()">+ Add Task</button>
    </header>

    @include('collaborate._flash')

    <section class="clb-bar">
        <span>Project progress</span>
        <strong>{{ $team->progress }}%</strong>
        <div class="clb-progress clb-progress--lg" role="progressbar" aria-valuenow="{{ $team->progress }}" aria-valuemin="0" aria-valuemax="100">
            <span style="width: {{ $team->progress }}%"></span>
        </div>
        <span class="clb-stack">
            @foreach ($members->take(4) as $m)<x-collaborate.avatar :user="$m" size="lg" />@endforeach
            @if ($members->count() > 4)<span class="clb-avatar clb-avatar--lg">+{{ $members->count() - 4 }}</span>@endif
        </span>
    </section>

    <div class="clb-layout">
        <div class="clb-board">
            @foreach (\App\Models\TeamTask::STATUSES as $status => $label)
                @php $col = $tasks->get($status, collect()); @endphp
                <section class="clb-col">
                    <header><h3>{{ $label }}</h3><span class="clb-count">{{ $col->count() }}</span></header>
                    @forelse ($col as $task)
                        <x-collaborate.task-card :task="$task" :team="$team" />
                    @empty
                        <p class="clb-col__empty">No tasks here.</p>
                    @endforelse
                </section>
            @endforeach
        </div>

        <aside class="clb-side">
            <div class="clb-panel">
                <h2 class="clb-side__title">Project resources</h2>

                @forelse ($resources as $r)
                    <div class="clb-res">
                        <a href="{{ route('collaborate.resources.download', [$team, $r]) }}" class="clb-res__main">
                            <span class="clb-res__ext">{{ strtoupper($r->extension ?: 'FILE') }}</span>
                            <span><strong>{{ $r->title }}</strong>
                                <small>{{ $r->uploader?->name ?? 'Former member' }} · {{ $r->created_at->diffForHumans() }}</small></span>
                        </a>
                        @if (auth()->id() === $team->owner_id || auth()->id() === $r->uploaded_by)
                            <form method="POST" action="{{ route('collaborate.resources.destroy', [$team, $r]) }}"
                                  onsubmit="return confirm('Remove this file?')">
                                @csrf @method('DELETE')
                                <button class="clb-x" aria-label="Remove {{ $r->title }}">×</button>
                            </form>
                        @endif
                    </div>
                @empty
                    <p class="clb-col__empty">No files yet. Upload the brief, notes, or sources.</p>
                @endforelse

                <form method="POST" action="{{ route('collaborate.resources.store', $team) }}" enctype="multipart/form-data" class="clb-upload">
                    @csrf
                    <label class="clb-drop">
                        <input type="file" name="file" required onchange="this.form.submit()">
                        + Upload resource
                    </label>
                    @error('file')<span class="clb-error">{{ $message }}</span>@enderror
                </form>

                <h3 class="clb-side__sub">Members</h3>
                <ul class="clb-members">
                    @foreach ($members as $m)
                        <li><x-collaborate.avatar :user="$m" size="sm" /> {{ $m->name }}
                            @if ($m->pivot->role === 'owner')<span class="clb-pill">Owner</span>@endif</li>
                    @endforeach
                </ul>

                @can('viewInviteCode', $team)
                    <h3 class="clb-side__sub">Invitation code</h3>
                    <code class="clb-code">{{ $team->invite_code }}</code>
                @endcan
            </div>
        </aside>
    </div>
</div>

<dialog id="task-dialog" class="clb-dialog">
    <div class="clb-dialog__head"><h2 id="task-dialog-title">Add task</h2>
        <button type="button" class="clb-x" aria-label="Close" onclick="this.closest('dialog').close()">×</button></div>
    <form method="POST" id="task-form" class="clb-form" action="{{ route('collaborate.tasks.store', $team) }}">
        @csrf
        <input type="hidden" name="_method" value="POST" id="task-method">
        <label>Title <input type="text" name="title" id="t-title" maxlength="150" required value="{{ old('title') }}"></label>
        <label>Description <textarea name="description" id="t-description" rows="3" maxlength="2000">{{ old('description') }}</textarea></label>
        <div class="clb-form__row">
            <label>Assignee
                <select name="assignee_id" id="t-assignee">
                    <option value="">Unassigned</option>
                    @foreach ($members as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach
                </select>
            </label>
            <label>Due date <input type="date" name="due_date" id="t-due" value="{{ old('due_date') }}"></label>
            <label>Status
                <select name="status" id="t-status">
                    @foreach (\App\Models\TeamTask::STATUSES as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach
                </select>
            </label>
        </div>
        @if ($errors->hasAny(['title','description','status','due_date','assignee_id']))
            <ul class="clb-error">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        @endif
        <div class="clb-form__actions"><button class="clb-btn clb-btn--dark">Save task</button></div>
    </form>
</dialog>

<script>
    const taskDialog = document.getElementById('task-dialog');
    const taskForm = document.getElementById('task-form');
    const createUrl = taskForm.action;

    function clbNewTask() {
        taskForm.reset();
        taskForm.action = createUrl;
        document.getElementById('task-method').value = 'POST';
        document.getElementById('task-dialog-title').textContent = 'Add task';
        taskDialog.showModal();
    }

    function clbEditTask(btn) {
        const t = JSON.parse(btn.dataset.task);
        taskForm.action = t.action;
        document.getElementById('task-method').value = 'PUT';
        document.getElementById('task-dialog-title').textContent = 'Edit task';
        document.getElementById('t-title').value = t.title;
        document.getElementById('t-description').value = t.description ?? '';
        document.getElementById('t-assignee').value = t.assignee_id ?? '';
        document.getElementById('t-due').value = t.due_date ?? '';
        document.getElementById('t-status').value = t.status;
        taskDialog.showModal();
    }

    @if ($errors->hasAny(['title','description','status','due_date','assignee_id']))
        taskDialog.showModal();
    @endif
</script>
@endsection
