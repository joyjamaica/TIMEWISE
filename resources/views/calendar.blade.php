<x-app-layout>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        /* Calendar spacing: a full 18-hour canvas prevents class cards from being compressed. */
        #calendarCanvas { min-width: 900px; }
        #calendarCanvasBody { height: 1080px; min-height: 1080px; }
        #weekHeaderColumns, #calendarGridDays { min-width: 840px; }
        #weekHeaderColumns > div, #calendarGridDays > div { flex: 1 0 168px; min-width: 168px; }
        #calendarGridDays > div { background-image: repeating-linear-gradient(to bottom, transparent 0, transparent calc(100% / 18 - 1px), #F2EFEA calc(100% / 18 - 1px), #F2EFEA calc(100% / 18)); }
        .schedule-event-card { min-height: 58px; padding: 10px 11px !important; border-radius: 11px !important; }
        .schedule-event-card h4 { font-size: 12px; line-height: 1.35; white-space: normal; overflow-wrap: anywhere; }
        .schedule-event-card p { font-size: 10px; line-height: 1.35; margin-top: 5px; }
        @media (max-width: 1023px) {
            #calendarCanvas { min-width: 900px; }
            #calendarPanel { overflow-x: auto; }
        }
        @media (max-width: 640px) {
            #calendarPanel { border-radius: 20px; }
            #calendarCanvasBody { height: 1000px; min-height: 1000px; }
        }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #EAE3D9; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #D6C1AE; }
    </style>
