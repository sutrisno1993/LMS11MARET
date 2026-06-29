<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;

class MockTimeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only allow mock time when environment is not production
        if (config('app.env') !== 'production' && app()->environment() !== 'production') {
            $mockTime = null;

            // Check query parameter and store it in session
            if ($request->has('mock_time')) {
                $mockTime = $request->query('mock_time');
                if ($request->hasSession()) {
                    if (empty($mockTime) || $mockTime === 'reset') {
                        $request->session()->forget('mock_time');
                        $mockTime = null;
                    } else {
                        $request->session()->put('mock_time', $mockTime);
                    }
                }
            }
            // Otherwise, check session
            elseif ($request->hasSession() && $request->session()->has('mock_time')) {
                $mockTime = $request->session()->get('mock_time');
            }
            // Otherwise, check header (sent by the mobile app in debug mode)
            elseif ($request->hasHeader('X-Mock-Time')) {
                $mockTime = $request->header('X-Mock-Time');
            }

            if ($mockTime) {
                try {
                    // Try parsing the time
                    $parsed = Carbon::parse($mockTime);
                    Carbon::setTestNow($parsed);
                } catch (\Exception $e) {
                    // Ignore parsing errors
                }
            }
        }

        return $next($request);
    }
}
