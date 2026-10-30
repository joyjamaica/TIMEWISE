
<x-app-layout>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/matrix.css') }}">

<div class="matrix-page">

    {{-- Page Header --}}
    <header class="matrix-header">
        <div>
            <span class="matrix-eyebrow">TASK ORGANIZATION</span>
            <h1>Task Matrix</h1>
            <p>Organize your tasks based on priority and difficulty.</p>
        </div>

        <div class="matrix-header-actions">
            <button type="button" class="matrix-btn matrix-btn--primary"
                onclick="toggleAddTask()">
                <span>＋</span> Add Task
            </button>

        </div>
    </header>

    {{-- Flash Messages --}}
    @if (session('success'))
        <div class="matrix-alert matrix-alert--success">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="matrix-alert matrix-alert--error">
            <strong>Please check the following:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Add Task Form --}}
    <section class="matrix-form-card" id="addTaskSection"
        style="display: {{ $errors->any() ? 'block' : 'none' }};">

        <div class="section-heading">
            <div>
                <span class="section-eyebrow">NEW TASK</span>
                <p>Enter your task details and organize your workload.</p>
            </div>
            <span class="section-icon">＋</span>
        </div>

        <form action="{{ route('tasks.store') }}" method="POST">
            @csrf

            <div class="matrix-form-grid">

                <div class="form-field form-field--title">
                    <label for="taskTitle">Task Title</label>
                    <input type="text" id="taskTitle" name="title"
                        placeholder="e.g. Complete project documentation"
                        value="{{ old('title') }}" required>
                </div>

                <div class="form-field">
                    <label for="taskPriority">Priority</label>
                    <select id="taskPriority" name="priority" required>
                        <option value="High" {{ old('priority', 'Medium') === 'High' ? 'selected' : '' }}>High</option>
                        <option value="Medium" {{ old('priority', 'Medium') === 'Medium' ? 'selected' : '' }}>Medium</option>
                        <option value="Low" {{ old('priority') === 'Low' ? 'selected' : '' }}>Low</option>
                    </select>
                </div>

                <div class="form-field">
                    <label for="taskDifficulty">Difficulty</label>
                    <select id="taskDifficulty" name="difficulty" required>
                        <option value="Hard" {{ old('difficulty') === 'Hard' ? 'selected' : '' }}>Hard</option>
                        <option value="Moderate" {{ old('difficulty', 'Moderate') === 'Moderate' ? 'selected' : '' }}>Moderate</option>
                        <option value="Easy" {{ old('difficulty') === 'Easy' ? 'selected' : '' }}>Easy</option>
                    </select>
                </div>

                <div class="form-field">
                    <label for="taskLocation">Location</label>
                    <input type="text" id="taskLocation" name="location"
                        placeholder="Optional" value="{{ old('location') }}">
                </div>

            </div>

            <div class="matrix-form-grid matrix-form-grid--details">

                <div class="form-field form-field--description">
                    <label for="taskDescription">Description / Notes</label>
                    <textarea id="taskDescription" name="description"
                        placeholder="Add additional task details...">{{ old('description') }}</textarea>
                </div>

                <div class="form-field">
                    <label for="taskDueDate">Due Date</label>
                    <input type="datetime-local" id="taskDueDate"
                        name="due_date" value="{{ old('due_date') }}">
                </div>

                <div class="form-field">
                    <label for="taskReminder">Reminder</label>
                    <input type="text" id="taskReminder" name="reminder"
                        placeholder="e.g. 1 hour before"
                        value="{{ old('reminder') }}">
                </div>

            </div>

            <div class="form-footer">
                <span>* Additional details are optional.</span>
                <button type="submit" class="matrix-btn matrix-btn--primary">
                    Save Task
                </button>
            </div>

        </form>
    </section>

    {{-- Matrix and Task Details --}}
    <div class="matrix-content">

        {{-- Priority and Difficulty Matrix --}}
        <section class="matrix-board">

            <div class="section-heading matrix-board-heading">
                <div>
                    <span class="section-eyebrow">YOUR WORKSPACE</span>
                    <h2>Priority × Difficulty</h2>
                    <p>Choose a task to view and manage its details.</p>
                </div>

                <span class="task-count">
                    {{ $tasks->count() }} {{ $tasks->count() === 1 ? 'Task' : 'Tasks' }}
                </span>
            </div>

            <div class="matrix-grid">

                @php
                    $gridCells = [
                        ['High', 'Easy'],
                        ['High', 'Moderate'],
                        ['High', 'Hard'],
                        ['Medium', 'Easy'],
                        ['Medium', 'Moderate'],
                        ['Medium', 'Hard'],
                        ['Low', 'Easy'],
                        ['Low', 'Moderate'],
                        ['Low', 'Hard'],
                    ];
                @endphp

                @foreach ($gridCells as $cellKey)

                    @php
                        $priority = $cellKey[0];
                        $difficulty = $cellKey[1];

                        $cellTasks = $tasks
                            ->where('priority', $priority)
                            ->where('difficulty', $difficulty);
                    @endphp

                    <div class="matrix-cell">

                        <div class="matrix-cell-header">
                            <span class="cell-priority cell-priority--{{ strtolower($priority) }}">
                                {{ $priority }}
                            </span>

                            <span class="cell-difficulty">
                                {{ $difficulty }}
                            </span>
                        </div>

                        <div class="matrix-cell-tasks">

                            @forelse ($cellTasks as $task)

                                <button type="button"
                                    class="matrix-task {{ $task->is_completed ? 'is-completed' : '' }}"
                                    onclick="selectTask(this)"
                                    data-id="{{ $task->id }}"
                                    data-title="{{ $task->title }}"
                                    data-description="{{ $task->description ?? '' }}"
                                    data-due="{{ $task->due_date ? \Illuminate\Support\Carbon::parse($task->due_date)->format('Y-m-d\TH:i') : '' }}"
                                    data-reminder="{{ $task->reminder ?? '' }}"
                                    data-location="{{ $task->location ?? '' }}"
                                    data-priority="{{ $task->priority }}"
                                    data-difficulty="{{ $task->difficulty }}"
                                    data-completed="{{ (int) $task->is_completed }}">

                                    <span class="task-status">
                                        {{ $task->is_completed ? '✓' : '○' }}
                                    </span>

                                    <span class="task-name">
                                        {{ $task->title }}
                                    </span>

                                    <span class="task-arrow">›</span>

                                </button>

                            @empty

                                <div class="matrix-empty">
                                    No tasks yet
                                </div>

                            @endforelse

                        </div>
                    </div>

                @endforeach

            </div>

            <div class="matrix-axis">
                <span>EASY</span>
                <span class="axis-line"></span>
                <span>MODERATE</span>
                <span class="axis-line"></span>
                <span>HARD</span>
                <span class="axis-label">DIFFICULTY →</span>
            </div>

        </section>

        {{-- Task Details Panel --}}
        <aside class="matrix-details">

            <div class="details-heading">
                <span class="section-eyebrow">TASK MANAGEMENT</span>
                <h2>Task Details</h2>
                <p>Inspect, update, or complete your selected task.</p>
            </div>

            {{-- Initial Instructions --}}
            <div id="instructionText" class="details-empty">
                <div class="details-empty-icon">✦</div>
                <h3>Select a task</h3>
                <p>
                    Choose any task from your Matrix to see its information
                    and manage its details.
                </p>
            </div>

            {{-- Edit Task Form --}}
            <form id="editForm" method="POST"
                class="matrix-edit-form" style="display: none;">

                @csrf
                @method('PUT')

                <div class="selected-task-label">SELECTED TASK</div>

                <div class="form-field">
                    <label for="editTitle">Task Title</label>
                    <input type="text" name="title" id="editTitle" required>
                </div>

                <div class="details-form-row">

                    <div class="form-field">
                        <label for="editPriority">Priority</label>
                        <select name="priority" id="editPriority" required>
                            <option value="High">High</option>
                            <option value="Medium">Medium</option>
                            <option value="Low">Low</option>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="editDifficulty">Difficulty</label>
                        <select name="difficulty" id="editDifficulty" required>
                            <option value="Hard">Hard</option>
                            <option value="Moderate">Moderate</option>
                            <option value="Easy">Easy</option>
                        </select>
                    </div>

                </div>

                <div class="form-field">
                    <label for="editDescription">Description</label>
                    <textarea name="description" id="editDescription"
                        placeholder="Add task notes..."></textarea>
                </div>

                <div class="form-field">
                    <label for="editDueDate">Due Date</label>
                    <input type="datetime-local" name="due_date" id="editDueDate">
                </div>

                <div class="form-field">
                    <label for="editReminder">Reminder</label>
                    <input type="text" name="reminder" id="editReminder"
                        placeholder="e.g. 1 hour before">
                </div>

                <div class="form-field">
                    <label for="editLocation">Location</label>
                    <input type="text" name="location" id="editLocation"
                        placeholder="Task location">
                </div>

                <button type="submit"
                    class="matrix-btn matrix-btn--primary matrix-btn--full">
                    Save Changes
                </button>

            </form>

            {{-- Complete / Undo Form --}}
            <form id="completeForm" method="POST"
                class="matrix-complete-form" style="display: none;">

                @csrf
                @method('PATCH')

                <button type="submit" id="completeBtn"
                    class="matrix-btn matrix-btn--complete">
                    ✓ Mark as Done
                </button>

            </form>

        </aside>

    </div>

