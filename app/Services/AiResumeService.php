<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AiResumeService
{
    protected string $provider;
    protected string $baseUrl;
    protected string $apiKey;
    protected string $model;

    public function __construct()
    {
        $this->provider = config('ai.provider', env('AI_PROVIDER', 'openai'));
        $this->baseUrl  = rtrim(env('AI_BASE_URL', 'https://api.openai.com/v1'), '/');
        $this->apiKey   = env('AI_API_KEY', '');
        $this->model    = env('AI_MODEL', 'gpt-4o-mini');
    }

    public function extractFromText(string $text): array
    {
        $prompt = "You are a strict JSON résumé parser. Extract:\n"
                . "- skills: array of short skill strings (lowercase, deduped)\n"
                . "- years_experience: number (float ok)\n"
                . "- education: short summary string\n"
                . "- keywords: array of important keywords\n"
                . "Return ONLY a JSON object.\n\nResume:\n"
                . $this->clip($text, 120000);

        $headers = [
            'Authorization' => "Bearer {$this->apiKey}",
        ];

        // OpenRouter extra headers are recommended
        if (strtolower($this->provider) === 'openrouter') {
            $headers['HTTP-Referer'] = config('app.url');
            $headers['X-Title']      = config('app.name', 'i-HR');
        }

        $payload = [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => 'You are an expert résumé parser that outputs compact JSON.'],
                ['role' => 'user',   'content' => $prompt],
            ],
            // Ask for JSON object
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0.2,
        ];

        $resp = Http::withHeaders($headers)
            ->post($this->baseUrl.'/chat/completions', $payload)
            ->throw()
            ->json();

        $content = data_get($resp, 'choices.0.message.content', '{}');
        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
    }

    protected function clip(string $text, int $max): string
    {
        $text = preg_replace('/[^\PC\s]/u', '', $text) ?? $text; // strip weird control chars
        if (mb_strlen($text) > $max) {
            return mb_substr($text, 0, $max);
        }
        return $text;
    }
}
