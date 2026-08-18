<?php

namespace App\Models\Settings;

use App\Models\Site\Site;
use App\Repositories\SettingsRepository;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

class Setting extends Model implements Auditable
{
    use CrudTrait;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'site_id',
        'key',
        'value',
        'is_active',
    ];

    protected $casts = [
        'value'       => 'array',
        'is_active'   => 'boolean',
    ];

    protected $auditInclude = [
        'site_id',
        'key',
        'value',
        'is_active',
    ];

    protected static function booted(): void
    {
        static::saved(function (Setting $setting): void {
            app(SettingsRepository::class)->forgetCache($setting->site_id);
        });

        static::deleted(function (Setting $setting): void {
            app(SettingsRepository::class)->forgetCache($setting->site_id);
        });
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
