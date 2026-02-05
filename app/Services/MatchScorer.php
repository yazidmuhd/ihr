<?php

namespace App\Services;

class MatchScorer
{
    /**
     * Main entry.
     * Accepts:
     *  $resume: parsed resume array (from ai_parsed / parsed_resumes.entities)
     *  $vacancy: array with title/description/requirements + optional fields like skills_required, weights, etc.
     */
    public static function score(array $resume, array $vacancy): array
    {
        // ====== Vacancy text ======
        $vtext = self::normText(
            ($vacancy['title'] ?? '') . ' ' .
            ($vacancy['description'] ?? '') . ' ' .
            ($vacancy['requirements'] ?? '') . ' ' .
            ($vacancy['responsibilities'] ?? '')
        );

        // ====== Detect job family (changes lexicon + defaults) ======
        $family = self::detectJobFamily($vtext);

        // ====== Weights (can override via $vacancy['weights']) ======
        $defaultWeights = self::defaultWeightsForFamily($family);
        $W = self::normalizeWeights($vacancy['weights'] ?? $defaultWeights);

        // ====== Required years ======
        $reqYears = self::extractMinYearsFromText($vtext);
        if ($reqYears <= 0) $reqYears = self::defaultMinYearsForFamily($family);

        // ====== Education ======
        $candEdu = self::highestEducation($resume['education'] ?? ($resume['edu'] ?? null));
        $reqEdu  = self::extractRequiredEducation($vtext);
        $eduPct  = (float) self::educationScore((int)$candEdu['level'], (int)$reqEdu['level']);

        // Field mismatch (light penalty) if JD asks specific engineering domain
        if (!empty($reqEdu['field']) && !empty($candEdu['field']) && $reqEdu['field'] !== $candEdu['field']) {
            $eduPct = max(0.0, $eduPct - 15.0);
        }

        // ====== Experience ======
        $yrs = self::extractCandidateYears($resume);
        $expPct = ($reqYears <= 0) ? min(100.0, $yrs * 20.0) : min(100.0, ($yrs / $reqYears) * 100.0);

        // ====== Skills (Required + Optional) ======
        $resSkills = self::normSkills(
            $resume['skills']
            ?? $resume['skills_extracted']
            ?? $resume['keywords']
            ?? []
        );

        // Prefer DB stored skills if available
        $jdRequiredSource =
            $vacancy['skills_required']
            ?? $vacancy['required_skills']
            ?? $vacancy['skills']
            ?? null;

        $jdOptionalSource =
            $vacancy['skills_optional']
            ?? $vacancy['skills_nice']
            ?? $vacancy['nice_to_have']
            ?? null;

        $jdRequired = self::normSkills(self::parseList($jdRequiredSource));
        $jdOptional = self::normSkills(self::parseList($jdOptionalSource));

        // If JD fields are empty, extract from vacancy text using family lexicon
        if (empty($jdRequired) && empty($jdOptional)) {
            [$jdRequired, $jdOptional] = self::extractJDSkillsByFamily($vtext, $family);
        }

        // Compute overlap
        $matchedReq = array_values(array_intersect($resSkills, $jdRequired));
        $missingReq = array_values(array_diff($jdRequired, $resSkills));
        $matchedOpt = array_values(array_intersect($resSkills, $jdOptional));
        $missingOpt = array_values(array_diff($jdOptional, $resSkills));

        $reqPct = empty($jdRequired) ? 0.0 : (count($matchedReq) / max(1, count($jdRequired))) * 100.0;
        $optPct = empty($jdOptional) ? 0.0 : (count($matchedOpt) / max(1, count($jdOptional))) * 100.0;

        // Required skills matter more than optional
        $skillsPct = (empty($jdRequired) && empty($jdOptional))
            ? 0.0
            : (0.80 * $reqPct + 0.20 * $optPct);

        // ====== Certifications / Licenses (bonus but important for plant roles) ======
        $candCerts = self::normSkills(self::parseList($resume['certifications'] ?? $resume['licenses'] ?? $resume['certs'] ?? []));
        $jdCerts = self::extractCertsFromText($vtext, $family);

        $matchedCerts = array_values(array_intersect($candCerts, $jdCerts));
        $certPct = empty($jdCerts) ? 100.0 : (count($matchedCerts) / max(1, count($jdCerts))) * 100.0;

        // ====== Tools/Systems (SAP, DCS, PLC, Excel, etc.) ======
        $candTools = self::normSkills(self::parseList($resume['tools'] ?? $resume['systems'] ?? $resume['tools_systems'] ?? []));
        $jdTools   = self::extractToolsFromText($vtext, $family);

        $matchedTools = array_values(array_intersect($candTools, $jdTools));
        $toolsPct = empty($jdTools) ? 0.0 : (count($matchedTools) / max(1, count($jdTools))) * 100.0;

        // ====== Final weighted score ======
        $score =
            $W['skills']       * $skillsPct +
            $W['experience']   * $expPct +
            $W['education']    * $eduPct +
            $W['certifications'] * $certPct +
            $W['tools']        * $toolsPct;

        $score = (int) round(max(0.0, min(100.0, $score)));

        // ====== Breakdown (UI-friendly + backward compatible keys) ======
        return [
            'score' => $score,
            'breakdown' => [
                'job_family' => $family,

                // UI-friendly structure
                'skills' => [
                    'required' => $jdRequired,
                    'optional' => $jdOptional,
                    'matched'  => array_values(array_unique(array_merge($matchedReq, $matchedOpt))),
                    'missing'  => array_values(array_unique(array_merge($missingReq, $missingOpt))),
                    'matched_required' => $matchedReq,
                    'missing_required' => $missingReq,
                    'matched_optional' => $matchedOpt,
                    'missing_optional' => $missingOpt,
                    'score_pct' => (int) round($skillsPct),
                ],
                'experience' => [
                    'required'  => $reqYears,
                    'candidate' => $yrs,
                    'score_pct' => (int) round($expPct),
                ],
                'education' => [
                    'required'  => $reqEdu['label'] ?? 'unspecified',
                    'candidate' => $candEdu['label'] ?? 'unspecified',
                    'required_level'  => $reqEdu['level'] ?? 0,
                    'candidate_level' => $candEdu['level'] ?? 0,
                    'required_field'  => $reqEdu['field'] ?? null,
                    'candidate_field' => $candEdu['field'] ?? null,
                    'score_pct' => (int) round($eduPct),
                ],
                'certifications' => [
                    'required' => $jdCerts,
                    'candidate' => $candCerts,
                    'matched' => $matchedCerts,
                    'score_pct' => (int) round($certPct),
                ],
                'tools' => [
                    'required' => $jdTools,
                    'candidate' => $candTools,
                    'matched' => $matchedTools,
                    'score_pct' => (int) round($toolsPct),
                ],

                // Backward-compatible keys you were using before
                'overlap_skills' => array_values(array_unique(array_merge($matchedReq, $matchedOpt))),
                'jd_skills'      => array_values(array_unique(array_merge($jdRequired, $jdOptional))),
                'res_skills'     => $resSkills,
                'req_years'      => $reqYears,
                'have_years'     => $yrs,

                'weights_used'   => $W,
            ],
        ];
    }

