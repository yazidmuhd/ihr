<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Turns audit_logs / application_status_histories rows (written by database
 * triggers) into readable entries for the HR Activity page and timelines.
 */
class ActivityFeed
{
    public const AREAS = [
        'vacancies'        => 'Vacancy',
        'applications'     => 'Application',
        'interviews'       => 'Interview',
        'interview_panels' => 'Interview panel',
        'employees'        => 'Employee',
        'departments'      => 'Department',
        'users'            => 'User account',
    ];

    /** Fields that are noise in a change list (timestamps, large AI blobs) */
    private const HIDDEN_FIELDS = ['id', 'created_at', 'updated_at', 'match_breakdown', 'ai_parsed', 'email_verified_at'];

    /** Status changes of one application, oldest first */
    public static function statusTimeline(int $applicationId): array
    {
        return DB::table('application_status_histories as h')
            ->leftJoin('users as u', 'u.id', '=', 'h.changed_by')
            ->where('h.application_id', $applicationId)
            ->orderBy('h.created_at')
            ->orderBy('h.id')
            ->get(['h.from_status', 'h.to_status', 'h.created_at', 'u.name as changed_by'])
            ->map(fn ($r) => [
                'from' => $r->from_status,
                'to'   => $r->to_status,
                'at'   => (string) $r->created_at,
                'by'   => $r->changed_by ?? 'System',
            ])
            ->all();
    }

    /**
     * @param array{area?:string, user?:int|string, from?:string, to?:string, application?:int|string} $filters
     */
    public static function auditPage(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        $q = DB::table('audit_logs as l')
            ->leftJoin('users as u', 'u.id', '=', 'l.user_id')
            ->select('l.*', 'u.name as user_name')
            ->orderByDesc('l.created_at')
            ->orderByDesc('l.id');

        if (!empty($filters['area']) && isset(self::AREAS[$filters['area']])) {
            $q->where('l.table_name', $filters['area']);
        }
        if (!empty($filters['user'])) {
            $filters['user'] === 'system'
                ? $q->whereNull('l.user_id')
                : $q->where('l.user_id', (int) $filters['user']);
        }
        if (!empty($filters['from'])) $q->where('l.created_at', '>=', $filters['from'] . ' 00:00:00');
        if (!empty($filters['to']))   $q->where('l.created_at', '<=', $filters['to'] . ' 23:59:59');

        if (!empty($filters['application'])) {
            self::scopeToApplication($q, (int) $filters['application']);
        }

        return $q->paginate($perPage)->withQueryString()->through(fn ($row) => self::present($row));
    }

    /** Everything that happened to one application: the row itself, its interview, panels and employee record */
    private static function scopeToApplication($q, int $appId): void
    {
        $id = (string) $appId;

        $interviewIds = DB::table('audit_logs')
            ->where('table_name', 'interviews')
            ->whereRaw("coalesce(new_values, old_values)->>'application_id' = ?", [$id])
            ->distinct()
            ->pluck('record_id')
            ->map(fn ($v) => (string) $v)
            ->all();

        $q->where(function ($w) use ($appId, $id, $interviewIds) {
            $w->where(fn ($x) => $x->where('l.table_name', 'applications')->where('l.record_id', $appId))
              ->orWhere(fn ($x) => $x->where('l.table_name', 'interviews')->whereRaw("coalesce(l.new_values, l.old_values)->>'application_id' = ?", [$id]))
              ->orWhere(fn ($x) => $x->where('l.table_name', 'employees')->whereRaw("coalesce(l.new_values, l.old_values)->>'application_id' = ?", [$id]));

            if ($interviewIds) {
                $w->orWhere(fn ($x) => $x->where('l.table_name', 'interview_panels')
                    ->whereRaw("coalesce(l.new_values, l.old_values)->>'interview_id' = any(?)", ['{' . implode(',', $interviewIds) . '}']));
            }
        });
    }

    private static function present(object $row): array
    {
        $old = $row->old_values ? json_decode($row->old_values, true) : [];
        $new = $row->new_values ? json_decode($row->new_values, true) : [];
        $current = $new ?: $old;

        $changes = [];
        if ($row->action === 'update') {
            foreach ($new as $field => $value) {
                if (in_array($field, self::HIDDEN_FIELDS, true)) continue;
                if (($old[$field] ?? null) === $value) continue;
                $changes[] = ['field' => self::label($field), 'old' => self::show($old[$field] ?? null), 'new' => self::show($value)];
            }
        }

        return [
            'id'      => $row->id,
            'at'      => (string) $row->created_at,
            'who'     => $row->user_name ?? 'System',
            'area'    => self::AREAS[$row->table_name] ?? $row->table_name,
            'action'  => $row->action,
            'record'  => self::recordLabel($row->table_name, (int) $row->record_id, $current),
            'changes' => $changes,
        ];
    }

    private static function recordLabel(string $table, int $id, array $v): string
    {
        return match ($table) {
            'vacancies'        => $v['title'] ?? "Vacancy #{$id}",
            'employees'        => trim(($v['name'] ?? '') . ' (' . ($v['employee_no'] ?? "#{$id}") . ')'),
            'users'            => $v['name'] ?? $v['username'] ?? "User #{$id}",
            'departments'      => $v['name'] ?? "Department #{$id}",
            'applications'     => "Application #{$id}",
            'interviews'       => "Interview #{$id} (application #" . ($v['application_id'] ?? '?') . ')',
            'interview_panels' => ($v['name'] ?? "Panel {$id}") . ' on interview #' . ($v['interview_id'] ?? '?'),
            default            => "#{$id}",
        };
    }

    private static function label(string $field): string
    {
        return ucfirst(str_replace('_', ' ', $field));
    }

    private static function show(mixed $value): string
    {
        if ($value === null || $value === '') return '—';
        if (is_bool($value)) return $value ? 'Yes' : 'No';
        if (is_array($value)) return implode(', ', array_map(fn ($x) => is_scalar($x) ? (string) $x : '…', $value));

        $s = (string) $value;
        return mb_strlen($s) > 80 ? mb_substr($s, 0, 77) . '…' : $s;
    }
}
