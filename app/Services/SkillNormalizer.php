<?php

namespace App\Services;

class SkillNormalizer
{
    /**
     * Map common aliases/synonyms → canonical skill names
     * (keep it simple; you can expand later).
     */
    protected array $map = [
        // safety / work permit
        'loto' => 'LOTO',
        'lockout tagout' => 'LOTO',
        'lock out tag out' => 'LOTO',
        'lock-out tag-out' => 'LOTO',

        'ptw' => 'PTW',
        'permit to work' => 'PTW',
        'permit-to-work' => 'PTW',

        // systems
        'sap pm' => 'SAP PM',
        'sap' => 'SAP',
        'cmms' => 'CMMS',

        // drawings
        'p&id' => 'P&ID',
        'pid' => 'P&ID',
        'p & id' => 'P&ID',

        // analysis
        'rca' => 'RCA',
        'root cause analysis' => 'RCA',

        // generic normalizations
        'preventive maintenance' => 'Preventive Maintenance',
        'corrective maintenance' => 'Corrective Maintenance',
        'rotating equipment' => 'Rotating Equipment',
        'condition monitoring' => 'Condition Monitoring',
        'instrument calibration' => 'Instrument Calibration',
        'electrical troubleshooting' => 'Electrical Troubleshooting',
        'troubleshooting' => 'Troubleshooting',
        'pumps' => 'Pumps',
        'pump' => 'Pumps',
        'motors' => 'Motors',
        'motor' => 'Motors',
        'valves' => 'Valves',
        'valve' => 'Valves',
        'piping' => 'Piping',
        'pipelines' => 'Pipelines',
        'pipeline' => 'Pipelines',
        'gearboxes' => 'Gearboxes',
        'gearbox' => 'Gearboxes',
    ];

    /**
     * Normalize an array of skills into a clean unique list.
     */
    public function normalize(array $skills): array
    {
        $out = [];

        foreach ($skills as $s) {
            if (!is_string($s)) continue;

            $s = trim($s);
            if ($s === '') continue;

            // normalize spacing + lowercase key for mapping
            $key = strtolower(preg_replace('/\s+/', ' ', $s));
            $key = str_replace(['–', '—'], '-', $key);
            $key = trim($key);

            // apply mapping if exists
            $normalized = $this->map[$key] ?? $this->smartTitle($s);

            $out[] = $normalized;
        }

        // unique (case-insensitive) keep order
        $seen = [];
        $final = [];
        foreach ($out as $item) {
            $k = strtolower($item);
            if (isset($seen[$k])) continue;
            $seen[$k] = true;
            $final[] = $item;
        }

        return $final;
    }

    /**
     * Small helper to make mixed input look consistent without breaking acronyms.
     */
    protected function smartTitle(string $s): string
    {
        $s = trim(preg_replace('/\s+/', ' ', $s));
        if ($s === '') return $s;

        $upper = ['SAP', 'SAP PM', 'CMMS', 'LOTO', 'PTW', 'RCA', 'P&ID'];
        foreach ($upper as $u) {
            if (strcasecmp($s, $u) === 0) return $u;
        }

        // If it's mostly uppercase already, keep it
        $letters = preg_replace('/[^a-zA-Z]+/', '', $s);
        if ($letters !== '' && strtoupper($letters) === $letters) return $s;

        return ucwords(strtolower($s));
    }
}