    // ---------------------- Job Family ----------------------

    private static function detectJobFamily(string $vtext): string
    {
        $t = $vtext;

        $rules = [
            'maintenance' => ['maintenance', 'rotating equipment', 'pump', 'valve', 'motor', 'gearbox', 'breakdown', 'cmms', 'sap pm'],
            'operations'  => ['production', 'operator', 'shift', 'panel', 'dcs', 'utilities', 'plant operation', 'process technician'],
            'process_engineering' => ['process engineer', 'process engineering', 'mass balance', 'heat exchanger', 'distillation', 'process optimization'],
            'instrumentation' => ['instrument', 'instrumentation', 'calibration', 'analyzer', 'control valve', 'loop tuning'],
            'electrical' => ['electrical', 'switchgear', 'motor control', 'mcc', 'hv', 'lv', 'power distribution'],
            'automation' => ['plc', 'scada', 'dcs', 'automation', 'control system', 'siemens', 'allen bradley'],
            'hse' => ['hse', 'safety', 'osh', 'hazop', 'risk assessment', 'incident', 'permit to work', 'loto', 'process safety'],
            'quality_lab' => ['qc', 'qa', 'laboratory', 'lab analyst', 'chromatography', 'gc', 'hplc', 'iso 9001'],
            'supply_chain' => ['logistics', 'warehouse', 'inventory', 'shipping', 'transport', 'supply chain', 'planner'],
            'procurement' => ['procurement', 'buyer', 'rfq', 'quotation', 'vendor', 'contract', 'purchase order'],
            'finance' => ['finance', 'account', 'accounting', 'audit', 'tax', 'invoice', 'ledger', 'budget', 'controlling'],
            'hr' => ['human resource', 'hr', 'recruitment', 'talent acquisition', 'payroll', 'employee relations'],
            'it' => ['it', 'developer', 'software', 'network', 'database', 'cybersecurity', 'laravel', 'vue', 'api', 'cloud'],
            'admin' => ['admin', 'administrator', 'secretary', 'office management', 'documentation', 'coordination'],
        ];

        foreach ($rules as $family => $keys) {
            foreach ($keys as $k) {
                if (str_contains($t, self::normText($k))) {
                    return $family;
                }
            }
        }

        return 'general';
    }

