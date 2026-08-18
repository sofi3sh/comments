<?php

namespace App\Models\User;

use Illuminate\Support\Facades\Lang;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{


    public function getTranslatedNameAttribute(): string
    {
        $key = 'permission.names.' . ($this->attributes['name'] ?? '');

        return Lang::has($key) ? __($key) : ($this->attributes['name'] ?? '');
    }
}
