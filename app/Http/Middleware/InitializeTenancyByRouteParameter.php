<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Middleware\InitializeTenancyByPath;

class InitializeTenancyByRouteParameter extends InitializeTenancyByPath
{
    public function handle($request, Closure $next)
    {
        // Use the tenant route parameter to identify and initialize the tenant
        $tenantId = $request->route('tenant');

        if ($tenantId) {
            // Check if tenant exists before initializing to avoid exceptions (or let it throw if preferred)
            // tenancy()->initialize($tenantId) throws TenantCouldNotBeIdentifiedException if not found.
            // This is good as it will fail clearly.
            try {
                tenancy()->initialize($tenantId);
            } catch (\Exception $e) {
                abort(404, 'Tenant not found');
            }
        }

        return $next($request);
    }
}