    private static function defaultWeightsForFamily(string $family): array
    {
        // More balanced for office roles; heavier skills/experience for plant roles
        return match ($family) {
            'maintenance', 'operations', 'instrumentation', 'electrical', 'automation' => [
                'skills' => 0.45, 'experience' => 0.30, 'education' => 0.15, 'certifications' => 0.07, 'tools' => 0.03
            ],
            'hse' => [
                'skills' => 0.40, 'experience' => 0.25, 'education' => 0.15, 'certifications' => 0.15, 'tools' => 0.05
            ],
            'quality_lab' => [
                'skills' => 0.45, 'experience' => 0.25, 'education' => 0.20, 'certifications' => 0.05, 'tools' => 0.05
            ],
            'finance', 'procurement', 'supply_chain', 'hr', 'admin' => [
                'skills' => 0.50, 'experience' => 0.25, 'education' => 0.20, 'certifications' => 0.02, 'tools' => 0.03
            ],
            'it' => [
                'skills' => 0.55, 'experience' => 0.25, 'education' => 0.15, 'certifications' => 0.02, 'tools' => 0.03
            ],
            default => [
                'skills' => 0.45, 'experience' => 0.30, 'education' => 0.20, 'certifications' => 0.03, 'tools' => 0.02
            ],
        };
    }

    private static function defaultMinYearsForFamily(string $family): int
    {
        return match ($family) {
            'admin', 'hr' => 0,
            'general' => 1,
            default => 2,
        };
    }

    // ---------------------- JD Extraction ----------------------

    /**
     * Returns [requiredSkills[], optionalSkills[]]
     */
    private static function extractJDSkillsByFamily(string $vtext, string $family): array
    {
        // Base skills that work across most roles
        $base = [
            'communication',
            'teamwork',
            'problem solving',
            'time management',
            'microsoft excel',
        ];

        $lex = match ($family) {
            'maintenance' => [
                'preventive maintenance','corrective maintenance','rotating equipment','pumps','motors','valves','piping',
                'pipelines','gearboxes','troubleshooting','breakdown','loto','ptw','pid','cmms','sap pm','condition monitoring','rca',
            ],
            'operations' => [
                'production','operator','shift','dcs','utilities','startup','shutdown','process monitoring','sop','plant operation',
            ],
            'process_engineering' => [
                'process optimization','mass balance','heat exchanger','distillation','reactor','pfd','pid','hazop','rca',
            ],
            'instrumentation' => [
                'instrument calibration','calibration','analyzer','control valve','loop tuning','pid','ptw','loto',
            ],
            'electrical' => [
                'electrical troubleshooting','switchgear','mcc','motor control','power distribution','preventive maintenance','loto','ptw',
            ],
            'automation' => [
                'plc','scada','dcs','control system','automation','loop tuning','pid',
            ],
            'hse' => [
                'hse','safety','risk assessment','incident investigation','hazop','ptw','loto','jsa','permit to work','process safety',
            ],
            'quality_lab' => [
                'qc','qa','laboratory','sample preparation','gc','hplc','iso 9001','documentation','data integrity',
            ],
            'supply_chain' => [
                'logistics','warehouse','inventory','shipping','planning','demand planning','customer service','sap',
            ],
            'procurement' => [
                'procurement','vendor management','rfq','quotation','contract','negotiation','purchase order','sap',
            ],
            'finance' => [
                'accounting','finance','budgeting','forecasting','invoice','audit','tax','cost control','excel',
            ],
            'hr' => [
                'recruitment','talent acquisition','payroll','onboarding','employee relations','hr policy','communication',
            ],
            'it' => [
                'software development','api','database','sql','python','laravel','vue','docker','git','cloud',
            ],
            'admin' => [
                'documentation','coordination','office management','scheduling','reporting','microsoft excel',
            ],
            default => $base,
        };

        // Find lex hits in JD text
        $found = [];
        foreach (array_merge($base, $lex) as $k) {
            $kk = self::normText($k);
            if ($kk !== '' && str_contains($vtext, $kk)) $found[] = $k;
        }

        $found = self::normSkills($found);

        // Heuristic: “required/must” terms => requiredSkills; else optional
        // (simple, stable)
        $required = [];
        $optional = [];

        foreach ($found as $s) {
            if (str_contains($vtext, 'must') || str_contains($vtext, 'required') || str_contains($vtext, 'mandatory')) {
                $required[] = $s;
            } else {
                $optional[] = $s;
            }
        }

        // If everything ended up optional, treat core family lex as required
        if (empty($required) && !empty($found)) {
            $required = $found;
            $optional = [];
        }

        return [array_values(array_unique($required)), array_values(array_unique($optional))];
    }

