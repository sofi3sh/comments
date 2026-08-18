<?php

namespace App\Models\Articles\Translate;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use App\Models\Articles\Category;
use OwenIt\Auditing\Contracts\Auditable;

class CategoryTranslation extends Model implements Auditable
{
    use HasFactory;
    use CrudTrait;
    use \OwenIt\Auditing\Auditable;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'id',
        'category_id',
        'locale',
        'name',
        'description',
        'title',
        'keywords',
        'slug',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'category_id' => 'integer',
    ];

    protected $auditInclude = [
        'category_id',
        'locale',
        'name',
        'description',
        'slug',
    ];


    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function category()
    {
        return $this->belongsTo(Category::class);
    }


    /*
    |--------------------------------------------------------------------------
    | TRANSLATIONS
    |--------------------------------------------------------------------------
    */

    public static function getFieldsConfig(): array
    {
        return [
            'name' => [
                'label' => __('category.translations.name'),
                'type' => 'text',
            ],
            'description' => [
                'label' => __('category.translations.description'),
                'type' => 'textarea',
            ],
        ];
    }

    public static function getFieldPrefix(): string
    {
        return 'article-translation';
    }
}
