<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Ollama Host
    |--------------------------------------------------------------------------
    | The URL of your local (or remote) Ollama server.
    | Default: http://localhost:11434
    */
    'host' => env('OLLAMA_HOST', 'http://localhost:11434'),

    /*
    |--------------------------------------------------------------------------
    | Default Model
    |--------------------------------------------------------------------------
    | The Ollama model to use for AI documentation generation.
    | Recommended: llama3, mistral, gemma2, codellama, phi3
    */
    'model' => env('OLLAMA_MODEL', 'llama3'),

    /*
    |--------------------------------------------------------------------------
    | Generation Temperature
    |--------------------------------------------------------------------------
    | Controls creativity. Lower = more factual. Range: 0.0 – 1.0
    */
    'temperature' => env('OLLAMA_TEMPERATURE', 0.3),

    /*
    |--------------------------------------------------------------------------
    | Max Tokens
    |--------------------------------------------------------------------------
    | Maximum number of tokens to generate per summary.
    */
    'max_tokens' => env('OLLAMA_MAX_TOKENS', 1024),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout (seconds)
    |--------------------------------------------------------------------------
    | How long to wait for Ollama to respond before timing out.
    */
    'timeout' => env('OLLAMA_TIMEOUT', 120),
];
