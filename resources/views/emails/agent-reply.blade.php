<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nowa odpowiedź — {{ $ticket->ticket_number }}</title>
</head>
<body style="margin:0; padding:0; background-color:#0f172a; font-family: 'Segoe UI', Arial, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#0f172a; padding: 32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width: 560px; background-color:#1e293b; border-radius:16px; border:1px solid rgba(255,255,255,0.08); overflow:hidden;">

                    <!-- Header -->
                    <tr>
                        <td style="padding: 28px 32px 20px; border-bottom: 1px solid rgba(255,255,255,0.06);">
                            <span style="font-size:18px; font-weight:700; color:#ffffff;">
                                🤖 TicketSystem
                            </span>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding: 28px 32px;">
                            <p style="margin:0 0 4px; color:#818cf8; font-size:13px; font-weight:600; letter-spacing:0.5px; text-transform:uppercase;">
                                {{ $ticket->ticket_number }}
                            </p>
                            <h1 style="margin:0 0 20px; color:#f1f5f9; font-size:20px; font-weight:700;">
                                Otrzymałeś odpowiedź na zgłoszenie
                            </h1>

                            <p style="margin:0 0 24px; color:#94a3b8; font-size:14px; line-height:1.6;">
                                Do Twojego zgłoszenia o numerze <strong style="color:#e2e8f0;">{{ $ticket->ticket_number }}</strong>
                                została wysłana odpowiedź. Sprawdź pod linkiem:
                            </p>

                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin: 0 0 16px;">
                                <tr>
                                    <td style="border-radius:10px; background-color:#6366f1;">
                                        <a href="{{ route('tickets.chat', $ticket->id) }}"
                                           style="display:inline-block; padding:12px 28px; color:#ffffff; font-size:14px; font-weight:600; text-decoration:none;">
                                            Przejdź do rozmowy →
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0; color:#64748b; font-size:12px; word-break:break-all;">
                                <a href="{{ route('tickets.chat', $ticket->id) }}" style="color:#818cf8;">{{ route('tickets.chat', $ticket->id) }}</a>
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 20px 32px; border-top: 1px solid rgba(255,255,255,0.06);">
                            <p style="margin:0; color:#475569; font-size:12px; line-height:1.5;">
                                Otrzymujesz tę wiadomość, ponieważ jesteś zgłaszającym w zgłoszeniu {{ $ticket->ticket_number }}.
                                Jeśli chcesz sprawdzić status zgłoszenia w dowolnej chwili, przejdź na stronę
                                <a href="{{ route('tickets.status') }}" style="color:#6366f1;">sprawdzania statusu</a>.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
