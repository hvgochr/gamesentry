<?php

return [
    'polling' => [
        'dispatch_budget_per_minute' => [
            'lol' => (int) env('GAMESENTRY_LOL_DISPATCH_BUDGET_PER_MINUTE', 15),
            'tft' => (int) env('GAMESENTRY_TFT_DISPATCH_BUDGET_PER_MINUTE', 15),
        ],
        'match_history_count' => (int) env('GAMESENTRY_MATCH_HISTORY_COUNT', 10),
        'min_interval_seconds' => (int) env('GAMESENTRY_MIN_POLL_INTERVAL_SECONDS', 300),
        'max_interval_seconds' => (int) env('GAMESENTRY_MAX_POLL_INTERVAL_SECONDS', 1800),
        'rate_limit_cooldown_seconds' => (int) env('GAMESENTRY_RATE_LIMIT_COOLDOWN_SECONDS', 120),
        'max_backoff_multiplier' => (int) env('GAMESENTRY_MAX_BACKOFF_MULTIPLIER', 6),
    ],
];
