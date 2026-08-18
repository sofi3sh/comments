<?php

namespace App\Models\Site;

use App\Services\StaticCache\SeoStaticInvalidator;
use App\Support\OctaneReloader;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Site extends Model
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
        'slug',
        'domain',
        'color_primary',
        'color_secondary',
        'logo',
        'active',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | BOOT
    |--------------------------------------------------------------------------
    */

    protected static function boot(): void
    {
        parent::boot();

        static::deleting(function ($site) {
            if ($site->logo) {
                Storage::disk('public')->delete($site->logo);
            }
        });

        // The frontend route group bakes in Site::getCachedDomains() at worker
        // boot, so the workers have to restart whenever the domain *set*
        // changes — but not for a colour/logo/slug edit. (Slug changes are safe:
        // ResolveSite reads getCachedSlugs() at request time.)
        //
        // These hang off created/updated rather than saved because neither flag
        // works on its own inside saved(): getChanges() is empty on insert, so
        // wasChanged('domain') cannot see a newly added domain, while
        // wasRecentlyCreated stays true for every later save of the same
        // in-memory instance. created/updated are unambiguous.
        //
        // Each clears the domain cache itself: both fire *before* saved(), so
        // relying on the reset there would signal the reload while the stale
        // list was still cached, and rebooting workers could read it back.
        static::created(function (self $site) {
            self::resetAllCached();

            app(OctaneReloader::class)->reload('site created');
        });

        static::updated(function (self $site) {
            if (! $site->wasChanged('domain')) {
                return;
            }

            self::resetAllCached();

            app(OctaneReloader::class)->reload('site domain changed');
        });

        static::saved(function (self $site) {
            self::resetAllCached();

            $seoInvalidator = app(SeoStaticInvalidator::class);

            // Domain changed: the old host's static tree is orphaned.
            if ($site->wasChanged('domain') && $site->getOriginal('domain')) {
                $seoInvalidator->invalidateSite($site->getOriginal('domain'));
            }

            $seoInvalidator->invalidateRobots($site->domain);
        });

        static::deleted(function (self $site) {
            self::resetAllCached();

            app(OctaneReloader::class)->reload('site deleted');

            app(SeoStaticInvalidator::class)->invalidateSite($site->domain);
        });
    }


    public static function getCachedSlugs(): array
    {
        return Cache::rememberForever('sites.slugs', function () {
            return self::pluck('slug')->toArray();
        });
    }

    public static function getCachedDomains(): array
    {
        return Cache::rememberForever('sites.domains', function () {
            return self::pluck('domain')->toArray();
        });
    }

    public static function resetAllCached(): void
    {
        Cache::forget('sites.slugs');
        Cache::forget('sites.domains');
    }


    // /*
    // |--------------------------------------------------------------------------
    // | MUTATORS
    // |--------------------------------------------------------------------------
    // */

    // public function setLogoAttribute($value)
    // {
    //     $attribute_name = "logo";
    //     $disk = "public";
    //     $destination_path = "uploads/sites";

    //     if (request()->hasFile($attribute_name)) {
    //         $file = request()->file($attribute_name);
    //         $filename = Str::random(20) . '.' . $file->getClientOriginalExtension();
    //         $path = $file->storeAs($destination_path, $filename, $disk);

    //         $this->attributes[$attribute_name] = $path;
    //     }
    // }

}
