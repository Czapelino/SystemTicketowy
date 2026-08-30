<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wszystkie zgłoszenia — HelpDesk AI</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>

<div class="layout">
    <aside class="sidebar">
        <div class="sidebar-logo">System Ticketowy<span>Panel Administracyjny</span></div>
        <div class="sidebar-section">Główne</div>
        <a href="{{ route('admin.dashboard') }}">Dashboard</a>
        <a href="{{ route('admin.tickets.index') }}" class="active">Wszystkie zgłoszenia</a>
        <div class="sidebar-section">Filtry</div>
        <a href="{{ route('admin.tickets.index', ['status' => 'escalated']) }}">Eskalowane</a>
        <a href="{{ route('admin.tickets.index', ['status' => 'ai_handling']) }}">Obsługiwane przez AI</a>
        <a href="{{ route('admin.tickets.index', ['status' => 'in_progress']) }}">W trakcie obsługi</a>
        <div class="sidebar-section">System</div>
        <a href="{{ route('admin.users.index') }}">Zarządzanie agentami</a>
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
                <h2>Wszystkie zgłoszenia</h2>
                <div class="sub">{{ $tickets->total() }} zgłoszeń łącznie</div>
            </div>
            <a href="{{ route('tickets.create') }}" target="_blank" class="btn btn-small">Strona klienta</a>
        </div>

        <div class="content">

            <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
                <div class="stat-box"><div class="num">{{ $stats['escalated'] }}</div><div class="lbl">Otwarte / eskalowane</div></div>
                <div class="stat-box"><div class="num">{{ $stats['in_progress'] }}</div><div class="lbl">W trakcie</div></div>
                <div class="stat-box"><div class="num">{{ $stats['open'] }}</div><div class="lbl">Otwarte</div></div>
            </div>

            <div class="box">
                <div class="box-head">Filtry</div>
                <div class="box-body">
                    <form method="GET" action="{{ route('admin.tickets.index') }}">
                        <div class="row2" style="grid-template-columns: 2fr 1fr 1fr 1fr 1fr; gap: 8px; align-items:end;">
                            <div class="field" style="margin-bottom:8px;">
                                <label class="f-label">Szukaj</label>
                                <input type="text" name="search" placeholder="Numer, temat, e-mail..." value="{{ request('search') }}">
                            </div>
                            <div class="field" style="margin-bottom:8px;">
                                <label class="f-label">Status</label>
                                <select name="status">
                                    <option value="">Wszystkie</option>
                                    @foreach(['open' => 'Otwarte', 'ai_handling' => 'AI', 'escalated' => 'Eskalowane', 'in_progress' => 'W trakcie', 'resolved' => 'Rozwiązane'] as $value => $label)
                                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="field" style="margin-bottom:8px;">
                                <label class="f-label">Priorytet</label>
                                <select name="priority">
                                    <option value="">Wszystkie</option>
                                    @foreach(['low' => 'Niski', 'medium' => 'Średni', 'high' => 'Wysoki', 'urgent' => 'Pilny'] as $value => $label)
                                        <option value="{{ $value }}" @selected(request('priority') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="field" style="margin-bottom:8px;">
                                <label class="f-label">Agent</label>
                                <select name="assigned_to">
                                    <option value="">Wszyscy</option>
                                    @foreach($agents as $agent)
                                        <option value="{{ $agent->id }}" @selected(request('assigned_to') === (string) $agent->id)>{{ $agent->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary" style="margin-bottom:8px;">Filtruj</button>
                        </div>
                        @if(request()->anyFilled(['search', 'status', 'priority', 'assigned_to']))
                        <a href="{{ route('admin.tickets.index') }}" style="font-size:11px; color:#666666;">Wyczyść filtry</a>
                        @endif
                    </form>
                </div>
            </div>

            <div class="box">
                <table class="plain">
                    <thead>
                        <tr><th>Numer</th><th>Temat</th><th>Status</th><th>Priorytet</th><th>Przypisany</th><th>Czas</th></tr>
                    </thead>
                    <tbody>
                        @forelse($tickets as $ticket)
                        <tr class="clickable" onclick="location.href='{{ route('admin.tickets.show', $ticket->id) }}'">
                            <td><code>{{ $ticket->ticket_number }}</code></td>
                            <td>
                                {{ Str::limit($ticket->subject, 45) }}<br>
                                <span style="color:#888888; font-size:11px;">{{ $ticket->customer_name }} · {{ $ticket->customer_email }}</span>
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
                            <td style="color:#666666; font-size:12px;">
                                @php $assignedAgent = $agents->firstWhere('id', $ticket->assigned_to); @endphp
                                {{ $assignedAgent->name ?? '—' }}
                            </td>
                            <td style="color:#666666; font-size:12px;">{{ $ticket->created_at->diffForHumans() }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" style="text-align:center; color:#888888; padding: 16px;">Brak zgłoszeń spełniających kryteria</td></tr>
                        @endforelse
                    </tbody>
                </table>
                @if($tickets->hasPages())
                <div style="padding: 10px 12px; border-top: 1px solid #dddddd; display:flex; justify-content:space-between; align-items:center; font-size:12px; background:white;">
                    <span style="color:#666666;">Strona {{ $tickets->currentPage() }} z {{ $tickets->lastPage() }}</span>
                    {{ $tickets->appends(request()->query())->links() }}
                </div>
                @endif
            </div>

        </div>
    </main>
</div>

</body>
</html>
