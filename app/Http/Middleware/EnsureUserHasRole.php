<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * Usage: ->middleware('role:admin') or ->middleware('role:admin,teacher')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isActive()) {
            abort(403, 'Ο λογαριασμός σας δεν είναι ενεργός. Επικοινωνήστε με τη Διεύθυνση του σχολείου.');
        }

        $allowed = array_map(fn (string $role) => UserRole::from($role), $roles);

        if (! in_array($user->role, $allowed, true)) {
            abort(403, 'Δεν έχετε δικαίωμα πρόσβασης σε αυτή τη σελίδα.');
        }

        return $next($request);
    }
}
