<?php

// app/Services/ResumeNLPService.php
namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Str;

class ResumeNLPService
{
    public function __construct(
        protected SkillNormalizer $norm
    ) {}

    /**
     * @param  string $resumeText  Plain text (extracted) OR raw text you assembled
     * @return array{skills:array, total_experience_years:int, education_level:string, educations:array, roles:array, basics:array}
     */
    public function extractProfile(string $resumeText): array
    {
        // Call your LLM endpoint (abstracted & replaceable) to produce strict JSON
        $client = new Client([
            'base_uri' => config('ai.base_url'),
            'timeout'  => config('ai.timeout'),
        ]);

        $prompt = <<<PROMPT
You are an information extraction engine. Given a resume, return ONLY valid JSON with:
{
  "skills": ["list", "..."],
  "total_experience_years": 0,
  "education_level": "none|diploma|bachelor|master|phd|other",
  "educations": [{"degree":"BSc Computer Science","institution":"...","year":2018}],
  "roles": [{"title":"Software Engineer","company":"...","start":"2019-05","end":"2022-11"}],
  "basics": {"name":"...","email":"...","phone":"...","location":"..."}
}
- Skills must be concise single tokens or short phrases (e.g., "javascript","vue","sql").
- Experience years must be integer.
- If unknown, use empty arrays or nulls; do not invent facts.
PROMPT;

$client = new \GuzzleHttp\Client([
    'base_uri' => rtrim(config('ai.base_url'), '/'),
    'timeout'  => config('ai.timeout'),
]);

$system = <<<SYS
You extract structured JSON from resumes. Respond with ONLY JSON:
{
  "skills": ["list", "..."],
  "total_experience_years": 0,
  "education_level": "none|diploma|bachelor|master|phd|other",
  "educations": [{"degree":"...", "institution":"...", "year":2018}],
  "roles": [{"title":"...", "company":"...", "start":"YYYY-MM", "end":"YYYY-MM|present"}],
  "basics": {"name":"...", "email":"...", "phone":"...", "location":"..."}
}
No prose. No markdown.
SYS;

$res = $client->post('/v1/chat/completions', [
    'headers' => [
        'Authorization' => 'Bearer '.config('ai.api_key'),
        'Content-Type'  => 'application/json',
    ],
    'json' => [
        'model' => config('ai.model'),
        'messages' => [
            ['role'=>'system', 'content'=>$system],
            ['role'=>'user',   'content'=>$resumeText],
        ],
        'temperature' => 0,
        'response_format' => ['type' => 'json_object'], // ensures valid JSON
    ],
]);

$payload = json_decode((string) $res->getBody(), true);
$json = json_decode($payload['choices'][0]['message']['content'] ?? '{}', true) ?: [];


        // Normalize + guard rails
        $skills = $this->norm->normalize($json['skills'] ?? []);
        $exp    = (int) ($json['total_experience_years'] ?? 0);
        $edu    = Str::lower((string) ($json['education_level'] ?? 'other'));

        return [
            'skills' => $skills,
            'total_experience_years' => max(0, $exp),
            'education_level' => in_array($edu, ['none','diploma','bachelor','master','phd']) ? $edu : 'other',
            'educations' => $json['educations'] ?? [],
            'roles'      => $json['roles'] ?? [],
            'basics'     => $json['basics'] ?? [],
        ];
    }
}
