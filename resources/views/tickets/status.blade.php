<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sprawdź status zgłoszenia — TicketSystem</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>

<div class="topbar">
    <a href="{{ route('home') }}" class="logo" style="text-decoration:none; color:inherit;">TicketSystem</a>
    <a href="{{ route('tickets.create') }}" class="btn-link">Nowe zgłoszenie</a>
</div>

<div class="wrap-narrow">

    <div class="page-title">
        <div class="icon-circle">🔍</div>
        <h1>Sprawdź status zgłoszenia</h1>
        <p>Podaj numer zgłoszenia i adres e-mail użyty przy zgłaszaniu</p>
    </div>

    @if($errors->any())
        <div class="alert alert-error">
            @foreach($errors->all() as $error)
                {{ $error }}
            @endforeach
        </div>
    @endif

    <div class="form-box">
        <form action="{{ route('tickets.status') }}" method="POST">
            @csrf
            <div class="field">
                <label class="f-label">Numer zgłoszenia *</label>
                <input type="text" name="ticket_number" placeholder="TKT-XXXXXXXX" value="{{ old('ticket_number') }}" required>
                @error('ticket_number') <div class="field-error">{{ $message }}</div> @enderror
            </div>
            <div class="field">
                <label class="f-label">Adres e-mail *</label>
                <input type="email" name="email" placeholder="jan@firma.pl" value="{{ old('email') }}" required>
                @error('email') <div class="field-error">{{ $message }}</div> @enderror
            </div>
            <button type="submit" class="btn btn-primary btn-block">Sprawdź status</button>
        </form>
    </div>

    <div class="footer-link">
        <a href="{{ route('tickets.create') }}">Nie masz jeszcze zgłoszenia? Utwórz nowe &rarr;</a>
    </div>
</div>

</body>
</html>