    private static function extractCertsFromText(string $vtext, string $family): array
    {
        $lex = [
            'nebosh','osh','iosh',
            'chargeman','boilerman','steam engineer',
            'first aid','forklift',
            'iso 9001','iso 14001','iso 45001',
        ];

        // Plant roles: emphasize PTW/LOTO-related credentials too
        if (in_array($family, ['maintenance','operations','electrical','instrumentation','automation','hse'], true)) {
            $lex = array_merge($lex, ['ptw', 'loto', 'hazop']);
        }

        $found = [];
        foreach ($lex as $k) {
            if (str_contains($vtext, self::normText($k))) $found[] = $k;
        }
        return self::normSkills($found);
    }

    private static function extractToolsFromText(string $vtext, string $family): array
    {
        $lex = [
            'sap', 'sap pm', 'cmms',
            'excel', 'power bi',
            'dcs', 'plc', 'scada',
            'autocad',
            'python', 'sql',
            'git', 'docker',
        ];

        // Lab roles
        if ($family === 'quality_lab') {
            $lex = array_merge($lex, ['gc', 'hplc', 'chromatography', 'lims']);
        }

        $found = [];
        foreach ($lex as $k) {
            if (str_contains($vtext, self::normText($k))) $found[] = $k;
        }
        return self::normSkills($found);
    }

    // ---------------------- Generic helpers ----------------------

    private static function parseList($v): array
    {
        if (is_array($v)) return $v;
        if (!is_string($v)) return [];

        $s = trim($v);
        if ($s === '') return [];

        // JSON array/object
        if ((str_starts_with($s, '[') && str_ends_with($s, ']')) || (str_starts_with($s, '{') && str_ends_with($s, '}'))) {
            $decoded = json_decode($s, true);
            if (is_array($decoded)) {
                if (array_keys($decoded) !== range(0, count($decoded) - 1)) {
                    return array_values($decoded);
                }
                return $decoded;
            }
        }

        return preg_split('/[,;\n\r|]+/', $s) ?: [];
    }

    private static function normalizeWeights(array $w): array
    {
        $skills = (float)($w['skills'] ?? 0.45);
        $exp    = (float)($w['experience'] ?? 0.30);
        $edu    = (float)($w['education'] ?? 0.20);
        $certs  = (float)($w['certifications'] ?? 0.03);
        $tools  = (float)($w['tools'] ?? 0.02);

        $sum = max(0.0001, $skills + $exp + $edu + $certs + $tools);

        return [
            'skills' => $skills / $sum,
            'experience' => $exp / $sum,
            'education' => $edu / $sum,
            'certifications' => $certs / $sum,
            'tools' => $tools / $sum,
        ];
    }

    private static function extractCandidateYears(array $cand): float
    {
        $v = $cand['experience_years']
            ?? $cand['years_experience']
            ?? $cand['experience']
            ?? 0;

        if (is_array($v)) {
            if (isset($v['years'])) return (float)$v['years'];
            $sum = 0.0;
            foreach ($v as $row) {
                if (is_array($row) && isset($row['years'])) $sum += (float)$row['years'];
            }
            return $sum;
        }

        return (float)$v;
    }

    private static function normSkills($skills): array
    {
        if (is_string($skills)) $skills = self::parseList($skills);

        $out = [];
        foreach ((array)$skills as $s) {
            $t = self::normaliseSkillToken((string)$s);
            if ($t !== '') $out[$t] = true;
        }
        return array_keys($out);
    }

