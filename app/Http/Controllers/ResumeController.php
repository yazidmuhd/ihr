<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesApplicant;
use App\Jobs\ParseResume;
use App\Services\Uploads;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ResumeController extends Controller
{
    use ResolvesApplicant;

    public function index(): Response
    {
        $applicantId = $this->resolveApplicantId(createIfMissing: true);

        $rows = DB::table('resumes')
            ->where('applicant_id', $applicantId)
            ->orderByDesc('is_active')
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->get([
                'id', 'applicant_id',
                'filename as file_name', 'path as file_path', 'size as file_size', 'mime',
                'is_active', 'ai_status', 'ai_parsed', 'ai_error', 'created_at', 'sort_order',
            ])
            ->map(function ($r) {
                $r->url = Uploads::url($r->file_path);
                return $r;
            });

        return Inertia::render('Applicant/Resume/Index', ['rows' => $rows]);
    }

    public function store(Request $request)
    {
        $applicantId = $this->resolveApplicantId(createIfMissing: true);

        // The public demo accepts PDF only, up to 2 MB
        $data = $request->validate([
            'resume' => config('demo.enabled')
                ? 'required|file|mimes:pdf|max:' . config('demo.resume_max_kb')
                : 'required|file|mimes:pdf,doc,docx|max:5120',
        ]);

        $file = $data['resume'];
        $path = Uploads::store($file, 'resumes');

        $id = DB::transaction(function () use ($applicantId, $file, $path) {
            $hasActive = DB::table('resumes')->where('applicant_id', $applicantId)->where('is_active', true)->exists();
            $nextSort  = (int) DB::table('resumes')->where('applicant_id', $applicantId)->max('sort_order') + 1;

            return DB::table('resumes')->insertGetId([
                'applicant_id' => $applicantId,
                'filename'     => $file->getClientOriginalName(),
                'path'         => $path,
                'size'         => $file->getSize(),
                'mime'         => $file->getClientMimeType(),
                'is_active'    => !$hasActive,   // first résumé becomes the active one
                'sort_order'   => $nextSort,
                'ai_status'    => 'pending',
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        });

        ParseResume::dispatch($id);   // runs inline when QUEUE_CONNECTION=sync

        return back()->with('status', 'Resume uploaded.');
    }

    /**
     * Reorder resumes
     * POST /app/resume/reorder   Body: { order: [id1, id2, ...] }
     */
    public function reorder(Request $request)
    {
        $applicantId = $this->resolveApplicantId();

        $data = $request->validate([
            'order'   => ['required', 'array', 'min:1'],
            'order.*' => ['integer', 'distinct'],
        ]);

        $ids = array_map('intval', array_values($data['order']));

        $owned = DB::table('resumes')->where('applicant_id', $applicantId)->whereIn('id', $ids)->count();
        abort_if($owned !== count($ids), 403, 'Invalid resume list.');

        DB::transaction(function () use ($ids, $applicantId) {
            foreach ($ids as $i => $id) {
                DB::table('resumes')
                    ->where('applicant_id', $applicantId)
                    ->where('id', $id)
                    ->update(['sort_order' => $i + 1]);
            }
        });

        return back(303);
    }

    /** Retry button posts here */
    public function retry(int $id)
    {
        $this->ownedResume($id);

        DB::table('resumes')->where('id', $id)->update([
            'ai_status'  => 'pending',
            'ai_parsed'  => null,
            'ai_error'   => null,
            'updated_at' => now(),
        ]);

        ParseResume::dispatch($id);

        return back()->with('status', 'Re-parsed.');
    }

    public function activate(int $id)
    {
        $row = $this->ownedResume($id);

        // Only one active résumé per applicant (also enforced by a unique index)
        DB::transaction(function () use ($row) {
            DB::table('resumes')->where('applicant_id', $row->applicant_id)->update(['is_active' => false, 'updated_at' => now()]);
            DB::table('resumes')->where('id', $row->id)->update(['is_active' => true, 'updated_at' => now()]);
        });

        return back()->with('status', 'Active resume updated.');
    }

    public function destroy(int $id)
    {
        $row = $this->ownedResume($id);

        DB::table('resumes')->where('id', $row->id)->delete();
        Uploads::delete($row->path);

        return back()->with('status', 'Resume removed.');
    }

    /** The résumé if it belongs to the signed-in applicant, otherwise 404 */
    protected function ownedResume(int $id): object
    {
        $row = DB::table('resumes')->where('id', $id)->first();
        abort_if(!$row || (int) $row->applicant_id !== (int) $this->resolveApplicantId(), 404);

        return $row;
    }
}
