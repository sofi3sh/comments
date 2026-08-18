<?php

namespace App\Models\Articles;

use Illuminate\Database\Eloquent\Model;

class ArticleView extends Model
{
    protected $fillable = [
        'article_id',
        'locale',
        'views',
        'date_hour'
    ];
}
