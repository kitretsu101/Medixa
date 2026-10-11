<?php

namespace App\Services\Ai;

use App\Contracts\AiCompletionProvider;

class UnconfiguredAiProvider implements AiCompletionProvider
{
    public function complete(string $systemPrompt, string $userMessage, string $sessionToken): AiCompletion
    {
        return new AiCompletion(null, 'unconfigured');
    }
}
