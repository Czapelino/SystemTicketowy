<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raporty — TicketSystem</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>

<div class="layout">
    <aside class="sidebar">
        <a href="{{ route('admin.dashboard') }}" class="sidebar-logo" style="text-decoration:none; display:block;">System Ticketowy<span>Panel Administracyjny</span></a>
        <div class="sidebar-section">Główne</div>
        <a href="{{ route('admin.dashboard') }}">Dashboard</a>
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
        <a href="{{ route('admin.reports') }}" class="active">Raporty</a>
        <a href="{{ route('admin.settings') }}">Ustawienia</a>
        <div class="sidebar-footer">{{ auth()->user()->name }}</div>
    </aside>

    <main class="main">
        <div class="admin-topbar">
            <h2>Raporty</h2>
            <div class="sub">{{ now()->format('l, d F Y') }}</div>
        </div>

        <div class="content">

            <div class="stats-grid">
                <div class="stat-box"><div class="num">{{ $stats['total'] }}</div><div class="lbl">Wszystkie zgłoszenia</div></div>
                <div class="stat-box"><div class="num">{{ $stats['resolved_today'] }}</div><div class="lbl">Rozwiązane dziś</div></div>
                <div class="stat-box"><div class="num">{{ $stats['ai_resolution_rate'] }}%</div><div class="lbl">Skuteczność AI</div></div>
                <div class="stat-box"><div class="num">{{ $stats['avg_resolution_time'] }}</div><div class="lbl">Śr. czas rozwiązania</div></div>
            </div>

            <div class="row2" style="grid-template-columns: 2fr 1fr 1fr; align-items:start;">

                <div class="box">
                    <div class="box-head">Zgłoszenia wg statusu</div>
                    <div class="box-body">
                        @php
                            $statusLabels = [
                                'open' => 'Otwarte', 'ai_handling' => 'Obsługiwane przez AI', 'escalated' => 'Eskalowane',
                                'in_progress' => 'W trakcie', 'resolved' => 'Rozwiązane', 'closed' => 'Zamknięte',
                            ];
                            $maxStatus = max(1, max($statusBreakdown));
                        @endphp
                        @foreach($statusBreakdown as $key => $count)
                            <div class="chart-row">
                                <div class="top">
                                    <span>{{ $statusLabels[$key] ?? ucfirst($key) }}</span>
                                    <strong>{{ $count }}</strong>
                                </div>
                                <div class="chart-bar-track">
                                    <div class="chart-bar-fill" style="width: {{ ($count / $maxStatus) * 100 }}%;"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="box">
                    <div class="box-head">Wg kategorii</div>
                    <div class="box-body">
                        @forelse($stats['by_category'] as $row)
                            <div class="settings-row" style="padding: 5px 0;">
                                <span class="lbl">{{ data_get($row, '_id', '—') }}</span>
                                <span class="val">{{ data_get($row, 'count', 0) }}</span>
                            </div>
                        @empty
                            <div style="color:#888888; font-size:12px;">Brak danych</div>
                        @endforelse
                    </div>
                </div>

                <div class="box">
                    <div class="box-head">Wg priorytetu</div>
                    <div class="box-body">
                        @php $priorityLabels = ['low' => 'Niski', 'medium' => 'Średni', 'high' => 'Wysoki', 'urgent' => 'Pilny']; @endphp
                        @forelse($stats['by_priority'] as $row)
                            @php $priorityKey = $row['_id'] ?? null; @endphp
                            <div class="settings-row" style="padding: 5px 0;">
                                <span class="lbl">{{ $priorityLabels[$priorityKey] ?? $priorityKey ?? '—' }}</span>
                                <span class="val">{{ $row['count'] ?? 0 }}</span>
                            </div>
                        @empty
                            <div style="color:#888888; font-size:12px;">Brak danych</div>
                        @endforelse
                    </div>
                </div>

            </div>

            <div class="box">
                <div class="box-head">Wydajność agentów</div>
                <table class="plain">
                    <thead>
                        <tr><th>Agent</th><th>Rola</th><th>Rozwiązane zgłoszenia</th><th>Aktywne zgłoszenia</th></tr>
                    </thead>
                    <tbody>
                        @forelse($agentPerformance as $agent)
                        <tr>
                            <td>{{ $agent->name }}</td>
                            <td style="color:#555555;">{{ ucfirst($agent->role) }}</td>
                            <td style="color:#228822; font-weight:bold;">{{ $agent->resolved_count }}</td>
                            <td>{{ $agent->open_tickets_count }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" style="text-align:center; color:#888888; padding:16px;">Brak agentów</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </main>
</div>

</body>
</html>

