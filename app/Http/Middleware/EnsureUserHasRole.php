<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Batasi akses route berdasarkan role user.
     *
     * Dipakai seperti: ->middleware('role:pustakawan')
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        if ($roles === []) {
            return $next($request);
        }

        abort_unless(in_array($user->role, $roles, true), 403);

        return $next($request);
    }
}