</div>

<script>
    const updateUrlTemplate = @json(route('tasks.update', ['id' => '__TASK_ID__']));
    const completeUrlTemplate = @json(route('tasks.complete', ['id' => '__TASK_ID__']));

    // Show or hide the Add Task form.
    function toggleAddTask() {
        const section = document.getElementById('addTaskSection');

        section.style.display =
            section.style.display === 'none' ? 'block' : 'none';
    }

    // Select a task and display its details.
    function selectTask(element) {

        document.querySelectorAll('.matrix-task').forEach(task => {
            task.classList.remove('is-active');
        });

        element.classList.add('is-active');

        const {
            id, title, description, due, reminder,
            location, priority, difficulty, completed
        } = element.dataset;

        document.getElementById('instructionText').style.display = 'none';
        document.getElementById('editForm').style.display = 'flex';
        document.getElementById('completeForm').style.display = 'block';

        document.getElementById('editForm').action =
            updateUrlTemplate.replace('__TASK_ID__', id);

        document.getElementById('completeForm').action =
            completeUrlTemplate.replace('__TASK_ID__', id);

        document.getElementById('editTitle').value = title;
        document.getElementById('editDescription').value = description;
        document.getElementById('editDueDate').value = due;
        document.getElementById('editReminder').value = reminder;
        document.getElementById('editLocation').value = location;
        document.getElementById('editPriority').value = priority;
        document.getElementById('editDifficulty').value = difficulty;

        const completeBtn = document.getElementById('completeBtn');

        if (completed === '1') {
            completeBtn.innerText = '↩ Mark as Undone';
            completeBtn.classList.add('is-completed');
        } else {
            completeBtn.innerText = '✓ Mark as Done';
            completeBtn.classList.remove('is-completed');
        }
    }
</script>

</x-app-layout>
