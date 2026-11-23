<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileReference extends Model
{
    protected $fillable = ['name', 'role', 'organization', 'contact', 'consent_confirmed_at'];

    protected function casts(): array
    {
        return ['contact' => 'encrypted', 'consent_confirmed_at' => 'datetime'];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
