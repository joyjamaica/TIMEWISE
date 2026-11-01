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
                ['✦', 'Summarize my notes', "Summarize my notes. I'll paste them next, or attach a PDF, TXT, Markdown, or CSV file. Highlight the key ideas and end by asking if I'd like a quiz."],
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
                <div class="pb-typing" id="pb-typing" role="status" aria-label="PulseBot is responding">
                    <span>PulseBot is thinking</span>
                    <i></i><i></i><i></i>
                </div>
            </div>

            <form class="pb-form" id="pb-form" autocomplete="off">
                <input class="pb-file-input" id="pb-file" type="file" accept=".pdf,.txt,.md,.csv,application/pdf,text/plain,text/markdown,text/csv">
                <button class="pb-attach" id="pb-attach" type="button" aria-label="Attach notes file" title="Attach a PDF, TXT, Markdown, or CSV file">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21.4 11.1 12 20.5a6 6 0 0 1-8.5-8.5l9.9-9.9a4 4 0 0 1 5.7 5.7l-10 10a2 2 0 0 1-2.8-2.8l9.2-9.2"/></svg>
                </button>
                <span class="pb-file-name" id="pb-file-name" aria-live="polite"></span>
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
    const clear = $('pb-clear'), fileInput = $('pb-file'), attach = $('pb-attach');
    const fileName = $('pb-file-name');

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
        return message;
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
        attach.disabled = value;
        fileInput.disabled = value;
        typing.classList.toggle('on', value);
        if (value) log.scrollTop = log.scrollHeight;
    }

    async function submit(text) {
        text = text.trim();
        const file = fileInput.files[0] || null;
        if ((!text && !file) || busy) return;

        add('me', text + (file ? `\n📎 ${file.name}` : ''));
        input.value = '';
        input.style.height = 'auto';
        setBusy(true);

        try {
            const body = new FormData();
            body.append('message', text);
            if (file) body.append('file', file);

            const res = await fetch(SEND_URL, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body
            });

            if (!res.ok) {
                const data = await res.json().catch(() => ({}));
                add('err', res.status === 429
                    ? 'You are sending messages too fast. Wait a few seconds and try again.'
                    : res.status === 419
                        ? 'Your session expired. Refresh the page to continue.'
                        : data.error || 'PulseBot could not answer. Please try again.');

                if (res.status >= 500) {
                    status.textContent = 'Offline · Try again later';
                    status.dataset.off = '1';
                }
                return;
            }

            const reader = res.body.getReader();
            const decoder = new TextDecoder();
            let buffer = '';
            let reply = '';
            let message = null;
            let completed = false;
            let failed = false;

            const processEvent = event => {
                const line = event.split(/\r?\n/).find(part => part.startsWith('data:'));
                if (!line) return;

                const data = JSON.parse(line.slice(5).trim());
                if (data.error) {
                    add('err', data.error);
                    failed = true;
                    return;
                }

                if (typeof data.token === 'string') {
                    typing.classList.remove('on');
                    reply += data.token;
                    if (!message) message = add('bot', '');
                    message.innerHTML = render(reply);
                    log.scrollTop = log.scrollHeight;
                }

                if (data.done) {
                    completed = true;
                    if (typeof data.reply === 'string') {
                        reply = data.reply;
                        if (!message) message = add('bot', '');
                        message.innerHTML = render(reply);
                        log.scrollTop = log.scrollHeight;
                    }
                }
            };

            while (true) {
                const { value, done } = await reader.read();
                if (done) break;

                buffer += decoder.decode(value, { stream: true });
                const events = buffer.split(/\r?\n\r?\n/);
                buffer = events.pop();
                events.forEach(processEvent);
            }

            buffer += decoder.decode();
            if (buffer.trim()) processEvent(buffer);
            if (!completed && !failed) throw new Error('PulseBot disconnected before finishing the response.');

            if (failed) {
                status.textContent = 'Offline · Try again later';
                status.dataset.off = '1';
            } else {
                status.textContent = 'Online · Ready to help';
                status.dataset.off = '';
                fileInput.value = '';
                fileName.textContent = '';
                attach.title = 'Attach a PDF, TXT, Markdown, or CSV file';
            }
        } catch {
            add('err', 'Could not complete the response. Check your connection and try again.');
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

    attach.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', () => {
        const file = fileInput.files[0];
        attach.title = file ? `Attached: ${file.name}` : 'Attach a PDF, TXT, Markdown, or CSV file';
        fileName.textContent = file ? file.name : '';
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
