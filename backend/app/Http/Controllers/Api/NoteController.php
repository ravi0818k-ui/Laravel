<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Note;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    /**
     * List notes for the current admin/super_admin.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Note::with('user')->orderByDesc('is_pinned')->orderByDesc('updated_at');

        if ($user->isAdmin()) {
            // Admin sees own notes + notes for their assigned PGs
            $assignedPgIds = $user->assignedPgLocations()->pluck('pg_locations.id');
            $query->where(function ($q) use ($user, $assignedPgIds) {
                $q->where('user_id', $user->id)
                  ->orWhereIn('pg_location_id', $assignedPgIds);
            });
        }
        // Super admin sees all notes

        $notes = $query->get();

        return response()->json(['notes' => $notes]);
    }

    /**
     * Create a new note.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'pg_location_id' => 'nullable|exists:pg_locations,id',
            'is_pinned' => 'boolean',
        ]);

        $note = Note::create([
            ...$validated,
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Note created.',
            'note' => $note,
        ], 201);
    }

    /**
     * Update a note.
     */
    public function update(Request $request, Note $note): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'string|max:255',
            'description' => 'nullable|string',
            'is_pinned' => 'boolean',
        ]);

        $note->update($validated);

        return response()->json([
            'message' => 'Note updated.',
            'note' => $note->fresh(),
        ]);
    }

    /**
     * Delete a note.
     */
    public function destroy(Note $note): JsonResponse
    {
        $note->delete();

        return response()->json([
            'message' => 'Note deleted.',
        ]);
    }
}
