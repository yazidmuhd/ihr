<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resume extends Model
{
    protected $fillable = [
        'applicant_id',
        'filename', 'path', 'size', 'mime',
        'is_active', 'sort_order',
        'ai_status', 'ai_parsed', 'ai_error',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'ai_parsed' => 'array',
    ];

    public function applicant()
    {
        return $this->belongsTo(Applicant::class);
    }

    public function applications()
    {
        return $this->hasMany(Application::class);
    }
}
