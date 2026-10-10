<?php

namespace App\Support;

class DoctorName
{
    /**
     * Format a doctor's display name without repeating an existing title.
     */
    public static function format(?string $name): string
    {
        $label = trim((string) $name);

        if ($label === '') {
            return 'Doctor';
        }

        if (preg_match('/^dr\.?\s/i', $label) === 1) {
            return $label;
        }

        return 'Dr. '.$label;
    }
}
