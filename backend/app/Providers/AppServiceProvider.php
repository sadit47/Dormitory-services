<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->input('email');
            $key = strtolower($email) . '|' . $request->ip();

            return [
                Limit::perMinute(5)->by($key),
            ];
        });

        RateLimiter::for('upload-slip', function (Request $request) {
            $userId = $request->user()?->id ?: $request->ip();

            return [
                Limit::perMinute(5)->by('upload-slip|' . $userId),
            ];
        });

        RateLimiter::for('repair-store', function (Request $request) {
            $userId = $request->user()?->id ?: $request->ip();

            return [
                Limit::perMinute(5)->by('repair-store|' . $userId),
            ];
        });

        RateLimiter::for('pdf-view', function (Request $request) {
            $userId = $request->user()?->id ?: $request->ip();

            return [
                Limit::perMinute(20)->by('pdf-view|' . $userId),
            ];
        });

        RateLimiter::for('announcement-read', function (Request $request) {
            $userId = $request->user()?->id ?: $request->ip();

            return [
                Limit::perMinute(30)->by('announcement-read|' . $userId),
            ];
        });

        RateLimiter::for('admin-actions', function (Request $request) {
            $userId = $request->user()?->id ?: $request->ip();

            return [
                Limit::perMinute(20)->by('admin-actions|' . $userId),
            ];
        });
    }
}