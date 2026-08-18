<?php

namespace App\Models\Articles;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use LogicException;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use App\Models\Traits\GetAvailableLocales;

class Marker extends Model implements TranslatableContract
{
    use CrudTrait;
    use HasFactory;
    use GetAvailableLocales;
    use Translatable;

    /** @var list<string> */
    public array $translatedAttributes = ['name'];

    protected string $translationModel = \App\Models\Articles\Translate\MarkerTranslation::class;

    protected $fillable = [
        'id',
        'code',
        'icon',
        'is_active',
        'is_system',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::deleting(function (self $marker): void {
            if ($marker->is_system) {
                throw new LogicException(__('marker.errors.system_delete_forbidden'));
            }
        });

        static::updating(function (self $marker): void {
            if (! $marker->getOriginal('is_system')) {
                return;
            }

            if ($marker->isDirty(['code', 'is_system', 'is_active'])) {
                throw new LogicException(__('marker.errors.system_properties_immutable'));
            }
        });

        // Eager load translations for select2 queries
        static::addGlobalScope('with_translations_for_select', function ($query) {
            // Only apply scope when used in select2 fields (not in CRUD list)
            if (request()->is('admin/article*') && !request()->is('admin/marker*')) {
                $query->with(['translations' => function ($q) {
                    $q->where('locale', app()->getLocale());
                }]);
            }
        });
    }

    /**
     * Визначає, чи є маркер системним.
     */
    public function isSystem(): bool
    {
        return $this->is_system;
    }

    /**
     * Articles that have this marker
     */
    public function articles()
    {
        return $this->belongsToMany(Article::class, 'article_marker');
    }

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
     * Get marker display name for Backpack CRUD and select2 fields.
     * This accessor is specifically for admin panel usage.
     */
    public function getDisplayNameAttribute(): string
    {
        // The Translatable package automatically uses the current locale
        return $this->name ?? '';
    }
}
