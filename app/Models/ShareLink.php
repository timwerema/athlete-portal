<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ShareLink extends Model
{
    protected $hidden = ['token_hash', 'token_encrypted'];

    protected function casts(): array
    {
        return ['token_encrypted' => 'encrypted', 'revoked_at' => 'datetime'];
    }

    /** Create a new secret link. The token is long enough that it cannot be guessed. */
    public static function issueFor(Profile $profile): self
    {
        $token = Str::random(43);

        $link = new self;
        $link->profile_id = $profile->id;
        $link->token_hash = self::hash($token);
        $link->token_encrypted = $token;
        $link->save();

        return $link;
    }

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    public static function findActive(string $token): ?self
    {
        if (! preg_match('/\A[A-Za-z0-9]{43}\z/', $token)) {
            return null;
        }

        return self::query()->where('token_hash', self::hash($token))->whereNull('revoked_at')->first();
    }

    public function token(): string
    {
        return $this->token_encrypted;
    }

    public function url(): string
    {
        return route('share.show', ['token' => $this->token()]);
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function views(): HasMany
    {
        return $this->hasMany(ProfileView::class);
    }
}
