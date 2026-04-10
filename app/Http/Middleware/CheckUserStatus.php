<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckUserStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->status !== 'active') {
            // Define routes that are ALWAYS allowed even for inactive/blocked users
            // These allow the user to see WHY they were blocked or log out.
            $allowedRoutes = [
                'api/notifications',
                'api/auth/logout',
            ];

            // Normalize path for comparison
            $path = $request->path();
            
            if (!in_array($path, $allowedRoutes)) {
                return response()->json([
                    'error' => 'Account blocked/inactive',
                    'message' => $user->status === 'blocked' 
                        ? "Your account has been blocked because you were reported by other users. Reason: {$user->block_reason}"
                        : 'Your account is deactivated. Please contact support.',
                    'status' => $user->status,
                    'block_reason' => $user->block_reason
                ], 403);
            }
        }

        return $next($request);
    }
}
