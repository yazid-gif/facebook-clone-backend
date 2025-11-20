<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ==========================================
        // Rate Limiter pour les routes API générales
        // ==========================================
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Trop de requêtes. Veuillez ralentir.',
                    ], 429, $headers);
                });
        });

        // ==========================================
        // Rate Limiter STRICT pour l'authentification
        // ==========================================
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)
                ->by($request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Trop de tentatives de connexion. Réessayez plus tard.',
                        'retry_after' => $headers['Retry-After'] ?? 60,
                    ], 429, $headers);
                });
        });

        // ==========================================
        // Rate Limiter personnalisé pour les posts
        // ==========================================
        RateLimiter::for('posts', function (Request $request) {
            // Utilisateurs authentifiés : 20 requêtes/minute
            // Invités : 10 requêtes/minute
            return $request->user()
                ? Limit::perMinute(20)->by($request->user()->id)
                : Limit::perMinute(10)->by($request->ip());
        });

        // ==========================================
        // EXEMPLES AVANCÉS DE RATE LIMITING
        // ==========================================

        // A. Limites Multiples (Par Minute ET Par Jour)
        // Décommentez pour activer :
        /*
        RateLimiter::for('api-advanced', function (Request $request) {
            return [
                // Limite par minute
                Limit::perMinute(60)
                    ->by($request->user()?->id ?: $request->ip()),
                // Limite par jour
                Limit::perDay(1000)
                    ->by($request->user()?->id ?: $request->ip()),
            ];
        });
        */

        // B. Limites Basées sur le Plan Utilisateur
        // Décommentez pour activer (nécessite un champ is_premium dans la table users) :
        /*
        RateLimiter::for('api-premium', function (Request $request) {
            // Vérifier si l'utilisateur a un plan premium
            if ($request->user()?->is_premium) {
                return Limit::perMinute(200)
                    ->by($request->user()->id);
            }

            // Utilisateurs gratuits authentifiés
            if ($request->user()) {
                return Limit::perMinute(60)
                    ->by($request->user()->id);
            }

            // Invités (non authentifiés)
            return Limit::perMinute(30)
                ->by($request->ip());
        });
        */
    }
}
