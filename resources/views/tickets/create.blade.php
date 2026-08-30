<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nowe zgłoszenie — TicketSystem</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>

<div class="topbar">
    <a href="{{ route('home') }}" class="logo" style="text-decoration:none; color:inherit;">TicketSystem</a>
    <a href="{{ route('tickets.status') }}" class="btn-link">Sprawdź zgłoszenie</a>
</div>

<div class="wrap-wide">

    <div class="page-title">
        <h1>Nowe zgłoszenie</h1>
        <p>AI odpowie natychmiast — konsultant gdy potrzeba</p>
    </div>

    <div class="steps">
        <div class="step-dot active">1</div>
        <div class="step-line"></div>
        <div class="step-dot">2</div>
        <div class="step-line"></div>
        <div class="step-dot">3</div>
    </div>

    @if(session('error'))
        <div class="alert alert-error">{{ session('error') }}</div>
    @endif

    <div class="form-box">
        <form action="{{ route('tickets.store') }}" method="POST" id="ticketForm">
            @csrf

            <div class="section-label">Twoje dane</div>
            <div class="row2">
                <div class="field">
                    <label class="f-label">Imię i nazwisko *</label>
                    <input type="text" name="customer_name" placeholder="Jan Kowalski" value="{{ old('customer_name') }}" required>
                    @error('customer_name') <div class="field-error">{{ $message }}</div> @enderror
                </div>
                <div class="field">
                    <label class="f-label">Adres e-mail *</label>
                    <input type="email" name="customer_email" placeholder="jan@firma.pl" value="{{ old('customer_email') }}" required>
                    @error('customer_email') <div class="field-error">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="field">
                <label class="f-label">Telefon (opcjonalnie)</label>
                <input type="tel" name="customer_phone" placeholder="+48 600 000 000" value="{{ old('customer_phone') }}">
            </div>

            <div class="section-label">Kategoria problemu</div>
            <input type="hidden" name="category" id="categoryInput" value="{{ old('category', 'general') }}" required>
            <div class="cat-grid">
                @foreach([
                    ['technical', '🔧', 'Techniczny'],
                    ['billing', '💳', 'Płatności'],
                    ['account', '👤', 'Konto'],
                    ['general', '💬', 'Ogólny'],
                    ['complaint', '⚠️', 'Reklamacja'],
                    ['other', '📋', 'Inne'],
                ] as [$val, $icon, $label])
                <div class="cat-btn {{ old('category', 'general') === $val ? 'selected' : '' }}" onclick="selectCategory('{{ $val }}', this)">
                    <span class="icon">{{ $icon }}</span>{{ $label }}
                </div>
                @endforeach
            </div>

            <div class="section-label">Opis problemu</div>
            <div class="field">
                <label class="f-label">Temat zgłoszenia *</label>
                <input type="text" name="subject" placeholder="Krótko opisz problem..." value="{{ old('subject') }}" required maxlength="200">
                @error('subject') <div class="field-error">{{ $message }}</div> @enderror
            </div>
            <div class="field">
                <label class="f-label">Szczegółowy opis *</label>
                <textarea name="description" rows="5" placeholder="Opisz dokładnie co się dzieje, kiedy problem wystąpił, co próbowałeś/aś zrobić..." required minlength="10" maxlength="5000">{{ old('description') }}</textarea>
                @error('description') <div class="field-error">{{ $message }}</div> @enderror
                <div class="char-count"><span id="charCount">0</span>/5000 znaków</div>
            </div>

            <div class="info-note">
                <strong>Asystent AI jest gotowy!</strong> Po przesłaniu formularza AI natychmiast przeanalizuje Twój problem i zacznie pomagać przez chat.
            </div>

            <button type="submit" class="btn btn-primary btn-block" id="submitBtn">Wyślij i rozpocznij rozmowę z AI</button>
        </form>
    </div>

    <div class="footer-link">
        <a href="{{ route('tickets.status') }}">Masz już zgłoszenie? Sprawdź status &rarr;</a>
    </div>
</div>

<script>
function selectCategory(value, el) {
    document.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('categoryInput').value = value;
}

const textarea = document.querySelector('textarea[name="description"]');
const counter = document.getElementById('charCount');
textarea.addEventListener('input', () => counter.textContent = textarea.value.length);
counter.textContent = textarea.value.length;

document.getElementById('ticketForm').addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    btn.textContent = 'Wysyłanie...';
    btn.disabled = true;
});
</script>
</body>
</html>

