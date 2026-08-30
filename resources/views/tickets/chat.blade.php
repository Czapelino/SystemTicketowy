<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $ticket->ticket_number }} — Chat</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        html, body { height: 100%; }
    </style>
</head>
<body>
<div class="chat-layout">

    <!-- Header -->
    <div class="chat-header">
        <a href="/">&larr;</a>
        <div class="ticket-info">
            <div class="ticket-number">{{ $ticket->ticket_number }}</div>
            <div class="ticket-subject">{{ Str::limit($ticket->subject, 60) }}</div>
        </div>
        <span class="status-badge badge-{{ $ticket->status }}" id="statusBadge">
            {{ match($ticket->status) {
                'open' => 'OTWARTE',
                'ai_handling' => 'AI POMAGA',
                'escalated' => 'ESKALOWANE',
                'in_progress' => 'KONSULTANT',
                'resolved' => 'ROZWIĄZANE',
                'closed' => 'ZAMKNIĘTE',
                default => strtoupper($ticket->status),
            } }}
        </span>
    </div>

    <!-- Messages -->
    <div class="messages-area" id="messagesArea">

        @if(session('success'))
        <div class="success-banner"><span>{{ session('success') }}</span></div>
        @endif

        <div class="context-card">
            <div class="inner">
                <div class="title">Asystent AI gotowy</div>
                <div class="sub">Analizuję Twoje zgłoszenie i jestem gotowy pomóc. Opisz problem, a postaram się go rozwiązać.</div>
            </div>
        </div>

        @foreach($messages as $message)
            @if($message->type === 'status_change' || $message->type === 'escalation')
                <div class="message" style="max-width: 100%;">
                    <div class="bubble bubble-system" style="width:100%;">
                        {{ $message->content }}
                        <span style="color:#999999; margin-left:6px;">{{ $message->created_at->format('d.m H:i') }}</span>
                    </div>
                </div>
            @elseif($message->role === 'user')
                <div class="message user">
                    <div>
                        <div class="bubble bubble-user">{{ $message->content }}</div>
                        <div class="msg-time">{{ $message->created_at->format('H:i') }}</div>
                    </div>
                    <div class="avatar">👤</div>
                </div>
            @elseif($message->role === 'assistant')
                <div class="message">
                    <div class="avatar">🤖</div>
                    <div>
                        <div class="bubble bubble-ai">{{ $message->content }}</div>
                        <div class="msg-time">
                            AI · {{ $message->created_at->format('H:i') }}
                            @if($message->ai_tokens_used) · {{ $message->ai_tokens_used }} tokenów @endif
                        </div>
                    </div>
                </div>
            @elseif($message->role === 'agent')
                <div class="message">
                    <div class="avatar">👩‍💼</div>
                    <div>
                        <div class="bubble bubble-agent">{{ $message->content }}</div>
                        <div class="msg-time">{{ $message->sender_name }} · {{ $message->created_at->format('H:i') }}</div>
                    </div>
                </div>
            @endif
        @endforeach

        <div id="typingIndicator" style="display: none;" class="message">
            <div class="avatar">🤖</div>
            <div class="bubble bubble-ai">
                <div class="typing">
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Escalation banner (shown only while waiting for an agent to pick up) -->
    @if($ticket->status === 'escalated')
    <div class="escalation-banner" id="escalationBanner">
        <div class="title">Przekazano do konsultanta</div>
        <div class="sub">Twoje zgłoszenie zostało przekazane. Konsultant odpowie w godzinach pracy (pon-pt 8:00-17:00).</div>
    </div>
    @endif

    <!-- Input area -->
    @if(!in_array($ticket->status, ['resolved', 'closed']))
    <div class="input-area">
        <div class="input-wrapper">
            <textarea class="msg-input" id="messageInput"
                      placeholder="{{ in_array($ticket->status, ['escalated', 'in_progress']) ? 'Odpowiedz konsultantowi...' : 'Opisz problem lub odpowiedz AI...' }}"
                      rows="1" maxlength="2000"></textarea>
            <button class="send-btn" id="sendBtn">Wyślij</button>
        </div>
        <div class="meta-line">
            <span>Enter = wyślij | Shift+Enter = nowa linia</span>
            <span id="inputCharCount">0/2000</span>
        </div>
    </div>
    @else
    <div class="resolved-note">
        Zgłoszenie zostało rozwiązane. <a href="{{ route('tickets.create') }}">Utwórz nowe &rarr;</a>
    </div>
    @endif

