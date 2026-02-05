<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vacancy extends Model
{
    protected $table = 'vacancies';

    protected $fillable = [
        'title',
        'department',
        'location',
        'employment_type',
        'description',
        'closing_date',
        'status',

        // legacy single-field requirement
        'experience_years_required',

        // NEW range-based fields
        'experience_min_years',
        'experience_max_years',

        'education_required',

        // AI config
        'skills_required',   // JSON array
        'scoring_config',    // JSON, optional
    ];

    protected $casts = [
        'skills_required'           => 'array',
        'scoring_config'            => 'array',
        'closing_date'              => 'date',
        'experience_min_years'      => 'integer',
        'experience_max_years'      => 'integer',
        'experience_years_required' => 'integer',
    ];

    // Keep this only if you actually have VacancyRule model/table
    public function rules()
    {
        return $this->hasMany(VacancyRule::class);
    }
}
