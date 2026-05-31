<?php

namespace App\Http\Middleware;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate;

class OptionalAuthenticate extends Authenticate
{
    /**
     * Allow requests with no token to proceed as guests.
     * Reject requests that carry an invalid or expired token with 401.
     */
    protected function unauthenticated($request, array $guards): void
    {
        if ($request->bearerToken()) {
            throw new AuthenticationException(
                'Token inválido ou expirado.',
                $guards,
            );
        }

        // No token present — continue as guest.
    }
}
