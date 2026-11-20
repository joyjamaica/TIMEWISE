<x-app-layout>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">

    @php
        $s = $d['schedule'];
        $hero = $s['hero'];
        $streak = $d['streak'];
        $pct = $d['tasks']['percent'];
    @endphp

    <div class="tw-dash-body">
    <div class="tw-dash-shell">
    <main class="tw-dash-main" id="twDash" data-server-now="{{ $serverNow }}"
          data-csrf="{{ csrf_token() }}" data-toggle-url="{{ url('/dashboard/tasks') }}">

        {{-- HEADER: greeting + live clock --}}
        <header class="tw-dash-header">
            <div class="tw-dash-hello">
                <p class="tw-dash-eyebrow">Dashboard</p>
                <h1 class="tw-dash-greeting">{{ $greeting }}, {{ $d['userName'] }}<span class="tw-dash-dot">.</span></h1>
                <p class="tw-dash-subtitle">{{ $d['subtitle'] }}</p>
            </div>

            <aside class="tw-dash-clock" aria-label="Current Philippine Standard Time">
                <p class="tw-dash-clock-time">
                    <span id="twClockTime">{{ $clock['time'] }}</span>
                    <span class="tw-dash-clock-ampm" id="twClockAmPm">{{ $clock['ampm'] }}</span>
                </p>
                <p class="tw-dash-clock-date" id="twClockDate">{{ $clock['date'] }}</p>
                <p class="tw-dash-clock-zone">PHT | Philippine Standard Time</p>
            </aside>
        </header>

        {{-- SUMMARY CARDS --}}
        <section class="tw-dash-summary" aria-label="Productivity summary">

            <article class="tw-dash-card tw-dash-stat">
                <div class="tw-dash-stat-top">
                    <span class="tw-dash-icon" aria-hidden="true">&#10003;</span>
                    <span id="twPendingPill" class="tw-dash-pill {{ $d['tasks']['pending'] === 0 && $d['tasks']['total'] > 0 ? 'tw-dash-pill-green' : '' }}">{{ $d['tasks']['pending'] }} pending</span>
                </div>
                <p class="tw-dash-label">Tasks done</p>
                <p class="tw-dash-big"><strong id="twDoneCount">{{ $d['tasks']['done'] }}</strong><span>/{{ $d['tasks']['total'] }}</span></p>
                <div class="tw-dash-bar" id="twTasksBar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $pct }}" aria-label="Tasks completed">
                    <div class="tw-dash-bar-fill" id="twTasksFill" style="width: {{ $pct }}%"></div>
                </div>
                <p class="tw-dash-note" id="twTasksNote">{{ $pct }}% complete. {{ $d['tasks']['note'] }}</p>
            </article>

            <article class="tw-dash-card tw-dash-stat">
                <div class="tw-dash-stat-top">
                    <span class="tw-dash-icon" aria-hidden="true">&#9719;</span>
                    <span class="tw-dash-note">This week</span>
                </div>
                <p class="tw-dash-label">Class time</p>
                <div class="tw-dash-focus-row">
                    <p class="tw-dash-big">{{ $d['classTime']['total'] }}</p>
                    <ul class="tw-dash-chart" aria-label="Class hours per day this week">
                        @foreach ($d['classTime']['week'] as $bar)
                            <li class="{{ $d['classTime']['hasData'] && $bar['day'] === $d['classTime']['highlight'] ? 'is-peak' : '' }}" style="height: {{ $bar['height'] }}%">
                                <span class="tw-dash-sr">{{ $bar['day'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
                @unless ($d['classTime']['hasData'])
                    <p class="tw-dash-note tw-dash-gap">No classes saved for this week.</p>
                @endunless
            </article>

            <article class="tw-dash-card tw-dash-stat">
                <div class="tw-dash-stat-top">
                    <span class="tw-dash-icon" aria-hidden="true">&#9636;</span>
                    <span class="tw-dash-pill" id="twScoreLabel">{{ $d['score']['label'] }}</span>
                </div>
                <p class="tw-dash-label">Productivity score</p>
                <div class="tw-dash-score-row">
                    <div>
                        <p class="tw-dash-big"><strong id="twScoreValue">{{ $d['score']['value'] }}</strong><span>/100</span></p>
                        <p class="tw-dash-note">Task completion and overdue tasks</p>
                    </div>
                    <div class="tw-dash-ring" id="twScoreRing" style="--pct: {{ $d['score']['value'] }}" role="img" aria-label="Score {{ $d['score']['value'] }} out of 100">
                        <span id="twScoreRingText">{{ $d['score']['value'] }}</span>
                    </div>
                </div>
            </article>
        </section>

        {{-- MAIN ROW --}}
        <section class="tw-dash-grid">

            <div class="tw-dash-col">

                {{-- TODAY'S FOCUS --}}
                <article class="tw-dash-card tw-dash-panel">
                    <header class="tw-dash-panel-head">
                        <div>
                            <p class="tw-dash-eyebrow">Plan your day</p>
                            <h2 class="tw-dash-h2">Today's Focus</h2>
                        </div>
                        @if ($links['tasks'])
                            <a href="{{ $links['tasks'] }}" class="tw-dash-link">View all &rarr;</a>
                        @endif
                    </header>

                    @if (count($d['todayTasks']))
                        <ul class="tw-dash-tasks">
                            @foreach ($d['todayTasks'] as $i => $task)
                                <li class="tw-dash-task {{ $task['done'] ? 'is-done' : '' }}" data-task-id="{{ $task['id'] }}">
                                    <input type="checkbox" id="task-{{ $i }}" class="tw-dash-check" data-id="{{ $task['id'] }}" @checked($task['done'])>
                                    <label for="task-{{ $i }}" class="tw-dash-task-text">
                                        <span class="tw-dash-task-title">{{ $task['title'] }}</span>
                                        <span class="tw-dash-task-sub">{{ $task['subject'] }}</span>
                                    </label>
                                    <span class="tw-dash-badge tw-dash-badge-{{ strtolower($task['priority']) }}">{{ $task['priority'] }}</span>
                                    <span class="tw-dash-time">{{ $task['time'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="tw-dash-empty">
                            <p class="tw-dash-empty-title">Nothing due today</p>
                            <p class="tw-dash-note">Tasks with a due date of today will show up here.</p>
                        </div>
                    @endif

                    @if ($links['tasks'])
                        <a href="{{ $links['tasks'] }}" class="tw-dash-add"><span aria-hidden="true">+</span> Add a new task</a>
                    @endif
                    <p class="tw-dash-note tw-dash-sample" id="twTaskMsg" role="status" aria-live="polite"></p>
                </article>

                {{-- DEADLINES + STREAK --}}
                <div class="tw-dash-pair">
                    <article class="tw-dash-card tw-dash-panel">
                        <header class="tw-dash-panel-head">
                            <div>
                                <p class="tw-dash-eyebrow">Coming up</p>
                                <h2 class="tw-dash-h2">Upcoming Deadlines</h2>
                            </div>
                            @if ($d['overdue'] > 0)
                                <span class="tw-dash-badge tw-dash-badge-high">{{ $d['overdue'] }} overdue</span>
                            @endif
                        </header>

                        @forelse ($d['deadlines'] as $dl)
                            <div class="tw-dash-later-row" data-task-id="{{ $dl['id'] }}">
                                <div>
                                    <p class="tw-dash-task-title">{{ $dl['title'] }}</p>
                                    <p class="tw-dash-task-sub">{{ $dl['when'] }}</p>
                                </div>
                                <span class="tw-dash-badge tw-dash-badge-{{ strtolower($dl['priority']) }}">{{ $dl['priority'] }}</span>
                            </div>
                        @empty
                            <div class="tw-dash-empty">
                                <p class="tw-dash-empty-title">No upcoming deadlines</p>
                                <p class="tw-dash-note">Pending tasks with a due date will appear here.</p>
                            </div>
                        @endforelse
                    </article>

                    <article class="tw-dash-card tw-dash-panel">
                        <header class="tw-dash-panel-head">
                            <div>
                                <p class="tw-dash-eyebrow">Consistency</p>
                                <h2 class="tw-dash-h2">Study Streak</h2>
                            </div>
                            <span class="tw-dash-streak-icon" aria-hidden="true">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2c1 4-3 5-3 9a3 3 0 0 0 6 0c0-1-.4-1.800-1-2.500 3 1.200 5 4 5 7a7 7 0 0 1-14 0c0-6 5-8 7-13.500z"/></svg>
                            </span>
                        </header>

                        @if ($streak)
                            <p class="tw-dash-big">{{ $streak['current'] }}<span> {{ $streak['current'] === 1 ? 'day' : 'days' }}</span></p>
                            <p class="tw-dash-note">Your best is {{ $streak['best'] }} {{ $streak['best'] === 1 ? 'day' : 'days' }}</p>
                            <p class="tw-dash-note tw-dash-gap">
                                @if ($streak['todayDone'])
                                    You completed a task today. Your streak is safe.
                                @elseif ($streak['current'] > 0)
                                    Complete a task today to keep your streak going.
                                @else
                                    Complete a task today to start a streak.
                                @endif
                            </p>
                            <ul class="tw-dash-dots" aria-label="Task completions in the last 7 days">
                                @foreach ($streak['last7'] as $day)
                                    <li class="{{ $day['on'] ? 'is-on' : '' }}"><i></i><span>{{ substr($day['day'], 0, 1) }}</span></li>
                                @endforeach
                            </ul>
                        @else
                            <div class="tw-dash-empty">
                                <p class="tw-dash-empty-title">Streak not tracked yet</p>
                                <p class="tw-dash-note">Tasks need a completion date to count study days.</p>
                            </div>
                        @endif
                    </article>
                </div>
            </div>

            {{-- UP NEXT --}}
            <article class="tw-dash-card tw-dash-panel tw-dash-upnext">
                <header class="tw-dash-panel-head">
                    <div>
                        <p class="tw-dash-eyebrow">Schedule</p>
                        <h2 class="tw-dash-h2">Up Next</h2>
                    </div>
                    <span class="tw-dash-icon tw-dash-icon-outline" aria-hidden="true">&#9638;</span>
                </header>

                @if ($s['stale'])
                    <p class="tw-dash-notice">Your saved schedule is for the week of {{ $s['weekLabel'] }}.
                        @if ($links['calendar'])<a href="{{ $links['calendar'] }}">Update it in Calendar</a>@else Update it in Calendar @endif.</p>
                @endif

                @if ($hero)
                    <div class="tw-dash-class {{ $hero['mode'] === 'ongoing' ? 'is-ongoing' : '' }}" id="twClass"
                         data-mode="{{ $hero['mode'] }}"
                         data-start="{{ $hero['startIso'] }}"
                         data-end="{{ $hero['endIso'] }}">
                        <div class="tw-dash-class-top">
                            <span class="tw-dash-status"><i class="tw-dash-live"></i> {{ $hero['status'] }}</span>
                            <span class="tw-dash-eyebrow tw-dash-on-dark">{{ $hero['room'] }}</span>
                        </div>
                        <h3 class="tw-dash-class-title">{{ $hero['title'] }}</h3>
                        <p class="tw-dash-class-detail">{{ $hero['subject'] ? $hero['subject'] . ' · ' : '' }}{{ $hero['timeRange'] }}</p>

                        <p class="tw-dash-eyebrow tw-dash-on-dark tw-dash-center">{{ $hero['label'] }}</p>
                        <p class="tw-dash-countdown" id="twCountdown" aria-live="off">--:--:--</p>

                        <div class="tw-dash-class-meta"><span>{{ $hero['mode'] === 'ongoing' ? 'Class progress' : 'Until class' }}</span><span id="twLeft"></span></div>
                        <div class="tw-dash-bar tw-dash-bar-dark" aria-hidden="true">
                            <div class="tw-dash-bar-fill" id="twProgress" style="width: 0%"></div>
                        </div>

                        @if ($links['calendar'])
                            <a href="{{ $links['calendar'] }}" class="tw-dash-notes-btn">
                                <span class="tw-dash-play" aria-hidden="true">&#9654;</span> <span>Open calendar</span> <span aria-hidden="true">&rarr;</span>
                            </a>
                        @endif
                    </div>
                @elseif (! $s['hasSchedule'])
                    <div class="tw-dash-empty tw-dash-empty-lg">
                        <p class="tw-dash-empty-title">No schedule saved yet</p>
                        <p class="tw-dash-note">Add your class schedule in the Calendar module and your classes will show up here.</p>
                        @if ($links['calendar'])
                            <a href="{{ $links['calendar'] }}" class="tw-dash-btn">Go to Calendar</a>
                        @endif
                    </div>
                @else
                    <div class="tw-dash-empty tw-dash-empty-lg">
                        <p class="tw-dash-empty-title">No upcoming classes today</p>
                        <p class="tw-dash-note">
                            @if ($s['nextOtherDay'])
                                Your next class is {{ $s['nextOtherDay']['title'] }} on {{ $s['nextOtherDay']['when'] }}.
                            @else
                                You have no classes left in your saved schedule.
                            @endif
                        </p>
                    </div>
                @endif

                @if ($s['hasSchedule'] && ! $s['stale'])
                    <p class="tw-dash-eyebrow tw-dash-later">Later today</p>
                    @forelse ($s['laterToday'] as $l)
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
                @endif
            </article>
        </section>

    </main>
    </div>
    </div>

    <script src="{{ asset('js/dashboard.js') }}" defer></script>
</x-app-layout>
