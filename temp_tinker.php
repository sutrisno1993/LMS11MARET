<?php
$request = \Illuminate\Http\Request::create('/api/student/dashboard', 'GET');
$request->headers->set('X-Mock-Time', '2026-06-29 10:00:00');
$middleware = new \App\Http\Middleware\MockTimeMiddleware();
echo "Before: " . \Carbon\Carbon::now()->toDateTimeString() . "\n";
$middleware->handle($request, function($req) {
    echo "Inside (Mocked): " . \Carbon\Carbon::now()->toDateTimeString() . "\n";
    return response('ok');
});
echo "After (Reset/Outside): " . \Carbon\Carbon::now()->toDateTimeString() . "\n";
