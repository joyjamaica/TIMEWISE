@props(['team'])
<a href="{{ route('collaborate.teams.show', $team) }}" class="clb-team">
    <div class="clb-team__top">
        <h3>{{ $team->name }}</h3>
        <span class="clb-pill">{{ ucfirst($team->visibility) }}</span>
    </div>
    <p class="clb-team__desc">{{ $team->description ?: 'No description yet.' }}</p>

    <div class="clb-progress" role="progressbar" aria-valuenow="{{ $team->progress }}" aria-valuemin="0" aria-valuemax="100">
        <span style="width: {{ $team->progress }}%"></span>
    </div>
    <div class="clb-team__meta">
        <span>{{ $team->progress }}% complete</span>
        <span class="clb-stack">
            @foreach ($team->members as $m)<x-collaborate.avatar :user="$m" size="sm" />@endforeach
            @if ($team->members_count > $team->members->count())
                <span class="clb-avatar clb-avatar--sm">+{{ $team->members_count - $team->members->count() }}</span>
            @endif
        </span>
    </div>
    <small>{{ $team->members_count }} {{ \Illuminate\Support\Str::plural('member', $team->members_count) }}
        · {{ $team->tasks_count }} {{ \Illuminate\Support\Str::plural('task', $team->tasks_count) }}</small>
</a>
