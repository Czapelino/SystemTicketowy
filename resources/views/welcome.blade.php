<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TicketSystem</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        html, body { height: 100%; }
        body { display: flex; flex-direction: column; min-height: 100vh; }
        .hero { flex: 1; display: flex; flex-direction: column; justify-content: center; align-items: center; }
    </style>
</head>
<body>

<div class="topbar">
    <a href="{{ route('home') }}" class="logo" style="text-decoration:none; color:inherit;">TicketSystem</a>
    <div class="topbar-actions">
        <a href="{{ route('tickets.status') }}">Sprawdź zgłoszenie</a>
        <a href="{{ route('admin.dashboard') }}">Panel</a>
    </div>
</div>

<div class="hero">
    <div style="display: inline-block; text-align: center;">
        <h1 style="font-size: 4em; white-space: nowrap; margin: 0 0 24px;">TICKETSYSTEM</h1>
        <div style="display: flex; flex-direction: column; gap: 10px;">
            <a href="{{ route('tickets.create') }}" class="btn btn-primary" style="width: 100%; box-sizing: border-box; text-align: center;">Zgłoś problem</a>
            <a href="{{ route('tickets.status') }}" class="btn" style="width: 100%; box-sizing: border-box; text-align: center;">Sprawdź status</a>
        </div>
    </div>
</div>

<div class="pub-footer">&copy; {{ date('Y') }} TicketSystem — System ticketowy z ChatGPT | Laravel + MongoDB</div>

</body>
</html>
