<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VacancyRule extends Model
{
    protected $fillable = ['vacancy_id','rule_type','pattern','weight','is_regex'];
    public function vacancy() { return $this->belongsTo(Vacancy::class); }
}
