<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class SwitchDatabaseBySubdomain
{
    // Maps subdomain → connection name defined in config/database.php
    private const SUBDOMAIN_MAP = [
        'shonamoni' => 'shonamoni',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $subdomain = $this->extractSubdomain($request->getHost());

        if ($subdomain && isset(self::SUBDOMAIN_MAP[$subdomain])) {
            $connection = self::SUBDOMAIN_MAP[$subdomain];
            Config::set('database.default', $connection);
            DB::setDefaultConnection($connection);

            // Sessions and cache must always use the main DB — never the tenant DB.
            // Without this, StartSession can't find/write the sessions table and
            // every request looks unauthenticated, causing an infinite redirect loop.
            Config::set('session.connection', env('SESSION_CONNECTION', 'mysql'));
        }

        return $next($request);
    }

    private function extractSubdomain(string $host): ?string
    {
        $appHost = parse_url(config('app.url'), PHP_URL_HOST) ?? '';

        // Strip port if present
        $host = strtolower(explode(':', $host)[0]);
        $appHost = strtolower(explode(':', $appHost)[0]);

        if ($appHost && str_ends_with($host, '.'.$appHost)) {
            return substr($host, 0, strlen($host) - strlen('.'.$appHost));
        }

        // Fallback: first segment when host has 3+ parts (e.g. shonamoni.example.com)
        $parts = explode('.', $host);
        if (count($parts) >= 3) {
            return $parts[0];
        }

        return null;
    }
}
