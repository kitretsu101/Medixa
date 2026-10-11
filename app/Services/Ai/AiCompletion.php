<?php

namespace App\Services\Ai;

/**
 * Result of one completion attempt.
 *
 * Status is "ok", "unconfigured", or "failed". The text is present only for a successful reply.
 */
final class AiCompletion
{
    public function __construct(
        public ?string $text,
        public string $status,
    ) {}

    public function succeeded(): bool
    {
        return $this->status === 'ok' && is_string($this->text) && $this->text !== '';
    }
}
