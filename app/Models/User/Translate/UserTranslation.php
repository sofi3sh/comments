<?php

namespace App\Models\User\Translate;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class UserTranslation extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'user_id',
        'locale',
        'bio',
        'name',
        'surname',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
        ];
    }

    protected $auditInclude = [
        'user_id',
        'locale',
        'bio',
        'name',
        'surname',
        'position',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
