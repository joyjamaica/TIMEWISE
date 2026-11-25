@section('title', 'Calendar')
@extends('layouts.app')
@section('content')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    .tw-cal-wrap,
    .tw-cal-drawer,
    .tw-cal-wrap input,
    .tw-cal-wrap select,
    .tw-cal-wrap textarea,
    .tw-cal-wrap button,
    .tw-cal-drawer input,
    .tw-cal-drawer select,
    .tw-cal-drawer textarea,
    .tw-cal-drawer button {
        font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        box-sizing: border-box;
    }

    .tw-cal-wrap {
        max-width: 1320px;
        margin: 0 auto;
        padding: 28px 32px;
        color: #2C221E;
    }

    .tw-cal-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 16px;
        flex-wrap: wrap;
        margin-bottom: 22px;
    }

    .tw-cal-eyebrow {
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: #A66E53;
        margin-bottom: 6px;
    }

    .tw-cal-title {
        font-size: 2.25rem;
        font-weight: 800;
        letter-spacing: -0.03em;
        line-height: 1.1;
        margin: 0 0 6px 0;
        color: #2C221E;
    }

    .tw-cal-title span {
        color: #C58B65;
    }

    .tw-cal-sub {
        margin: 0;
        color: #6C5D53;
        font-size: 0.95rem;
        font-weight: 400;
    }

    .tw-cal-controls {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .tw-cal-nav {
        display: inline-flex;
        background: #FFFFFF;
        border: 1px solid rgba(218, 195, 172, 0.65);
        border-radius: 12px;
        overflow: hidden;
    }

    .tw-cal-nav a {
        text-decoration: none;
        color: #2C221E;
        font-weight: 600;
        font-size: 0.86rem;
        padding: 9px 14px;
        border-right: 1px solid rgba(218, 195, 172, 0.55);
        transition: background-color 0.15s ease;
    }

    .tw-cal-nav a:last-child { border-right: none; }
    .tw-cal-nav a:hover { background: #EEDCC5; }

    .tw-cal-btn {
        background: #2C221E;
        color: #FFFFFF;
        border: none;
        border-radius: 12px;
        padding: 10px 18px;
        font-weight: 600;
        font-size: 0.88rem;
        cursor: pointer;
        transition: background-color 0.15s ease;
    }

    .tw-cal-btn:hover { background: #A66E53; }

    .tw-cal-board {
        background: #F7EFE2;
        border: 1px solid rgba(218, 195, 172, 0.6);
        border-radius: 22px;
        padding: 16px;
        box-shadow: 0 10px 28px rgba(44, 34, 30, 0.04);
    }

    .tw-cal-weekdays {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 8px;
        margin-bottom: 8px;
    }

    .tw-cal-weekday {
        text-align: center;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #6C5D53;
        padding: 4px 0;
    }

    .tw-cal-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 8px;
    }

    .tw-cal-cell {
        background: #FFFFFF;
        border: 1px solid rgba(228, 179, 129, 0.3);
        border-radius: 14px;
        min-height: 120px;
        padding: 8px;
        display: flex;
        flex-direction: column;
        gap: 5px;
        cursor: pointer;
        transition: border-color 0.15s ease;
    }

    .tw-cal-cell:hover { border-color: #A66E53; }
    .tw-cal-cell.is-outside { background: rgba(247, 239, 226, 0.55); opacity: 0.65; }
    .tw-cal-cell.is-today { border: 2px solid #A66E53; }

    .tw-cal-daynum {
        font-size: 0.82rem;
        font-weight: 700;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .tw-cal-cell.is-today .tw-cal-daynum {
        background: #2C221E;
        color: #FFFFFF;
    }

    .tw-cal-badge {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 6px;
        padding: 4px 8px;
        border-radius: 7px;
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: -0.01em;
        border-left: 3px solid #A66E53;
        background: #F9F3EA;
        color: #2C221E;
        transition: transform 0.12s ease;
    }

    .tw-cal-badge:hover { transform: translateY(-1px); }
    .tw-cal-badge.prio-high { background: #F4E2D0; border-left-color: #A66E53; }
    .tw-cal-badge.prio-medium { background: #FDF1DC; border-left-color: #E4B381; }
    .tw-cal-badge.prio-low { background: #F5F0E8; border-left-color: #9C8B7E; }
    .tw-cal-badge.is-done { opacity: 0.55; text-decoration: line-through; }

    .tw-cal-badge-title {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .tw-cal-badge-time {
        font-size: 0.66rem;
        font-weight: 500;
        color: #6C5D53;
        flex-shrink: 0;
    }

    .tw-cal-drawer {
        position: fixed;
        inset: 0;
        background: rgba(44, 34, 30, 0.35);
        display: none;
        justify-content: flex-end;
        z-index: 60;
    }

    .tw-cal-drawer.open { display: flex; }

    .tw-cal-panel {
        width: 100%;
        max-width: 400px;
        background: #2C221E;
        color: #FFFFFF;
        height: 100%;
        padding: 26px;
        overflow-y: auto;
    }

    .tw-cal-panel label {
        display: block;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #E4B381;
        margin: 12px 0 5px;
    }

    .tw-cal-panel input,
    .tw-cal-panel select,
    .tw-cal-panel textarea {
        width: 100%;
        padding: 10px 12px;
        border-radius: 10px;
        border: 1px solid rgba(255, 255, 255, 0.16);
        background: rgba(255, 255, 255, 0.08);
        color: #FFFFFF;
        font-size: 0.88rem;
        font-weight: 400;
    }

    .tw-cal-panel select option { background: #2C221E; color: #FFFFFF; }

    .tw-cal-submit {
        width: 100%;
        margin-top: 16px;
        padding: 12px;
        border: none;
        border-radius: 12px;
        background: linear-gradient(135deg, #E4B381, #C58B65);
        color: #2C221E;
        font-weight: 700;
        font-size: 0.9rem;
        cursor: pointer;
    }

    @media (max-width: 960px) {
        .tw-cal-wrap { padding: 18px 14px; }
        .tw-cal-grid { gap: 5px; }
        .tw-cal-cell { min-height: 92px; padding: 6px; }
        .tw-cal-badge-time { display: none; }
    }
</style>

<div class="tw-cal-wrap">
    <div class="tw-cal-top">
        <div>
            <div class="tw-cal-eyebrow">Calendar</div>
            <h1 class="tw-cal-title">{{ $currentDate->format('F Y') }}<span>.</span></h1>
            <p class="tw-cal-sub">Click any day to add a task or event, or click a badge to inspect and update it.</p>
        </div>

        <div class="tw-cal-controls">
            <div class="tw-cal-nav">
                <a href="{{ route('calendar.month.index', ['year' => $prevMonth->year, 'month' => $prevMonth->month]) }}">‹ Prev</a>
                <a href="{{ route('calendar.month.index') }}">Today</a>
                <a href="{{ route('calendar.month.index', ['year' => $nextMonth->year, 'month' => $nextMonth->month]) }}">Next ›</a>
            </div>

            <button type="button" class="tw-cal-btn" onclick="openCalCreate('{{ now()->format('Y-m-d\TH:i') }}')">
                + Add Event / Task
            </button>
        </div>
    </div>

    <div class="tw-cal-board">
        <div class="tw-cal-weekdays">
            <div class="tw-cal-weekday">Sun</div>
            <div class="tw-cal-weekday">Mon</div>
            <div class="tw-cal-weekday">Tue</div>
            <div class="tw-cal-weekday">Wed</div>
            <div class="tw-cal-weekday">Thu</div>
            <div class="tw-cal-weekday">Fri</div>
            <div class="tw-cal-weekday">Sat</div>
        </div>

        <div class="tw-cal-grid">
            @foreach($days as $day)
                <div class="tw-cal-cell {{ !$day['isCurrentMonth'] ? 'is-outside' : '' }} {{ $day['isToday'] ? 'is-today' : '' }}"
                     onclick="openCalCreate('{{ $day['dateKey'] }}T09:00')">
                    <div>
                        <span class="tw-cal-daynum">{{ $day['dayNumber'] }}</span>
                    </div>

                    @foreach($day['tasks'] as $task)
                        @php
                            $prioClass = 'prio-' . strtolower($task->priority);
                            $timeLabel = $task->due_date ? \Carbon\Carbon::parse($task->due_date)->format('g:i A') : '';
                        @endphp
                        <div class="tw-cal-badge {{ $prioClass }} {{ $task->is_completed ? 'is-done' : '' }}"
                             onclick="event.stopPropagation(); openCalEdit(this)"
                             data-id="{{ $task->id }}"
                             data-title="{{ $task->title }}"
                             data-priority="{{ $task->priority }}"
                             data-difficulty="{{ $task->difficulty }}"
                             data-description="{{ $task->description }}"
                             data-due-date="{{ $task->due_date ? \Carbon\Carbon::parse($task->due_date)->format('Y-m-d\TH:i') : '' }}"
                             data-reminder="{{ $task->reminder }}"
                             data-location="{{ $task->location }}"
                             data-completed="{{ $task->is_completed }}">
                            <span class="tw-cal-badge-title">{{ $task->is_completed ? '✓ ' : '' }}{{ $task->title }}</span>
                            @if($timeLabel)
                                <span class="tw-cal-badge-time">{{ $timeLabel }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="tw-cal-drawer" id="calDrawer" onclick="if(event.target === this) closeCalDrawer()">
    <div class="tw-cal-panel">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
            <div class="tw-cal-eyebrow" id="calDrawerTitle" style="margin:0;">New Calendar Entry</div>
            <button type="button" onclick="closeCalDrawer()" style="background:rgba(255,255,255,0.1); color:#fff; border:none; border-radius:8px; padding:5px 10px; cursor:pointer; font-weight:700;">✕</button>
        </div>

        <form id="calCreateForm" action="{{ route('tasks.store') }}" method="POST">
            @csrf
            <label>Title</label>
            <input type="text" name="title" id="calCreateTitle" required>

            <label>Date & Time</label>
            <input type="datetime-local" name="due_date" id="calCreateDue" required>

            <label>Priority & Difficulty</label>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
                <select name="priority">
                    <option value="High">High Priority</option>
                    <option value="Medium" selected>Medium Priority</option>
                    <option value="Low">Low Priority</option>
                </select>
                <select name="difficulty">
                    <option value="Easy">Easy</option>
                    <option value="Moderate" selected>Moderate</option>
                    <option value="Hard">Hard</option>
                </select>
            </div>

            <label>Location (Optional)</label>
            <input type="text" name="location">

            <label>Reminder (Optional)</label>
            <input type="text" name="reminder">

            <label>Notes / Description (Optional)</label>
            <textarea name="description" rows="3"></textarea>

            <button type="submit" class="tw-cal-submit">Save to Calendar</button>
        </form>

        <form id="calEditForm" method="POST" style="display:none;">
            @csrf
            @method('PUT')
            <label>Title</label>
            <input type="text" name="title" id="calEditTitle" required>

            <label>Date & Time</label>
            <input type="datetime-local" name="due_date" id="calEditDue">

            <label>Priority & Difficulty</label>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
                <select name="priority" id="calEditPriority">
                    <option value="High">High Priority</option>
                    <option value="Medium">Medium Priority</option>
                    <option value="Low">Low Priority</option>
                </select>
                <select name="difficulty" id="calEditDifficulty">
                    <option value="Easy">Easy</option>
                    <option value="Moderate">Moderate</option>
                    <option value="Hard">Hard</option>
                </select>
            </div>

            <label>Location</label>
            <input type="text" name="location" id="calEditLocation">

            <label>Reminder</label>
            <input type="text" name="reminder" id="calEditReminder">

            <label>Notes / Description</label>
            <textarea name="description" id="calEditDesc" rows="3"></textarea>

            <button type="submit" class="tw-cal-submit">Update Entry</button>
        </form>

        <form id="calCompleteForm" method="POST" style="display:none; margin-top:10px;">
            @csrf
            @method('PATCH')
            <button type="submit" id="calCompleteBtn" style="width:100%; padding:11px; border-radius:12px; border:1px solid rgba(255,255,255,0.2); background:rgba(255,255,255,0.1); color:#fff; font-weight:700; cursor:pointer;">✓ Mark as Done</button>
        </form>
    </div>
</div>

<script>
    function openCalCreate(dateStr) {
        document.getElementById('calDrawerTitle').innerText = 'New Calendar Entry';
        document.getElementById('calCreateForm').style.display = 'block';
        document.getElementById('calEditForm').style.display = 'none';
        document.getElementById('calCompleteForm').style.display = 'none';
        document.getElementById('calCreateDue').value = dateStr;
        document.getElementById('calCreateTitle').value = '';
        document.getElementById('calDrawer').classList.add('open');
    }

    function openCalEdit(el) {
        document.getElementById('calDrawerTitle').innerText = 'Edit Calendar Entry';
        document.getElementById('calCreateForm').style.display = 'none';
        document.getElementById('calEditForm').style.display = 'block';
        document.getElementById('calCompleteForm').style.display = 'block';

        const id = el.getAttribute('data-id');
        const done = el.getAttribute('data-completed') === '1';

        document.getElementById('calEditForm').action = '/tasks/' + id;
        document.getElementById('calCompleteForm').action = '/tasks/' + id + '/complete';
        document.getElementById('calEditTitle').value = el.getAttribute('data-title') || '';
        document.getElementById('calEditDue').value = el.getAttribute('data-due-date') || '';
        document.getElementById('calEditPriority').value = el.getAttribute('data-priority') || 'Medium';
        document.getElementById('calEditDifficulty').value = el.getAttribute('data-difficulty') || 'Moderate';
        document.getElementById('calEditLocation').value = el.getAttribute('data-location') || '';
        document.getElementById('calEditReminder').value = el.getAttribute('data-reminder') || '';
        document.getElementById('calEditDesc').value = el.getAttribute('data-description') || '';
        document.getElementById('calCompleteBtn').innerText = done ? '↩ Mark as Undone' : '✓ Mark as Done';

        document.getElementById('calDrawer').classList.add('open');
    }

    function closeCalDrawer() {
        document.getElementById('calDrawer').classList.remove('open');
    }
</script>
@endsection