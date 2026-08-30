<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zarządzanie agentami — TicketSystem</title>
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
        <a href="{{ route('admin.users.index') }}" class="active">Zarządzanie agentami</a>
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
                <h2>Zarządzanie agentami</h2>
                <div class="sub">{{ $users->count() }} kont łącznie</div>
            </div>
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-small">Dodaj agenta</a>
        </div>

        <div class="content">

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-error">{{ session('error') }}</div>
            @endif

            <div class="box">
                <table class="plain">
                    <thead>
                        <tr><th>Agent</th><th>Rola</th><th>Dział</th><th>Status</th><th>Aktywne zgłoszenia</th><th>Akcje</th></tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                        <tr>
                            <td>{{ $user->name }}<br><span style="color:#888888; font-size:11px;">{{ $user->email }}</span></td>
                            <td>
                                <span class="badge badge-role-{{ $user->role }}">
                                    {{ match($user->role) {
                                        'admin' => 'Administrator', 'supervisor' => 'Supervisor', 'agent' => 'Agent', default => ucfirst($user->role),
                                    } }}
                                </span>
                            </td>
                            <td style="color:#555555;">{{ $user->department ?? '—' }}</td>
                            <td>
                                <span class="{{ $user->is_active ? 'dot-green' : 'dot-red' }}">&#9679;</span>
                                {{ $user->is_active ? 'Aktywny' : 'Nieaktywny' }}
                            </td>
                            <td>{{ $user->open_tickets_count }}</td>
                            <td>
                                <a href="{{ route('admin.users.edit', $user->id) }}" class="icon-link">Edytuj</a>
                                @if($user->id !== auth()->id())
                                &nbsp;|&nbsp;
                                <form action="{{ route('admin.users.toggle', $user->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="icon-link">{{ $user->is_active ? 'Dezaktywuj' : 'Aktywuj' }}</button>
                                </form>
                                &nbsp;|&nbsp;
                                <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Na pewno usunąć tego agenta?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="icon-link" style="color:#aa3333;">Usuń</button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" style="text-align:center; color:#888888; padding:16px;">Brak agentów</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </main>
</div>

</body>
</html>

