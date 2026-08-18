<?php

namespace App\Models\Seo\Translate;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use App\Models\Seo\SeoMeta;
use OwenIt\Auditing\Contracts\Auditable;

class SeoMetaTranslation extends Model implements Auditable
{
    use CrudTrait;
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'id',
        'seo_meta_id',
        'locale',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'seo_meta_id' => 'integer',
    ];

    protected $auditInclude = [
        'seo_meta_id',
        'locale',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];


    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function seoMeta()
    {
        return $this->belongsTo(SeoMeta::class);
    }


    /**
     * Get the fields config for the seo meta translation
    * @return array
    */
    public static function getFieldsConfig(): array
    {
        return [
            'meta_title' => [
                'label' => __('seo-meta-translate.fields.meta_title'),
                'type' => 'text',
            ],
            'meta_description' => [
                'label' => __('seo-meta-translate.fields.meta_description'),
                'type' => 'text',
            ],
            'meta_keywords' => [
                'label' => __('seo-meta-translate.fields.meta_keywords'),
                'type' => 'text',
            ],            
        ];
    }
}
