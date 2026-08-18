<?php

namespace App\Models\Settings;

use App\Helpers\LocaleUrlHelper;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Locale extends Model
{
    use CrudTrait;
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'id',
        'name',
        'code',
        'prefix',
        'icon',
        'is_default',
        'is_active',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public const ALL       = 'locales_all';
    public const DEFAULT   = 'locale_default';
    /*
    |--------------------------------------------------------------------------
    | BOOT
    |--------------------------------------------------------------------------
    */

     /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        /**
         * When updating the model, if the “Default” field changes to true, 
         * the previous one changes to false.
         */
        static::saving(function ($locale) {
            if ($locale->is_default) {
                static::where('id', '!=', $locale->id ?? 0)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });

        /**
         * If the status of a default language model changes to inactive, 
         * the next one is searched for and assigned the default value.
         */
        static::saved(function ($locale) {
            self::clearAll();

            if ($locale->is_active === false && $locale->is_default === true) {
                $newDefaultLocale = static::where('id', '!=', $locale->id)
                    ->active()
                    ->orderBy('created_at')
                    ->first();

                if ($newDefaultLocale) {
                    $newDefaultLocale->is_default = true;
                    $newDefaultLocale->save();
                    return;
                }
                
                $locale->is_default = false;
                $locale->saveQuietly();
            }
        });

        /**
         * If the language model is deleted and it was the default, 
         * the next one is searched for and assigned the default value.
         */
        static::deleted(function ($locale) {
            self::clearAll();

            if ($locale->is_default === true) {
                
                $newDefaultLocale = static::active()
                    ->orderBy('created_at')
                    ->first();
                    
                if ($newDefaultLocale) {
                    $newDefaultLocale->is_default = true;
                    $newDefaultLocale->save();
                }
            }
        });
    }

   


    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */

    /**
     * @return mixed
     */
    public static function getAll()
    {
        return Cache::rememberForever(self::ALL, function () {
            return static::all();
        });
    }


    /**
     * @return mixed
     */
    public static function getActive()
    {
        return self::getAll()->where('is_active', true);
    }


    /**
     * @param string $column
     * @return array
     */
    public static function getAvailableAsArr(string $column = 'prefix'): array
    {
        return self::getAll()
            ->pluck($column)
            ->filter()
            ->unique()
            ->values()
            ->toArray();
    }


    /**
     * @return string
     */
    public static function getLocalesForRoute(): string
    {
        $locales = self::getAll()
                ->pluck('prefix')
                ->filter()
                ->unique()
                ->values()
                ->toArray();

        if (empty($locales)) {
            $locales = config('app.locales', []);
        }

        return implode('|', $locales);
    }


    /**
     * Get the default locale with caching
     *
     * @return self|null
     */
    public static function getDefault(): ?self
    {
        return Cache::rememberForever(self::DEFAULT, function () {
            return static::where('is_default', true)->first();
        });
    }


    /**
     * @return void
     */
    public static function clearAll(): void
    {
        Cache::forget(self::ALL);
        Cache::forget(self::DEFAULT);
    }


    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope a query to only include default locale.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope a query to only include active locales.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }


    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */
}