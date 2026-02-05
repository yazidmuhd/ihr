<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    protected $fillable = ['applicant_id','vacancy_id','status'];
    public function applicant(){ return $this->belongsTo(Applicant::class); }
    public function vacancy(){ return $this->belongsTo(Vacancy::class); }
    public function resume(){ return $this->hasOne(Resume::class); }

protected $casts = [
    'match_breakdown' => 'array',
];

}
