<?php

namespace App\Models\Articles;

use App\Contracts\LocalizedUrlContract;
use App\Models\Settings\Locale;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use App\Models\Seo\SeoMeta;
use App\Models\Traits\GetAvailableLocales;
use OwenIt\Auditing\Contracts\Auditable;

class Tag extends Model implements TranslatableContract, LocalizedUrlContract, Auditable
{
    use CrudTrait;
    use HasFactory;
    use GetAvailableLocales;
    use Translatable;
    use \OwenIt\Auditing\Auditable;

    /** @var list<string> */
    public array $translatedAttributes = ['title', 'slug', 'main_title', 'heading'];

    protected string $translationModel = \App\Models\Articles\Translate\TagTranslation::class;

    
    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'id',
        'view_count',
        'homepage',
        'created_at',
        'updated_at',
    ];

    protected $auditInclude = [
        'homepage',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        // Eager load translations for select2 queries
        static::addGlobalScope('with_translations_for_select', function ($query) {
            // Only apply scope when used in select2 fields (not in CRUD list)
            if ((request()->is('admin/article*') || request()->is('admin/attachment*')) && !request()->is('admin/tag*')) {
                $query->with(['translations' => function ($q) {
                    $q->where('locale', app()->getLocale());
                }]);
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function articles()
    {
        return $this->belongsToMany(Article::class, 'article_tags', 'tag_id', 'article_id');
    }

    public function attachments()
    {
        return $this->belongsToMany(Attachment::class, 'attachment_tag', 'tag_id', 'attachment_id');
    }

    public function seoMeta()
    {
        return $this->morphOne(SeoMeta::class, 'entity');
    }

    
    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Get the available locales with icons
     *
     * @return string
     */
    public function getAvailableLocalesAttribute(): string
    {
        $localeCodes = $this->translations->pluck('locale')->toArray();
        
        return $this->getAvailableLocalesHtml($localeCodes);
    }

    /**
     * Get the available SEO locales with icons
     *
     * @return string
     */
    public function getSeoAvailableLocalesAttribute(): string
    {
        $localeCodes = $this->seoMeta?->translations?->pluck('locale')?->toArray() ?? [];
        
        return $this->getAvailableLocalesHtml($localeCodes);
    }

    public function getAvailableLocales(): Collection
    {
        $availableLocales = Locale::getActive();

        $articleLocales = $this->translations
            ->pluck('locale')
            ->toArray();

        return $availableLocales->filter(function ($locale) use ($articleLocales) {
            return in_array($locale->code, $articleLocales);
        });
    }

    public function getItemUrlForLocale(string $locale): string
    {
        $currentSite = app('currentSite');
        return route('locale.tag.show', [
            'domain'      => $currentSite->domain,
            'locale'      => $locale ?? app()->getLocale(),
            'slug'        => $this->translateOrDefault($locale)->slug,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    /**
     * Get tag display name for Backpack CRUD and select2 fields.
     * This accessor is specifically for admin panel usage.
     * Note: Don't use getNameAttribute() as 'title' is in translatedAttributes.
     */
    public function getDisplayNameAttribute(): string
    {
        // The Translatable package automatically uses the current locale
        return $this->title ?? $this->attributes['name'] ?? '';
    }

    /*
    |--------------------------------------------------------------------------
    | SEO
    |--------------------------------------------------------------------------
    */

    public static function getFieldSeoPrefix(): string
    {
        return 'tag-seo';
    }
}


   
