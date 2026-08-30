<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logowanie — TicketSystem</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        body { min-height: 100vh; display: flex; align-items: center; justify-content: center; }
    </style>
</head>
<body>
<div class="login-box">
    <div class="login-head">
        <a href="{{ route('home') }}" class="logo" style="text-decoration:none; color:inherit;">TicketSystem</a>
        <div class="sub">Panel Administratora</div>
    </div>
    <div class="login-body">

        @if($errors->any())
        <div class="alert alert-error">{{ $errors->first() }}</div>
        @endif

        <form action="{{ route('login.post') }}" method="POST">
            @csrf
            <div class="field">
                <label class="f-label">Adres e-mail</label>
                <input type="email" name="email" placeholder="admin@helpdesk.pl" value="{{ old('email') }}" required autofocus>
            </div>
            <div class="field">
                <label class="f-label">Hasło</label>
                <input type="password" name="password" placeholder="********" required>
            </div>
            <div class="checkbox-row">
                <input type="checkbox" name="remember" id="remember">
                <label for="remember">Zapamiętaj mnie</label>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Zaloguj się</button>
        </form>

        <div class="footer-link">
            <a href="{{ route('home') }}">&larr; Wróć do strony klienta</a>
        </div>
    </div>
</div>
</body>
</html>
