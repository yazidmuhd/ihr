<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Read-only: rows are written by the applications_status_history database trigger */
class ApplicationStatusHistory extends Model
{
    public const UPDATED_AT = null;

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function application() { return $this->belongsTo(Application::class); }
    public function changedBy()   { return $this->belongsTo(User::class, 'changed_by'); }
}
