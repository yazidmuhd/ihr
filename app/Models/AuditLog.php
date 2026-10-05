<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Read-only: rows are written by the *_audit database triggers */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public function user() { return $this->belongsTo(User::class); }
}
