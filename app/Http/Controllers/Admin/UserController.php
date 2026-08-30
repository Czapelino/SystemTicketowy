<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * List all agents/admins
     */
    public function index()
    {
        $users = User::orderBy('created_at', 'desc')->get();

        return view('admin.users.index', compact('users'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        return view('admin.users.create');
    }

    /**
     * Store new agent
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_AGENT, User::ROLE_SUPERVISOR])],
            'department' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['is_active'] = $request->boolean('is_active', true);

        User::create($validated);

        return redirect()->route('admin.users.index')->with('success', 'Agent został dodany.');
    }

    /**
     * Redirect show to edit (no separate detail page)
     */
    public function show(string $id): RedirectResponse
    {
        return redirect()->route('admin.users.edit', $id);
    }

    /**
     * Show edit form
     */
    public function edit(string $id)
    {
        $user = User::findOrFail($id);

        return view('admin.users.edit', compact('user'));
    }

    /**
     * Update agent
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|string|min:8',
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_AGENT, User::ROLE_SUPERVISOR])],
            'department' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        $user->update($validated);

        return redirect()->route('admin.users.index')->with('success', 'Dane agenta zaktualizowane.');
    }

    /**
     * Delete agent
     */
    public function destroy(string $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')->with('error', 'Nie możesz usunąć własnego konta.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'Agent został usunięty.');
    }

    /**
     * Toggle active status
     */
    public function toggleActive(string $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')->with('error', 'Nie możesz dezaktywować własnego konta.');
        }

        $user->update(['is_active' => !$user->is_active]);

        return redirect()->route('admin.users.index')->with('success', 'Status agenta zaktualizowany.');
    }
}

