<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PgLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PgController extends Controller
{
    /**
     * Public: list PG locations with availability (no auth required).
     * Returns data compatible with the static website frontend.
     */
    public function publicIndex(): JsonResponse
    {
        $locations = PgLocation::active()
            ->get()
            ->map(function ($pg) {
                // Decode metadata stored as JSON in description or dedicated columns
                $meta = is_array($pg->metadata) ? $pg->metadata : [];

                return [
                    'id' => $pg->id,
                    'slug' => $meta['slug'] ?? \Illuminate\Support\Str::slug($pg->name),
                    'name' => $pg->name,
                    'address' => $pg->address,
                    'city' => $pg->city,
                    'pincode' => $pg->pincode,
                    'description' => $pg->description,
                    'photos' => $pg->photos ?? [],
                    'starting_rent' => $pg->starting_rent,
                    'security_deposit' => $meta['security_deposit'] ?? 0,
                    'latitude' => $pg->latitude,
                    'longitude' => $pg->longitude,
                    'available_beds' => $pg->available_beds_count,
                    'total_beds' => $pg->total_beds_count,
                    // Frontend-specific fields (stored in pg_locations metadata or JSON column)
                    'sharing_type' => $meta['sharing_type'] ?? 'Double Sharing',
                    'whatsapp' => $meta['whatsapp'] ?? $pg->contact_mobile,
                    'phone' => $pg->contact_mobile,
                    'phone_display' => $meta['phone_display'] ?? $pg->contact_mobile,
                    'map_iframe' => $meta['map_iframe'] ?? '',
                    'map_link' => $meta['map_link'] ?? '',
                    'videos' => $meta['videos'] ?? [],
                    'amenities' => $meta['amenities'] ?? [],
                    'meals' => $meta['meals'] ?? '',
                    'tags' => $meta['tags'] ?? [],
                ];
            });

        return response()->json(['pg_locations' => $locations]);
    }

    /**
     * Admin/Super Admin: list all PG locations with full details.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = PgLocation::query();

        // Admin only sees assigned PGs (if they have any assigned)
        if ($user->isAdmin()) {
            $assignedIds = $user->assignedPgLocations()->pluck('pg_locations.id');
            if ($assignedIds->isNotEmpty()) {
                $query->whereIn('id', $assignedIds);
            }
            // If no PGs assigned, show all (so admin can still function)
        }

        $locations = $query->withCount(['rooms', 'tenants'])->get();

        return response()->json(['pg_locations' => $locations]);
    }

    /**
     * Super Admin: create a new PG location.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'city' => 'required|string|max:100',
            'state' => 'string|max:100',
            'pincode' => 'required|string|max:10',
            'tenant_id_prefix' => 'required|string|max:10|unique:pg_locations',
            'contact_mobile' => 'nullable|string|digits:10',
            'contact_email' => 'nullable|email',
            'description' => 'nullable|string',
            'starting_rent' => 'nullable|numeric|min:0',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'metadata' => 'nullable|array',
        ]);

        // Handle metadata for website display
        if ($request->has('metadata')) {
            $validated['metadata'] = $request->metadata;
        }

        $pgLocation = PgLocation::create($validated);

        return response()->json([
            'message' => 'PG location created successfully.',
            'pg_location' => $pgLocation,
        ], 201);
    }

    /**
     * Super Admin: update a PG location.
     */
    public function update(Request $request, PgLocation $pgLocation): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'address' => 'string',
            'city' => 'string|max:100',
            'state' => 'string|max:100',
            'pincode' => 'string|max:10',
            'contact_mobile' => 'nullable|string|digits:10',
            'contact_email' => 'nullable|email',
            'description' => 'nullable|string',
            'starting_rent' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'metadata' => 'nullable|array',
            'photos' => 'nullable|array',
        ]);

        $pgLocation->update($validated);

        return response()->json([
            'message' => 'PG location updated.',
            'pg_location' => $pgLocation->fresh(),
        ]);
    }

    public function show(PgLocation $pgLocation): JsonResponse
    {
        $pgLocation->load(['rooms.beds']);

        return response()->json(['pg_location' => $pgLocation]);
    }

    /**
     * Super Admin: upload photos for a PG location.
     */
    public function uploadPhotos(Request $request, PgLocation $pgLocation): JsonResponse
    {
        $request->validate([
            'photos' => 'required|array|min:1',
            'photos.*' => 'image|max:5120',
        ]);

        $existingPhotos = $pgLocation->photos ?? [];

        foreach ($request->file('photos') as $photo) {
            $path = $photo->store("pg_photos/{$pgLocation->id}", 'public');
            $existingPhotos[] = '/storage/' . $path;
        }

        $pgLocation->update(['photos' => $existingPhotos]);

        return response()->json([
            'message' => count($request->file('photos')) . ' photo(s) uploaded.',
            'photos' => $existingPhotos,
        ]);
    }

    /**
     * Super Admin: remove a photo from PG location.
     */
    public function removePhoto(Request $request, PgLocation $pgLocation): JsonResponse
    {
        $request->validate(['photo_url' => 'required|string']);

        $photos = $pgLocation->photos ?? [];
        $photos = array_values(array_filter($photos, fn($p) => $p !== $request->photo_url));
        $pgLocation->update(['photos' => $photos]);

        // Delete file from storage
        $path = str_replace('/storage/', '', $request->photo_url);
        \Illuminate\Support\Facades\Storage::disk('public')->delete($path);

        return response()->json(['message' => 'Photo removed.', 'photos' => $photos]);
    }
}
