<x-app-layout>
<link rel="stylesheet" href="{{ asset('css/analytics.css') }}">
<div class="analytics-page">
    <header class="analytics-header">
        <div>
            <span class="analytics-eyebrow">PROGRESS ANALYTICS</span>
            <h1>Your momentum, made visible<span>.</span></h1>
            <p>See the habits and study rhythms that are moving you forward.</p>
        </div>
        <a class="analytics-add" href="{{ route('matrix.index') }}">＋ Add Task</a>
    </header>

    <section class="analytics-stats">
        <article class="analytics-stat"><small>Total focus hours</small><strong>{{ number_format($focusSeconds / 3600, 1) }}h</strong><span>Recorded focus time</span></article>
        <article class="analytics-stat"><small>Task completion</small><strong>{{ $completionRate }}%</strong><span>{{ $completed }} of {{ $total }} tasks completed</span></article>
        <article class="analytics-stat"><small>Current streak</small><strong>{{ $currentStreak }} {{ $currentStreak === 1 ? 'day' : 'days' }}</strong><span>Best streak: {{ $bestStreak }} {{ $bestStreak === 1 ? 'day' : 'days' }}</span></article>
    </section>

    <section class="analytics-main-grid">
        <article class="analytics-card effort-card">
            <div class="analytics-card-head"><h2>Task completion rhythm</h2><small>This week</small></div>
            <div class="analytics-bars">
                @foreach($dailyCompletions as $day)
                    <div class="analytics-bar-column">
                        <strong>{{ $day['count'] }}</strong>
                        <div class="analytics-bar-track"><i style="height: {{ $day['count'] ? max(8, min(100, $day['count'] * 18)) : 3 }}%"></i></div>
                        <small>{{ $day['label'] }}</small>
                    </div>
                @endforeach
            </div>
            <div class="analytics-legend"><span></span> Tasks completed</div>
        </article>
        <article class="analytics-card streak-card">
            <div class="analytics-card-head"><h2>Your consistency</h2><small>All time</small></div>
            <div class="streak-ring" style="--progress: {{ min(100, $completionRate) }}%"><div><strong>{{ $currentStreak }}</strong><small>Day streak</small></div></div>
            <p class="streak-note">{{ $completed }} completed tasks out of {{ $total }} total tasks.</p>
        </article>
    </section>

    <section class="analytics-card focus-card">
        <div class="analytics-card-head"><div><h2>Focus session</h2><p>Track your actual study time. Your sessions are saved to your account.</p></div><span class="focus-status" id="focus-status">{{ $activeSession ? 'SESSION ACTIVE' : 'READY' }}</span></div>
        <div class="focus-controls">
            <input id="focus-label" type="text" maxlength="100" placeholder="What are you working on?" value="Study session" {{ $activeSession ? 'disabled' : '' }}>
            <div class="focus-timer" id="focus-timer" data-started="{{ $activeSession ? \Illuminate\Support\Carbon::parse($activeSession->started_at)->toIso8601String() : '' }}">00:00:00</div>
            @if($activeSession)
                <button type="button" class="focus-stop" id="focus-stop">Stop & Save</button>
                <button type="button" class="focus-start" id="focus-start" hidden>Start Focus</button>
            @else
                <button type="button" class="focus-start" id="focus-start">Start Focus</button>
                <button type="button" class="focus-stop" id="focus-stop" hidden>Stop & Save</button>
            @endif
        </div>
        <p class="focus-feedback" id="focus-feedback" role="status">Focus time is counted only while a session is running.</p>
    </section>

    <section class="analytics-card subject-card">
        <div class="analytics-card-head"><div><h2>Subject effort</h2><p>Planned class hours from your saved Calendar schedule.</p></div><small>Saved timetable</small></div>
        @forelse($subjectEffort as $subject)
            <div class="subject-row"><div><strong>{{ $subject['subject'] }}</strong><span>{{ number_format($subject['hours'], 1) }} planned hours</span></div><div class="subject-track"><i style="width: {{ min(100, ($subject['hours'] / max(1, $subjectEffort->max('hours'))) * 100) }}%"></i></div></div>
        @empty
            <div class="analytics-empty">No saved class schedule yet. Import your study load in Calendar to see your planned subject effort here.</div>
        @endforelse
    </section>

    <section class="analytics-card heatmap-card">
        <div class="analytics-card-head"><div><h2>Weekly focus rhythm</h2><p>Daily recorded focus duration, Monday through Sunday.</p></div><small>This week</small></div>
        <div class="focus-heatmap">
            @foreach($dailyFocus as $seconds)
                @php $level = $seconds <= 0 ? 0 : ($seconds < 1800 ? 1 : ($seconds < 3600 ? 2 : ($seconds < 7200 ? 3 : 4))); @endphp
                <div class="heat-cell heat-{{ $level }}" title="{{ round($seconds / 60) }} focus minutes"></div>
            @endforeach
        </div>
        <div class="heatmap-labels"><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span><span>Sun</span></div>
    </section>

    <section class="milestones-strip">
        <span class="milestones-label">MILESTONES</span>
        <div class="milestones-list">
            @foreach($milestones as $milestone)
                <div class="milestone {{ $milestone['unlocked'] ? 'is-unlocked' : '' }}"><span>{{ $milestone['icon'] }}</span><div><strong>{{ $milestone['title'] }}</strong><small>{{ $milestone['unlocked'] ? 'Unlocked' : $milestone['description'] }}</small></div></div>
            @endforeach
        </div>
    </section>
