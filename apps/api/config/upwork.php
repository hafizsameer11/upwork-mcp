<?php

return [
    'mcp_url' => env('UPWORK_MCP_URL', 'https://mcp.upwork.com/mcp'),
    'polling_enabled' => (bool) env('UPWORK_POLLING_ENABLED', false),
    'default_frequency_minutes' => (int) env('UPWORK_WATCH_FREQUENCY', 15),
    'strong_match_threshold' => 85,
    'medium_match_threshold' => 70,
    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'embedding_model' => env('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small'),
    ],
    'slack' => [
        'webhook_url' => env('SLACK_WEBHOOK_URL'),
    ],
    'frontend_url' => env('FRONTEND_URL', 'http://localhost:3000'),
    'match_weights' => [
        'technical' => 30,
        'portfolio' => 25,
        'profile' => 15,
        'client' => 10,
        'budget' => 10,
        'freshness' => 5,
        'competition' => 5,
    ],
];
