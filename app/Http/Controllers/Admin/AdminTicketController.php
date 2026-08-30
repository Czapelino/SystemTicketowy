<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Ticket;
use App\Models\User;
use App\Services\ChatGPTService;
use App\Services\TicketService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminTicketController extends Controller
{
    public function __construct(
        private TicketService $ticketService,
        private ChatGPTService $chatGPT
    ) {}

    /**
     * Admin dashboard
     */
    public function dashboard()
    {
        $stats = $this->ticketService->getDashboardStats();
        $recentTickets = Ticket::orderBy('created_at', 'desc')->limit(10)->get();
        $agents = User::where('is_active', true)->get();

        return view('admin.dashboard', compact('stats', 'recentTickets', 'agents'));
    }

    /**
     * List all tickets with filters
     */
    public function index(Request $request)
    {
        $query = Ticket::query();

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($priority = $request->get('priority')) {
            $query->where('priority', $priority);
        }
        if ($category = $request->get('category')) {
            $query->where('category', $category);
        }
        if ($assignedTo = $request->get('assigned_to')) {
            $query->where('assigned_to', $assignedTo);
        }
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                  ->orWhere('ticket_number', 'like', "%{$search}%")
                  ->orWhere('customer_email', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        $tickets = $query->orderBy('created_at', 'desc')->paginate(20);
        $agents = User::where('is_active', true)->get();
        $stats = [
            'open' => Ticket::where('status', Ticket::STATUS_OPEN)->count(),
            'escalated' => Ticket::where('status', Ticket::STATUS_ESCALATED)->count(),
            'in_progress' => Ticket::where('status', Ticket::STATUS_IN_PROGRESS)->count(),
        ];

        return view('admin.tickets.index', compact('tickets', 'agents', 'stats'));
    }

    /**
     * Show ticket detail with full conversation
     */
    public function show(string $ticketId)
    {
        $ticket = Ticket::findOrFail($ticketId);
        $messages = $ticket->messages()->orderBy('created_at', 'asc')->get();
        $agents = User::where('is_active', true)->get();
        $aiSuggestion = null;

        if ($ticket->status === Ticket::STATUS_ESCALATED) {
            $aiSuggestion = $this->chatGPT->generateAgentSuggestion($ticket);
        }

        return view('admin.tickets.show', compact('ticket', 'messages', 'agents', 'aiSuggestion'));
    }

    /**
     * Agent sends message to customer
     */
    public function sendMessage(Request $request, string $ticketId): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|min:1|max:5000',
            'is_internal' => 'boolean',
        ]);

        $ticket = Ticket::findOrFail($ticketId);
        $agent = auth()->user();

        $message = $this->ticketService->handleAgentMessage(
            $ticket,
            $agent,
            $request->message,
            $request->boolean('is_internal', false)
        );

        // Update status to in_progress when agent first responds
        if ($ticket->status === Ticket::STATUS_ESCALATED) {
            $ticket->update(['status' => Ticket::STATUS_IN_PROGRESS]);
        }

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $message->id,
                'content' => $message->content,
                'role' => $message->role,
                'type' => $message->type,
                'sender_name' => $message->sender_name,
                'created_at' => $message->created_at->format('H:i'),
            ],
        ]);
    }

    /**
     * Assign ticket to agent
     */
    public function assign(Request $request, string $ticketId): JsonResponse
    {
        $request->validate(['agent_id' => 'required|string']);

        $ticket = Ticket::findOrFail($ticketId);
        $agent = User::findOrFail($request->agent_id);

        $ticket->update([
            'assigned_to' => $agent->id,
            'status' => Ticket::STATUS_IN_PROGRESS,
        ]);

        Message::create([
            'ticket_id' => $ticket->id,
            'role' => Message::ROLE_SYSTEM,
            'type' => Message::TYPE_STATUS_CHANGE,
            'content' => "Zgłoszenie przypisano do: {$agent->name}",
            'sender_name' => 'System',
        ]);

        return response()->json(['success' => true, 'agent_name' => $agent->name]);
    }

    /**
     * Resolve ticket
     */
    public function resolve(Request $request, string $ticketId): JsonResponse
    {
        $request->validate(['notes' => 'required|string|min:5']);

        $ticket = Ticket::findOrFail($ticketId);
        $this->ticketService->resolveTicket($ticket, auth()->user(), $request->notes);

        return response()->json(['success' => true]);
    }

    /**
     * Update ticket priority
     */
    public function updatePriority(Request $request, string $ticketId): JsonResponse
    {
        $request->validate([
            'priority' => 'required|in:low,medium,high,urgent',
        ]);

        $ticket = Ticket::findOrFail($ticketId);
        $ticket->update(['priority' => $request->priority]);

        return response()->json(['success' => true]);
    }

    /**
     * Escalate ticket manually
     */
    public function escalate(Request $request, string $ticketId): JsonResponse
    {
        $ticket = Ticket::findOrFail($ticketId);
        $this->ticketService->escalateTicket($ticket, $request->get('reason', 'manual'));

        return response()->json(['success' => true]);
    }
}
