<?php

return [
    /*
    | The one-time setup page that creates the first admin. It is disabled the
    | moment an admin exists, but this flag lets the installer close the door
    | explicitly as well -- see docs/05-DEPLOYMENT.md.
    */
    'setup_enabled' => env('SETUP_ENABLED', true),

    /*
    | AI question generation. The client pays for every call the router makes,
    | so these are cost guards first and rate limits second. Credentials live in
    | config/services.php under 'ai_router'.
    */
    'ai' => [
        // Per teacher, per hour. See .claude/rules/security.md.
        'jobs_per_hour' => (int) env('AI_JOBS_PER_HOUR', 20),

        // Per job. The database enforces this too, via a CHECK constraint.
        'max_questions_per_job' => (int) env('AI_MAX_QUESTIONS_PER_JOB', 20),

        // Teachers double-click. An identical request inside this window reuses
        // the previous answer instead of paying the router twice.
        'cache_ttl' => (int) env('AI_CACHE_TTL', 86400),

        'timeout' => (int) env('AI_ROUTER_TIMEOUT', 120),

        'tries' => (int) env('AI_ROUTER_TRIES', 3),

        /*
        | Router price per one million tokens, in the currency the router bills
        | in. Used only to estimate what a job cost so the admin recap can show
        | it; the invoice from the router remains the source of truth.
        */
        'price_per_million' => [
            'prompt' => (float) env('AI_PRICE_PROMPT', 0),
            'completion' => (float) env('AI_PRICE_COMPLETION', 0),
        ],
    ],
];
