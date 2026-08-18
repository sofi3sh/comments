<?php

namespace App\Models\User;

use App\Models\Articles\Article;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail, TranslatableContract, Auditable
{
    use CrudTrait;
    use HasRoles;
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use Notifiable;
    use Translatable;
    use SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    /** @var list<string> */
    public array $translatedAttributes = ['bio', 'name', 'surname', 'position'];

    protected string $translationModel = \App\Models\User\Translate\UserTranslation::class;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'surname',
        'email',
        'phone',
        'avatar',
        'facebook_url',
        'linkedin_url',
        'twitter_url',
        'personal_data_processed',
        'site_rules_accepted',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $auditInclude = [
        'company_id',
        'email',
        'phone',
        'avatar',
        'facebook_url',
        'linkedin_url',
        'twitter_url',
        'personal_data_processed',
        'site_rules_accepted',
        'deleted_at',
    ];

    protected $auditExclude = [
        'password',
        'remember_token',
    ];

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
            'personal_data_processed' => 'boolean',
            'site_rules_accepted' => 'boolean',
        ];
    }

    /**
     * Get the avatar URL.
     *
     * @return string|null
     */
    public function getAvatarUrlAttribute(): ?string
    {
        if (!$this->avatar) {
            return null;
        }

        return Storage::disk('public')->url($this->avatar);
    }

    public function slug(): Attribute
    {
        return Attribute::get(
            fn () => Str::slug($this->fullname)
        );
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'company_id');
    }

    public function articlesAuthor(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'article_authors');
    }

    public function articlesEditor(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'article_editors');
    }

    public function getLocalizedNameAttribute(): ?string
    {
        return $this->name ?: ($this->attributes['name'] ?? null);
    }

    public function getLocalizedSurnameAttribute(): ?string
    {
        return $this->surname ?: ($this->attributes['surname'] ?? null);
    }

    public function getFullnameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->localized_name,
            $this->localized_surname,
        ])));
    }

    public function getPosition(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        $position = $this->translate($locale)?->position
            ?: $this->translate('uk')?->position;

        return filled($position)
            ? $position
            : __('app-meta.publication_author');
    }

}
