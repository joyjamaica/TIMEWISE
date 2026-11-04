// TIMEWISE Dashboard behaviour.
document.addEventListener('DOMContentLoaded', function () {

    // 1. Sample countdown --------------------------------------------------
    // Counts down from data-seconds (set in DashboardController). It is a
    // SAMPLE: later, send a real start timestamp from class_schedules and
    // compute: seconds = (startTime - Date.now()) / 1000.
    var box = document.getElementById('twClass');
    if (box) {
        var countdown = document.getElementById('twCountdown');
        var progress = document.getElementById('twProgress');
        var left = document.getElementById('twLeft');
        var windowSec = parseInt(box.dataset.window, 10);
        var endAt = Date.now() + parseInt(box.dataset.seconds, 10) * 1000;

        var pad = function (n) { return String(n).padStart(2, '0'); };

        var tick = function () {
            var remaining = Math.max(0, Math.round((endAt - Date.now()) / 1000));
            var h = Math.floor(remaining / 3600);
            var m = Math.floor((remaining % 3600) / 60);
            var s = remaining % 60;

            countdown.textContent = pad(h) + ':' + pad(m) + ':' + pad(s);
            left.textContent = remaining === 0 ? 'Started' : Math.ceil(remaining / 60) + ' min left';
            progress.style.width = Math.min(100, Math.max(0, (1 - remaining / windowSec) * 100)) + '%';

            if (remaining === 0) { clearInterval(timer); }
        };

        tick();
        var timer = setInterval(tick, 1000);
    }

    // 2. Checkbox strike-through (visual only, nothing is saved) -------------
    document.querySelectorAll('.tw-dash-check').forEach(function (box) {
        box.addEventListener('change', function () {
            box.closest('.tw-dash-task').classList.toggle('is-done', box.checked);
        });
    });
});