<?php
namespace App\Http\Controllers;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
class TicketController extends Controller
{
    public function __construct(
        private TicketService $ticketService
    ) {}
    /**
     * Show ticket submission form
     */
    public function create()
    {
        return view('tickets.create');
    }
    /**
     * Submit new ticket
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:100',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'subject' => 'required|string|max:200',
            'description' => 'required|string|min:10|max:5000',
            'category' => 'required|in:technical,billing,general,account,complaint,other',
        ]);
        $ticket = $this->ticketService->createTicket($validated);
        return redirect()->route('tickets.chat', $ticket->id)
            ->with('success', "Zgłoszenie {$ticket->ticket_number} zostało utworzone!");
    }
    /**
     * Show chat interface for ticket
     */
    public function chat(string $ticketId)
    {
        $ticket = Ticket::findOrFail($ticketId);
        $messages = $ticket->messages()
            ->orderBy('created_at', 'asc')
            ->get();
        return view('tickets.chat', compact('ticket', 'messages'));
    }
    /**
     * Send message via AJAX
     */
    public function sendMessage(Request $request, string $ticketId): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|min:1|max:2000',
        ]);
        $ticket = Ticket::findOrFail($ticketId);
        if (in_array($ticket->status, [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED])) {
            return response()->json(['error' => 'Zgłoszenie jest zamknięte.'], 422);
        }
        $result = $this->ticketService->handleCustomerMessage($ticket, $request->message);
        $ticket->refresh();
        return response()->json([
            'success' => true,
            'escalated' => $result['escalated'],
            'ai_message' => $result['message'] ? [
                'id' => $result['message']->id,
                'content' => $result['message']->content,
                'role' => $result['message']->role,
                'created_at' => $result['message']->created_at->format('H:i'),
            ] : null,
            'ticket_status' => $ticket->status,
        ]);
    }

    /**
     * Poll for new messages and status changes (used by the customer chat
     * while a ticket is escalated / being handled by an agent, so replies
     * appear without the customer having to refresh the page).
     */
    public function updates(Request $request, string $ticketId): JsonResponse
    {
        $ticket = Ticket::findOrFail($ticketId);

        $query = $ticket->messages()->orderBy('created_at', 'asc');

        if ($afterId = $request->query('after')) {
            $afterMessage = $ticket->messages()->find($afterId);
            if ($afterMessage) {
                $query->where('created_at', '>', $afterMessage->created_at);
            }
        }

        $messages = $query->get();

        return response()->json([
            'status' => $ticket->status,
            'messages' => $messages->map(fn ($m) => [
                'id' => $m->id,
                'role' => $m->role,
                'type' => $m->type,
                'content' => $m->content,
                'sender_name' => $m->sender_name,
                'created_at' => $m->created_at->format('H:i'),
            ]),
        ]);
    }

    /**
     * Check ticket status by email + ticket number (public lookup)
     */
    public function status(Request $request)
    {
        if ($request->isMethod('post')) {
            $request->validate([
                'ticket_number' => 'required|string',
                'email' => 'required|email',
            ]);
            $ticket = Ticket::where('ticket_number', $request->ticket_number)
                ->where('customer_email', $request->email)
                ->first();
            if (!$ticket) {
                return back()->withErrors(['ticket_number' => 'Nie znaleziono zgłoszenia.']);
            }
            return redirect()->route('tickets.chat', $ticket->id);
        }
        return view('tickets.status');
    }
}
