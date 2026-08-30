<?php

use App\Http\Controllers\Admin\AdminTicketController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

// ============================================
// PUBLIC ROUTES - Customer facing
// ============================================

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Ticket submission and chat
Route::get('/submit', [TicketController::class, 'create'])->name('tickets.create');
Route::post('/submit', [TicketController::class, 'store'])->name('tickets.store');
Route::get('/ticket/{id}/chat', [TicketController::class, 'chat'])->name('tickets.chat');
Route::post('/ticket/{id}/message', [TicketController::class, 'sendMessage'])->name('tickets.message');
Route::get('/ticket/{id}/updates', [TicketController::class, 'updates'])->name('tickets.updates');
// TYMCZASOWE — podgląd szablonu e-maila do zrzutu ekranu, usunąć po użyciu
Route::get('/preview-email', function () {
    $ticket = \App\Models\Ticket::where('status', 'resolved')->latest()->first()
        ?? \App\Models\Ticket::latest()->first();
    $message = $ticket->messages()->where('role', 'agent')->latest()->first()
        ?? new \App\Models\Message(['content' => 'Przykładowa odpowiedź konsultanta.', 'sender_name' => 'Marek Nowak']);
    return new \App\Mail\AgentReplyNotification($ticket, $message);
});
Route::match(['get', 'post'], '/status', [TicketController::class, 'status'])->name('tickets.status');
// ============================================
// AUTH ROUTES
// ============================================

Route::get('/admin/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/admin/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/admin/logout', [LoginController::class, 'logout'])->name('logout');

// ============================================
// ADMIN ROUTES - Protected
// ============================================

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {

    // Dashboard
    Route::get('/', [AdminTicketController::class, 'dashboard'])->name('dashboard');

    // Tickets CRUD
    Route::get('/tickets', [AdminTicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/{id}', [AdminTicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{id}/message', [AdminTicketController::class, 'sendMessage'])->name('tickets.message');
    Route::post('/tickets/{id}/assign', [AdminTicketController::class, 'assign'])->name('tickets.assign');
    Route::post('/tickets/{id}/resolve', [AdminTicketController::class, 'resolve'])->name('tickets.resolve');
    Route::patch('/tickets/{id}/priority', [AdminTicketController::class, 'updatePriority'])->name('tickets.priority');
    Route::post('/tickets/{id}/escalate', [AdminTicketController::class, 'escalate'])->name('tickets.escalate');

    // User management (admin only)
    Route::middleware('can:admin-only')->group(function () {
        Route::resource('/users', UserController::class);
        Route::patch('/users/{id}/toggle', [UserController::class, 'toggleActive'])->name('users.toggle');
    });
    // Knowledge base / instructions used by the AI
    Route::resource('/instructions', \App\Http\Controllers\Admin\InstructionController::class)->except(['show']);
    Route::patch('/instructions/{id}/toggle', [\App\Http\Controllers\Admin\InstructionController::class, 'toggleActive'])->name('instructions.toggle');
// Reports
Route::get('/reports', function () {
    $stats = app(\App\Services\TicketService::class)->getDashboardStats();

    $statusBreakdown = [
        'open' => \App\Models\Ticket::where('status', \App\Models\Ticket::STATUS_OPEN)->count(),
        'ai_handling' => \App\Models\Ticket::where('status', \App\Models\Ticket::STATUS_AI_HANDLING)->count(),
        'escalated' => \App\Models\Ticket::where('status', \App\Models\Ticket::STATUS_ESCALATED)->count(),
        'in_progress' => \App\Models\Ticket::where('status', \App\Models\Ticket::STATUS_IN_PROGRESS)->count(),
        'resolved' => \App\Models\Ticket::where('status', \App\Models\Ticket::STATUS_RESOLVED)->count(),
        'closed' => \App\Models\Ticket::where('status', \App\Models\Ticket::STATUS_CLOSED)->count(),
    ];

    $agentPerformance = \App\Models\User::where('role', '!=', \App\Models\User::ROLE_ADMIN)
        ->get()
        ->map(function ($agent) {
            $agent->resolved_count = \App\Models\Ticket::where('assigned_to', $agent->id)
                ->where('status', \App\Models\Ticket::STATUS_RESOLVED)
                ->count();
            return $agent;
        })
        ->sortByDesc('resolved_count')
        ->values();

    return view('admin.reports', compact('stats', 'statusBreakdown', 'agentPerformance'));
})->name('reports');

    // Settings
    Route::get('/settings', function () {
        return view('admin.settings');
    })->name('settings');
});
