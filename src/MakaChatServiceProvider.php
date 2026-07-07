<?php

namespace Hongayetu\MakaChat;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class MakaChatServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/makachat.php', 'makachat');
        $this->app->singleton(TokenIssuer::class);
        $this->app->singleton(MakaChatClient::class);
    }

    public function boot(): void
    {
        $this->publishes([__DIR__.'/../config/makachat.php' => config_path('makachat.php')], 'makachat-config');

        /**
         * Route::makachatToken('/api/makachat/token', MeuIdentityResolver::class)
         * — regista o endpoint de troca de sessão por token de chat.
         */
        Route::macro('makachatToken', function (string $uri, string $resolverClass) {
            return Route::post($uri, function (Request $request) use ($resolverClass) {
                /** @var IdentityResolver $resolver */
                $resolver = app($resolverClass);
                $identidade = $resolver->resolve($request);

                if ($identidade === null) {
                    return response()->json(['estado' => 'erro', 'texto' => 'Não autenticado'], 401);
                }

                return response()->json(app(TokenIssuer::class)->resposta(
                    $identidade['id'],
                    $identidade['tipo'],
                    $identidade['nome'],
                    $identidade['foto'] ?? null,
                    $identidade['honga_user_id'] ?? null,
                ));
            });
        });
    }
}
