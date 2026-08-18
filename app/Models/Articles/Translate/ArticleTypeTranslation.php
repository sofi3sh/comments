<?php

namespace App\Models\Articles\Translate;

use App\Models\Articles\ArticleType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

class ArticleTypeTranslation extends Model
{
    use CrudTrait;
    use HasFactory;

    protected $fillable = [
        'article_type_id',
        'locale',
        'name',
    ];

    protected $casts = [
        'article_type_id' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saved(function () {
            ArticleType::clearCache();
        });

        static::deleted(function () {
            ArticleType::clearCache();
        });
    }

    public function articleType()
    {
        return $this->belongsTo(\App\Models\Articles\ArticleType::class);
    }

    public static function getFieldsConfig(): array
    {
        return [
            'name' => [
                'label' => __('article-type-translate.fields.name'),
                'type' => 'text',
            ],
        ];
    }

    public static function getFieldPrefix(): string
    {
        return 'article-type-translation';
    }
}
