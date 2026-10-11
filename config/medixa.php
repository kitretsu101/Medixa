<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Knowledge review policy
    |--------------------------------------------------------------------------
    |
    | An approved article leaves retrieval when its review date is older than
    | this many days. Pending and rejected articles are never retrieved.
    |
    */

    'knowledge' => [
        'review_valid_days' => (int) env('KNOWLEDGE_REVIEW_VALID_DAYS', 365),
    ],

    /*
    |--------------------------------------------------------------------------
    | Medical safety
    |--------------------------------------------------------------------------
    |
    | The emergency number is configuration, not a clinical judgement. The
    | default is Bangladesh's national emergency number as published by
    | Bangladesh Police. Confirm it before real-world patient use. The
    | child-age cutoff is an administrative threshold, not a pediatric definition.
    |
    */

    'safety' => [
        'child_age_years' => (int) env('MEDIXA_CHILD_AGE_YEARS', 16),
        'emergency' => [
            'number' => env('MEDIXA_EMERGENCY_NUMBER', '999'),
            'service_name' => env('MEDIXA_EMERGENCY_SERVICE_NAME', 'National Emergency Service'),
            'source_name' => env('MEDIXA_EMERGENCY_SOURCE_NAME', 'Bangladesh Police'),
            'source_url' => env('MEDIXA_EMERGENCY_SOURCE_URL', 'https://www.police.gov.bd/en/bangladesh_police'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Chat session retention
    |--------------------------------------------------------------------------
    |
    | Session rows keep an opaque id and, for a signed-in user, that user id.
    | Message text is not stored. Rows are deleted when they expire.
    |
    */

    'chat' => [
        'retention_days' => (int) env('CHAT_SESSION_RETENTION_DAYS', 7),
    ],

];
