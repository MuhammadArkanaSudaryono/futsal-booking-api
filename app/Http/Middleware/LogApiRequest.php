<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $duration = round((microtime(true) - $startTime) * 1000, 2); // ms

        Log::channel('daily')->info('API Request', [
            'method'      => $request->method(),
            'url'         => $request->fullUrl(),
            'ip'          => $request->ip(),
            'user_id'     => optional($request->user())->id,
            'user_role'   => optional($request->user())->role,
            'status_code' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'user_agent'  => $request->userAgent(),
        ]);

        return $response;
    }
}
