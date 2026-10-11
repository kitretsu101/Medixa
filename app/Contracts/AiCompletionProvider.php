<?php

namespace App\Contracts;

use App\Services\Ai\AiCompletion;

interface AiCompletionProvider
{
    /**
     * Complete one chat turn.
     *
     * The session token is a correlation id. It is not a patient identifier.
     */
    public function complete(string $systemPrompt, string $userMessage, string $sessionToken): AiCompletion;
}