    private static function normaliseSkillToken(string $s): string
    {
        $s = strtolower(trim($s));

        // remove level hints
        $s = preg_replace('/\((?:basic|intermediate|advanced|expert)\)/', '', $s);

        $s = str_replace(['&'], ['and'], $s);
        $s = preg_replace('/[^\p{L}\p{N}\+\#\.\-\/ ]+/u', ' ', $s);
        $s = preg_replace('/\s+/', ' ', $s);
        $s = trim($s);

        // common aliases
        $map = [
            'lockout tagout' => 'loto',
            'lock out tag out' => 'loto',
            'loto' => 'loto',
            'permit to work' => 'ptw',
            'ptw' => 'ptw',
            'p&id' => 'pid',
            'p and id' => 'pid',
            'pid' => 'pid',
            'root cause analysis' => 'rca',
            'rca' => 'rca',
            'sap' => 'sap',
            'sap pm' => 'sap pm',
            'cmms' => 'cmms',
            'powerbi' => 'power bi',
        ];

        return $map[$s] ?? $s;
    }

    private static function extractMinYearsFromText(string $text): int
    {
        $t = strtolower($text);
        $re = '/(?:min(?:imum)?\s*)?(\d+)\s*\+?\s*(?:years?|yrs?)(?:\s+of\s+experience)?/i';
        if (preg_match_all($re, $t, $m)) {
            $vals = array_map('intval', $m[1]);
            $vals = array_values(array_filter($vals, fn($x) => $x > 0));
            if ($vals) return min($vals);
        }
        return 0;
    }

    private static function eduLevelFromString(string $s): array
    {
        $s = strtolower($s);

        $levels = [
            7 => ['phd','doctor of philosophy','doktor falsafah'],
            6 => ['master','sarjana'],
            5 => ['bachelor','degree','sarjana muda','b.sc','b eng','bsc','beng','hons'],
            4 => ['advanced diploma','higher national diploma','hnd'],
            3 => ['diploma'],
            2 => ['stpm','matriculation','asasi','foundation','pre-university'],
            1 => ['spm','o-level','sijil pelajaran malaysia'],
        ];

        $label = 'unspecified';
        $lvl   = 0;

        foreach ($levels as $L => $keys) {
            foreach ($keys as $kw) {
                if (str_contains($s, $kw)) {
                    $lvl = $L;
                    $label = strtoupper($kw);
                    break 2;
                }
            }
        }

        // detect common fields
        $field = null;
        if (preg_match('/(mechanical|chemical|electrical|instrumentation|mechatronic|process|finance|accounting|it|computer science|hr|human resource|business|supply chain|logistics)/i', $s, $m)) {
            $field = strtolower(trim($m[1]));
            $field = str_replace('computer science', 'it', $field);
            $field = str_replace('human resource', 'hr', $field);
        }

        return ['level'=>$lvl, 'label'=>$label, 'field'=>$field];
    }

    private static function highestEducation($edu): array
    {
        if (is_array($edu)) {
            $best = ['level'=>0,'label'=>null,'field'=>null];
            foreach ($edu as $row) {
                $txt = is_array($row) ? implode(' ', array_map('strval', $row)) : (string)$row;
                $x = self::eduLevelFromString($txt);
                if ($x['level'] > $best['level']) $best = $x;
            }
            return $best;
        }

        if (is_string($edu) && trim($edu) !== '') {
            return self::eduLevelFromString($edu);
        }

        return ['level'=>0,'label'=>null,'field'=>null];
    }

    private static function extractRequiredEducation(string $text): array
    {
        $x = self::eduLevelFromString($text);
        if ($x['level'] === 0) return ['level'=>0, 'label'=>'unspecified', 'field'=>null];
        return ['level'=>$x['level'], 'label'=>$x['label'], 'field'=>$x['field']];
    }

    private static function educationScore(int $cand, int $req): int
    {
        if ($req === 0) {
            if ($cand >= 5) return 100;
            if ($cand === 3 || $cand === 4) return 80;
            if ($cand === 2) return 60;
            if ($cand === 1) return 40;
            return 0;
        }
        if ($cand >= $req) return 100;
        $diff = $req - $cand;
        return (int) max(0, 100 - $diff * 40);
    }

    private static function normText(string $s): string
    {
        $s = strtolower(trim($s));
        $s = preg_replace('/\s+/', ' ', $s);
        return $s ?? '';
    }
}
