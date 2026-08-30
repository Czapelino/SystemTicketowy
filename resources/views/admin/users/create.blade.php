<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dodaj agenta — TicketSystem</title>
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
        <div class="sidebar-footer">{{ auth()->user()->name }}</div>
    </aside>

    <main class="main">
        <div class="admin-topbar"><h2>Dodaj agenta</h2></div>

        <div class="content" style="max-width: 560px;">
            <div class="box">
                <div class="box-body">
                    <form action="{{ route('admin.users.store') }}" method="POST">
                        @csrf

                        @if($errors->any())
                            <div class="alert alert-error">
                                <ul style="margin:0; padding-left:16px;">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="field">
                            <label class="f-label">Imię i nazwisko</label>
                            <input type="text" name="name" value="{{ old('name') }}" required>
                        </div>

                        <div class="field">
                            <label class="f-label">E-mail</label>
                            <input type="email" name="email" value="{{ old('email') }}" required>
                        </div>

                        <div class="field">
                            <label class="f-label">Hasło</label>
                            <input type="password" name="password" minlength="8" required>
                        </div>

                        <div class="row2">
                            <div class="field">
                                <label class="f-label">Rola</label>
                                <select name="role" required>
                                    <option value="agent" {{ old('role') === 'agent' ? 'selected' : '' }}>Agent</option>
                                    <option value="supervisor" {{ old('role') === 'supervisor' ? 'selected' : '' }}>Supervisor</option>
                                    <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Administrator</option>
                                </select>
                            </div>
                            <div class="field">
                                <label class="f-label">Dział</label>
                                <input type="text" name="department" value="{{ old('department') }}">
                            </div>
                        </div>

                        <div class="field">
                            <label class="f-label">Telefon</label>
                            <input type="text" name="phone" value="{{ old('phone') }}">
                        </div>

                        <div class="checkbox-row">
                            <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                            <label for="is_active">Konto aktywne</label>
                        </div>

                        <button type="submit" class="btn btn-primary">Zapisz</button>
                        <a href="{{ route('admin.users.index') }}" class="btn">Anuluj</a>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

</body>
</html>

