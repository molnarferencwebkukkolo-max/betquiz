<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class ServerErrorAlertSpool
{
    public function capture(Throwable $exception, Request $request): void
    {
        $status = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;

        if (! config('error-alerts.enabled') || $status < 500 || app()->environment('testing')) {
            return;
        }

        try {
            $fingerprint = hash('sha256', implode('|', [get_class($exception), $exception->getFile(), $exception->getLine()]));
            $directory = storage_path('app/error-alerts/pending');
            File::ensureDirectoryExists($directory);

            // Az időablakos fájlnév ugyanazt a hibát nem engedi levélviharrá válni.
            $window = max(1, (int) config('error-alerts.cooldown_minutes', 15)) * 60;
            $filename = $fingerprint.'-'.intdiv(time(), $window).'.json';
            $path = $directory.DIRECTORY_SEPARATOR.$filename;

            if (File::exists($path)) {
                return;
            }

            File::put($path, json_encode([
                'occurred_at' => now()->toIso8601String(),
                'status' => $status,
                'method' => $request->method(),
                // A route-minta diagnosztikára elég, de nem szivárogtat ki
                // aláírt linkből tokent, query stringet vagy személyes adatot.
                'route' => $request->route()?->uri() ?? '(unmatched request)',
                'exception' => get_class($exception),
                'message' => mb_substr($exception->getMessage(), 0, 1500),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'fingerprint' => $fingerprint,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), true);
        } catch (Throwable) {
            // A hibajelentés soha nem fedheti el az eredeti kivételt.
        }
    }
}
