<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Applicant extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
    ];

    public $timestamps = false; // your table uses created_at only; set to true if you also have updated_at

    public function user()
    {
    return $this->belongsTo(\App\Models\User::class);
    }

    public function applicantProfile()
{
    return $this->hasOne(\App\Models\Applicant::class);
}
}
