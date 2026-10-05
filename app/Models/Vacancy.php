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

        // AI screening criteria
        'experience_min_years',
        'experience_max_years',
        'education_required',
        'skills_required',   // JSON array
    ];

    protected $casts = [
        'skills_required'      => 'array',
        'closing_date'         => 'date',
        'experience_min_years' => 'integer',
        'experience_max_years' => 'integer',
    ];

    public function departmentRecord()
    {
        return $this->belongsTo(Department::class, 'department', 'name');
    }
}
