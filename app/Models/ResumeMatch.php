<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResumeMatch extends Model
{
    protected $fillable = [
        'application_id','vacancy_id','total_score','section_scores','matched_spans','overridden_by','override_note'
    ];
    protected $casts = ['section_scores'=>'array','matched_spans'=>'array'];
}
