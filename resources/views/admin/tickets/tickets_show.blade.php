<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $ticket->ticket_number }} — Admin Panel</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        html, body { height: 100%; }
        .layout2 { display: flex; height: 100vh; }
        .sidebar-slim { width: 180px; }
        .main2 { flex: 1; display: flex; flex-direction: column; min-width: 0; }
    </style>
</head>
<body>

<div class="layout2">
    <aside class="sidebar sidebar-slim">
        <div class="sidebar-logo">HelpDesk<span>AI</span></div>
        <a href="{{ route('admin.dashboard') }}">Dashboard</a>
        <a href="{{ route('admin.tickets.index') }}" class="active">Zgłoszenia</a>
        <a href="{{ route('admin.tickets.index', ['status' => 'escalated']) }}">Eskalowane</a>
        <a href="{{ route('admin.users.index') }}">Agenci</a>
    </aside>

    <main class="main2">
        <div class="admin-topbar">
            <div style="display:flex; align-items:center; gap:12px;">
                <a href="{{ route('admin.tickets.index') }}" style="color:#cccccc;">&larr;</a>
                <div>
                    <code style="color:#ccddee;">{{ $ticket->ticket_number }}</code>
                    <strong style="margin-left:8px;">{{ $ticket->subject }}</strong>
                </div>
            </div>
            <span class="status-badge badge-{{ $ticket->status }}" id="statusBadge">
                {{ match($ticket->status) {
                    'open' => 'OTWARTE', 'ai_handling' => 'AI', 'escalated' => 'ESKALOWANE',
                    'in_progress' => 'W TRAKCIE', 'resolved' => 'ROZWIĄZANE', default => strtoupper($ticket->status),
                } }}
            </span>
        </div>

        <div class="ticket-layout">

            <!-- Chat column -->
            <div class="chat-col">
                <div class="messages-area" id="chatMessages">
                    @foreach($messages as $message)
                        @if(in_array($message->type, ['status_change', 'escalation']))
                            <div class="message" style="max-width:100%;">
                                <div class="bubble-system" style="width:100%;">{{ $message->content }}</div>
                            </div>
                        @elseif($message->role === 'user')
                            <div class="message user">
                                <div>
                                    <div class="bubble bubble-user">{{ $message->content }}</div>
                                    <div class="msg-time" style="text-align:right;">{{ $ticket->customer_name }} · {{ $message->created_at->format('H:i') }}</div>
                                </div>
                                <div class="avatar">👤</div>
                            </div>
                        @elseif($message->role === 'assistant')
                            <div class="message">
                                <div class="avatar">🤖</div>
                                <div>
                                    <div class="bubble bubble-ai">{{ $message->content }}</div>
                                    <div class="msg-time">AI · {{ $message->created_at->format('H:i') }}
                                        @if($message->ai_tokens_used) · {{ $message->ai_tokens_used }} tok @endif
                                    </div>
                                </div>
                            </div>
                        @elseif($message->role === 'agent')
                            <div class="message">
                                <div class="avatar">👩‍💼</div>
                                <div>
                                    <div class="bubble {{ $message->type === 'internal_note' ? 'bubble-internal' : 'bubble-agent' }}">
                                        @if($message->type === 'internal_note')
                                            <span class="tag">🔒 Notatka wewnętrzna</span>
                                        @endif
                                        {{ $message->content }}
                                    </div>
                                    <div class="msg-time">{{ $message->sender_name }} · {{ $message->created_at->format('H:i') }}</div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>

                @if(!in_array($ticket->status, ['resolved', 'closed']))
                <div class="input-area">
                    @if($aiSuggestion)
                    <div class="ai-suggestion" onclick="useAiSuggestion()" title="Kliknij by użyć tej odpowiedzi">
                        <div class="lbl">Sugestia AI:</div>
                        {{ Str::limit($aiSuggestion, 150) }}
                    </div>
                    @endif
                    <div class="input-tabs">
                        <div class="input-tab active" id="tab-reply" onclick="setTab('reply')">Odpowiedź</div>
                        <div class="input-tab" id="tab-note" onclick="setTab('note')">Notatka wewnętrzna</div>
                    </div>
                    <textarea class="msg-input" id="agentInput" style="min-height:70px;" placeholder="Napisz odpowiedź..."></textarea>
                    <div class="meta-line" style="margin-top:6px;">
                        <span>Ctrl+Enter = wyślij</span>
                        <button class="btn btn-primary btn-small" onclick="sendAgentMessage()">Wyślij</button>
                    </div>
                </div>
                @endif
            </div>

            <!-- Info column -->
            <div class="info-col">

                <div class="info-card">
                    <h6>Akcje</h6>
                    <div class="info-card-body">
                        @if($ticket->status !== 'resolved')
                        <button class="btn btn-green btn-block" style="margin-bottom:10px;" onclick="resolveTicket()">Oznacz jako rozwiązane</button>
                        @endif
                        <div class="field" style="margin-bottom:10px;">
                            <label class="f-label">Przypisz do agenta</label>
                            <select id="agentSelect" onchange="assignAgent(this.value)">
                                <option value="">— wybierz agenta —</option>
                                @foreach($agents as $agent)
                                    <option value="{{ $agent->id }}" {{ $ticket->assigned_to == $agent->id ? 'selected' : '' }}>{{ $agent->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field" style="margin-bottom:0;">
                            <label class="f-label">Priorytet</label>
                            <select id="prioritySelect" onchange="updatePriority(this.value)">
                                <option value="low" {{ $ticket->priority === 'low' ? 'selected' : '' }}>Niski</option>
                                <option value="medium" {{ $ticket->priority === 'medium' ? 'selected' : '' }}>Średni</option>
                                <option value="high" {{ $ticket->priority === 'high' ? 'selected' : '' }}>Wysoki</option>
                                <option value="urgent" {{ $ticket->priority === 'urgent' ? 'selected' : '' }}>Pilny</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="info-card">
                    <h6>Szczegóły zgłoszenia</h6>
                    <div class="info-card-body">
                        <div class="info-row-2"><span class="il">Numer</span><span class="iv"><code>{{ $ticket->ticket_number }}</code></span></div>
                        <div class="info-row-2"><span class="il">Status</span><span class="badge badge-{{ $ticket->status }}">{{ $ticket->status }}</span></div>
                        <div class="info-row-2"><span class="il">Kategoria</span><span class="iv">{{ ucfirst($ticket->category) }}</span></div>
                        <div class="info-row-2"><span class="il">Utworzono</span><span class="iv">{{ $ticket->created_at->format('d.m.Y H:i') }}</span></div>
                        @if($ticket->escalation_reason)
                        <div class="info-row-2"><span class="il">Powód eskalacji</span><span class="iv" style="color:#cc7a22;">{{ $ticket->escalation_reason }}</span></div>
                        @endif
                    </div>
                </div>

                <div class="info-card">
                    <h6>Klient</h6>
                    <div class="info-card-body">
                        <div class="info-row-2"><span class="il">Imię</span><span class="iv">{{ $ticket->customer_name }}</span></div>
                        <div class="info-row-2"><span class="il">E-mail</span><a href="mailto:{{ $ticket->customer_email }}" style="font-size:12px;">{{ $ticket->customer_email }}</a></div>
                        @if($ticket->customer_phone)
                        <div class="info-row-2"><span class="il">Telefon</span><span class="iv">{{ $ticket->customer_phone }}</span></div>
                        @endif
                    </div>
                </div>

                @if($ticket->ai_summary)
                <div class="info-card">
                    <h6>Analiza AI</h6>
                    <div class="info-card-body">
                        <div style="font-size:12px; color:#555555; margin-bottom:8px;">{{ $ticket->ai_summary }}</div>
                        @if($ticket->ai_sentiment)
                        <div style="font-size:11px;">Nastrój: <strong>{{ ucfirst($ticket->ai_sentiment) }}</strong></div>
                        @endif
                        @if($ticket->tags && count($ticket->tags))
                        <div style="margin-top:6px;">
                            @foreach($ticket->tags as $tag)
                                <span class="tag-chip">{{ $tag }}</span>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                <div class="info-card">
                    <h6>Statystyki</h6>
                    <div class="info-card-body">
                        <div class="info-row-2"><span class="il">Wszystkich wiadomości</span><span class="iv">{{ $messages->count() }}</span></div>
                        <div class="info-row-2"><span class="il">Wiadomości klienta</span><span class="iv">{{ $messages->where('role', 'user')->count() }}</span></div>
                        <div class="info-row-2"><span class="il">Odpowiedzi AI</span><span class="iv">{{ $messages->where('role', 'assistant')->count() }}</span></div>
                        <div class="info-row-2"><span class="il">Odpowiedzi agenta</span><span class="iv">{{ $messages->where('role', 'agent')->count() }}</span></div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
const TICKET_ID = '{{ $ticket->id }}';
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
let activeTab = 'reply';
@if($aiSuggestion)
const aiSuggestionText = @json($aiSuggestion);
@endif

const chat = document.getElementById('chatMessages');
chat.scrollTop = chat.scrollHeight;

function setTab(tab) {
    activeTab = tab;
    document.getElementById('tab-reply').classList.toggle('active', tab === 'reply');
    document.getElementById('tab-note').classList.toggle('active', tab === 'note');
    const input = document.getElementById('agentInput');
    input.placeholder = tab === 'note' ? 'Notatka wewnętrzna (widoczna tylko dla agentów)...' : 'Napisz odpowiedź do klienta...';
}

function useAiSuggestion() {
    if (typeof aiSuggestionText !== 'undefined') {
        document.getElementById('agentInput').value = aiSuggestionText;
    }
}

async function sendAgentMessage() {
    const text = document.getElementById('agentInput').value.trim();
    if (!text) return;

    const isInternal = activeTab === 'note';

    try {
        const res = await fetch(`/admin/tickets/${TICKET_ID}/message`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify({ message: text, is_internal: isInternal }),
        });
        const data = await res.json();
        if (data.success) {
            document.getElementById('agentInput').value = '';
            appendAgentMessage(data.message);
        }
    } catch(e) { console.error(e); }
}

function appendAgentMessage(msg) {
    const div = document.createElement('div');
    div.className = 'message';
    const bubbleClass = msg.type === 'internal_note' ? 'bubble-internal' : 'bubble-agent';
    div.innerHTML = `
        <div class="avatar">👩‍💼</div>
        <div>
            <div class="bubble ${bubbleClass}">
                ${msg.type === 'internal_note' ? '<span class="tag">🔒 Notatka wewnętrzna</span>' : ''}
                ${msg.content}
            </div>
            <div class="msg-time">${msg.sender_name} · ${msg.created_at}</div>
        </div>`;
    chat.appendChild(div);
    chat.scrollTop = chat.scrollHeight;
}

document.getElementById('agentInput')?.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && e.ctrlKey) sendAgentMessage();
});

async function assignAgent(agentId) {
    if (!agentId) return;
    await fetch(`/admin/tickets/${TICKET_ID}/assign`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ agent_id: agentId }),
    });
}

async function updatePriority(priority) {
    await fetch(`/admin/tickets/${TICKET_ID}/priority`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ priority }),
    });
}

async function resolveTicket() {
    const notes = prompt('Notatka o rozwiązaniu (wymagane, min. 5 znaków):');
    if (notes === null) return;
    if (notes.trim().length < 5) {
        alert('Notatka musi mieć co najmniej 5 znaków.');
        return;
    }

    const res = await fetch(`/admin/tickets/${TICKET_ID}/resolve`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ notes: notes.trim() }),
    });
    const data = await res.json();
    if (data.success) location.reload();
}
</script>
</body>
</html>
