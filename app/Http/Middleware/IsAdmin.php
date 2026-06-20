<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Role;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized. Admin access required.'], 403);
        }

        $role = Role::where('name', $user->role)->first();

        if (!$role) {
            return response()->json(['error' => 'Unauthorized. Admin access required.'], 403);
        }

        $hasDashboard = $role->menus()->where('path', '/dashboard')->exists();

        if (!$hasDashboard) {
            return response()->json(['error' => 'Unauthorized. Admin access required.'], 403);
        }

        return $next($request);
    }
}
