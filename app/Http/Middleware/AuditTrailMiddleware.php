<?php

namespace App\Http\Middleware;

use App\Models\SecurityLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditTrailMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->getStatusCode() === 401 || $response->getStatusCode() === 403) {
            SecurityLog::create([
                'user_id' => $request->user()?->id,
                'ip_address' => $request->ip(),
                'event_type' => 'unauthorized_url',
                'url_accessed' => $request->fullUrl(),
                'payload' => json_encode($request->except(['password', 'password_confirmation'])),
                'user_agent' => $request->userAgent() ?? 'Unknown',
            ]);
        }

        return $response;
    }
}
