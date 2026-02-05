<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParsedResume extends Model
{
    protected $fillable = ['resume_id','raw_text','meta','entities'];
    protected $casts = ['meta'=>'array','entities'=>'array'];
    public function resume(){ return $this->belongsTo(Resume::class); }
}