</div>
<script>
(() => {
    const startUrl = @json(route('analytics.focus.start'));
    const stopUrl = @json(route('analytics.focus.stop'));
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const startButton = document.getElementById('focus-start');
    const stopButton = document.getElementById('focus-stop');
    const timer = document.getElementById('focus-timer');
    const label = document.getElementById('focus-label');
    const feedback = document.getElementById('focus-feedback');
    const status = document.getElementById('focus-status');
    let startedAt = timer.dataset.started ? new Date(timer.dataset.started).getTime() : null;
    let ticker = null;
    const format = ms => { const sec = Math.max(0, Math.floor(ms / 1000)); return [Math.floor(sec / 3600), Math.floor(sec % 3600 / 60), sec % 60].map(n => String(n).padStart(2, '0')).join(':'); };
    const render = () => { if (startedAt) timer.textContent = format(Date.now() - startedAt); };
    const run = () => { clearInterval(ticker); render(); ticker = setInterval(render, 1000); };
    if (startedAt) run();
    const post = async url => { const response = await fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' }, body: JSON.stringify({ label: label.value }) }); const data = await response.json(); if (!response.ok) throw new Error(data.message || 'Something went wrong.'); return data; };
    startButton?.addEventListener('click', async () => { startButton.disabled = true; try { const data = await post(startUrl); startedAt = new Date(data.started_at).getTime(); label.disabled = true; startButton.hidden = true; stopButton.hidden = false; status.textContent = 'SESSION ACTIVE'; feedback.textContent = 'Your focus session is running.'; run(); } catch (e) { feedback.textContent = e.message; } finally { startButton.disabled = false; } });
    stopButton?.addEventListener('click', async () => { stopButton.disabled = true; try { const data = await post(stopUrl); clearInterval(ticker); startedAt = null; timer.textContent = '00:00:00'; label.disabled = false; stopButton.hidden = true; startButton.hidden = false; status.textContent = 'SAVED'; feedback.textContent = `Session saved: ${Math.floor(data.duration_seconds / 60)} minutes recorded. Refresh to update your analytics totals.`; } catch (e) { feedback.textContent = e.message; } finally { stopButton.disabled = false; } });
})();
</script>
</x-app-layout>
