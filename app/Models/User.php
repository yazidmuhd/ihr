<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
    'name',
    'email',
    'password',
    'username',
    'phone',
    'about',
    'avatar_path',
    'avatar',
    'role',
    'is_hr',
    'google_id',
    'avatar',
    'email_verified_at',
    
];

protected $casts = [
    'email_verified_at' => 'datetime',
    'is_hr' => 'boolean',
];


    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_hr'             => 'boolean',
        ];
    }

    /**
     * Role label based on is_hr flag
     */
    public function roleName(): string
    {
        return $this->is_hr ? 'hr' : 'applicant';
    }

    /**
     * Instance check: $user->hasRole('hr') / $user->hasRole('applicant')
     */
    public function hasRole(string $role): bool
    {
        $role = strtolower(trim($role));

        return match ($role) {
            'hr'        => (bool) $this->is_hr,
            'applicant' => !((bool) $this->is_hr),
            default     => false,
        };
    }

    /**
     * Query scope so this works:
     * User::role('applicant')->get()
     * User::role('hr')->get()
     */
    public function scopeRole(Builder $query, string $role): Builder
    {
        $role = strtolower(trim($role));

        return match ($role) {
            'hr'        => $query->where('is_hr', true),
            'applicant' => $query->where('is_hr', false),
            default     => $query,
        };
    }

    // ✅ link to applicants.user_id
    public function applicantProfile()
    {
        return $this->hasOne(Applicant::class, 'user_id');
    }
}
