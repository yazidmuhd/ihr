<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AiClient
{
    // -------------------- Config helpers --------------------

    protected function key(): ?string
    {
        $k = config('services.ai.key')
            ?: env('AI_API_KEY')
            ?: env('OPENROUTER_API_KEY')
            ?: env('OPENAI_API_KEY');

        if (is_string($k)) {
            $k = trim($k);
            $k = trim($k, "\"'"); // strip accidental quotes
        }

        return $k ?: null;
    }

    protected function base(): string
    {
        return rtrim((string) config('services.ai.base_url', 'https://openrouter.ai/api/v1'), '/');
    }

    protected function model(): string
    {
        return (string) config('services.ai.model', 'openai/gpt-4o-mini');
    }

    protected function referer(): ?string
    {
        $v = config('services.ai.referer') ?: config('app.url');
        return $v ? (string) $v : null;
    }

    protected function title(): ?string
    {
        $v = config('services.ai.title') ?: config('app.name');
        return $v ? (string) $v : null;
    }

    // -------------------- Public methods --------------------

    /** Quick connectivity check */
    public function ping(): array
    {
        $key = $this->key();
        if (!$key) {
            throw new \RuntimeException('AI_API_KEY is not set.');
        }

        $r = Http::withToken($key)
            ->acceptJson()
            ->timeout(20)
            ->withHeaders(array_filter([
                'HTTP-Referer' => $this->referer(),
                'X-Title'      => $this->title(),
            ]))
            ->get($this->base() . '/models');

        return [
            'ok'     => $r->ok(),
            'status' => $r->status(),
            'body'   => $r->json() ?: $r->body(),
        ];
    }

    /**
     * Main parser used by ParseResume job
     * Must return JSON array:
     *  full_name, email, phone, skills (array), education, years_experience (number)
     */
    public function parseResumeText(string $text): array
    {
        $key = $this->key();
        if (!$key) {
            throw new \RuntimeException('AI_API_KEY is not set.');
        }

        $payload = [
            'model' => $this->model(),
            'messages' => [
                [
                    'role' => 'system',
                    'content' =>
                        "Extract a clean JSON object from a resume.\n" .
                        "Return ONLY valid JSON (no markdown, no explanation).\n" .
                        "Keys:\n" .
                        "- full_name (string|null)\n" .
                        "- email (string|null)\n" .
                        "- phone (string|null)\n" .
                        "- skills (array of strings)\n" .
                        "- education (string|null)\n" .
                        "- years_experience (number|null)\n",
                ],
                [
                    'role' => 'user',
                    'content' => mb_substr($text, 0, 120000),
                ],
            ],
            // OpenRouter supports this for many models, but some may still return plain text.
            'response_format' => ['type' => 'json_object'],
            'max_tokens' => 1000,
        ];

        $r = Http::withToken($key)
            ->acceptJson()
            ->timeout(60)
            ->withHeaders(array_filter([
                'HTTP-Referer' => $this->referer(),
                'X-Title'      => $this->title(),
            ]))
            ->post($this->base() . '/chat/completions', $payload);

        if (!$r->successful()) {
            throw new \RuntimeException("AI HTTP {$r->status()}: " . $r->body());
        }

        $data = $r->json();
        $content = $data['choices'][0]['message']['content'] ?? null;

        if (!$content || !is_string($content)) {
            throw new \RuntimeException('AI returned no content.');
        }

        // 1) Try direct decode first
        $json = json_decode($content, true);

        // 2) If failed, try to extract first {...} block
        if (!is_array($json)) {
            $extracted = $this->extractFirstJsonObject($content);
            if ($extracted) {
                $json = json_decode($extracted, true);
            }
        }

        if (!is_array($json)) {
            throw new \RuntimeException('AI returned invalid JSON.');
        }

        // ✅ normalize output (important so your scorer doesn't get empty skills)
        return $this->normalizeAiOutput($json);
    }

    // -------------------- Internal helpers --------------------

    /** Extract the first JSON object substring from a messy response */
    protected function extractFirstJsonObject(string $s): ?string
    {
        $start = strpos($s, '{');
        $end   = strrpos($s, '}');
        if ($start === false || $end === false || $end <= $start) return null;
        return substr($s, $start, $end - $start + 1);
    }

    /** Ensure consistent types (skills always array, years_experience numeric, etc.) */
    protected function normalizeAiOutput(array $json): array
    {
        // skills: allow "a, b, c" -> ["a","b","c"]
        $skills = $json['skills'] ?? [];
        if (is_string($skills)) {
            $skills = preg_split('/[,;\n\r|]+/', $skills) ?: [];
            $skills = array_values(array_filter(array_map('trim', $skills)));
        } elseif (is_array($skills)) {
            $out = [];
            foreach ($skills as $item) {
                if (is_string($item)) $out[] = trim($item);
                elseif (is_array($item) && isset($item['name'])) $out[] = trim((string)$item['name']);
                elseif (!is_null($item)) $out[] = trim((string)$item);
            }
            $skills = array_values(array_filter($out, fn($x) => $x !== ''));
        } else {
            $skills = [];
        }

        $json['skills'] = $skills;

        // years_experience: cast to number if possible
        if (isset($json['years_experience'])) {
            if (is_string($json['years_experience'])) {
                $json['years_experience'] = (float) preg_replace('/[^\d\.]/', '', $json['years_experience']);
            } elseif (is_int($json['years_experience']) || is_float($json['years_experience'])) {
                // ok
            } else {
                $json['years_experience'] = null;
            }
        } else {
            $json['years_experience'] = null;
        }

        // Keep keys always present
        $json += [
            'full_name'        => null,
            'email'            => null,
            'phone'            => null,
            'education'        => null,
            'years_experience' => $json['years_experience'] ?? null,
            'skills'           => $json['skills'] ?? [],
        ];

        return $json;
    }
}
