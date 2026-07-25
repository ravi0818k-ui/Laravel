<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExpenseController extends Controller
{
    /**
     * List expenses, optionally filtered by month.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Expense::with('user')->orderByDesc('expense_date');

        if ($user->isAdmin()) {
            $assignedPgIds = $user->assignedPgLocations()->pluck('pg_locations.id');
            $query->where(function ($q) use ($user, $assignedPgIds) {
                $q->where('user_id', $user->id)
                  ->orWhereIn('pg_location_id', $assignedPgIds);
            });
        }

        // Filter by month (format: YYYY-MM)
        if ($request->has('month')) {
            $parts = explode('-', $request->month);
            if (count($parts) === 2) {
                $query->whereYear('expense_date', $parts[0])
                      ->whereMonth('expense_date', $parts[1]);
            }
        }

        if ($request->has('pg_location_id')) {
            $query->where('pg_location_id', $request->pg_location_id);
        }

        $expenses = $query->get();
        $total = $expenses->sum('amount');

        return response()->json([
            'expenses' => $expenses,
            'total' => $total,
        ]);
    }

    /**
     * Create a new expense.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
            'pg_location_id' => 'nullable|exists:pg_locations,id',
            'notes' => 'nullable|string|max:500',
            'bill_image' => 'nullable|image|max:5120',
        ]);

        $imagePath = null;
        if ($request->hasFile('bill_image')) {
            $imagePath = $request->file('bill_image')->store('expenses', 'local');
        }

        $expense = Expense::create([
            'user_id' => $request->user()->id,
            'pg_location_id' => $request->pg_location_id,
            'title' => $request->title,
            'amount' => $request->amount,
            'expense_date' => $request->expense_date,
            'bill_image_path' => $imagePath,
            'notes' => $request->notes,
        ]);

        return response()->json([
            'message' => 'Expense added.',
            'expense' => $expense,
        ], 201);
    }

    /**
     * Update an expense.
     */
    public function update(Request $request, Expense $expense): JsonResponse
    {
        $request->validate([
            'title' => 'string|max:255',
            'amount' => 'numeric|min:0.01',
            'expense_date' => 'date',
            'notes' => 'nullable|string|max:500',
            'bill_image' => 'nullable|image|max:5120',
        ]);

        $data = $request->only(['title', 'amount', 'expense_date', 'notes']);

        if ($request->hasFile('bill_image')) {
            // Delete old image
            if ($expense->bill_image_path && Storage::disk('local')->exists($expense->bill_image_path)) {
                Storage::disk('local')->delete($expense->bill_image_path);
            }
            $data['bill_image_path'] = $request->file('bill_image')->store('expenses', 'local');
        }

        $expense->update($data);

        return response()->json([
            'message' => 'Expense updated.',
            'expense' => $expense->fresh(),
        ]);
    }

    /**
     * Delete an expense.
     */
    public function destroy(Expense $expense): JsonResponse
    {
        if ($expense->bill_image_path && Storage::disk('local')->exists($expense->bill_image_path)) {
            Storage::disk('local')->delete($expense->bill_image_path);
        }

        $expense->delete();

        return response()->json(['message' => 'Expense deleted.']);
    }

    /**
     * View bill image.
     */
    public function viewImage(Expense $expense)
    {
        if (!$expense->bill_image_path || !Storage::disk('local')->exists($expense->bill_image_path)) {
            return response()->json(['message' => 'Image not found.'], 404);
        }

        $file = Storage::disk('local')->get($expense->bill_image_path);
        $mimeType = Storage::disk('local')->mimeType($expense->bill_image_path);

        return response($file, 200)
            ->header('Content-Type', $mimeType)
            ->header('Cache-Control', 'private, max-age=3600');
    }
}
