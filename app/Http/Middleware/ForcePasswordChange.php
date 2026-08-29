<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    /**
     * Redirect users flagged with must_change_password to the forced
     * password-change screen (e.g. accounts created by an administrator
     * or via bulk Excel import) until they set their own password.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $exempt = $request->routeIs('password.force-change')
            || $request->routeIs('password.force-change.update')
            || $request->routeIs('logout');

        if ($user && $user->must_change_password && ! $exempt) {
            return redirect()->route('password.force-change');
        }

        return $next($request);
    }
}
