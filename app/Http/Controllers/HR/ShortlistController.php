<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class ShortlistController extends Controller
{
    /* Helpers */
    protected function col(string $t, string ...$n): ?string {
        foreach ($n as $x) if (Schema::hasColumn($t,$x)) return $x;
        return null;
    }
    protected function anon(int $id): string {
        return "Candidate #".strtoupper(substr(sha1('i-hr'.$id),0,6));
    }

    /**
     * GET /hr/shortlist
     * Hub: list all vacancies with how many are shortlisted (modern UI page).
     */
    public function vacancies(Request $req): Response
    {
        $tv = 'vacancies';
        $ta = 'applications';

        $vacancies = DB::table("$tv as v")
            ->leftJoin("$ta as a", 'a.vacancy_id', '=', 'v.id')
            ->groupBy('v.id', 'v.title', 'v.department', 'v.location', 'v.status', 'v.created_at')
            ->orderByDesc('v.created_at')
            ->get([
                'v.id',
                'v.title',
                DB::raw("coalesce(v.department,'') as department"),
                DB::raw("coalesce(v.location,'') as location"),
                DB::raw("coalesce(v.status,'open') as status"),
                DB::raw("count(*) FILTER (WHERE a.status = 'shortlisted') as shortlisted_count"),
                DB::raw("count(*) FILTER (WHERE a.status in ('submitted','in_review')) as in_review_count"),
                DB::raw("count(a.id) as total_apps"),
            ]);

        return Inertia::render('HR/Shortlist/Vacancies', [
            'vacancies' => $vacancies,
        ]);
    }

    /**
     * GET /hr/shortlist/vacancy/{id}
     * The per-vacancy shortlisted list (used by your page).
     */
    public function byVacancy(Request $req, int $vacancyId): Response
    {
        $ta='applications'; $tv='vacancies';

        $a_id      = $this->col($ta,'id')??'id';
        $a_app     = $this->col($ta,'applicant_id');
        $a_status  = $this->col($ta,'status','state');
        $a_score   = $this->col($ta,'match_score','score');
        $a_vac     = $this->col($ta,'vacancy_id','job_id');
        $a_created = $this->col($ta,'created_at');

        $v_id    = $this->col($tv,'id')??'id';
        $v_title = $this->col($tv,'title');

        // Verify vacancy
        $vacancy = DB::table($tv)->where('id', $vacancyId)->first(['id','title','department','location']);
        abort_if(!$vacancy, 404);

        // Build base query
        $q = DB::table("$ta as a")
            ->join("$tv as v","v.$v_id",'=',"a.$a_vac")
            ->where("a.$a_status",'shortlisted')
            ->where("a.$a_vac",$vacancyId);

        // Left-join interviews if table exists
        $hasInterviews = Schema::hasTable('interviews');
        if ($hasInterviews) {
            $q->leftJoin('interviews as i', 'i.application_id', '=', "a.$a_id");
        }

        // SELECT columns
        $selects = [
            DB::raw("a.\"$a_id\" as id"),
            DB::raw("a.\"$a_app\" as applicant_id"),
            $a_score   ? DB::raw("a.\"$a_score\" as match_score") : DB::raw("NULL as match_score"),
            $a_created ? DB::raw("a.\"$a_created\" as created_at") : DB::raw("NULL as created_at"),
            DB::raw("v.\"$v_title\" as vacancy_title"),
            DB::raw("v.\"$v_id\" as vacancy_id"),
        ];

        // Add computed 'invited' boolean if interviews table exists
        if ($hasInterviews) {
            // Works on Postgres/MySQL: aggregate 1/0 then > 0 to get boolean
            $selects[] = DB::raw("
                MAX(CASE WHEN i.status IN ('invited','scheduled','completed') THEN 1 ELSE 0 END) > 0
                AS invited
            ");
        } else {
            $selects[] = DB::raw("false as invited");
        }

        // GROUP BY for aggregates
        $groupCols = ["a.$a_id", "a.$a_app", "v.$v_title", "v.$v_id"];
        if ($a_score)   $groupCols[] = "a.$a_score";
        if ($a_created) $groupCols[] = "a.$a_created";
        $q->groupBy($groupCols);

        // ORDER BY
        if ($a_score) {
            $q->orderByDesc("a.$a_score");
        } else {
            $q->orderByDesc("a.$a_id");
        }

        $rows = $q->get($selects)->map(function($r){
            $r->anon_name = $this->anon((int)$r->applicant_id);
            return $r;
        });

        return Inertia::render('HR/Shortlist/Index', [
            'rows'    => $rows,
            'vacancy' => $vacancy,
        ]);
    }

    /** POST /hr/applications/{id}/decision  (shortlisted page actions) */
    public function decision(int $id, Request $req)
    {
        $dec = (string)$req->input('decision','');
        $allowed = ['shortlisted','rejected','hired'];
        abort_unless(in_array($dec,$allowed,true), 422, 'Invalid decision.');
        $ta='applications'; $statusCol = $this->col($ta,'status','state');
        abort_if(!$statusCol, 500, 'applications.status not found.');
        DB::table($ta)->where('id',$id)->update([
            $statusCol => $dec, 'updated_at'=>now(),
        ]);
        return back()->with('status', ucfirst($dec).'.');
    }
}
