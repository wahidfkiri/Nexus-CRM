<?php

namespace Vendor\User\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CanManageUsersMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!in_array($user->role_in_tenant, ['owner', 'admin'])) {
            abort(403, 'Vous n\'avez pas les droits pour gérer les membres.');
        }

        return $next($request);
    }
}