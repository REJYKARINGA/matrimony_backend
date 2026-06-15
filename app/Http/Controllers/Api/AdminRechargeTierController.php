<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RechargeTier;
use Illuminate\Support\Facades\Validator;

class AdminRechargeTierController extends Controller
{
    public function index()
    {
        $tiers = RechargeTier::orderBy('priority_order')->get();
        return response()->json(['tiers' => $tiers]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0',
            'contacts' => 'required|integer|min:1',
            'priority_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed', 'messages' => $validator->errors()], 422);
        }

        if ($request->has('priority_order') && $request->priority_order !== '' && $request->priority_order !== null) {
            $priority = (int) $request->priority_order;
            RechargeTier::where('priority_order', '>=', $priority)->increment('priority_order');
        } else {
            $priority = (RechargeTier::max('priority_order') ?? -1) + 1;
        }

        $tier = RechargeTier::create([
            'amount' => $request->amount,
            'contacts' => $request->contacts,
            'priority_order' => $priority,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json(['message' => 'Tier created', 'tier' => $tier], 201);
    }

    public function update(Request $request, $id)
    {
        $tier = RechargeTier::find($id);
        if (!$tier) {
            return response()->json(['error' => 'Tier not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'sometimes|numeric|min:0',
            'contacts' => 'sometimes|integer|min:1',
            'priority_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed', 'messages' => $validator->errors()], 422);
        }

        $tier->update($request->only(['amount', 'contacts', 'priority_order', 'is_active']));

        return response()->json(['message' => 'Tier updated', 'tier' => $tier]);
    }

    public function destroy($id)
    {
        $tier = RechargeTier::find($id);
        if (!$tier) {
            return response()->json(['error' => 'Tier not found'], 404);
        }

        $tier->delete();

        return response()->json(['message' => 'Tier deleted']);
    }

    public function reorder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tiers' => 'required|array',
            'tiers.*.id' => 'required|integer|exists:recharge_tiers,id',
            'tiers.*.priority_order' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed', 'messages' => $validator->errors()], 422);
        }

        foreach ($request->tiers as $item) {
            RechargeTier::where('id', $item['id'])->update(['priority_order' => $item['priority_order']]);
        }

        return response()->json(['message' => 'Reordered successfully']);
    }

    public function toggleActive($id)
    {
        $tier = RechargeTier::find($id);
        if (!$tier) {
            return response()->json(['error' => 'Tier not found'], 404);
        }

        $tier->update(['is_active' => !$tier->is_active]);

        return response()->json(['message' => 'Tier status toggled', 'tier' => $tier]);
    }
}
