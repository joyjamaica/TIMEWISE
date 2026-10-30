<x-app-layout>
<link rel="stylesheet" href="{{ asset('css/pulsebot.css') }}">

<div class="pb" id="pulsebot">
    <header class="pb-head">
        <div>
            <p class="pb-kicker">AI study partner</p>
            <h1 class="pb-title">Turn confusion into action<i>.</i></h1>
            <p class="pb-sub">PulseBot understands your schedule, tasks, and study rhythm.</p>
        </div>
    </header>

    <div class="pb-grid">
        <aside class="pb-prompts" aria-label="Quick prompts">
            @foreach ([
                ['✦', 'Summarize my notes', "Summarize my notes. I'll paste them next."],
                ['?', 'Explain this simply', 'Explain this simply: '],
                ['✓', 'Create a practice quiz', 'Create a short practice quiz on this topic: '],
                ['↗', 'Break down an assignment', 'Break down this assignment into smaller steps with a realistic schedule: ']
            ] as [$icon, $label, $prompt])
                <button type="button" class="pb-prompt" data-prompt="{{ $prompt }}">
                    <span class="pb-ico">{{ $icon }}</span><span>{{ $label }}</span>
                    <svg viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </button>
            @endforeach
        </aside>

        <section class="pb-chat" aria-label="Chat with PulseBot">
            <div class="pb-bar">
                <div class="pb-logo">
                    <svg viewBox="0 0 24 24"><path d="M12 3c.6 4.6 3.4 7.4 9 9-5.6 1.6-8.4 4.4-9 9-.6-4.6-3.4-7.4-9-9 5.6-1.6 8.4-4.4 9-9z"/></svg>
                </div>
                <div><b>PulseBot</b><small id="pb-status">Online · Ready to help</small></div>
                <button type="button" class="pb-clear" id="pb-clear">Clear chat</button>
            </div>

            <div class="pb-log" id="pb-log" role="log" aria-live="polite">
                <div class="pb-typing" id="pb-typing" aria-label="PulseBot is typing">
                    <i></i><i></i><i></i>
                </div>
            </div>

            <form class="pb-form" id="pb-form" autocomplete="off">
                <textarea class="pb-input" id="pb-input" rows="1" maxlength="1000"
                    placeholder="Ask PulseBot anything…" aria-label="Message"></textarea>
                <button class="pb-send" id="pb-send" type="submit" aria-label="Send message">
                    <svg viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </button>
            </form>
        </section>
    </div>
</div>

@php
    $firstName = explode(' ', auth()->user()->name ?? 'there')[0];
    $greeting = "Hi {$firstName}. Ask me about a topic, a deadline, or how to plan your week.";
    $sendUrl = route('pulsebot.send');
    $clearUrl = route('pulsebot.clear');
    $csrfToken = csrf_token();
    $chatHistory = $history ?? [];
@endphp

<script>
(() => {
    const SEND_URL = @json($sendUrl);
    const CLEAR_URL = @json($clearUrl);
    const CSRF = @json($csrfToken);

    const $ = id => document.getElementById(id);
    const log = $('pb-log'), typing = $('pb-typing'), form = $('pb-form');
    const input = $('pb-input'), send = $('pb-send'), status = $('pb-status');
    const clear = $('pb-clear');

    let busy = false;
    let history = @json($chatHistory);
    const GREETING = @json($greeting);

    const esc = text => text.replace(/[&<>"']/g, c => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));

    function render(text) {
        let html = esc(text).replace(/```(?:\w+)?\n?([\s\S]*?)```/g,
            (_, code) => `<pre><code>${code.trim()}</code></pre>`)
            .replace(/`([^`\n]+)`/g, '<code>$1</code>')
            .replace(/\*\*([^*\n]+)\*\*/g, '<strong>$1</strong>');

        return html.split(/\n{2,}/).map(block => {
            if (block.startsWith('<pre>')) return block;
            const lines = block.split('\n');

            if (lines.every(line => /^\s*[-*]\s+/.test(line)))
                return '<ul>' + lines.map(line => `<li>${line.replace(/^\s*[-*]\s+/, '')}</li>`).join('') + '</ul>';

            if (lines.every(line => /^\s*\d+[.)]\s+/.test(line)))
                return '<ol>' + lines.map(line => `<li>${line.replace(/^\s*\d+[.)]\s+/, '')}</li>`).join('') + '</ol>';

            return '<p>' + lines.join('<br>') + '</p>';
        }).join('');
    }

    function add(kind, text) {
        const message = document.createElement('div');
        message.className = 'pb-msg ' + kind;
        if (kind === 'bot') message.innerHTML = render(text);
        else message.textContent = text;
        log.insertBefore(message, typing);
        log.scrollTop = log.scrollHeight;
    }

    function loadHistory() {
        if (Array.isArray(history) && history.length) {
            history.forEach(({ role, content }) => {
                if (['user', 'assistant'].includes(role) && typeof content === 'string')
                    add(role === 'user' ? 'me' : 'bot', content);
            });
        } else {
            add('bot', GREETING);
        }
        log.scrollTop = log.scrollHeight;
    }

    function setBusy(value) {
        busy = value;
        send.disabled = value;
        typing.classList.toggle('on', value);
        if (value) log.scrollTop = log.scrollHeight;
    }

    async function submit(text) {
        text = text.trim();
        if (!text || busy) return;

        add('me', text);
        input.value = '';
        input.style.height = 'auto';
        setBusy(true);

        try {
            const res = await fetch(SEND_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ message: text })
            });

            const data = await res.json().catch(() => ({}));

            if (res.ok && data.reply) {
                add('bot', data.reply);
                status.textContent = 'Online · Ready to help';
                status.dataset.off = '';
            } else {
                add('err', res.status === 429
                    ? 'You are sending messages too fast. Wait a few seconds and try again.'
                    : res.status === 419
                        ? 'Your session expired. Refresh the page to continue.'
                        : data.error || 'PulseBot could not answer. Please try again.');

                if (res.status >= 500) {
                    status.textContent = 'Offline · Try again later';
                    status.dataset.off = '1';
                }
            }
        } catch {
            add('err', 'Could not reach the server. Check your connection and try again.');
        } finally {
            setBusy(false);
            input.focus();
        }
    }

    loadHistory();

    form.addEventListener('submit', e => {
        e.preventDefault();
        submit(input.value);
    });

    input.addEventListener('keydown', e => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            submit(input.value);
        }
    });

    input.addEventListener('input', () => {
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 120) + 'px';
    });

    document.querySelectorAll('.pb-prompt').forEach(button => {
        button.addEventListener('click', () => {
            input.value = button.dataset.prompt;
            input.focus();
        });
    });

    clear.addEventListener('click', async () => {
        if (busy) return;
        clear.disabled = true;

        try {
            const res = await fetch(CLEAR_URL, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!res.ok) throw new Error('Clear failed');

            log.querySelectorAll('.pb-msg').forEach(message => message.remove());
            history = [];
            add('bot', GREETING);
        } catch {
            add('err', 'Could not clear the conversation. Please try again.');
        } finally {
            clear.disabled = false;
        }
    });
})();
</script>
</x-app-layout>
