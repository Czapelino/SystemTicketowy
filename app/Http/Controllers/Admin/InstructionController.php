<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Instruction;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class InstructionController extends Controller
{
    /**
     * List all knowledge base entries
     */
    public function index()
    {
        $instructions = Instruction::orderBy('created_at', 'desc')->get();

        return view('admin.instructions.index', compact('instructions'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        return view('admin.instructions.create');
    }

    /**
     * Store new entry
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'content' => 'required|string|max:10000',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['created_by'] = auth()->user()->name;

        Instruction::create($validated);

        return redirect()->route('admin.instructions.index')->with('success', 'Instrukcja została dodana.');
    }

    /**
     * Show edit form
     */
    public function edit(string $id)
    {
        $instruction = Instruction::findOrFail($id);

        return view('admin.instructions.edit', compact('instruction'));
    }

    /**
     * Update entry
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        $instruction = Instruction::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'content' => 'required|string|max:10000',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $instruction->update($validated);

        return redirect()->route('admin.instructions.index')->with('success', 'Instrukcja została zaktualizowana.');
    }

    /**
     * Delete entry
     */
    public function destroy(string $id): RedirectResponse
    {
        Instruction::findOrFail($id)->delete();

        return redirect()->route('admin.instructions.index')->with('success', 'Instrukcja została usunięta.');
    }

    /**
     * Toggle active status (whether AI should use this entry)
     */
    public function toggleActive(string $id): RedirectResponse
    {
        $instruction = Instruction::findOrFail($id);
        $instruction->update(['is_active' => !$instruction->is_active]);

        return redirect()->route('admin.instructions.index')->with('success', 'Status instrukcji zaktualizowany.');
    }
}