</head>
<body class="min-h-screen bg-[#FDFBF7] text-[#2B2621] relative overflow-x-hidden selection:bg-[#EAD5C3]">

    <!-- Background Grid Pattern -->
    <div 
        class="fixed inset-0 pointer-events-none opacity-40 z-0"
        style="background-image: linear-gradient(to right, #EBE4DA 1px, transparent 1px), linear-gradient(to bottom, #EBE4DA 1px, transparent 1px); background-size: 80px 80px;"
    ></div>

    <div class="relative z-10 max-w-[1600px] mx-auto p-6 md:p-10">
        
        <!-- Header -->
        <header class="flex flex-col md:flex-row md:items-end justify-between mb-8 gap-4">
            <div>
                <p class="text-[11px] font-bold tracking-[0.15em] text-[#8C7A6B] uppercase mb-3">SMART SCHEDULE</p>
                <h1 class="text-5xl md:text-6xl font-extrabold tracking-tight text-[#2B2621] mb-2 leading-none">
                    Protect time for what matters<span class="text-[#CBA888]">.</span>
                </h1>
                <p class="text-[#8C7A6B] text-lg font-medium">Classes, deadlines, and study loads in one rhythm.</p>
            </div>
            <button 
                id="openImportBtn"
                class="bg-[#332D27] hover:bg-[#1A1613] text-white px-5 py-3 rounded-xl font-semibold flex items-center gap-2 transition-colors shadow-sm self-start md:self-auto shrink-0"
            >
                <i data-lucide="plus" class="w-5 h-5"></i>
                Import Schedule
            </button>
        </header>

        <!-- Toolbar Container -->
        <div class="bg-[#FCFAF8] border border-[#EBE4DA] rounded-2xl p-2 flex items-center justify-between mb-6 shadow-sm">
            <div class="flex bg-[#F2ECE4] p-1 rounded-xl">
                <button class="px-6 py-1.5 rounded-lg text-sm font-semibold transition-all bg-[#2B2621] text-white shadow-sm">
                    Month
                </button>
            </div>
            
            <div class="flex items-center gap-6 pr-4">
                <div class="flex items-center gap-2 text-[#8A7F75] text-xs font-semibold">
                    <div id="syncStatusDot" class="w-1.5 h-1.5 rounded-full bg-[#D4C9C0]"></div>
                    <span id="syncStatusText">Waiting for import</span>
                </div>
                <div class="flex items-center gap-3 text-sm font-semibold text-[#2B2621]">
                    Auto-Block Study Time
                    <button id="autoBlockToggle" class="w-11 h-6 rounded-full transition-colors relative flex items-center bg-[#A38A73]">
                        <div id="autoBlockKnob" class="w-4 h-4 bg-white rounded-full shadow-sm absolute transition-transform duration-200 translate-x-6"></div>
                    </button>
                </div>
            </div>
        </div>

        <!-- Alert Banner - Appears ONLY when genuine conflicts exist in imported schedule -->
        <div id="conflictBanner" class="hidden bg-[#F8EBE0] border border-[#EAD3C1] rounded-2xl p-4 flex items-center justify-between mb-8 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-[#B28260] flex items-center justify-center text-white">
                    <i data-lucide="alert-circle" class="w-4 h-4"></i>
                </div>
                <div>
                    <h4 class="font-bold text-sm text-[#2B2621]">Schedule conflict detected</h4>
                    <p class="text-xs text-[#827161]">Some imported classes or deadlines overlap with each other.</p>
                </div>
            </div>
            <button class="text-sm font-bold text-[#B28260] hover:text-[#8C5E40] transition-colors pr-2">
                Auto-reschedule
            </button>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            
            <!-- Left: Weekly Calendar (Takes 3 columns) -->
            <div id="calendarPanel" class="lg:col-span-3 bg-white border border-[#EAE3D9] rounded-3xl overflow-hidden shadow-sm flex flex-col min-h-[850px]">
                <div id="calendarCanvas">
                <!-- Calendar Header -->
                <div class="flex border-b border-[#EAE3D9]">
                    <div class="w-16 shrink-0 flex items-center justify-center py-4">
                        <span class="text-[10px] font-bold text-[#A89F93] uppercase tracking-widest">Time</span>
                    </div>
                    <div id="weekHeaderColumns" class="flex-1 flex">
                        <!-- Populated via JS -->
                    </div>
                </div>

                <!-- Calendar Body -->
                <div id="calendarCanvasBody" class="flex-none flex relative">
                    <!-- Time Labels Column (8 AM to 10 PM -> 14 hours total) -->
                    <div class="w-16 shrink-0 border-r border-[#EAE3D9] relative bg-white z-10">
                        <?php 
                        $hours = [5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23];
                        foreach ($hours as $hour) {
                            $top = (($hour - 5) / 18) * 100;
                            $label = $hour > 12 ? ($hour - 12) . ' PM' : ($hour === 12 ? '12 PM' : $hour . ' AM');
                            echo '<div class="absolute w-full text-center text-[10px] font-bold text-[#A89F93] transform -translate-y-1/2" style="top: ' . $top . '%;">' . $label . '</div>';
                        }
                        ?>
                    </div>

                    <!-- Grid Lines Overlay -->
                    <div class="absolute inset-0 left-16 right-0 pointer-events-none">
                        <?php 
                        foreach ($hours as $hour) {
                            $top = (($hour - 5) / 18) * 100;
                            echo '<div class="absolute w-full border-t border-[#F2EFEA]" style="top: ' . $top . '%;"></div>';
                        }
                        ?>
                    </div>

                    <!-- Day Columns -->
                    <div id="calendarGridDays" class="flex-1 flex relative">
                        <!-- Populated via JS -->
                    </div>
                </div>
                </div><!-- /calendarCanvas -->
            </div>

            <!-- Right: Daily Agenda (Takes 1 column) -->
            <div class="bg-[#2B211B] border border-[#49372B] rounded-3xl p-8 shadow-lg h-[750px] overflow-y-auto flex flex-col">
                <p id="agendaDayTitle" class="text-[10px] font-bold text-[#E7B98F] uppercase tracking-widest mb-2">THURSDAY 24</p>
                <h2 class="text-2xl font-semibold text-[#FFF9F2] tracking-tight mb-8">Daily agenda</h2>

                <div class="relative flex-1">
                    <div class="absolute left-[52px] top-2 bottom-2 w-px bg-[#604A3A]"></div>
                    <div id="agendaEventsList" class="flex flex-col gap-10">
                        <div class="text-center py-12 ml-6">
                            <p class="text-sm font-semibold text-[#F4E7DA]">No schedule imported yet.</p>
                            <p class="text-xs text-[#C7B4A3] mt-1">Click "Import Schedule" above to generate your calendar.</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Import Modal -->
    <div id="importModal" class="fixed inset-0 bg-[#2B2621]/40 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-3xl shadow-xl w-full max-w-2xl overflow-hidden flex flex-col">
            
            <div class="px-6 py-4 border-b border-[#EAE3D9] flex items-center justify-between bg-[#FCFAF8]">
                <h3 class="font-bold text-lg text-[#2B2621] flex items-center gap-2">
                    <i data-lucide="sparkles" class="w-5 h-5 text-[#B28260]"></i>
                    Import & Organize Schedule
                </h3>
                <button id="closeModalBtn" class="text-[#A89F93] hover:text-[#2B2621] transition-colors p-1">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <div class="p-6 flex flex-col gap-6 bg-[#FDFBF7]">
                <div id="modalError" class="p-3 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm hidden flex items-start gap-2">
                    <i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 shrink-0"></i> 
                    <span id="errorText"></span>
                </div>

                <div>
                    <label class="block text-sm font-bold text-[#2B2621] mb-2">Upload Syllabus / Tasks (PDF/Image)</label>
                    <div 
                        id="dropZone"
                        class="border-2 border-dashed border-[#EAE3D9] hover:border-[#CBA888] rounded-2xl p-6 text-center transition-all bg-white cursor-pointer"
                    >
                        <input type="file" id="scheduleFileInput" class="hidden" accept="image/jpeg,image/png,image/webp,application/pdf">
                        <div id="uploadPrompt" class="flex flex-col items-center">
                            <i data-lucide="upload-cloud" class="w-8 h-8 text-[#B28260] mb-3 opacity-80"></i>
                            <p class="text-sm font-semibold text-[#2B2621]">Click to upload or drag & drop</p>
                            <p class="text-xs text-[#A89F93] mt-1">PDF, JPG, PNG up to 5MB</p>
                        </div>
                        <div id="fileSelectedDisplay" class="flex items-center justify-center gap-3 hidden">
                            <i data-lucide="file-up" id="fileIcon" class="w-6 h-6 text-red-400"></i>
                            <span id="fileName" class="text-sm font-bold text-[#2B2621] truncate max-w-[200px]"></span>
                            <button id="removeFileBtn" type="button" class="text-[#A89F93] hover:text-red-500 ml-2">
                                <i data-lucide="x" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-[#2B2621] mb-2">Or Paste Raw Text</label>
                    <textarea
                        id="inputTextarea"
                        placeholder="e.g. Calculus II Mon/Wed 9:00 AM Room 402, Physics Lab Thu 1:00 PM..."
                        class="w-full p-4 bg-white border border-[#EAE3D9] rounded-2xl focus:ring-2 focus:ring-[#CBA888] focus:border-[#CBA888] outline-none resize-none h-32 text-sm text-[#2B2621] placeholder:text-[#A89F93]"
                    ></textarea>
                </div>

                <button
                    id="submitImportBtn"
                    class="w-full py-3.5 bg-[#332D27] text-white font-bold rounded-xl hover:bg-[#1A1613] transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 mt-2"
                >
                    <span id="btnText">Generate Smart Schedule</span>
                    <i data-lucide="chevron-right" class="w-4 h-4" id="btnChevron"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Frontend JavaScript Logic -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();

            let schedule = @json($savedSchedule ?? []);
            let currentWeekStart = getMonday(new Date());
            let selectedFile = null;
            let autoBlockEnabled = true;

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

            // Modal Toggles
            openBtn.addEventListener('click', () => modal.classList.remove('hidden'));
            closeBtn.addEventListener('click', () => modal.classList.add('hidden'));
            modal.addEventListener('click', (e) => { if (e.target === modal) modal.classList.add('hidden'); });

            // File Upload Interactivity
            dropZone.addEventListener('click', () => fileInput.click());
            fileInput.addEventListener('change', (e) => {
                const f = e.target.files[0];
                if (f) {
                    selectedFile = f;
                    fileNameDisplay.textContent = f.name;
                    uploadPrompt.classList.add('hidden');
                    fileSelectedDisplay.classList.remove('hidden');
                    errorBox.classList.add('hidden');
                }
            });

            removeFileBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                selectedFile = null;
                fileInput.value = '';
                fileSelectedDisplay.classList.add('hidden');
                uploadPrompt.classList.remove('hidden');
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

            // Submit Import Handler
            submitBtn.addEventListener('click', async () => {
                const textVal = inputTextarea.value.trim();
                if (!textVal && !selectedFile) {
                    errorText.textContent = "Please provide some text or upload a syllabus/assignment file.";
                    errorBox.classList.remove('hidden');
                    return;
                }

                submitBtn.disabled = true;
                btnText.textContent = "Generating Schedule...";
                errorBox.classList.add('hidden');

                const formData = new FormData();
                // Laravel route handles this request; no standalone PHP action field needed.
                formData.append('input_text', textVal);
                formData.append('week_start', currentWeekStart.toISOString().split('T')[0]);
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                if (selectedFile) {
                    formData.append('schedule_file', selectedFile);
                }

                try {
                    const response = await fetch("{{ route('smart-student-calendar.organize') }}", {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();

                    if (result.error) {
                        throw new Error(result.error);
                    }

                    if (Array.isArray(result)) {
                        schedule = result;
                        modal.classList.add('hidden');
                        inputTextarea.value = '';
                        selectedFile = null;
                        fileInput.value = '';
                        fileSelectedDisplay.classList.add('hidden');
                        uploadPrompt.classList.remove('hidden');
                        
                        // Update UI status
                        document.getElementById('syncStatusDot').className = 'w-1.5 h-1.5 rounded-full bg-[#8C9C86]';
                        document.getElementById('syncStatusText').textContent = 'Class schedule synced';

                        renderCalendar();
                    } else {
                        throw new Error("Invalid response structure from AI planner.");
                    }
                } catch (err) {
                    console.error(err);
                    errorText.textContent = err.message || "An error occurred while analyzing the file. Please try again.";
                    errorBox.classList.remove('hidden');
                } finally {
                    submitBtn.disabled = false;
                    btnText.textContent = "Generate Smart Schedule";
                }
            });

            function getMonday(d) {
                const date = new Date(d);
                const day = date.getDay();
                const diff = date.getDate() - day + (day === 0 ? -6 : 1);
                return new Date(date.setDate(diff));
            }

            function getWorkWeek(startDate) {
                const days = [];
                let current = new Date(startDate);
                for (let i = 0; i < 5; i++) {
                    days.push(new Date(current));
                    current.setDate(current.getDate() + 1);
                }
                return days;
            }

            function formatTime(isoString) {
                if (!isoString) return '';
                const date = new Date(isoString);
                let hours = date.getHours();
                let minutes = date.getMinutes();
                const ampm = hours >= 12 ? 'PM' : 'AM';
                hours = hours % 12;
                hours = hours ? hours : 12;
                minutes = minutes < 10 ? '0' + minutes : minutes;
                return `${hours}:${minutes} ${ampm}`;
            }

            function getDurationMinutes(start, end) {
                const diffMs = new Date(end) - new Date(start);
                return Math.round(diffMs / 60000);
            }

            function hasScheduleConflict(events) {
                if (!events || events.length < 2) return false;
                for (let i = 0; i < events.length; i++) {
                    for (let j = i + 1; j < events.length; j++) {
                        const startA = new Date(events[i].startTime).getTime();
                        const endA = new Date(events[i].endTime).getTime();
                        const startB = new Date(events[j].startTime).getTime();
                        const endB = new Date(events[j].endTime).getTime();
                        if (startA < endB && startB < endA) {
                            return true;
                        }
                    }
                }
                return false;
            }

            function renderCalendar() {
                const workWeek = getWorkWeek(currentWeekStart);

                // Render Week Header Columns
                const headerContainer = document.getElementById('weekHeaderColumns');
                headerContainer.innerHTML = '';
                workWeek.forEach((day) => {
                    const col = document.createElement('div');
                    col.className = 'py-4 text-center border-l border-[#EAE3D9]';
                    col.innerHTML = `<span class="text-xs font-bold text-[#2B2621]">${day.toLocaleDateString('en-US', { weekday: 'short' })} ${day.getDate()}</span>`;
                    headerContainer.appendChild(col);
                });

                // Render Grid Day Columns
                const gridDaysContainer = document.getElementById('calendarGridDays');
                gridDaysContainer.innerHTML = '';

                workWeek.forEach((day) => {
                    const dayCol = document.createElement('div');
                    dayCol.className = 'border-r border-[#EAE3D9] last:border-r-0 relative';

                    const dayEvents = schedule.filter(e => new Date(e.startTime).toDateString() === day.toDateString());
                    
                    dayEvents.forEach(event => {
                        const start = new Date(event.startTime);
                        const calendarStartHour = 5;
                        const calendarEndHour = 23;
                        const calendarDurationMinutes =
                            (calendarEndHour - calendarStartHour) * 60;

                        const startMinutes =
                            start.getHours() * 60 + start.getMinutes();

                        const topPercentage =
                            ((startMinutes - calendarStartHour * 60) /
                            calendarDurationMinutes) * 100;

                        const durationMins = getDurationMinutes(
                            event.startTime,
                            event.endTime
                        );

                        const heightPercentage =
                            (durationMins / calendarDurationMinutes) * 100;


                        const eventCard = document.createElement('div');
                        eventCard.className =
                        'schedule-event-card absolute left-2 right-2 rounded-xl p-3 overflow-hidden ' +
                        'shadow-sm transition-shadow hover:shadow-md ' +
                        'cursor-default bg-[#EAD5C3] border border-[#D9C4B2]';
                        eventCard.style.top = `${topPercentage}%`;
                        eventCard.style.height = `${Math.max(heightPercentage, 5.5)}%`;
                        eventCard.style.minHeight = '58px';
                        
                        eventCard.innerHTML = `
                            <h4 class="text-xs font-bold text-[#2B2621] leading-tight truncate">${event.subject || event.title}</h4>
                            <p class="text-[10px] text-[#7A6C5F] mt-0.5 font-semibold truncate">
                                ${event.room ? `${event.room} • ` : ''}${formatTime(event.startTime)}
                            </p>
                        `;
                        dayCol.appendChild(eventCard);
                    });

                    gridDaysContainer.appendChild(dayCol);
                });
                
                // Render Daily Agenda for today's date
                const agendaDay = new Date();
                agendaDay.setHours(0, 0, 0, 0);

                document.getElementById('agendaDayTitle').textContent =
                    agendaDay.toLocaleDateString('en-US', {
                        weekday: 'long',
                        day: 'numeric'
                    }).toUpperCase();

                const agendaEvents = schedule
                    .filter(event => {
                        const eventDate = new Date(event.startTime);
                        eventDate.setHours(0, 0, 0, 0);
                        return eventDate.getTime() === agendaDay.getTime();
                    })
                    .sort((a, b) =>
                        new Date(a.startTime) - new Date(b.startTime)
                    );

                const agendaList = document.getElementById('agendaEventsList');
                agendaList.innerHTML = '';

                if (agendaEvents.length === 0) {
                    agendaList.innerHTML = `
                        <div class="text-center py-12 ml-6">
                            <p class="text-sm font-semibold text-[#F4E7DA]">
                                No classes scheduled for this day.
                            </p>
                        </div>
                    `;
                } else {
                    agendaEvents.forEach(event => {
                        const item = document.createElement('div');
                        item.className = 'flex items-start gap-6 relative z-10';

                        item.innerHTML = `
                            <div class="w-10 text-right pt-0.5">
                                <span class="text-[11px] font-bold text-[#6D6257]">
                                    ${new Date(event.startTime).toLocaleTimeString([], {
                                        hour: '2-digit',
                                        minute: '2-digit'
                                    })}
                                </span>
                            </div>
                            <div class="shrink-0 w-4 h-4 rounded-full bg-[#2B211B] border-[3px] border-[#E7B98F] shadow-sm relative mt-0.5 ml-[-3px]"></div>
                            <div class="flex-1 pb-1">
                                <h4 class="text-sm font-bold text-[#FFF9F2]">
                                    ${event.subject || event.title}
                                </h4>
                                <p class="text-[11px] font-medium text-[#C7B4A3] mt-1">
                                    ${event.room ? `Room: ${event.room} • ` : ''}${getDurationMinutes(event.startTime, event.endTime)} mins
                                </p>
                            </div>
                        `;

                        agendaList.appendChild(item);
                    });
                }

                // Conflict Banner check
                const conflictBanner = document.getElementById('conflictBanner');
                if (hasScheduleConflict(schedule)) {
                    conflictBanner.classList.remove('hidden');
                    conflictBanner.classList.add('flex');
                } else {
                    conflictBanner.classList.add('hidden');
                    conflictBanner.classList.remove('flex');
                }
            }

            // Initial empty calendar render
            // Load previously saved schedule
            if (schedule.length > 0) {
                document.getElementById('syncStatusDot').className =
                    'w-1.5 h-1.5 rounded-full bg-[#8C9C86]';

                document.getElementById('syncStatusText').textContent =
                    'Saved schedule loaded';
            } else {
                document.getElementById('syncStatusText').textContent =
                    'Waiting for import';
            }

            // Display calendar
            renderCalendar();
        });
    </script>
@stack('scripts')
</body>
</x-app-layout>