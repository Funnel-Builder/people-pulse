<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class SwitchDatabaseBySubdomain
{
    private const SUBDOMAIN_MAP = [
        'shonamoni' => 'shonamoni',
        'dev' => 'dev',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $subdomain = $this->extractSubdomain($request->getHost());

        if ($subdomain && isset(self::SUBDOMAIN_MAP[$subdomain])) {
            $connection = self::SUBDOMAIN_MAP[$subdomain];

            // 1. Switch Database connections dynamically
            Config::set('database.default', $connection);
            DB::setDefaultConnection($connection);

            // 2. Keep core systemic functions running on main server cluster
            Config::set('session.connection', env('SESSION_CONNECTION', 'mysql'));
        }

        return $next($request);
    }

    private function extractSubdomain(string $host): ?string
    {
        $appHost = parse_url(config('app.url'), PHP_URL_HOST) ?? '';

        $host = strtolower(explode(':', $host)[0]);
        $appHost = strtolower(explode(':', $appHost)[0]);

        if ($appHost && str_ends_with($host, '.'.$appHost)) {
            return substr($host, 0, strlen($host) - strlen('.'.$appHost));
        }

        $parts = explode('.', $host);
        if (count($parts) >= 3) {
            return $parts[0];
        }

        return null;
    }
}
