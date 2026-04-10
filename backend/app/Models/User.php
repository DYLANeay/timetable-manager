<?php

namespace App\Models;

use App\Enums\Role;
use App\Notifications\ResetPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'role', 'is_active', 'avatar'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'is_active' => 'boolean',
        ];
    }

    public function sendPasswordResetNotification($token): void
    {
        $userLocale = app()->getLocale();
        $notification = (new ResetPasswordNotification($token, $userLocale))->locale($userLocale);
        $this->notify($notification);
    }

    public function isManager(): bool
    {
        return $this->role === Role::Manager;
    }

    public function isEmployee(): bool
    {
        return $this->role === Role::Employee;
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }
}
