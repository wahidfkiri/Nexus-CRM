<?php

namespace Vendor\CrmCore\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Vendor\CrmCore\Models\Tenant;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Récupérer le tenant depuis l'utilisateur connecté
        if (auth()->check() && auth()->user()->tenant_id) {
            $tenant = Tenant::find(auth()->user()->tenant_id);
            
            if (!$tenant || $tenant->status !== 'active') {
                abort(403, 'Tenant non trouvé ou inactif');
            }
            
            // Partager le tenant avec toutes les vues
            view()->share('currentTenant', $tenant);
            $request->merge(['current_tenant' => $tenant]);
            session()->put('current_tenant_id', $tenant->id);
        }
        
        return $next($request);
    }
}