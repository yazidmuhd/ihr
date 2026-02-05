<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resume extends Model
{
    protected $fillable = [
        'applicant_id','application_id',
        'filename','file_name',
        'mime_type','mime',
        'size_bytes','file_size',
        'storage_path','file_path',
        'is_active','ai_status','ai_parsed',
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function parsed()
    {
        return $this->hasOne(ParsedResume::class);
    }
}
