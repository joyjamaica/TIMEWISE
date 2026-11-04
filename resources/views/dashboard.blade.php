<x-app-layout>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard | TIMEWISE</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
</head>
<body class="tw-dash-body">

{{-- A shared sidebar can be placed in this wrapper later. Not part of this task. --}}
<div class="tw-dash-shell">
<main class="tw-dash-main">

    {{-- HEADER --}}
    <header class="tw-dash-header">
        <div>
            <p class="tw-dash-eyebrow">{{ $dateLabel }}</p>
            <h1 class="tw-dash-greeting">{{ $greeting }}, {{ $d['userName'] }}<span class="tw-dash-dot">.</span></h1>
            <p class="tw-dash-subtitle">You're making steady progress. Let's keep the momentum going.</p>
        </div>

        <aside class="tw-dash-streak" aria-label="Study streak (sample data)">
            <span class="tw-dash-streak-icon" aria-hidden="true">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2c1 4-3 5-3 9a3 3 0 0 0 6 0c0-1-.4-1.800-1-2.500 3 1.200 5 4 5 7a7 7 0 0 1-14 0c0-6 5-8 7-13.500z"/></svg>
            </span>
            <div>
                <p class="tw-dash-streak-title">{{ $d['streak']['current'] }}-day streak</p>
                <p class="tw-dash-streak-sub">Your best is {{ $d['streak']['best'] }} days</p>
            </div>
            <div class="tw-dash-mini-bars" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
        </aside>
    </header>

    {{-- SUMMARY CARDS --}}
    <section class="tw-dash-summary" aria-label="Summary (sample data)">

        <article class="tw-dash-card tw-dash-stat">
            <div class="tw-dash-stat-top">
                <span class="tw-dash-icon" aria-hidden="true">&#10003;</span>
                <span class="tw-dash-pill tw-dash-pill-green">{{ $d['tasks']['change'] }}</span>
            </div>
            <p class="tw-dash-label">Tasks done</p>
            <p class="tw-dash-big">{{ $d['tasks']['done'] }}<span>/{{ $d['tasks']['total'] }}</span></p>
            <div class="tw-dash-bar" role="progressbar" aria-valuemin="0" aria-valuemax="{{ $d['tasks']['total'] }}" aria-valuenow="{{ $d['tasks']['done'] }}" aria-label="Tasks completed">
                <div class="tw-dash-bar-fill" style="width: {{ round($d['tasks']['done'] / $d['tasks']['total'] * 100) }}%"></div>
            </div>
            <p class="tw-dash-note">Keep going, you're over halfway there</p>
        </article>

        <article class="tw-dash-card tw-dash-stat">
            <div class="tw-dash-stat-top">
                <span class="tw-dash-icon" aria-hidden="true">&#9719;</span>
                <span class="tw-dash-note">This week</span>
            </div>
            <p class="tw-dash-label">Focus time</p>
            <div class="tw-dash-focus-row">
                <p class="tw-dash-big">{{ $d['focus']['total'] }}</p>
                <ul class="tw-dash-chart" aria-label="Weekly focus activity (sample data)">
                    @foreach ($d['focus']['week'] as $bar)
                        <li class="{{ $bar['day'] === $d['focus']['highlight'] ? 'is-peak' : '' }}" style="height: {{ $bar['height'] }}%">
                            <span class="tw-dash-sr">{{ $bar['day'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </article>

        <article class="tw-dash-card tw-dash-stat">
            <div class="tw-dash-stat-top">
                <span class="tw-dash-icon" aria-hidden="true">&#9636;</span>
                <span class="tw-dash-pill">{{ $d['score']['label'] }}</span>
            </div>
            <p class="tw-dash-label">Productivity score</p>
            <div class="tw-dash-score-row">
                <div>
                    <p class="tw-dash-big">{{ $d['score']['value'] }}<span>/100</span></p>
                    <p class="tw-dash-note">Sample score, no formula yet</p>
                </div>
                <div class="tw-dash-ring" style="--pct: {{ $d['score']['value'] }}" role="img" aria-label="Score {{ $d['score']['value'] }} out of 100">
                    <span>{{ $d['score']['value'] }}</span>
                </div>
            </div>
        </article>
    </section>

    {{-- MAIN ROW --}}
    <section class="tw-dash-grid">

        {{-- TODAY'S FOCUS --}}
        <article class="tw-dash-card tw-dash-panel">
            <header class="tw-dash-panel-head">
                <div>
                    <p class="tw-dash-eyebrow">Plan your day</p>
                    <h2 class="tw-dash-h2">Today's Focus</h2>
                </div>
                {{-- Later: href="{{ route('tasks.index') }}" once that route exists --}}
                <a href="#" class="tw-dash-link" aria-disabled="true">View all &rarr;</a>
            </header>

            <ul class="tw-dash-tasks">
                @foreach ($d['todayTasks'] as $i => $task)
                    <li class="tw-dash-task {{ $task['done'] ? 'is-done' : '' }}">
                        <input type="checkbox" id="task-{{ $i }}" class="tw-dash-check" @checked($task['done'])>
                        <label for="task-{{ $i }}" class="tw-dash-task-text">
                            <span class="tw-dash-task-title">{{ $task['title'] }}</span>
                            <span class="tw-dash-task-sub">{{ $task['subject'] }}</span>
                        </label>
                        <span class="tw-dash-badge tw-dash-badge-{{ strtolower($task['priority']) }}">{{ $task['priority'] }}</span>
                        <span class="tw-dash-time">{{ $task['time'] }}</span>
                    </li>
                @endforeach
            </ul>

            {{-- Later: open an "add task" form or link to the Tasks module --}}
            <button type="button" class="tw-dash-add"><span aria-hidden="true">+</span> Add a new task</button>
            <p class="tw-dash-note tw-dash-sample">Sample tasks. Checking a box here is not saved.</p>
        </article>

        {{-- UP NEXT --}}
        <article class="tw-dash-card tw-dash-panel">
            <header class="tw-dash-panel-head">
                <div>
                    <p class="tw-dash-eyebrow">Schedule</p>
                    <h2 class="tw-dash-h2">Up Next</h2>
                </div>
                <span class="tw-dash-icon tw-dash-icon-outline" aria-hidden="true">&#9638;</span>
            </header>

            @php $c = $d['nextClass']; @endphp
            <div class="tw-dash-class" id="twClass"
                 data-seconds="{{ $c['sampleSecondsUntilStart'] }}"
                 data-window="{{ $c['windowMinutes'] * 60 }}">
                <div class="tw-dash-class-top">
                    <span class="tw-dash-eyebrow tw-dash-on-dark"><i class="tw-dash-live"></i> Starting soon</span>
                    <span class="tw-dash-eyebrow tw-dash-on-dark">{{ $c['room'] }}</span>
                </div>
                <h3 class="tw-dash-class-title">{{ $c['title'] }}</h3>
                <p class="tw-dash-class-detail">{{ $c['detail'] }}</p>

                <p class="tw-dash-eyebrow tw-dash-on-dark tw-dash-center">Starts in</p>
                <p class="tw-dash-countdown" id="twCountdown" aria-live="off">--:--:--</p>

                <div class="tw-dash-class-meta"><span>Until class</span><span id="twLeft"></span></div>
                <div class="tw-dash-bar tw-dash-bar-dark" aria-hidden="true">
                    <div class="tw-dash-bar-fill" id="twProgress" style="width: 0%"></div>
                </div>

                {{-- Later: link to the class notes route --}}
                <button type="button" class="tw-dash-notes-btn">
                    <span class="tw-dash-play" aria-hidden="true">&#9654;</span> Open class notes <span aria-hidden="true">&rarr;</span>
                </button>
            </div>
            <p class="tw-dash-note tw-dash-sample">Sample class. The countdown restarts on every page load.</p>

            <p class="tw-dash-eyebrow tw-dash-later">Later today</p>
            @forelse ($d['laterToday'] as $l)
                <div class="tw-dash-later-row">
                    <div>
                        <p class="tw-dash-task-title">{{ $l['title'] }}</p>
                        <p class="tw-dash-task-sub">{{ $l['time'] }} &middot; {{ $l['room'] }}</p>
                    </div>
                    <span class="tw-dash-task-sub">{{ $l['length'] }}</span>
                </div>
            @empty
                <p class="tw-dash-note">No more classes today.</p>
            @endforelse
        </article>
    </section>

</main>
</div>

<script src="{{ asset('js/dashboard.js') }}" defer></script>
</body>
</html>
</x-app-layout>
