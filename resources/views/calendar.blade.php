	@section('title', 'Scheduler')
<x-app-layout>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; }

        #calendarCanvas { min-width: 850px; }
        #calendarCanvasBody { height: 1080px; min-height: 1080px; }

        #weekHeaderColumns,
        #calendarGridDays {
            min-width: 784px;
        }

        #weekHeaderColumns > div,
        #calendarGridDays > div {
            flex: 1 0 112px;
            min-width: 112px;
        }

        #calendarGridDays > div {
            background-image: repeating-linear-gradient(
                to bottom,
                transparent 0,
                transparent calc(100% / 18 - 1px),
                #F2EFEA calc(100% / 18 - 1px),
                #F2EFEA calc(100% / 18)
            );
        }

        .schedule-event-card {
            min-height: 58px;
            padding: 9px 8px !important;
            border-radius: 10px !important;
        }

        .schedule-event-card h4 {
            font-size: 11px;
            line-height: 1.35;
            white-space: normal;
            overflow-wrap: anywhere;
        }

        .schedule-event-card p {
            font-size: 9px;
            line-height: 1.35;
            margin-top: 5px;
        }

        @media (max-width: 1023px) {
            #calendarPanel { overflow-x: auto; }
        }

        @media (max-width: 640px) {
            #calendarPanel { border-radius: 20px; }
            #calendarCanvasBody {
                height: 900px;
                min-height: 900px;
            }
        }

        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb {
            background: #EAE3D9;
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover { background: #D6C1AE; }
    </style>

    <div class="relative z-10 max-w-[1600px] mx-auto p-6 md:p-10">

        <!-- Header -->
        <header class="flex flex-col md:flex-row md:items-end justify-between mb-8 gap-4">
            <div>
                <p class="text-[11px] font-bold tracking-[0.15em] text-[#c18d66] uppercase mb-3">
                    SMART SCHEDULE
                </p>

                <h1 class="text-5xl md:text-6xl font-extrabold tracking-tight text-[#2B2621] mb-2 leading-none">
                    Protect time for what matters<span class="text-[#CBA888]">.</span>
                </h1>

                <p class="text-[#8C7A6B] text-lg font-medium">
                    Your recurring classes, organized throughout the semester.
                </p>
            </div>

            <button
                id="openImportBtn"
                type="button"
                class="bg-[#332D27] hover:bg-[#1A1613] text-white px-5 py-3 rounded-xl font-semibold flex items-center gap-2 transition-colors shadow-sm self-start md:self-auto shrink-0"
            >
                <i data-lucide="plus" class="w-5 h-5"></i>
                Import Study Load
            </button>
        </header>

        <!-- Semester Information -->
        <div class="bg-[#FCFAF8] border border-[#EBE4DA] rounded-2xl px-5 py-4 mb-6 flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div>
                <p class="text-[10px] font-bold tracking-widest text-[#A38A73] uppercase mb-1">
                    CURRENT SEMESTER
                </p>

                <p id="semesterDisplay" class="text-sm font-bold text-[#2B2621]">
                    No semester schedule loaded
                </p>
            </div>

            <div class="flex items-center gap-2 text-[#8A7F75] text-xs font-semibold">
                <div id="syncStatusDot" class="w-2 h-2 rounded-full bg-[#D4C9C0]"></div>
                <span id="syncStatusText">Waiting for import</span>
            </div>
        </div>

        <!-- Weekly Toolbar -->
        <div class="bg-[#FCFAF8] border border-[#EBE4DA] rounded-2xl p-3 flex flex-col md:flex-row items-center justify-between gap-4 mb-6 shadow-sm">

            <div class="flex items-center gap-2">
                <button
                    id="previousWeekBtn"
                    type="button"
                    class="w-9 h-9 flex items-center justify-center rounded-lg border border-[#EAE3D9] hover:bg-[#F2ECE4] transition"
                    title="Previous week"
                >
                    <i data-lucide="chevron-left" class="w-4 h-4"></i>
                </button>

                <button
                    id="todayBtn"
                    type="button"
                    class="px-4 py-2 rounded-lg text-sm font-semibold text-[#5C4C3E] hover:bg-[#F2ECE4] transition"
                >
                    Today
                </button>

                <button
                    id="nextWeekBtn"
                    type="button"
                    class="w-9 h-9 flex items-center justify-center rounded-lg border border-[#EAE3D9] hover:bg-[#F2ECE4] transition"
                    title="Next week"
                >
                    <i data-lucide="chevron-right" class="w-4 h-4"></i>
                </button>

                <span id="weekRangeDisplay" class="ml-2 text-sm font-bold text-[#2B2621]">
                    Loading week...
                </span>
            </div>

            <div class="flex items-center gap-3 text-sm font-semibold text-[#2B2621]">
                Auto-Block Study Time

                <button
                    id="autoBlockToggle"
                    type="button"
                    class="w-11 h-6 rounded-full transition-colors relative flex items-center bg-[#A38A73]"
                >
                    <div
                        id="autoBlockKnob"
                        class="w-4 h-4 bg-white rounded-full shadow-sm absolute transition-transform duration-200 translate-x-6"
                    ></div>
                </button>
            </div>
        </div>

        <!-- Conflict Banner -->
        <div
            id="conflictBanner"
            class="hidden bg-[#F8EBE0] border border-[#EAD3C1] rounded-2xl p-4 items-center justify-between mb-8 shadow-sm"
        >
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-[#B28260] flex items-center justify-center text-white">
                    <i data-lucide="alert-circle" class="w-4 h-4"></i>
                </div>

                <div>
                    <h4 class="font-bold text-sm text-[#2B2621]">
                        Schedule conflict detected
                    </h4>
                    <p class="text-xs text-[#827161]">
                        Some recurring classes overlap within this week.
                    </p>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">

            <!-- Weekly Calendar -->
            <div
                id="calendarPanel"
                class="lg:col-span-3 bg-white border border-[#EAE3D9] rounded-3xl overflow-hidden shadow-sm flex flex-col min-h-[850px]"
            >
                <div id="calendarCanvas">

                    <!-- Calendar Header -->
                    <div class="flex border-b border-[#EAE3D9]">
                        <div class="w-16 shrink-0 flex items-center justify-center py-4">
                            <span class="text-[10px] font-bold text-[#A89F93] uppercase tracking-widest">
                                Time
                            </span>
                        </div>

                        <div id="weekHeaderColumns" class="flex-1 flex"></div>
                    </div>

                    <!-- Calendar Body -->
                    <div id="calendarCanvasBody" class="flex-none flex relative">

                        <!-- Time Labels -->
                        <div class="w-16 shrink-0 border-r border-[#EAE3D9] relative bg-white z-10">
                            <?php
                                $hours = range(7, 21);

                                foreach ($hours as $hour) {
                                    $top = (($hour - 7) / 14.5) * 100;

                                    $label = $hour > 12
                                        ? ($hour - 12) . ' PM'
                                        : ($hour === 12 ? '12 PM' : $hour . ' AM');

                                    echo '<div class="absolute w-full text-center text-[10px] font-bold text-[#A89F93] transform -translate-y-1/2" style="top: ' . $top . '%;">' . $label . '</div>';
                                }
                            ?>
                        </div>

                        <!-- Grid Lines -->
                        <div class="absolute inset-0 left-16 right-0 pointer-events-none">
                            <?php
                                foreach ($hours as $hour) {
                                    $top = (($hour - 5) / 18) * 100;

                                    echo '<div class="absolute w-full border-t border-[#F2EFEA]" style="top: ' . $top . '%;"></div>';
                                }
                            ?>
                        </div>

                        <!-- Seven Day Columns -->
                        <div id="calendarGridDays" class="flex-1 flex relative"></div>
                    </div>
                </div>
            </div>

            <!-- Daily Agenda -->
            <div class="bg-[#2B211B] border border-[#49372B] rounded-3xl p-8 shadow-lg h-[750px] overflow-y-auto flex flex-col">

                <p id="agendaDayTitle" class="text-[10px] font-bold text-[#E7B98F] uppercase tracking-widest mb-2">
                    TODAY
                </p>

                <h2 class="text-2xl font-semibold text-[#FFF9F2] tracking-tight mb-8">
                    Daily agenda
                </h2>

                <div class="relative flex-1">
                    <div class="absolute left-[52px] top-2 bottom-2 w-px bg-[#604A3A]"></div>

                    <div id="agendaEventsList" class="flex flex-col gap-10">
                        <div class="text-center py-12 ml-6">
                            <p class="text-sm font-semibold text-[#F4E7DA]">
                                No classes scheduled yet.
                            </p>
                            <p class="text-xs text-[#C7B4A3] mt-1">
                                Import your semester study load to get started.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Import Modal -->
    <div
        id="importModal"
        class="fixed inset-0 bg-[#2B2621]/40 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden"
    >
        <div class="bg-white rounded-3xl shadow-xl w-full max-w-2xl overflow-hidden flex flex-col max-h-[90vh] overflow-y-auto">

            <div class="px-6 py-4 border-b border-[#EAE3D9] flex items-center justify-between bg-[#FCFAF8]">
                <h3 class="font-bold text-lg text-[#2B2621] flex items-center gap-2">
                    <i data-lucide="sparkles" class="w-5 h-5 text-[#B28260]"></i>
                    Import Semester Study Load
                </h3>

                <button
                    id="closeModalBtn"
                    type="button"
                    class="text-[#A89F93] hover:text-[#2B2621] transition-colors p-1"
                >
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <div class="p-6 flex flex-col gap-6 bg-[#FDFBF7]">

                <div
                    id="modalError"
                    class="p-3 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm hidden flex items-start gap-2"
                >
                    <i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 shrink-0"></i>
                    <span id="errorText"></span>
                </div>

                <!-- Semester Dates -->
                <div>
                    <h4 class="text-sm font-bold text-[#2B2621] mb-3">
                        Semester Duration
                    </h4>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="semesterStartInput" class="block text-xs font-bold text-[#827161] mb-2">
                                Semester Start Date
                            </label>

                            <input
                                type="date"
                                id="semesterStartInput"
                                value="{{ $semesterStart ?? '' }}"
                                class="w-full p-3 bg-white border border-[#EAE3D9] rounded-xl text-sm text-[#2B2621] focus:ring-2 focus:ring-[#CBA888] outline-none"
                            >
                        </div>

                        <div>
                            <label for="semesterEndInput" class="block text-xs font-bold text-[#827161] mb-2">
                                Semester End Date
                            </label>

                            <input
                                type="date"
                                id="semesterEndInput"
                                value="{{ $semesterEnd ?? '' }}"
                                class="w-full p-3 bg-white border border-[#EAE3D9] rounded-xl text-sm text-[#2B2621] focus:ring-2 focus:ring-[#CBA888] outline-none"
                            >
                        </div>
                    </div>

                    <p class="text-[11px] text-[#A89F93] mt-2">
                        Your recurring classes will appear automatically between these dates.
                    </p>
                </div>

                <div class="border-t border-[#EAE3D9]"></div>

                <!-- File Upload -->
                <div>
                    <label class="block text-sm font-bold text-[#2B2621] mb-2">
                        Upload Official Study Load (PDF/Image)
                    </label>

                    <div
                        id="dropZone"
                        class="border-2 border-dashed border-[#EAE3D9] hover:border-[#CBA888] rounded-2xl p-6 text-center transition-all bg-white cursor-pointer"
                    >
                        <input
                            type="file"
                            id="scheduleFileInput"
                            class="hidden"
                            accept="image/jpeg,image/png,image/webp,application/pdf"
                        >

                        <div id="uploadPrompt" class="flex flex-col items-center">
                            <i data-lucide="upload-cloud" class="w-8 h-8 text-[#B28260] mb-3 opacity-80"></i>

                            <p class="text-sm font-semibold text-[#2B2621]">
                                Click to upload or drag & drop
                            </p>

                            <p class="text-xs text-[#A89F93] mt-1">
                                PDF, JPG, PNG up to 5MB
                            </p>
                        </div>

                        <div id="fileSelectedDisplay" class="flex items-center justify-center gap-3 hidden">
                            <i data-lucide="file-up" id="fileIcon" class="w-6 h-6 text-red-400"></i>

                            <span id="fileName" class="text-sm font-bold text-[#2B2621] truncate max-w-[200px]"></span>

                            <button
                                id="removeFileBtn"
                                type="button"
                                class="text-[#A89F93] hover:text-red-500 ml-2"
                            >
                                <i data-lucide="x" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Text Input -->
                <div>
                    <label class="block text-sm font-bold text-[#2B2621] mb-2">
                        Or Paste Your Study Load
                    </label>

                    <textarea
                        id="inputTextarea"
                        placeholder="Example: Programming 2, Monday and Wednesday, 9:00 AM to 10:30 AM, Room 402..."
                        class="w-full p-4 bg-white border border-[#EAE3D9] rounded-2xl focus:ring-2 focus:ring-[#CBA888] focus:border-[#CBA888] outline-none resize-none h-32 text-sm text-[#2B2621] placeholder:text-[#A89F93]"
                    ></textarea>
                </div>

                <button
                    id="submitImportBtn"
                    type="button"
                    class="w-full py-3.5 bg-[#332D27] text-white font-bold rounded-xl hover:bg-[#1A1613] transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 mt-2"
                >
                    <span id="btnText">Generate Semester Schedule</span>
                    <i data-lucide="chevron-right" class="w-4 h-4" id="btnChevron"></i>
                </button>

            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {

            lucide.createIcons();

            let schedule = @json($savedSchedule ?? []);
            let semesterStart = @json($semesterStart ?? null);
            let semesterEnd = @json($semesterEnd ?? null);

            let currentWeekStart = getMonday(new Date());
            let selectedDate = new Date();
            let selectedFile = null;
            let autoBlockEnabled = true;

            const weekdays = [
                'Monday',
                'Tuesday',
                'Wednesday',
                'Thursday',
                'Friday',
                'Saturday',
                'Sunday'
            ];

            const modal = document.getElementById('importModal');
            const openBtn = document.getElementById('openImportBtn');
            const closeBtn = document.getElementById('closeModalBtn');
            const dropZone = document.getElementById('dropZone');
            const fileInput = document.getElementById('scheduleFileInput');
            const submitBtn = document.getElementById('submitImportBtn');
            const btnText = document.getElementById('btnText');
            const errorBox = document.getElementById('modalError');
            const errorText = document.getElementById('errorText');
            const inputTextarea = document.getElementById('inputTextarea');
            const fileSelectedDisplay = document.getElementById('fileSelectedDisplay');
            const uploadPrompt = document.getElementById('uploadPrompt');
            const fileNameDisplay = document.getElementById('fileName');
            const removeFileBtn = document.getElementById('removeFileBtn');

            const semesterStartInput = document.getElementById('semesterStartInput');
            const semesterEndInput = document.getElementById('semesterEndInput');

            // Date helpers use local dates to avoid UTC date shifts.
            function dateKey(date) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');

                return `${year}-${month}-${day}`;
            }

            function parseLocalDate(value) {
                if (!value) return null;

                const parts = value.split('-').map(Number);

                if (parts.length !== 3 || parts.some(Number.isNaN)) {
                    return null;
                }

                return new Date(parts[0], parts[1] - 1, parts[2], 0, 0, 0, 0);
            }

            function getMonday(date) {
                const result = new Date(date);
                result.setHours(0, 0, 0, 0);

                const day = result.getDay();
                const difference = day === 0 ? -6 : 1 - day;

                result.setDate(result.getDate() + difference);

                return result;
            }

            function addDays(date, amount) {
                const result = new Date(date);
                result.setDate(result.getDate() + amount);

                return result;
            }

            function getWeekDays(startDate) {
                return Array.from({ length: 7 }, (_, index) => {
                    return addDays(startDate, index);
                });
            }

            function formatDisplayTime(time) {
                if (!time) return '';

                const [hours, minutes] = time.split(':').map(Number);

                const date = new Date();
                date.setHours(hours, minutes, 0, 0);

                return date.toLocaleTimeString('en-US', {
                    hour: 'numeric',
                    minute: '2-digit'
                });
            }

            function getTimeInMinutes(time) {
                const [hours, minutes] = time.split(':').map(Number);
                return hours * 60 + minutes;
            }

            function getDurationMinutes(startTime, endTime) {
                return getTimeInMinutes(endTime) - getTimeInMinutes(startTime);
            }

            function isWithinSemester(date) {
                if (!semesterStart || !semesterEnd) return false;

                const key = dateKey(date);

                return key >= semesterStart && key <= semesterEnd;
            }

            function getClassesForDate(date) {
                if (!isWithinSemester(date)) return [];

                const dayName = weekdays[(date.getDay() + 6) % 7];

                return schedule
                    .filter(classItem => {
                        return Array.isArray(classItem.days) &&
                            classItem.days.includes(dayName);
                    })
                    .map(classItem => ({
                        ...classItem,
                        date: new Date(date),
                        dateKey: dateKey(date)
                    }))
                    .sort((a, b) => {
                        return getTimeInMinutes(a.startTime) -
                            getTimeInMinutes(b.startTime);
                    });
            }

            function getClassesForWeek(startDate) {
                const events = [];

                getWeekDays(startDate).forEach(day => {
                    getClassesForDate(day).forEach(classItem => {
                        events.push(classItem);
                    });
                });

                return events;
            }

            function hasScheduleConflict(events) {
                if (!events || events.length < 2) return false;

                for (let i = 0; i < events.length; i++) {
                    for (let j = i + 1; j < events.length; j++) {

                        if (events[i].dateKey !== events[j].dateKey) {
                            continue;
                        }

                        const startA = getTimeInMinutes(events[i].startTime);
                        const endA = getTimeInMinutes(events[i].endTime);

                        const startB = getTimeInMinutes(events[j].startTime);
                        const endB = getTimeInMinutes(events[j].endTime);

                        if (startA < endB && startB < endA) {
                            return true;
                        }
                    }
                }

                return false;
            }

            function updateSemesterDisplay() {
                const display = document.getElementById('semesterDisplay');

                if (semesterStart && semesterEnd) {
                    const start = parseLocalDate(semesterStart);
                    const end = parseLocalDate(semesterEnd);

                    display.textContent =
                        start.toLocaleDateString('en-US', {
                            month: 'short',
                            day: 'numeric',
                            year: 'numeric'
                        }) +
                        ' - ' +
                        end.toLocaleDateString('en-US', {
                            month: 'short',
                            day: 'numeric',
                            year: 'numeric'
                        });
                } else {
                    display.textContent = 'No semester schedule loaded';
                }
            }

            function updateWeekRange() {
                const weekDays = getWeekDays(currentWeekStart);
                const first = weekDays[0];
                const last = weekDays[6];

                const startText = first.toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric'
                });

                const endText = last.toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric'
                });

                document.getElementById('weekRangeDisplay').textContent =
                    `${startText} - ${endText}`;
            }

            // Modal controls
            openBtn.addEventListener('click', () => {
                modal.classList.remove('hidden');
            });

            closeBtn.addEventListener('click', () => {
                modal.classList.add('hidden');
            });

            modal.addEventListener('click', event => {
                if (event.target === modal) {
                    modal.classList.add('hidden');
                }
            });

            // File upload
            dropZone.addEventListener('click', () => {
                fileInput.click();
            });

            fileInput.addEventListener('change', event => {
                const file = event.target.files[0];

                if (file) {
                    selectedFile = file;
                    fileNameDisplay.textContent = file.name;

                    uploadPrompt.classList.add('hidden');
                    fileSelectedDisplay.classList.remove('hidden');
                    errorBox.classList.add('hidden');
                }
            });

            removeFileBtn.addEventListener('click', event => {
                event.stopPropagation();

                selectedFile = null;
                fileInput.value = '';

                fileSelectedDisplay.classList.add('hidden');
                uploadPrompt.classList.remove('hidden');
            });

            // Drag and drop
            dropZone.addEventListener('dragover', event => {
                event.preventDefault();
                dropZone.classList.add('border-[#CBA888]');
            });

            dropZone.addEventListener('dragleave', () => {
                dropZone.classList.remove('border-[#CBA888]');
            });

            dropZone.addEventListener('drop', event => {
                event.preventDefault();
                dropZone.classList.remove('border-[#CBA888]');

                const file = event.dataTransfer.files[0];

                if (file) {
                    const allowedTypes = [
                        'image/jpeg',
                        'image/png',
                        'image/webp',
                        'application/pdf'
                    ];

                    if (!allowedTypes.includes(file.type)) {
                        errorText.textContent = 'Please upload a PDF, JPG, PNG, or WEBP file.';
                        errorBox.classList.remove('hidden');
                        return;
                    }

                    if (file.size > 5 * 1024 * 1024) {
                        errorText.textContent = 'The file must not exceed 5MB.';
                        errorBox.classList.remove('hidden');
                        return;
                    }

                    selectedFile = file;
                    fileNameDisplay.textContent = file.name;

                    uploadPrompt.classList.add('hidden');
                    fileSelectedDisplay.classList.remove('hidden');
                    errorBox.classList.add('hidden');
                }
            });

            // Auto-block toggle
            const autoBlockToggle = document.getElementById('autoBlockToggle');
            const autoBlockKnob = document.getElementById('autoBlockKnob');

            autoBlockToggle.addEventListener('click', () => {
                autoBlockEnabled = !autoBlockEnabled;

                if (autoBlockEnabled) {
                    autoBlockToggle.classList.remove('bg-[#D4C9C0]');
                    autoBlockToggle.classList.add('bg-[#A38A73]');
                    autoBlockKnob.style.transform = 'translateX(24px)';
                } else {
                    autoBlockToggle.classList.remove('bg-[#A38A73]');
                    autoBlockToggle.classList.add('bg-[#D4C9C0]');
                    autoBlockKnob.style.transform = 'translateX(4px)';
                }
            });

            // Week navigation
            document.getElementById('previousWeekBtn').addEventListener('click', () => {
                currentWeekStart = addDays(currentWeekStart, -7);
                selectedDate = new Date(currentWeekStart);
                renderCalendar();
            });

            document.getElementById('nextWeekBtn').addEventListener('click', () => {
                currentWeekStart = addDays(currentWeekStart, 7);
                selectedDate = new Date(currentWeekStart);
                renderCalendar();
            });

            document.getElementById('todayBtn').addEventListener('click', () => {
                selectedDate = new Date();
                currentWeekStart = getMonday(selectedDate);
                renderCalendar();
            });

            // Submit import
            submitBtn.addEventListener('click', async () => {

                const textVal = inputTextarea.value.trim();
                const startDate = semesterStartInput.value;
                const endDate = semesterEndInput.value;

                if (!textVal && !selectedFile) {
                    errorText.textContent =
                        'Please paste your study load or upload a schedule file.';

                    errorBox.classList.remove('hidden');
                    return;
                }

                if (!startDate || !endDate) {
                    errorText.textContent =
                        'Please select both the semester start and end dates.';

                    errorBox.classList.remove('hidden');
                    return;
                }

                if (endDate < startDate) {
                    errorText.textContent =
                        'The semester end date cannot be earlier than the start date.';

                    errorBox.classList.remove('hidden');
                    return;
                }

                submitBtn.disabled = true;
                btnText.textContent = 'Analyzing Study Load...';
                errorBox.classList.add('hidden');

                const formData = new FormData();

                formData.append('input_text', textVal);
                formData.append('semester_start', startDate);
                formData.append('semester_end', endDate);
                formData.append(
                    '_token',
                    document.querySelector('meta[name="csrf-token"]').content
                );

                if (selectedFile) {
                    formData.append('schedule_file', selectedFile);
                }

                try {
                    const response = await fetch(
                        "{{ route('smart-student-calendar.organize') }}",
                        {
                            method: 'POST',
                            body: formData
                        }
                    );

                    const result = await response.json();

                    if (!response.ok || result.error) {
                        throw new Error(
                            result.error || 'Unable to generate the semester schedule.'
                        );
                    }

                    if (!Array.isArray(result)) {
                        throw new Error('Invalid response structure from the AI planner.');
                    }

                    schedule = result;
                    semesterStart = startDate;
                    semesterEnd = endDate;

                    // Move to the current week or the semester's first week.
                    const today = new Date();

                    if (
                        dateKey(today) >= semesterStart &&
                        dateKey(today) <= semesterEnd
                    ) {
                        selectedDate = today;
                    } else {
                        selectedDate = parseLocalDate(semesterStart);
                    }

                    currentWeekStart = getMonday(selectedDate);

                    // Reset form
                    inputTextarea.value = '';
                    selectedFile = null;
                    fileInput.value = '';

                    fileSelectedDisplay.classList.add('hidden');
                    uploadPrompt.classList.remove('hidden');

                    modal.classList.add('hidden');

                    // Update status
                    document.getElementById('syncStatusDot').className =
                        'w-2 h-2 rounded-full bg-[#8C9C86]';

                    document.getElementById('syncStatusText').textContent =
                        'Semester schedule synced';

                    updateSemesterDisplay();
                    renderCalendar();

                } catch (error) {
                    console.error(error);

                    errorText.textContent =
                        error.message || 'An error occurred. Please try again.';

                    errorBox.classList.remove('hidden');

                } finally {
                    submitBtn.disabled = false;
                    btnText.textContent = 'Generate Semester Schedule';
                }
            });

            // Render weekly calendar
            function renderCalendar() {

                const weekDays = getWeekDays(currentWeekStart);

                updateWeekRange();

                const headerContainer =
                    document.getElementById('weekHeaderColumns');

                const gridDaysContainer =
                    document.getElementById('calendarGridDays');

                headerContainer.innerHTML = '';
                gridDaysContainer.innerHTML = '';

                // Header columns
                weekDays.forEach(day => {

                    const dayName = weekdays[(day.getDay() + 6) % 7];
                    const isToday = dateKey(day) === dateKey(new Date());
                    const isSelected = dateKey(day) === dateKey(selectedDate);

                    const col = document.createElement('div');

                    col.className =
                        'py-4 text-center border-l border-[#EAE3D9] cursor-pointer transition ' +
                        (isSelected ? 'bg-[#F8F1EA]' : 'hover:bg-[#FCFAF8]');

                    col.innerHTML = `
                        <span class="block text-[10px] font-bold tracking-wide uppercase ${
                            isToday ? 'text-[#B28260]' : 'text-[#A89F93]'
                        }">
                            ${dayName.substring(0, 3)}
                        </span>

                        <span class="block mt-1 text-sm font-bold ${
                            isSelected ? 'text-[#B28260]' : 'text-[#2B2621]'
                        }">
                            ${day.getDate()}
                        </span>
                    `;

                    col.addEventListener('click', () => {
                        selectedDate = new Date(day);
                        renderCalendar();
                    });

                    headerContainer.appendChild(col);
                });

                // Day grid columns
                weekDays.forEach(day => {

                    const dayCol = document.createElement('div');

                    dayCol.className =
                        'border-r border-[#EAE3D9] last:border-r-0 relative';

                    const dayEvents = getClassesForDate(day);

                    dayEvents.forEach(event => {

                        const startMinutes = getTimeInMinutes(event.startTime);
                        const durationMinutes = getDurationMinutes(
                            event.startTime,
                            event.endTime
                        );

                        const calendarStartHour = 5;
                        const calendarEndHour = 23;
                        const calendarDurationMinutes =
                            (calendarEndHour - calendarStartHour) * 60;

                        const topPercentage =
                            ((startMinutes - calendarStartHour * 60) /
                                calendarDurationMinutes) * 100;

                        const heightPercentage =
                            (durationMinutes / calendarDurationMinutes) * 100;

                        // Hide classes outside the displayed calendar hours.
                        if (
                            startMinutes < calendarStartHour * 60 ||
                            startMinutes >= calendarEndHour * 60
                        ) {
                            return;
                        }

                        const eventCard = document.createElement('div');

                        eventCard.className =
                            'schedule-event-card absolute left-1 right-1 rounded-xl p-2 overflow-hidden shadow-sm transition-shadow hover:shadow-md cursor-default bg-[#EAD5C3] border border-[#D9C4B2]';

                        eventCard.style.top = `${topPercentage}%`;
                        eventCard.style.height = `${Math.max(heightPercentage, 5.5)}%`;
                        eventCard.style.minHeight = '58px';

                        const subject = document.createElement('h4');
                        subject.className = 'font-bold text-[#2B2621] leading-tight';
                        subject.textContent = event.subject || event.title;

                        const details = document.createElement('p');
                        details.className = 'text-[#7A6C5F] font-semibold';

                        details.textContent =
                            `${event.room ? event.room + ' • ' : ''}` +
                            `${formatDisplayTime(event.startTime)} - ${formatDisplayTime(event.endTime)}`;

                        eventCard.appendChild(subject);
                        eventCard.appendChild(details);

                        dayCol.appendChild(eventCard);
                    });

                    gridDaysContainer.appendChild(dayCol);
                });

                // Daily agenda for the selected day
                renderDailyAgenda();

                // Check conflicts for this displayed week
                const weeklyEvents = getClassesForWeek(currentWeekStart);

                const conflictBanner =
                    document.getElementById('conflictBanner');

                if (hasScheduleConflict(weeklyEvents)) {
                    conflictBanner.classList.remove('hidden');
                    conflictBanner.classList.add('flex');
                } else {
                    conflictBanner.classList.add('hidden');
                    conflictBanner.classList.remove('flex');
                }
            }

            // Render daily agenda
            function renderDailyAgenda() {

                const agendaDay = new Date(selectedDate);

                const agendaTitle =
                    document.getElementById('agendaDayTitle');

                agendaTitle.textContent =
                    agendaDay.toLocaleDateString('en-US', {
                        weekday: 'long',
                        day: 'numeric',
                        month: 'long'
                    }).toUpperCase();

                const agendaList =
                    document.getElementById('agendaEventsList');

                agendaList.innerHTML = '';

                const agendaEvents = getClassesForDate(agendaDay);

                if (agendaEvents.length === 0) {

                    const emptyState = document.createElement('div');

                    emptyState.className = 'text-center py-12 ml-6';

                    emptyState.innerHTML = `
                        <p class="text-sm font-semibold text-[#F4E7DA]">
                            No classes scheduled for this day.
                        </p>
                        <p class="text-xs text-[#C7B4A3] mt-2">
                            Enjoy your free time!
                        </p>
                    `;

                    agendaList.appendChild(emptyState);
                    return;
                }

                agendaEvents.forEach(event => {

                    const item = document.createElement('div');

                    item.className =
                        'flex items-start gap-6 relative z-10';

                    const timeColumn = document.createElement('div');
                    timeColumn.className = 'w-10 text-right pt-0.5';

                    const timeLabel = document.createElement('span');
                    timeLabel.className =
                        'text-[11px] font-bold text-[#C7B4A3]';

                    timeLabel.textContent =
                        formatDisplayTime(event.startTime);

                    timeColumn.appendChild(timeLabel);

                    const marker = document.createElement('div');
                    marker.className =
                        'shrink-0 w-4 h-4 rounded-full bg-[#2B211B] border-[3px] border-[#E7B98F] shadow-sm relative mt-0.5 ml-[-3px]';

                    const details = document.createElement('div');
                    details.className = 'flex-1 pb-1';

                    const title = document.createElement('h4');
                    title.className =
                        'text-sm font-bold text-[#FFF9F2]';

                    title.textContent = event.subject || event.title;

                    const description = document.createElement('p');
                    description.className =
                        'text-[11px] font-medium text-[#C7B4A3] mt-1';

                    const duration = getDurationMinutes(
                        event.startTime,
                        event.endTime
                    );

                    description.textContent =
                        `${event.room ? 'Room: ' + event.room + ' • ' : ''}` +
                        `${formatDisplayTime(event.startTime)} - ${formatDisplayTime(event.endTime)} • ${duration} mins`;

                    details.appendChild(title);
                    details.appendChild(description);

                    item.appendChild(timeColumn);
                    item.appendChild(marker);
                    item.appendChild(details);

                    agendaList.appendChild(item);
                });
            }

            // Initial setup
            updateSemesterDisplay();

            if (schedule.length > 0) {

                document.getElementById('syncStatusDot').className =
                    'w-2 h-2 rounded-full bg-[#8C9C86]';

                document.getElementById('syncStatusText').textContent =
                    'Saved semester schedule loaded';

            } else {

                document.getElementById('syncStatusText').textContent =
                    'Waiting for import';
            }

            renderCalendar();

        });
    </script>

    @stack('scripts')
</x-app-layout>
