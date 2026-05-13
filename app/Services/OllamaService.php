<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaService
{
    private string $baseUrl;
    private int    $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('ollama.host', 'http://localhost:11434'), '/');
        $this->timeout = (int) config('ollama.timeout', 120);
    }

    /**
     * Ping Ollama to check if it's running.
     */
    public function ping(): array
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/api/version");

            if ($response->successful()) {
                return ['online' => true, 'version' => $response->json('version')];
            }
        } catch (\Throwable $e) {
            Log::warning('Ollama ping failed: ' . $e->getMessage());
        }

        return ['online' => false];
    }

    /**
     * List all locally available models.
     */
    public function listModels(): array
    {
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/api/tags");

            if ($response->successful()) {
                return collect($response->json('models', []))
                    ->map(fn($m) => [
                        'name'       => $m['name'],
                        'size'       => $m['size'] ?? null,
                        'modified'   => $m['modified_at'] ?? null,
                        'family'     => $m['details']['family'] ?? null,
                        'parameters' => $m['details']['parameter_size'] ?? null,
                    ])
                    ->values()
                    ->all();
            }
        } catch (\Throwable $e) {
            Log::error('Ollama list models failed: ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Stream generate response from Ollama, calling $callback with each text chunk.
     *
     * Uses /api/generate (non-chat) for straightforward prompts.
     */
    public function streamGenerate(string $prompt, string $model, callable $callback): void
    {
        $url     = "{$this->baseUrl}/api/generate";
        $payload = [
            'model'  => $model,
            'prompt' => $prompt,
            'stream' => true,
            'options' => [
                'temperature' => (float) config('ollama.temperature', 0.3),
                'num_predict' => (int)   config('ollama.max_tokens',  1024),
            ],
        ];

        try {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($payload),
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT        => $this->timeout,
                CURLOPT_RETURNTRANSFER => false,
                CURLOPT_WRITEFUNCTION  => function ($curl, $data) use ($callback) {
                    // Each line from Ollama is a JSON object
                    foreach (explode("\n", $data) as $line) {
                        $line = trim($line);
                        if (!$line) continue;

                        $json = json_decode($line, true);
                        if (isset($json['response'])) {
                            $callback($json['response']);
                        }
                    }
                    return strlen($data);
                },
            ]);

            $result = curl_exec($ch);

            if (curl_errno($ch)) {
                Log::error('Ollama stream error: ' . curl_error($ch));
            }

            curl_close($ch);
        } catch (\Throwable $e) {
            Log::error('Ollama streamGenerate exception: ' . $e->getMessage());
        }
    }

    /**
     * Non-streaming generate (returns full string). Used for batch jobs.
     */
    public function generate(string $prompt, string $model): string
    {
        $result = '';

        $this->streamGenerate($prompt, $model, function (string $chunk) use (&$result) {
            $result .= $chunk;
        });

        return trim($result);
    }
}
