<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;

class EnsureWebsiteActive
{
    public function handle(Request $request, Closure $next)
    {
        $status = (int) Setting::valueOf('website_status', 1);

        if ($status === 1 || $this->isBypassAllowed($request)) {
            return $next($request);
        }

        return response()->view('maintenance', [], 503);
    }

    private function isBypassAllowed(Request $request): bool
    {
        if ($request->is('secret-setting') || $request->is('secret-setting/*')) {
            return true;
        }

        if ($request->is('favicon.ico')) {
            return true;
        }

        return false;
    }
}
