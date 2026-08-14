<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleComingSoonMode
{
    /**
     * Coming Soon módban csak a hostadmin és a belépéshez szükséges
     * végpontok érhetők el. Így a publikus oldal zárt, az adminisztráció viszont
     * külön titkos kerülőút nélkül is használható marad.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('app.coming_soon', false)) {
            return $next($request);
        }

        if (($request->user()?->isHostadmin() || $request->user()?->isEmergencyAdmin()) || $this->isAccessRoute($request)) {
            return $next($request);
        }

        return response()
            ->view('coming-soon', status: Response::HTTP_SERVICE_UNAVAILABLE)
            ->header('Retry-After', '3600');
    }

    private function isAccessRoute(Request $request): bool
    {
        // A POST /login útvonalnak nincs külön route-neve, ezért az elérési
        // utat is engedjük; különben a belépőoldal látszana, de nem lenne beküldhető.
        if ($request->is('up', 'login')) {
            return true;
        }

        return $request->routeIs([
            'login',
            'logout',
            'password.*',
            'auth.google.*',
        ]);
    }
}
