// TIMEWISE Dashboard behaviour.
document.addEventListener('DOMContentLoaded', function () {

    var TZ = 'Asia/Manila';
    var pad = function (n) { return String(n).padStart(2, '0'); };

    // Shared time source --------------------------------------------------
    // The server sends its own Manila time (data-server-now). We keep the
    // difference to the visitor's clock so both widgets stay correct even if
    // the computer's clock is wrong. Timezone never matters here: every
    // timestamp is an absolute moment (ISO 8601 with offset).
    var root = document.getElementById('twDash');
    var serverNow = root ? Date.parse(root.dataset.serverNow) : NaN;
    var skew = isNaN(serverNow) ? 0 : serverNow - Date.now();
    var now = function () { return Date.now() + skew; };

    // 1. Live digital clock (independent of the class countdown) ---------
    var timeEl = document.getElementById('twClockTime');
    var ampmEl = document.getElementById('twClockAmPm');
    var dateEl = document.getElementById('twClockDate');

    if (timeEl && ampmEl && dateEl) {
        var timeFmt = new Intl.DateTimeFormat('en-US', {
            timeZone: TZ, hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true
        });
        var dateFmt = new Intl.DateTimeFormat('en-US', {
            timeZone: TZ, weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
        });

        var renderClock = function () {
            var d = new Date(now());
            var p = {};
            timeFmt.formatToParts(d).forEach(function (part) { p[part.type] = part.value; });
            timeEl.textContent = p.hour + ':' + p.minute + ':' + p.second;
            ampmEl.textContent = String(p.dayPeriod || '').toUpperCase();
            dateEl.textContent = dateFmt.format(d);
        };

        // Re-sync to the next whole second so the display does not drift.
        var clockLoop = function () {
            renderClock();
            setTimeout(clockLoop, 1000 - (now() % 1000) + 5);
        };
        clockLoop();
    }

    // 2. Up Next countdown (real timestamps from calendar_schedules) -----
    var card = document.getElementById('twClass');
    if (card) {
        var countdown = document.getElementById('twCountdown');
        var progress = document.getElementById('twProgress');
        var left = document.getElementById('twLeft');

        var mode = card.dataset.mode; // "upcoming" or "ongoing"
        var startMs = Date.parse(card.dataset.start);
        var endMs = Date.parse(card.dataset.end);
        var targetMs = mode === 'ongoing' ? endMs : startMs;
        var WINDOW_MS = 60 * 60 * 1000; // progress fills during the last hour before start

        if (!isNaN(startMs) && !isNaN(endMs)) {
            var timer;

            // After the countdown hits zero, reload once so the server can
            // switch the card (upcoming -> ongoing -> next class).
            var reloadOnce = function () {
                var key = 'twReload:' + targetMs;
                try {
                    if (sessionStorage.getItem(key)) { return; }
                    sessionStorage.setItem(key, '1');
                } catch (e) { /* storage unavailable: still reload once below */ }
                setTimeout(function () { window.location.reload(); }, 1500);
            };

            var tick = function () {
                var remainingMs = Math.max(0, targetMs - now());
                var remaining = Math.ceil(remainingMs / 1000);
                var h = Math.floor(remaining / 3600);
                var m = Math.floor((remaining % 3600) / 60);
                var s = remaining % 60;

                countdown.textContent = pad(h) + ':' + pad(m) + ':' + pad(s);

                var pct = mode === 'ongoing'
                    ? ((now() - startMs) / (endMs - startMs)) * 100
                    : (1 - remainingMs / WINDOW_MS) * 100;
                progress.style.width = Math.min(100, Math.max(0, pct)) + '%';

                var mins = Math.ceil(remaining / 60);
                if (remaining === 0) {
                    left.textContent = mode === 'ongoing' ? 'Finished' : 'Started';
                } else {
                    left.textContent = mins + (mode === 'ongoing' ? ' min left' : ' min');
                }

                if (remaining === 0) {
                    clearInterval(timer);
                    reloadOnce();
                }
            };

            tick();
            timer = setInterval(tick, 1000);
        } else {
            countdown.textContent = '--:--:--';
        }
    }

    // 3. Task checkboxes: strike-through and save to the database ----------
    var csrf = root ? root.dataset.csrf : '';
    var toggleBase = root ? root.dataset.toggleUrl : '';
    var taskMsg = document.getElementById('twTaskMsg');

    var setText = function (id, value) {
        var el = document.getElementById(id);
        if (el) { el.textContent = value; }
    };

    // Refresh the Tasks done and Productivity score cards from the server's numbers.
    var applyStats = function (s) {
        setText('twDoneCount', s.done);
        setText('twPendingPill', s.pending + ' pending');
        var pill = document.getElementById('twPendingPill');
        if (pill) { pill.classList.toggle('tw-dash-pill-green', s.pending === 0 && s.total > 0); }

        var fill = document.getElementById('twTasksFill');
        if (fill) { fill.style.width = s.percent + '%'; }
        var bar = document.getElementById('twTasksBar');
        if (bar) { bar.setAttribute('aria-valuenow', s.percent); }
        setText('twTasksNote', s.percent + '% complete. ' + s.note);

        setText('twScoreValue', s.score.value);
        setText('twScoreLabel', s.score.label);
        setText('twScoreRingText', s.score.value);
        var ring = document.getElementById('twScoreRing');
        if (ring) {
            ring.style.setProperty('--pct', s.score.value);
            ring.setAttribute('aria-label', 'Score ' + s.score.value + ' out of 100');
        }
    };

    document.querySelectorAll('.tw-dash-check').forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            var row = checkbox.closest('.tw-dash-task');
            var id = checkbox.dataset.id;
            var wanted = checkbox.checked;

            row.classList.toggle('is-done', wanted); // instant visual feedback
            if (!id || !toggleBase) { return; }

            checkbox.disabled = true;
            if (taskMsg) { taskMsg.textContent = ''; }

            fetch(toggleBase + '/' + encodeURIComponent(id) + '/toggle', {
                method: 'PATCH',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ completed: wanted })
            })
                .then(function (response) {
                    if (!response.ok) { throw new Error('HTTP ' + response.status); }
                    return response.json();
                })
                .then(function (data) {
                    applyStats(data);
                    // A completed task is no longer an upcoming deadline.
                    document.querySelectorAll('.tw-dash-later-row[data-task-id="' + id + '"]').forEach(function (el) {
                        el.style.display = data.completed ? 'none' : '';
                    });
                })
                .catch(function () {
                    // Undo the visual change if saving failed.
                    checkbox.checked = !wanted;
                    row.classList.toggle('is-done', !wanted);
                    if (taskMsg) { taskMsg.textContent = "Couldn't save that change. Please try again."; }
                })
                .finally(function () {
                    checkbox.disabled = false;
                });
        });
    });
});
