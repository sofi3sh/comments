<?php

namespace App\Models\Articles\Translate;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use OwenIt\Auditing\Contracts\Auditable;

class TagTranslation extends Model implements Auditable
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
        'tag_id',
        'slug',
        'locale',
        'title',
        'main_title',
        'heading',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'tag_id' => 'integer',
    ];

    protected $auditInclude = [
        'tag_id',
        'slug',
        'locale',
        'title',
        'main_title',
        'heading',
    ];


    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function tag()
    {
        return $this->belongsTo(\App\Models\Articles\Tag::class);
    }


    /*
    |--------------------------------------------------------------------------
    | TRANSLATIONS
    |--------------------------------------------------------------------------
    */
    public static function getFieldsConfig(): array
    {
        return [
            'title' => [
                'label' => __('tag.fields.title'),
                'type' => 'text',
            ],
        ];
    }

    public static function getFieldPrefix(): string
    {
        return 'tag-translation';
    }
}
