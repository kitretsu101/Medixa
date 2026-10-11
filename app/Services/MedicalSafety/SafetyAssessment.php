<?php

namespace App\Services\MedicalSafety;

/**
 * Result of the inbound phrase check.
 *
 * A routine result means no configured rule matched. It does not mean the person is safe.
 */
final readonly class SafetyAssessment
{
    /**
     * @param  'emergency'|'prescription'|'dose'|'high_risk'|'routine'  $level
     */
    public function __construct(
        public string $level,
        public ?string $ruleId,
    ) {}

    public function isRoutine(): bool
    {
        return $this->level === 'routine';
    }
}
