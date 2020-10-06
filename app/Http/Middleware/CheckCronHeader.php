<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response as IlluminateResponse;

class CheckCronHeader
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!app()->environment('development') && !$request->hasHeader('X-Appengine-Cron'))
        {
            Log::warning('unauthorized_access '. $request->getClientIp());

            return response()->json(
                [
                    'success' => false,
                    'error' => [
                        'messages' => 'Unauthorized'
                    ]
                ], IlluminateResponse::HTTP_UNAUTHORIZED
            );

        }
        return $next($request);
    }
}
