<?php

namespace App\Services\Ai;

use App\Contracts\AiCompletionProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OpenAiCompletionProvider implements AiCompletionProvider
{
    public function complete(string $systemPrompt, string $userMessage, string $sessionToken): AiCompletion
    {
        $apiKey = config('services.ai.key') ?: config('services.openai.api_key');

        if (! is_string($apiKey) || $apiKey === '') {
            return new AiCompletion(null, 'unconfigured');
        }

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->connectTimeout((int) config('services.ai.connect_timeout', 5))
                ->timeout((int) config('services.ai.timeout', 20))
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
        } catch (Throwable) {
            Log::warning('AI provider request failed.', ['reason' => 'connection']);

            return new AiCompletion(null, 'failed');
        }

        if (! $response->successful()) {
            Log::warning('AI provider request failed.', ['status' => $response->status()]);

            return new AiCompletion(null, 'failed');
        }

        $content = $response->json('choices.0.message.content');

        if (! is_string($content) || trim($content) === '') {
            return new AiCompletion(null, 'failed');
        }

        return new AiCompletion($content, 'ok');
    }
}
