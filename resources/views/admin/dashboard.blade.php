<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administratora — TicketSystem</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>

<div class="layout">
    <aside class="sidebar">
        <a href="{{ route('admin.dashboard') }}" class="sidebar-logo" style="text-decoration:none; display:block;">System Ticketowy<span>Panel Administracyjny</span></a>
        <div class="sidebar-section">Główne</div>
        <a href="{{ route('admin.dashboard') }}" class="active">Dashboard</a>
        <a href="{{ route('admin.tickets.index') }}">
            Wszystkie zgłoszenia
            @if($sidebarEscalatedCount > 0) <span style="float:right; background:#cc7a22; color:white; font-size:10px; padding:1px 6px;">{{ $sidebarEscalatedCount }}</span> @endif
        </a>
        <div class="sidebar-section">Filtry</div>
        <a href="{{ route('admin.tickets.index', ['status' => 'escalated']) }}">
            Eskalowane
            @if($sidebarEscalatedCount > 0) <span style="float:right; background:#cc7a22; color:white; font-size:10px; padding:1px 6px;">{{ $sidebarEscalatedCount }}</span> @endif
        </a>
        <a href="{{ route('admin.tickets.index', ['status' => 'ai_handling']) }}">Obsługiwane przez AI</a>
        <a href="{{ route('admin.tickets.index', ['status' => 'in_progress']) }}">W trakcie obsługi</a>
        <div class="sidebar-section">System</div>
        <a href="{{ route('admin.users.index') }}">Zarządzanie agentami</a>
        <a href="{{ route('admin.instructions.index') }}">Instrukcje / Baza wiedzy</a>
        <a href="{{ route('admin.reports') }}">Raporty</a>
        <a href="{{ route('admin.settings') }}">Ustawienia</a>
        <div class="sidebar-footer">
            {{ auth()->user()->name }}
            <form method="POST" action="{{ route('logout') }}" style="margin-top:4px;">
                @csrf
                <button type="submit">Wyloguj</button>
            </form>
        </div>
    </aside>

    <main class="main">
        <div class="admin-topbar">
            <div>
                <h2>Dashboard</h2>
                <div class="sub">{{ now()->format('l, d F Y') }}</div>
            </div>
            <a href="{{ route('tickets.create') }}" target="_blank" class="btn btn-small">Strona klienta</a>
        </div>

        <div class="content">

            <div class="stats-grid" style="grid-template-columns: repeat(6, 1fr);">
                <div class="stat-box"><div class="num">{{ $stats['total'] }}</div><div class="lbl">Wszystkie</div></div>
                <div class="stat-box"><div class="num">{{ $stats['ai_handling'] }}</div><div class="lbl">AI obsługuje</div></div>
                <div class="stat-box"><div class="num">{{ $stats['escalated'] }}</div><div class="lbl">Eskalowane</div></div>
                <div class="stat-box"><div class="num">{{ $stats['in_progress'] }}</div><div class="lbl">W trakcie</div></div>
                <div class="stat-box"><div class="num">{{ $stats['resolved_today'] }}</div><div class="lbl">Dziś rozwiązane</div></div>
                <div class="stat-box"><div class="num">{{ $stats['ai_resolution_rate'] }}%</div><div class="lbl">Wskaźnik AI</div></div>
            </div>

            <div class="box">
                <div class="box-head">
                    <span>Ostatnie zgłoszenia</span>
                    <a href="{{ route('admin.tickets.index') }}" style="color:#cccccc; font-size:11px;">Zobacz wszystkie &rarr;</a>
                </div>
                <table class="plain">
                    <thead>
                        <tr><th>Numer</th><th>Temat</th><th>Status</th><th>Priorytet</th><th>Czas</th></tr>
                    </thead>
                    <tbody>
                        @forelse($recentTickets as $ticket)
                        <tr class="clickable" onclick="location.href='{{ route('admin.tickets.show', $ticket->id) }}'">
                            <td><code>{{ $ticket->ticket_number }}</code></td>
                            <td>
                                {{ Str::limit($ticket->subject, 35) }}<br>
                                <span style="color:#888888; font-size:11px;">{{ $ticket->customer_name }}</span>
                            </td>
                            <td>
                                <span class="badge badge-{{ $ticket->status }}">
                                    {{ match($ticket->status) {
                                        'open' => 'Otwarte', 'ai_handling' => 'AI', 'escalated' => 'Eskalowane',
                                        'in_progress' => 'W trakcie', 'resolved' => 'Rozwiązane', default => $ticket->status,
                                    } }}
                                </span>
                            </td>
                            <td><span class="badge badge-{{ $ticket->priority }}">{{ ucfirst($ticket->priority) }}</span></td>
                            <td style="color:#666666; font-size:12px;">{{ $ticket->created_at->diffForHumans() }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" style="text-align:center; color:#888888; padding: 16px;">Brak zgłoszeń</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="box">
                <div class="box-head">
                    <span>Agenci</span>
                    <a href="{{ route('admin.users.index') }}" style="color:#cccccc; font-size:11px;">Zarządzaj &rarr;</a>
                </div>
                <table class="plain">
                    <thead>
                        <tr><th>Agent</th><th>Rola</th><th>Status</th><th>Aktywne zgłoszenia</th></tr>
                    </thead>
                    <tbody>
                        @forelse($agents as $agent)
                        <tr>
                            <td>{{ $agent->name }}<br><span style="color:#888888; font-size:11px;">{{ $agent->email }}</span></td>
                            <td>{{ ucfirst($agent->role) }}</td>
                            <td>
                                <span class="{{ $agent->is_active ? 'dot-green' : 'dot-red' }}">&#9679;</span>
                                {{ $agent->is_active ? 'Aktywny' : 'Nieaktywny' }}
                            </td>
                            <td>{{ $agent->open_tickets_count }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" style="text-align:center; color:#888888; padding: 16px;">Brak agentów</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </main>
</div>

</body>
</html>

