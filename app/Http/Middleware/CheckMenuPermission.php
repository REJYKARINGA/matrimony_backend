<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\Menu;
use Symfony\Component\HttpFoundation\Response;

class CheckMenuPermission
{
    private static array $routeMenuMap = [
        'wallet' => '/wallet-transactions',
        'religions' => '/religion-management',
        'castes' => '/religion-management',
        'sub-castes' => '/religion-management',
        'roles' => '/permissions',
        'menus' => '/permissions',
        'role-permissions' => '/permissions',
        'login-histories' => '/audit-logs',
        'activity-logs' => '/audit-logs',
        'theme-presets' => '/theme-settings',
        'suggestions' => '/suggestions',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        $role = Role::where('name', $user->role)->first();

        if (!$role) {
            return response()->json(['error' => 'Unauthorized. Admin access required.'], 403);
        }

        $menuPath = $this->resolveMenuPath($request);

        if (!$menuPath) {
            return $next($request);
        }

        $menu = Menu::where('path', $menuPath)->first();

        if (!$menu) {
            return $next($request);
        }

        $hasPermission = $role->menus()->where('menus.path', $menuPath)->exists();

        if (!$hasPermission) {
            return response()->json([
                'error' => "You don't have the &quot;{$menu->label}&quot; menu permission.",
                'role' => $user->role,
                'required_menu' => $menu->label,
            ], 403);
        }

        return $next($request);
    }

    private function resolveMenuPath(Request $request): ?string
    {
        $uri = $request->path();
        if (preg_match('#^api/admin/([^/]+)#', $uri, $m)) {
            $segment = $m[1];
            return self::$routeMenuMap[$segment] ?? '/' . $segment;
        }
        return null;
    }
}
