<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instrukcje / Baza wiedzy — TicketSystem</title>
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
        <a href="{{ route('admin.instructions.index') }}" class="active">Instrukcje / Baza wiedzy</a>
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
                <h2>Instrukcje / Baza wiedzy</h2>
                <div class="sub">{{ $instructions->count() }} wpisów — AI korzysta z aktywnych przy odpowiadaniu klientom</div>
            </div>
            <a href="{{ route('admin.instructions.create') }}" class="btn btn-primary btn-small">Dodaj instrukcję</a>
        </div>

        <div class="content">

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="info-note" style="margin-bottom:15px;">
                Treść aktywnych instrukcji jest automatycznie dołączana do promptu systemowego asystenta AI.
                Dzięki temu AI odpowiada zgodnie z Twoimi wewnętrznymi zasadami, procedurami i wiedzą specyficzną dla firmy —
                zamiast zgadywać. Nieaktywne instrukcje są ignorowane przez AI, ale zostają zapisane na przyszłość.
            </div>

            <div class="box">
                <table class="plain">
                    <thead>
                        <tr><th>Tytuł</th><th>Treść (podgląd)</th><th>Autor</th><th>Status</th><th>Akcje</th></tr>
                    </thead>
                    <tbody>
                        @forelse($instructions as $instruction)
                        <tr>
                            <td style="font-weight:bold;">{{ $instruction->title }}</td>
                            <td style="color:#555555; font-size:12px;">{{ Str::limit(strip_tags($instruction->content), 80) }}</td>
                            <td style="color:#888888; font-size:11px;">{{ $instruction->created_by ?? '—' }}</td>
                            <td>
                                <span class="{{ $instruction->is_active ? 'dot-green' : 'dot-red' }}">&#9679;</span>
                                {{ $instruction->is_active ? 'Aktywna (AI używa)' : 'Nieaktywna' }}
                            </td>
                            <td>
                                <a href="{{ route('admin.instructions.edit', $instruction->id) }}" class="icon-link">Edytuj</a>
                                &nbsp;|&nbsp;
                                <form action="{{ route('admin.instructions.toggle', $instruction->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="icon-link">{{ $instruction->is_active ? 'Wyłącz' : 'Włącz' }}</button>
                                </form>
                                &nbsp;|&nbsp;
                                <form action="{{ route('admin.instructions.destroy', $instruction->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Na pewno usunąć tę instrukcję?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="icon-link" style="color:#aa3333;">Usuń</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" style="text-align:center; color:#888888; padding:16px;">Brak instrukcji. Dodaj pierwszą, aby AI korzystało z Twojej wiedzy.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </main>
</div>

</body>
</html>

