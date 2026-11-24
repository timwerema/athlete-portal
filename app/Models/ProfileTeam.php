<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileTeam extends Model
{
    protected $fillable = ['position', 'kind', 'name', 'detail', 'start_year', 'end_year'];

    public const KINDS = ['college' => 'College', 'club' => 'Club', 'high_school' => 'High school', 'national' => 'National team'];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
