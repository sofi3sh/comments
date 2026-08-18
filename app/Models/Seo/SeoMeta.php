<?php

namespace App\Models\Seo;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use OwenIt\Auditing\Contracts\Auditable;

class SeoMeta extends Model implements TranslatableContract, Auditable
{
    use CrudTrait;
    use HasFactory;
    use Translatable;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'seo_meta';

    /** @var list<string> */
    public array $translatedAttributes = ['meta_title', 'meta_description', 'meta_keywords'];

    protected string $translationModel = \App\Models\Seo\Translate\SeoMetaTranslation::class;

    protected $fillable = [
        'id',
        'entity_type',
        'entity_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'entity_id' => 'integer',
    ];

    protected $auditInclude = [
        'entity_type',
        'entity_id',
    ];

    public function entity()
    {
        return $this->morphTo();
    }
}
