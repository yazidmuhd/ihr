<?php

namespace App\Services;

use GuzzleHttp\Client;

class ResumeAiService
{
    protected Client $http;
    protected string $model;
    protected float $temperature;

    public function __construct()
    {
        $this->http = new Client([
            'base_uri' => config('ai.base_url'),
            'headers'  => [
                'Authorization' => 'Bearer ' . config('ai.api_key'),
                'Content-Type'  => 'application/json',
            ],
            'timeout'  => 60,
        ]);

        $this->model       = (string) config('ai.model');
        $this->temperature = (float) config('ai.temperature', 0.0);
    }

    /**
     * Ask the LLM to parse a resume's plain text into structured JSON.
     * Returns an associative array.
     */
    public function analyze(string $resumeText, ?array $jobHints = null): array
    {
        $schema = <<<JSON
{
  "experience_years_total": number, 
  "skills": [{"name": string, "years": number?}],
  "education": [{"level": string, "field": string?, "institution": string?, "graduation_year": number?}],
  "roles": [{"title": string, "company": string?, "years": number?, "start_year": number?, "end_year": number?}],
  "certifications": [string]?,
  "languages": [string]?,
  "keywords": [string]
}
JSON;

        $req = [
            'model'       => $this->model,
            'temperature' => $this->temperature,
            'messages'    => [
                [
                    'role'    => 'system',
                    'content' => "You are a strict information extractor. Output ONLY a single valid JSON object that matches the schema below. Do not add commentary.\nSchema:\n{$schema}"
                ],
                [
                    'role'    => 'user',
                    'content' => trim(
                        ($jobHints ? "Job hints (optional): " . json_encode($jobHints) . "\n\n" : "")
                        . "Resume text:\n" . $resumeText
                    )
                ],
            ],
        ];

        // OpenAI-compatible Chat Completions
        $res = $this->http->post('/chat/completions', ['body' => json_encode($req)]);
        $json = json_decode((string) $res->getBody(), true);

        $content = $json['choices'][0]['message']['content'] ?? '{}';
        $parsed  = json_decode($content, true);

        if (!is_array($parsed)) {
            // try to recover small JSON snippets
            $content = $this->extractJson($content);
            $parsed  = json_decode($content, true);
        }

        if (!is_array($parsed)) {
            throw new \RuntimeException('Model did not return valid JSON.');
        }

        return $parsed;
    }

    /** Try to pull the first JSON object from a string. */
    protected function extractJson(string $s): string
    {
        $start = strpos($s, '{');
        $end   = strrpos($s, '}');
        if ($start !== false && $end !== false && $end > $start) {
            return substr($s, $start, $end - $start + 1);
        }
        return '{}';
    }
}
