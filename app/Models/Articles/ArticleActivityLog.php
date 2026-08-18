<?php

namespace App\Models\Articles;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleActivityLog extends Model
{
    use HasFactory;

    public const EVENT_CONTENT_UPDATED = 'article.content_updated';

    protected $fillable = [
        'article_id',
        'user_id',
        'event',
        'locale',
        'ip_address',
        'url',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'article_id' => 'integer',
        'user_id' => 'integer',
        'metadata' => 'array',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
