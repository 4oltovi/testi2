<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * TrackSessionActivity
 *
 * Records the timestamp of the last authenticated request in the session.
 * This value is used by /session-check to compute the remaining session
 * lifetime and keep the client-side countdown synchronized with the
 * server-side session expiration.
 *
 * The server remains authoritative: this middleware only stores a
 * timestamp. The actual session expiry is governed by the session
 * driver's lifetime (FileSessionHandler::read rejects sessions whose
 * last modification is older than the configured lifetime).
 */
class TrackSessionActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Only update for authenticated users on stateful (non-API) requests.
        // Skip precognitive (Livewire) requests to avoid interfering with
        // the validation workflow.
        if ($request->user() && ! $request->isPrecognitive()) {
            $session = $request->session();
            if ($session) {
                $session->put('last_activity', time());
            }
        }

        return $response;
    }
}