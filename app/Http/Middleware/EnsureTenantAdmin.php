<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureTenantAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!in_array($user->role_in_tenant, ['owner', 'admin'], true) && !$user->is_tenant_owner) {
            abort(403, 'Acces reserve aux administrateurs du tenant.');
        }

        return $next($request);
    }
}

