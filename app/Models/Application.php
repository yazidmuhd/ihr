<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    protected $fillable = ['applicant_id', 'vacancy_id', 'resume_id', 'status', 'match_score', 'match_breakdown'];

    protected $casts = [
        'match_score'     => 'float',
        'match_breakdown' => 'array',
    ];

    public function applicant() { return $this->belongsTo(Applicant::class); }
    public function vacancy()   { return $this->belongsTo(Vacancy::class); }
    public function resume()    { return $this->belongsTo(Resume::class); }
    public function interview() { return $this->hasOne(Interview::class); }

    public function statusHistory()
    {
        return $this->hasMany(ApplicationStatusHistory::class)->orderBy('created_at');
    }
}
