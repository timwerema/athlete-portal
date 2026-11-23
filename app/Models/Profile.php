<?php

namespace App\Models;

use App\Sports\Sport;
use App\Sports\Sports;
use Database\Factories\ProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Profile extends Model
{
    /** @use HasFactory<ProfileFactory> */
    use HasFactory;

    /** Parts of the shared page the athlete can switch on or off. */
    public const HIDEABLE = [
        'photo' => 'Photo',
        'pronouns' => 'Pronouns',
        'height' => 'Height',
        'school' => 'Current school, division and conference',
        'class_year' => 'Class year',
        'eligibility' => 'Eligibility left (self-reported)',
        'portal_date' => 'Official portal entry date (self-reported)',
        'history' => 'Club and school history',
        'stats' => 'Season stats',
        'honours' => 'Honours',
        'major' => 'Major',
        'gpa' => 'GPA',
        'videos' => 'Video',
        'references' => 'References',
        'looking_for' => 'What I\'m looking for',
        'statement' => 'Personal statement',
    ];

    /** Hidden until the athlete switches them on. */
    public const HIDDEN_BY_DEFAULT = ['gpa', 'references', 'portal_date'];

    public const DIVISIONS = ['NCAA Division I', 'NCAA Division II', 'NCAA Division III', 'NAIA', 'NJCAA', 'Other'];

    public const CLASS_YEARS = ['Freshman', 'Sophomore', 'Junior', 'Senior', 'Graduate student'];

    public const REGIONS = ['Northeast', 'Mid-Atlantic', 'Southeast', 'Midwest', 'Southwest', 'Mountain West', 'West Coast', 'Open to anywhere'];

    protected $fillable = [
        'display_name', 'pronouns', 'height_cm',
        'current_school', 'division', 'conference', 'class_year', 'eligibility_years_left', 'portal_entered_on',
        'sport_data', 'major', 'gpa', 'gpa_scale', 'looking_for', 'statement', 'hidden_sections',
    ];

    protected $attributes = [
        'sport' => 'soccer',
    ];

    protected function casts(): array
    {
        return [
            'is_demo' => 'boolean',
            'sport_data' => 'array',
            'looking_for' => 'array',
            'hidden_sections' => 'array',
            'portal_entered_on' => 'date:Y-m-d',
            'gpa' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Profile $profile) {
            $profile->hidden_sections ??= self::HIDDEN_BY_DEFAULT;
        });
    }

    public function sportDefinition(): Sport
    {
        return Sports::get($this->sport);
    }

    public function shows(string $section): bool
    {
        return ! in_array($section, $this->hidden_sections ?? [], true);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(ProfileTeam::class)->orderByRaw('end_year is null desc')->orderByDesc('end_year')->orderByDesc('start_year');
    }

    public function seasonStats(): HasMany
    {
        return $this->hasMany(SeasonStat::class)->orderByDesc('season')->orderBy('id');
    }

    public function honours(): HasMany
    {
        return $this->hasMany(Honour::class)->orderByDesc('year')->orderBy('id');
    }

    public function videos(): HasMany
    {
        return $this->hasMany(Video::class)->orderBy('kind', 'desc')->orderBy('id');
    }

    public function references(): HasMany
    {
        return $this->hasMany(ProfileReference::class)->orderBy('id');
    }

    public function shareLinks(): HasMany
    {
        return $this->hasMany(ShareLink::class);
    }

    public function currentLink(): ?ShareLink
    {
        return $this->shareLinks()->whereNull('revoked_at')->latest('id')->first();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ContactMessage::class)->latest();
    }
}
