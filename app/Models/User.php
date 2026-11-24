<?php

namespace App\Models;

use App\Services\PhotoStorage;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $pending_email
 * @property string $password
 * @property Carbon|null $email_verified_at
 * @property Carbon|null $age_confirmed_at
 * @property string|null $two_factor_secret
 * @property list<string>|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property int|null $two_factor_last_step
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Profile|null $profile
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_last_step',
        'pending_email',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'age_confirmed_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // However an account is removed, its private photo file goes too.
        static::deleting(function (User $user) {
            if ($user->profile) {
                PhotoStorage::remove($user->profile);
            }
        });
    }

    /** @return HasOne<Profile, $this> */
    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    /** Every account has exactly one profile; create the empty one on first use. */
    public function profileOrCreate(): Profile
    {
        if ($this->profile) {
            return $this->profile;
        }

        $profile = $this->profile()->create(['display_name' => $this->name]);
        $this->setRelation('profile', $profile);

        return $profile;
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_confirmed_at !== null && $this->two_factor_secret !== null;
    }
}
