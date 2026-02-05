<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class ResumeAiClient
{
    public function sendChat(string $prompt): array
    {
        $key  = config('ai.api_key');
        $base = config('ai.base_url'); // e.g. https://openrouter.ai/api
        $model = config('ai.model');

        if (!$key) {
            throw new RuntimeException('AI_API_KEY is not set.');
        }

        // OpenRouter Chat Completions endpoint
        $endpoint = $base . '/v1/chat/completions';

        $resp = Http::timeout(config('ai.timeout', 30))
            ->withHeaders([
                'Authorization'   => "Bearer {$key}",
                'HTTP-Referer'    => config('app.url', 'http://localhost'),
                'X-Title'         => config('app.name', 'i-HR'),
                'Accept'          => 'application/json',
                'Content-Type'    => 'application/json',
            ])
            ->post($endpoint, [
                'model'    => $model,
                'messages' => [
                    ['role' => 'system', 'content' => 'You extract structured data from resume text into JSON with keys: skills[], years_experience(number), education(string). If data missing, infer sensibly. Respond with JSON only.'],
                    ['role' => 'user',   'content' => $prompt],
                ],
                // A little safety on token usage
                'max_tokens' => 800,
            ]);

        if ($resp->failed()) {
            // Include body for visibility in UI
            throw new RuntimeException("HTTP {$resp->status()}: " . $resp->body());
        }

        $data = $resp->json();
        $content = $data['choices'][0]['message']['content'] ?? null;
        if (!$content) {
            throw new RuntimeException('Empty AI response.');
        }

        // AI returns a JSON string; decode once more
        $parsed = json_decode($content, true);
        if (!is_array($parsed)) {
            throw new RuntimeException('AI returned non-JSON content: ' . substr($content, 0, 400));
        }

        return $parsed;
    }
}
