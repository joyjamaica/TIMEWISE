 @props(['task', 'team'])
@php [$label, $tone] = $task->badge(); @endphp

<article class="clb-task">
    <span class="clb-badge clb-badge--{{ $tone }}">{{ $label }}</span>
    <h4>{{ $task->title }}</h4>

    @if ($task->description)
        <p>{{ \Illuminate\Support\Str::limit($task->description, 70) }}</p>
    @endif

    <div class="clb-task__foot">
        @if ($task->assignee)
            <x-collaborate.avatar :user="$task->assignee" />
        @else
            <span class="clb-avatar clb-avatar--empty" title="Unassigned">–</span>
        @endif

        <span class="clb-task__date">{{ $task->due_date?->format('M j') ?? 'No date' }}</span>
    </div>

    <div class="clb-task__actions">
        <form method="POST" action="{{ route('collaborate.tasks.status', [$team, $task]) }}">
            @csrf
            @method('PATCH')

            <label class="sr-only" for="status-{{ $task->id }}">Status</label>

            <select id="status-{{ $task->id }}" name="status" onchange="this.form.submit()">
                @foreach (\App\Models\TeamTask::STATUSES as $value => $text)
                    <option value="{{ $value }}" @selected($task->status === $value)>{{ $text }}</option>
                @endforeach
            </select>
        </form>

        @php
            $taskData = [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'status' => $task->status,
                'assignee_id' => $task->assignee_id,
                'due_date' => $task->due_date?->format('Y-m-d'),
                'action' => route('collaborate.tasks.update', [$team, $task]),
            ];
        @endphp

        <button
            type="button"
            class="clb-link"
            data-task="{{ json_encode($taskData, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) }}"
            onclick="clbEditTask(this)"
        >Edit</button>

        @if (auth()->id() === $team->owner_id || auth()->id() === $task->created_by)
            <form method="POST" action="{{ route('collaborate.tasks.destroy', [$team, $task]) }}"
                  onsubmit="return confirm('Delete this task?')">
                @csrf
                @method('DELETE')

                <button class="clb-link clb-link--danger">Delete</button>
            </form>
        @endif
    </div>
</article>