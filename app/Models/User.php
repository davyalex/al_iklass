<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'username', 'email', 'telephone', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * Nombre de tentatives de connexion échouées avant verrouillage du compte.
     */
    public const MAX_FAILED_LOGIN_ATTEMPTS = 3;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'locked_at' => 'datetime',
            'dette' => 'decimal:2',
        ];
    }

    public function isLocked(): bool
    {
        return $this->locked_at !== null;
    }

    public function registerFailedLogin(): void
    {
        $this->increment('failed_login_attempts');

        if ($this->failed_login_attempts >= self::MAX_FAILED_LOGIN_ATTEMPTS) {
            $this->forceFill(['locked_at' => now()])->save();
        }
    }

    public function clearFailedLogins(): void
    {
        if ($this->failed_login_attempts !== 0 || $this->locked_at !== null) {
            $this->forceFill(['failed_login_attempts' => 0, 'locked_at' => null])->save();
        }
    }

    public function vehiculesAttribues(): HasMany
    {
        return $this->hasMany(Vehicule::class, 'gestionnaire_id');
    }
}
