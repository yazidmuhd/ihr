<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class FilterController extends Controller
{
    protected function col(string $t, string ...$n): ?string {
        foreach ($n as $x) if (Schema::hasColumn($t, $x)) return $x;
        return null;
    }
    protected function anon(int $id): string {
        return "Candidate #".strtoupper(substr(sha1('i-hr'.$id),0,6));
    }

    public function index(Request $req): Response
    {
        $min = (int)$req->get('min', 0);
        $vac = (int)$req->get('vacancy_id', 0);
        $status = (string)$req->get('status', 'All');

        $ta='applications'; $tv='vacancies';
        $a_id=$this->col($ta,'id')??'id';
        $a_vac=$this->col($ta,'vacancy_id','job_id');
        $a_app=$this->col($ta,'applicant_id');
        $a_score=$this->col($ta,'match_score','score');
        $a_status=$this->col($ta,'status','state');
        $a_created=$this->col($ta,'created_at');

        $v_id=$this->col($tv,'id')??'id';
        $v_title=$this->col($tv,'title');

        $q = DB::table("$ta as a")
            ->join("$tv as v","v.$v_id",'=',"a.$a_vac")
            ->when($vac>0, fn($q)=>$q->where("a.$a_vac",$vac))
            ->when($min>0, fn($q)=>$q->where("a.$a_score",'>=',$min))
            ->when($status!=='All', fn($q)=>$q->where("a.$a_status",$status))
            ->orderByDesc("a.$a_score");

        $rows = $q->get([
            "a.$a_id as id",
            "a.$a_app as applicant_id",
            $a_score ? DB::raw("a.\"$a_score\" as match_score") : DB::raw("NULL as match_score"),
            $a_status? DB::raw("a.\"$a_status\" as status") : DB::raw("'submitted' as status"),
            $a_created? DB::raw("a.\"$a_created\" as created_at") : DB::raw("NULL as created_at"),
            "v.$v_title as vacancy_title",
            "v.$v_id as vacancy_id",
        ])->map(function($r){
            $r->anon_name = $this->anon((int)$r->applicant_id);
            return $r;
        });

        $vacancies = DB::table($tv)->orderBy('id','desc')->get(['id','title']);

        return Inertia::render('HR/Filter/Index', [
            'rows'=>$rows,
            'filters'=>['min'=>$min,'vacancy_id'=>$vac,'status'=>$status],
            'vacancies'=>$vacancies,
        ]);
    }
}
