<?php

namespace App\Http\Controllers;

use App\Jobs\ParseResume;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ResumeController extends Controller
{
    /** Return the first column name that exists on the resumes table. */
    protected function col(string ...$candidates): ?string
    {
        foreach ($candidates as $name) {
            if (Schema::hasColumn('resumes', $name)) return $name;
        }
        return null;
    }

    public function index(): Response
    {
        $applicantId = $this->resolveApplicantId(createIfMissing: true);

        $map = [
            'file_name'   => $this->col('file_name', 'filename', 'name'),
            'file_path'   => $this->col('file_path', 'path', 'pathname', 'stored_path'),
            'file_size'   => $this->col('file_size', 'size', 'bytes', 'filesize', 'size_bytes'),
            'mime'        => $this->col('mime', 'mime_type', 'content_type', 'mimetype'),
            'is_active'   => $this->col('is_active', 'active', 'is_primary'),
            'ai_status'   => $this->col('ai_status'),
            'ai_parsed'   => $this->col('ai_parsed'),
            'ai_error'    => $this->col('ai_error'),
            'created_at'  => $this->col('created_at', 'createdOn', 'created'),
            // ✅ for reorder
            'sort_order'  => $this->col('sort_order', 'position', 'order', 'display_order', 'sort'),
        ];

        $select = ['id', 'applicant_id'];
        foreach ($map as $alias => $col) {
            if ($col) $select[] = DB::raw("\"$col\" as \"$alias\"");
        }

        $q = DB::table('resumes')->where('applicant_id', $applicantId);

        // ✅ Sort: active first, then saved order, then newest
        if (!empty($map['is_active'])) {
            $q->orderByDesc($map['is_active']);
        }

        if (!empty($map['sort_order'])) {
            $q->orderBy($map['sort_order']);
        }

        $q->orderByDesc($map['created_at'] ?? 'id');

        $rows = $q->get($select)->map(function ($r) {
            $path = $r->file_path ?? null;
            $r->url = $path ? Storage::disk('public')->url($path) : null;
            return $r;
        });

        return Inertia::render('Applicant/Resume/Index', ['rows' => $rows]);
    }

    public function store(Request $request)
    {
        $applicantId = $this->resolveApplicantId(createIfMissing: true);

        $data = $request->validate([
            // ✅ match Vue: pdf/doc/docx and 5MB
            'resume'         => 'required|file|mimes:pdf,doc,docx|max:5120',
            'application_id' => 'nullable|integer',
        ]);

        $file     = $data['resume'];
        $path     = $file->store('resumes', 'public');          // relative (public disk)
        $abs      = Storage::disk('public')->path($path);       // absolute local path
        $original = $file->getClientOriginalName();
        $size     = $file->getSize();
        $mime     = $file->getClientMimeType();

        $cols = [
            'file_name'      => $this->col('file_name', 'filename', 'name'),
            'file_path'      => $this->col('file_path', 'path', 'pathname', 'stored_path'),
            'storage_path'   => $this->col('storage_path'),
            'file_size'      => $this->col('file_size', 'size', 'bytes', 'filesize', 'size_bytes'),
            'mime'           => $this->col('mime', 'mime_type', 'content_type', 'mimetype'),
            'is_active'      => $this->col('is_active', 'active', 'is_primary'),
            'ai_status'      => $this->col('ai_status'),
            'ai_parsed'      => $this->col('ai_parsed'),
            'ai_error'       => $this->col('ai_error'),
            'application_id' => $this->col('application_id', 'job_application_id'),
            'sort_order'     => $this->col('sort_order', 'position', 'order', 'display_order', 'sort'),
            'created_at'     => $this->col('created_at'),
            'updated_at'     => $this->col('updated_at'),
        ];

        $activeCol = $cols['is_active'] ?? 'is_active';

        $hasActive = DB::table('resumes')
            ->where('applicant_id', $applicantId)
            ->when(Schema::hasColumn('resumes', $activeCol), fn($q) => $q->where($activeCol, true))
            ->exists();

        // ✅ new resume goes to bottom (if sort column exists)
        $nextSort = null;
        if (!empty($cols['sort_order'])) {
            $max = DB::table('resumes')->where('applicant_id', $applicantId)->max($cols['sort_order']);
            $nextSort = ((int)($max ?? 0)) + 1;
        }

        $insert = [
            'applicant_id' => $applicantId,
        ];
        if ($cols['file_name'])     $insert[$cols['file_name']]    = $original;
        if ($cols['file_path'])     $insert[$cols['file_path']]    = $path;
        if ($cols['storage_path'])  $insert[$cols['storage_path']] = $abs;     // absolute local path for parser
        if ($cols['file_size'])     $insert[$cols['file_size']]    = $size;
        if ($cols['mime'])          $insert[$cols['mime']]         = $mime;
        if ($activeCol)             $insert[$activeCol]            = !$hasActive;
        if ($cols['ai_status'])     $insert[$cols['ai_status']]    = 'pending';
        if ($cols['ai_parsed'])     $insert[$cols['ai_parsed']]    = null;
        if ($cols['ai_error'])      $insert[$cols['ai_error']]     = null;
        if ($cols['sort_order'] && $nextSort !== null) $insert[$cols['sort_order']] = $nextSort;
        if ($cols['created_at'])    $insert[$cols['created_at']]   = now();
        if ($cols['updated_at'])    $insert[$cols['updated_at']]   = now();

        // Optional: attach to owned application
        if (!empty($data['application_id']) && $cols['application_id']) {
            $appId = (int) $data['application_id'];
            $owned = DB::table('applications')->where('id', $appId)->where('applicant_id', $applicantId)->exists();
            if ($owned) $insert[$cols['application_id']] = $appId;
        }

        $id = DB::table('resumes')->insertGetId($insert);

        // ✅ Dispatch the job
        if (config('queue.default') === 'sync') {
            ParseResume::dispatchSync($id);
        } else {
            ParseResume::dispatch($id);
        }

        return back()->with('status', 'Resume uploaded.');
    }

    /**
     * ✅ Reorder resumes
     * POST /app/resume/reorder
     * Body: { order: [id1, id2, ...] }
     */
    public function reorder(Request $request)
    {
        $applicantId = $this->resolveApplicantId();

        $sortCol = $this->col('sort_order', 'position', 'order', 'display_order', 'sort');
        if (!$sortCol) {
            abort(422, 'No ordering column found. Please add a sort_order column to resumes table.');
        }

        $data = $request->validate([
            'order' => ['required', 'array', 'min:1'],
            'order.*' => ['integer', 'distinct'],
        ]);

        $ids = array_values($data['order']);

        // ✅ ensure all ids belong to applicant
        $owned = DB::table('resumes')
            ->where('applicant_id', $applicantId)
            ->whereIn('id', $ids)
            ->count();

        if ($owned !== count($ids)) {
            abort(403, 'Invalid resume list.');
        }

        DB::transaction(function () use ($ids, $sortCol, $applicantId) {
            // Efficient CASE update (safe because we cast to int)
            $case = "CASE id ";
            foreach ($ids as $i => $id) {
                $id = (int)$id;
                $pos = $i + 1;
                $case .= "WHEN {$id} THEN {$pos} ";
            }
            $case .= "END";

            DB::table('resumes')
                ->where('applicant_id', $applicantId)
                ->whereIn('id', $ids)
                ->update([$sortCol => DB::raw($case)]);
        });

        return back(303);
    }

    /** Retry button posts here */
    public function retry(int $id)
    {
        $applicantId = $this->resolveApplicantId();
        $row = DB::table('resumes')->where('id', $id)->first();
        abort_if(!$row || (int)$row->applicant_id !== (int)$applicantId, 404);

        DB::table('resumes')->where('id', $id)->update(array_filter([
            $this->col('ai_status')  => 'pending',
            $this->col('ai_parsed')  => null,
            $this->col('ai_error')   => null,
            $this->col('updated_at') => now(),
        ]));

        if (config('queue.default') === 'sync') {
            ParseResume::dispatchSync($id);
        } else {
            ParseResume::dispatch($id);
        }

        return back()->with('status', 'Re-parsed.');
    }

    public function activate(int $id)
    {
        $applicantId = $this->resolveApplicantId();

        $row = DB::table('resumes')->where('id', $id)->first();
        abort_if(!$row || (int) $row->applicant_id !== (int) $applicantId, 404);

        $activeCol  = $this->col('is_active', 'active', 'is_primary') ?? 'is_active';
        $updatedCol = $this->col('updated_at');

        DB::table('resumes')->where('applicant_id', $applicantId)->update(array_filter([
            $activeCol  => false,
            $updatedCol => $updatedCol ? now() : null,
        ], fn($v) => true));

        DB::table('resumes')->where('id', $id)->update(array_filter([
            $activeCol  => true,
            $updatedCol => $updatedCol ? now() : null,
        ], fn($v) => true));

        return back()->with('status', 'Active resume updated.');
    }

    public function destroy(int $id)
    {
        $applicantId = $this->resolveApplicantId();
        $pathCol = $this->col('file_path', 'path', 'pathname', 'stored_path');
        $row     = DB::table('resumes')->where('id', $id)->first();
        abort_if(!$row || (int) $row->applicant_id !== (int) $applicantId, 404);

        if ($pathCol && !empty($row->$pathCol)) {
            Storage::disk('public')->delete($row->$pathCol);
        }

        DB::table('resumes')->where('id', $id)->delete();

        return back()->with('status', 'Resume removed.');
    }

    /** Resolve current user's applicant_id; create minimal applicant if missing. */
    protected function resolveApplicantId(bool $createIfMissing = false): ?int
    {
        $user = Auth::user();
        if (!$user) return null;

        if (property_exists($user, 'applicant_id') && $user->applicant_id) {
            return (int) $user->applicant_id;
        }

        $row = DB::table('applicants')->where('user_id', $user->id)->first();
        if ($row?->id) return (int) $row->id;

        $row = DB::table('applicants')->where('email', $user->email)->first();
        if ($row?->id) return (int) $row->id;

        if (!$createIfMissing) return null;

        $insert = [
            'email'      => $user->email,
            'user_id'    => $user->id,
            'name'       => $user->name ?? $user->email,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $id = DB::table('applicants')->insertGetId($insert);

        try {
            DB::table('users')->where('id', $user->id)->update([
                'applicant_id' => $id,
                'updated_at'   => now(),
            ]);
        } catch (\Throwable $e) {
            // ignore if column not present
        }

        return (int) $id;
    }
}
