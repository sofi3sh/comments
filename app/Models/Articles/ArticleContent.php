<?php

namespace App\Models\Articles;

use App\Models\Articles\Translate\ArticleTranslation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleContent extends Model
{
    public const PROVIDER_CONTENT_WATCH = 'content_watch';

    public const STATUS_PENDING = 'pending';

    public const STATUS_CHECKING = 'checking';

    public const STATUS_CHECKED = 'checked';

    public const STATUS_FAILED = 'failed';

    protected $table = 'article_content';

    protected $fillable = [
        'article_id',
        'article_translation_id',
        'locale',
        'provider',
        'status',
        'uniqueness_percent',
        'content_hash',
        'response',
        'error_message',
        'checked_at',
    ];

    protected $casts = [
        'article_id'   => 'integer',
        'article_translation_id' => 'integer',
        'uniqueness_percent'     => 'decimal:2',
        'response'     => 'array',
        'checked_at'   => 'datetime',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function translation(): BelongsTo
    {
        return $this->belongsTo(ArticleTranslation::class, 'article_translation_id');
    }

    public function markPending(string $contentHash): void
    {
        $this->forceFill([
            'status' => self::STATUS_PENDING,
            'uniqueness_percent' => null,
            'content_hash' => $contentHash,
            'response' => null,
            'error_message' => null,
            'checked_at' => null,
        ])->save();
    }
}
