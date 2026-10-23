<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect()->guest(route('login'));
        }

        if (! $request->session()->has('account_session_version') && Auth::viaRemember()) {
            $request->session()->put('account_session_version', (int) $request->user()?->session_version);
        }

        if (! $request->user()?->is_admin
            || ! $request->user()?->is_active
            || (int) $request->session()->get('account_session_version', 0) !== (int) $request->user()?->session_version) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            abort(403);
        }

        return $next($request);
    }
}
