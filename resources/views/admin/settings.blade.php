<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ustawienia — TicketSystem</title>
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
        <a href="{{ route('admin.reports') }}">Raporty</a>
        <a href="{{ route('admin.settings') }}" class="active">Ustawienia</a>
        <div class="sidebar-footer">
            {{ auth()->user()->name }}
            <form method="POST" action="{{ route('logout') }}" style="margin-top:4px;">
                @csrf
                <button type="submit">Wyloguj</button>
            </form>
        </div>
    </aside>

    <main class="main">
        <div class="admin-topbar"><h2>Ustawienia</h2></div>

        <div class="content" style="max-width: 640px;">

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-error">
                    <ul style="margin:0; padding-left:16px;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Moje konto -->
            <div class="box">
                <div class="box-head">Moje konto</div>
                <div class="box-body">
                    <form action="{{ route('admin.users.update', auth()->id()) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="role" value="{{ auth()->user()->role }}">
                        <input type="hidden" name="is_active" value="1">

                        <div class="field">
                            <label class="f-label">Imię i nazwisko</label>
                            <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}" required>
                        </div>

                        <div class="field">
                            <label class="f-label">E-mail</label>
                            <input type="email" name="email" value="{{ old('email', auth()->user()->email) }}" required>
                        </div>

                        <div class="row2">
                            <div class="field">
                                <label class="f-label">Dział</label>
                                <input type="text" name="department" value="{{ old('department', auth()->user()->department) }}">
                            </div>
                            <div class="field">
                                <label class="f-label">Telefon</label>
                                <input type="text" name="phone" value="{{ old('phone', auth()->user()->phone) }}">
                            </div>
                        </div>

                        <div class="field">
                            <label class="f-label">Nowe hasło (zostaw puste, aby nie zmieniać)</label>
                            <input type="password" name="password" minlength="8">
                        </div>

                        <button type="submit" class="btn btn-primary">Zapisz zmiany</button>
                    </form>
                </div>
            </div>

            <!-- Konfiguracja OpenAI -->
            <div class="box">
                <div class="box-head">Konfiguracja OpenAI / ChatGPT</div>
                <div class="settings-row">
                    <div class="lbl">Model AI:</div>
                    <div class="val">{{ config('openai.model', 'gpt-4o-mini') }}</div>
                </div>
                <div class="settings-row">
                    <div class="lbl">Klucz API OpenAI:</div>
                    <div class="val">
                        @if(config('openai.api_key'))
                            <span class="dot-green">&#9679;</span>
                            sk-...{{ substr(config('openai.api_key'), -6) }}
                        @else
                            <span class="dot-red">&#9679;</span> BRAK KLUCZA!
                        @endif
                    </div>
                </div>
                <div class="settings-row">
                    <div class="lbl">Maks. tokenów na odpowiedź:</div>
                    <div class="val">{{ config('openai.max_tokens', 1000) }}</div>
                </div>
            </div>

            <!-- Informacje systemowe -->
            <div class="box">
                <div class="box-head">Informacje o systemie</div>
                <div class="settings-row">
                    <div class="lbl">Wersja Laravel:</div>
                    <div class="val">{{ app()->version() }}</div>
                </div>
                <div class="settings-row">
                    <div class="lbl">Wersja PHP:</div>
                    <div class="val">{{ PHP_VERSION }}</div>
                </div>
                <div class="settings-row">
                    <div class="lbl">Baza danych:</div>
                    <div class="val">
                        @php
                            $dbOk = false;
                            try {
                                \Illuminate\Support\Facades\DB::connection()->getMongoDB()->command(['ping' => 1]);
                                $dbOk = true;
                            } catch (\Throwable $e) {
                                $dbOk = false;
                            }
                        @endphp
                        <span class="{{ $dbOk ? 'dot-green' : 'dot-red' }}">&#9679;</span>
                        MongoDB {{ $dbOk ? '— połączono' : '— brak połączenia' }}
                    </div>
                </div>
                <div class="settings-row">
                    <div class="lbl">Środowisko:</div>
                    <div class="val">{{ app()->environment() }}</div>
                </div>
            </div>

        </div>
    </main>
</div>

</body>
</html>
