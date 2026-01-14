<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TenantLookupController extends Controller
{
    /**
     * Show the office ID lookup form.
     */
    public function show(): Response
    {
        return Inertia::render('auth/TenantLookup');
    }

    /**
     * Handle the office ID lookup.
     */
    public function lookup(Request $request)
    {
        $request->validate([
            'office_id' => 'required|string|max:255',
        ]);

        $officeId = strtolower(trim($request->office_id));
        $tenant = Tenant::find($officeId);

        if (!$tenant) {
            return back()->withErrors([
                'office_id' => 'Office not found. Please check your Office ID.',
            ]);
        }

        if (!$tenant->isActive()) {
            return back()->withErrors([
                'office_id' => 'This office account is not active. Please contact support.',
            ]);
        }

        $domain = $tenant->domains->first()->domain;
        $protocol = request()->secure() ? 'https://' : 'http://';
        $port = request()->getPort();
        $url = $protocol . $domain;
        if (! in_array($port, [80, 443])) {
            $url .= ':' . $port;
        }
        $url .= '/login';

        return Inertia::location($url);
    }
}
