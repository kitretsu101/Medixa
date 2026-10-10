<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AIService
{
    /**
     * Ask the configured provider for a completion.
     *
     * Returns null when no key is configured or the provider call fails,
     * so the caller can use a local fallback. The session token is a
     * correlation id only and is not a patient identifier.
     */
    public function complete(string $systemPrompt, string $userMessage, string $sessionToken): ?string
    {
        $apiKey = config('services.ai.key') ?: config('services.openai.api_key');

        if (empty($apiKey)) {
            return null;
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(30)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => config('services.ai.model') ?: config('services.openai.model', 'gpt-4o-mini'),
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userMessage],
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 500,
                    'user' => 'session:'.$sessionToken,
                ]);

            if (! $response->successful()) {
                return null;
            }

            return (string) $response->json('choices.0.message.content', '');
        } catch (\Throwable) {
            return null;
        }
    }
}
