<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PartnerAgent;
use App\Models\PartnerOffice;
use App\Models\User;
use Illuminate\Http\Request;

class PartnerAgentController extends Controller
{
    public function index(Request $request)
    {
        $query = PartnerAgent::with(['office', 'user']);

        if ($request->partner_office_id) {
            $query->where('partner_office_id', $request->partner_office_id);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('phone', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        $agents = $query->orderBy('created_at', 'desc')->paginate($request->per_page ?? 15);

        return response()->json([
            'agents' => $agents,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'partner_office_id' => 'required|exists:partner_offices,id',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'status' => 'nullable|in:active,inactive',
            'create_login' => 'boolean',
            'password' => 'required_if:create_login,true|nullable|string|min:6',
        ]);

        if ($request->create_login) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'] ?? ('agent_' . uniqid() . '@partner.local'),
                'phone' => $validated['phone'] ?? null,
                'password' => bcrypt($validated['password']),
                'role' => 'partner_office',
                'status' => 'active',
            ]);
            $validated['user_id'] = $user->id;
        }

        $agent = PartnerAgent::create($validated);

        return response()->json([
            'message' => 'Partner agent created successfully',
            'agent' => $agent->load(['office', 'user']),
        ], 201);
    }

    public function show($id)
    {
        $agent = PartnerAgent::with(['office', 'user'])->findOrFail($id);

        $registrationsCount = \App\Models\Reference::where('partner_agent_id', $id)->count();

        return response()->json([
            'agent' => $agent,
            'registrations_count' => $registrationsCount,
        ]);
    }

    public function update(Request $request, $id)
    {
        $agent = PartnerAgent::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'status' => 'nullable|in:active,inactive',
        ]);

        $agent->update($validated);

        return response()->json([
            'message' => 'Partner agent updated successfully',
            'agent' => $agent->fresh()->load(['office', 'user']),
        ]);
    }

    public function destroy($id)
    {
        $agent = PartnerAgent::findOrFail($id);
        $agent->delete();

        return response()->json([
            'message' => 'Partner agent deleted successfully',
        ]);
    }

    public function listByOffice($officeId)
    {
        $office = PartnerOffice::findOrFail($officeId);
        $agents = $office->activeAgents;

        return response()->json(['agents' => $agents]);
    }
}
