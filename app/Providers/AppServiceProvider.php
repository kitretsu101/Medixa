<?php

namespace App\Providers;

use App\Contracts\AiCompletionProvider;
use App\Services\Ai\OpenAiCompletionProvider;
use App\Services\Ai\UnconfiguredAiProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AiCompletionProvider::class, function ($app): AiCompletionProvider {
            $provider = (string) config('services.ai.provider', 'openai');

            if ($provider === 'openai') {
                return $app->make(OpenAiCompletionProvider::class);
            }

            return $app->make(UnconfiguredAiProvider::class);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
