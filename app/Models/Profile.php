<?php

namespace App\Models;

use App\Sports\Sport;
use App\Sports\Sports;
use Database\Factories\ProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $sport
 * @property bool $is_demo
 * @property string|null $display_name
 * @property string|null $pronouns
 * @property string|null $photo_path
 * @property int|null $height_cm
 * @property string|null $current_school
 * @property string|null $division
 * @property string|null $conference
 * @property string|null $class_year
 * @property int|null $eligibility_years_left
 * @property Carbon|null $portal_entered_on
 * @property array<string, mixed>|null $sport_data
 * @property string|null $major
 * @property string|null $gpa
 * @property string|null $gpa_scale
 * @property array<string, mixed>|null $looking_for
 * @property string|null $statement
 * @property list<string>|null $hidden_sections
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 */
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

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<ProfileTeam, $this> */
    public function teams(): HasMany
    {
        return $this->hasMany(ProfileTeam::class)->orderBy('position')->orderBy('id');
    }

    /** @return HasMany<SeasonStat, $this> */
    public function seasonStats(): HasMany
    {
        return $this->hasMany(SeasonStat::class)->orderByDesc('season')->orderBy('id');
    }

    /** @return HasMany<Honour, $this> */
    public function honours(): HasMany
    {
        return $this->hasMany(Honour::class)->orderBy('position')->orderBy('id');
    }

    /** @return HasMany<Video, $this> */
    public function videos(): HasMany
    {
        return $this->hasMany(Video::class)->orderBy('position')->orderBy('id');
    }

    /** @return HasMany<ProfileReference, $this> */
    public function references(): HasMany
    {
        return $this->hasMany(ProfileReference::class)->orderBy('position')->orderBy('id');
    }

    /** @return HasMany<ShareLink, $this> */
    public function shareLinks(): HasMany
    {
        return $this->hasMany(ShareLink::class);
    }

    public function currentLink(): ?ShareLink
    {
        return $this->shareLinks()->whereNull('revoked_at')->latest('id')->first();
    }

    /** @return HasMany<ContactMessage, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(ContactMessage::class)->latest();
    }
}
