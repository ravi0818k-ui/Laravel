<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\PgLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Room::with(['beds', 'pgLocation']);

        if ($user->isAdmin()) {
            $assignedIds = $user->assignedPgLocations()->pluck('pg_locations.id');
            $query->whereIn('pg_location_id', $assignedIds);
        }

        if ($request->has('pg_location_id')) {
            $query->where('pg_location_id', $request->pg_location_id);
        }

        $rooms = $query->get();

        return response()->json(['rooms' => $rooms]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pg_location_id' => 'required|exists:pg_locations,id',
            'room_number' => 'required|string|max:50',
            'floor' => 'required|integer|min:0',
            'room_type' => 'required|in:single,double,triple,quad',
            'total_beds' => 'required|integer|min:1|max:10',
            'has_attached_bathroom' => 'boolean',
            'has_ac' => 'boolean',
            'has_balcony' => 'boolean',
            'description' => 'nullable|string',
        ]);

        // Check for duplicate room number in the same PG
        $exists = Room::where('pg_location_id', $validated['pg_location_id'])
            ->where('room_number', $validated['room_number'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => "Room '{$validated['room_number']}' already exists in this PG location.",
            ], 422);
        }

        $room = Room::create($validated);

        return response()->json([
            'message' => 'Room created successfully.',
            'room' => $room,
        ], 201);
    }

    public function show(Room $room): JsonResponse
    {
        $room->load(['beds', 'pgLocation']);

        return response()->json(['room' => $room]);
    }

    /**
     * Admin: delete a room (only if no occupied beds).
     */
    public function destroy(Room $room): JsonResponse
    {
        $occupiedBeds = $room->beds()->where('status', 'occupied')->count();

        if ($occupiedBeds > 0) {
            return response()->json([
                'message' => "Cannot delete this room. It has {$occupiedBeds} occupied bed(s). Vacate all tenants first.",
            ], 422);
        }

        // Delete all beds in the room first (cascade would handle it, but be explicit)
        $room->beds()->delete();
        $room->delete();

        return response()->json([
            'message' => 'Room and its beds deleted successfully.',
        ]);
    }
}