</div>

<script>
const TICKET_ID = '{{ $ticket->id }}';
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
let isEscalated = {{ in_array($ticket->status, ['escalated', 'in_progress', 'resolved', 'closed']) ? 'true' : 'false' }};

const messagesArea = document.getElementById('messagesArea');
const messageInput = document.getElementById('messageInput');
const sendBtn = document.getElementById('sendBtn');
const typingIndicator = document.getElementById('typingIndicator');
const statusBadge = document.getElementById('statusBadge');
const inputCharCount = document.getElementById('inputCharCount');

function scrollToBottom() {
    messagesArea.scrollTop = messagesArea.scrollHeight;
}
scrollToBottom();

messageInput?.addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    if (inputCharCount) inputCharCount.textContent = `${this.value.length}/2000`;
});

messageInput?.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
});

sendBtn?.addEventListener('click', sendMessage);

function appendMessage(role, content, time, senderName = null) {
    const div = document.createElement('div');

    if (role === 'user') {
        div.className = 'message user';
        div.innerHTML = `
            <div>
                <div class="bubble bubble-user">${escapeHtml(content)}</div>
                <div class="msg-time">${time}</div>
            </div>
            <div class="avatar">👤</div>`;
    } else if (role === 'assistant') {
        div.className = 'message';
        div.innerHTML = `
            <div class="avatar">🤖</div>
            <div>
                <div class="bubble bubble-ai">${escapeHtml(content).replace(/\n/g, '<br>')}</div>
                <div class="msg-time">AI · ${time}</div>
            </div>`;
    } else if (role === 'agent') {
        div.className = 'message';
        div.innerHTML = `
            <div class="avatar">👩‍💼</div>
            <div>
                <div class="bubble bubble-agent">${escapeHtml(content)}</div>
                <div class="msg-time">${senderName || 'Konsultant'} · ${time}</div>
            </div>`;
    }

    messagesArea.insertBefore(div, typingIndicator);
    scrollToBottom();
}

function showEscalationBanner() {
    const existing = document.getElementById('escalationBanner');
    if (existing) return;

    const banner = document.createElement('div');
    banner.className = 'escalation-banner';
    banner.id = 'escalationBanner';
    banner.innerHTML = `
        <div class="title">Przekazano do konsultanta</div>
        <div class="sub">Asystent AI nie mógł rozwiązać Twojego problemu. Konsultant odpowie wkrótce.</div>`;

    const inputArea = document.querySelector('.input-area');
    inputArea.parentNode.insertBefore(banner, inputArea);

    if (statusBadge) {
        statusBadge.className = 'status-badge badge-escalated';
        statusBadge.textContent = 'ESKALOWANE';
    }
    if (messageInput) {
        messageInput.placeholder = 'Odpowiedz konsultantowi...';
    }
}

