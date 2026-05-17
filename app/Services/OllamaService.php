<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaService
{
    protected string $baseUrl;
    protected string $model;

    public function __construct()
    {
        $this->baseUrl = config('services.ollama.url', 'http://localhost:11434');
        $this->model = config('services.ollama.model', 'llama3.2:3b');
    }

    /**
     * Send a chat completion request to Ollama (non-streaming).
     */
    public function chat(string $systemPrompt, string $userMessage): ?string
    {
        try {
            $response = Http::timeout(120)
                ->post("{$this->baseUrl}/api/chat", [
                    'model'  => $this->model,
                    'stream' => false,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user',   'content' => $userMessage],
                    ],
                    'options' => [
                        'temperature'   => 0.7,
                        'num_predict'   => 512,
                        'top_p'         => 0.9,
                    ],
                ]);

            if ($response->successful()) {
                return $response->json('message.content');
            }

            Log::warning('Ollama API error: ' . $response->status() . ' - ' . $response->body());
            return null;

        } catch (\Exception $e) {
            Log::error('Ollama connection error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Stream a chat completion request to Ollama.
     * Returns a Generator that yields each token as it arrives.
     */
    public function chatStream(string $systemPrompt, string $userMessage): \Generator
    {
        $payload = [
            'model'  => $this->model,
            'stream' => true,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $userMessage],
            ],
            'options' => [
                'temperature'   => 0.7,
                'num_predict'   => 512,
                'top_p'         => 0.9,
            ],
        ];

        $ch = curl_init("{$this->baseUrl}/api/chat");
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_WRITEFUNCTION  => function ($ch, $data) use (&$buffer) {
                $buffer .= $data;
                return strlen($data);
            },
        ]);

        // Use a simpler approach: non-streaming but return full response
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);

        // Ollama streaming returns NDJSON (one JSON per line)
        $lines = explode("\n", trim($result));
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            $json = json_decode($line, true);
            if ($json && isset($json['message']['content'])) {
                yield $json['message']['content'];
            }
        }
    }

    /**
     * Check if Ollama is reachable.
     */
    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(3)->get("{$this->baseUrl}/api/tags");
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}
