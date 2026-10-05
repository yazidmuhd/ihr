<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One panel member's rating (1-5) and notes for an interview */
class InterviewPanel extends Model
{
    protected $fillable = ['interview_id', 'panel_no', 'name', 'department', 'rating', 'notes'];

    public function interview()
    {
        return $this->belongsTo(Interview::class);
    }
}
