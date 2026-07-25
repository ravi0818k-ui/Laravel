<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BedController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Bed::with(['room.pgLocation', 'currentAllocation.tenant']);

        if ($request->has('room_id')) {
            $query->where('room_id', $request->room_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $beds = $query->get();

        return response()->json(['beds' => $beds]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'bed_number' => 'required|string|max:20',
            'monthly_rent' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:255',
        ]);

        // Check for duplicate bed number in the same room
        $exists = Bed::where('room_id', $validated['room_id'])
            ->where('bed_number', $validated['bed_number'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => "Bed '{$validated['bed_number']}' already exists in this room.",
            ], 422);
        }

        $bed = Bed::create($validated);

        return response()->json([
            'message' => 'Bed created successfully.',
            'bed' => $bed,
        ], 201);
    }

    public function update(Request $request, Bed $bed): JsonResponse
    {
        $validated = $request->validate([
            'monthly_rent' => 'numeric|min:0',
            'status' => 'in:available,occupied,reserved,maintenance',
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $bed->update($validated);

        return response()->json([
            'message' => 'Bed updated.',
            'bed' => $bed->fresh(),
        ]);
    }

    /**
     * Admin: delete a bed (only if not occupied).
     */
    public function destroy(Bed $bed): JsonResponse
    {
        if ($bed->status === 'occupied') {
            return response()->json([
                'message' => 'Cannot delete an occupied bed. Please vacate the tenant first.',
            ], 422);
        }

        $bed->delete();

        return response()->json([
            'message' => 'Bed deleted successfully.',
        ]);
    }
}
