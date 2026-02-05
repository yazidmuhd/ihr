<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Interview extends Model
{
    protected $table = 'interviews';

    protected $fillable = [
        'application_id',
        'applicant_id',
        'vacancy_id',
        'scheduled_at',
        'panel_count',
        'interview_score',
        'final_score',
        'status',
        'mode',
        'location',
        'meeting_link',
        'extra_info',
        'candidate_status',
        'candidate_reason',
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function panels()
    {
        return $this->hasMany(InterviewPanel::class);
    }
}