async function sendMessage() {
    const text = messageInput.value.trim();
    if (!text || sendBtn.disabled) return;

    sendBtn.disabled = true;
    messageInput.value = '';
    messageInput.style.height = 'auto';
    if (inputCharCount) inputCharCount.textContent = '0/2000';

    const now = new Date().toLocaleTimeString('pl-PL', { hour: '2-digit', minute: '2-digit' });
    appendMessage('user', text, now);

    if (!isEscalated) {
        typingIndicator.style.display = 'flex';
        messagesArea.scrollTop = messagesArea.scrollHeight;
    }

    try {
        const response = await fetch(`/ticket/${TICKET_ID}/message`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ message: text }),
        });

        const data = await response.json();
        typingIndicator.style.display = 'none';

        if (data.success) {
            if (data.escalated && !isEscalated) {
                isEscalated = true;
                showEscalationBanner();

                const sysDiv = document.createElement('div');
                sysDiv.className = 'message';
                sysDiv.style.maxWidth = '100%';
                sysDiv.innerHTML = `<div class="bubble bubble-system" style="width:100%;">Przekazuję Cię do konsultanta, który wkrótce się z Tobą skontaktuje.</div>`;
                messagesArea.insertBefore(sysDiv, typingIndicator);
            } else if (data.ai_message) {
                appendMessage('assistant', data.ai_message.content, data.ai_message.created_at);
            }
        }
    } catch (err) {
        typingIndicator.style.display = 'none';
        console.error('Send error:', err);
    }

    sendBtn.disabled = false;
    messageInput.focus();
    scrollToBottom();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
}

function removeEscalationBanner() {
    const existing = document.getElementById('escalationBanner');
    if (existing) existing.remove();
}

function appendSystemMessage(content) {
    const div = document.createElement('div');
    div.className = 'message';
    div.style.maxWidth = '100%';
    div.innerHTML = `<div class="bubble bubble-system" style="width:100%;">${escapeHtml(content)}</div>`;
    messagesArea.insertBefore(div, typingIndicator);
    scrollToBottom();
}

// Live-update: while a ticket is escalated / being handled by an agent,
// poll for new messages and status changes so replies show up without
// the customer having to refresh the page.
@if(in_array($ticket->status, ['escalated', 'in_progress']))
let lastMessageId = '{{ $messages->last()?->id ?? '' }}';
let currentStatus = '{{ $ticket->status }}';

const pollTimer = setInterval(async () => {
    try {
        const url = `/ticket/${TICKET_ID}/updates` + (lastMessageId ? `?after=${lastMessageId}` : '');
        const r = await fetch(url, { headers: { 'Accept': 'application/json' } });
        if (!r.ok) return;
        const data = await r.json();

        (data.messages || []).forEach(msg => {
            if (msg.type === 'status_change' || msg.type === 'escalation') {
                appendSystemMessage(msg.content);
            } else if (msg.role === 'agent') {
                appendMessage('agent', msg.content, msg.created_at, msg.sender_name);
            } else if (msg.role === 'assistant') {
                appendMessage('assistant', msg.content, msg.created_at);
            } else if (msg.role === 'user') {
                // Own messages already appended locally on send; skip to avoid duplicates.
            }
            lastMessageId = msg.id;
        });

        if (data.status && data.status !== currentStatus) {
            const previousStatus = currentStatus;
            currentStatus = data.status;

            if (statusBadge) {
                statusBadge.className = 'status-badge badge-' + data.status;
                statusBadge.textContent = {
                    open: 'OTWARTE', ai_handling: 'AI POMAGA', escalated: 'ESKALOWANE',
                    in_progress: 'KONSULTANT', resolved: 'ROZWIĄZANE', closed: 'ZAMKNIĘTE',
                }[data.status] || data.status.toUpperCase();
            }

            // Consultant just picked up the ticket — the "waiting" banner no longer applies.
            if (previousStatus === 'escalated' && data.status === 'in_progress') {
                removeEscalationBanner();
            }

            // Ticket got resolved/closed by the agent — reload to show the
            // proper "resolved" state (input hidden, closing note shown).
            if (['resolved', 'closed'].includes(data.status)) {
                clearInterval(pollTimer);
                location.reload();
            }
        }
    } catch (e) {
        console.error('Polling error:', e);
    }
}, 5000);
@endif
</script>
</body>
</html>
