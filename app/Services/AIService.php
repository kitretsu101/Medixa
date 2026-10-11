<?php

namespace App\Services;

use App\Contracts\AiCompletionProvider;
use App\Services\Ai\AiCompletion;

class AIService
{
    public function __construct(private AiCompletionProvider $provider) {}

    /**
     * Ask the configured provider for a completion.
     *
     * The provider receives only the prompt strings. It has no database,
     * filesystem, or application-action access.
     */
    public function complete(string $systemPrompt, string $userMessage, string $sessionToken): AiCompletion
    {
        return $this->provider->complete($systemPrompt, $userMessage, $sessionToken);
    }
}
