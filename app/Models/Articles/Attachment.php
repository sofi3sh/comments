<?php

namespace App\Models\Articles;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    use CrudTrait;
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'parent_id',
        'size_key',
        'filename',
        'path',
        'mime_type',
        'size',
        'alt',
        'title',
        'caption',
        'user_id',
        'is_public',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'parent_id' => 'integer',
        'size' => 'integer',
        'user_id' => 'integer',
        'is_public' => 'boolean',
    ];

    protected $appends = [
        'url',
        'thumbnail_url',
        'formatted_size',
    ];


    public const COVER_TYPE = 'cover';

    public const THUMBNAIL_TYPE = 'thumbnail';

    /*
    |--------------------------------------------------------------------------
    | BOOT
    |--------------------------------------------------------------------------
    */

    protected static function boot(): void
    {
        parent::boot();

        static::deleting(function (self $attachment): void {
            if ($attachment->parent_id === null) {
                $attachment->sizes()->get()->each->delete();
            }

            $attachment->deleteFiles();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function articles()
    {
        return $this->morphedByMany(
            Article::class,
            'owner',
            'article_attachments'
        );
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User\User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function sizes(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'attachment_tag', 'attachment_id', 'tag_id');
    }

    public function scopeParents(Builder $query): Builder
    {
        return $query->whereNull($this->qualifyColumn('parent_id'));
    }
    
    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    /**
     * Get the full URL to the attachment
     *
     * @return string
     */
    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    /**
     * Get the thumbnail URL (for images)
     *
     * @return string|null
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        if (!$this->isImage()) {
            return null;
        }

        return $this->getSizeUrl('card_sm');
    }

    /**
     * Get metadata for a generated image size.
     *
     * @param string $size
     * @return array|null
     */
    public function getSizeMetadata(string $size): ?array
    {
        if ($this->isCoverSize($size)) {
            return [
                'path'   => $this->path,
                'width'  => $this->metadata['width'] ?? null,
                'height' => $this->metadata['height'] ?? null,
            ];
        }

        $attachment = $this->findSizeAttachment($size);
        if ($attachment) {
            return [
                'path'   => $attachment->path,
                'width'  => $attachment->metadata['width'] ?? null,
                'height' => $attachment->metadata['height'] ?? null,
            ];
        }

        $metadata = $this->metadata['sizes'][$size] ?? null;

        return is_array($metadata) ? $metadata : null;
    }

    /**
     * Get storage path for a generated image size.
     *
     * @param string $size
     * @return string|null
     */
    public function getSizePath(string $size): ?string
    {
        $path = $this->getSizeMetadata($size)['path'] ?? null;

        // Перевірка exists() для S3 виконує мережевий HEAD-запит на кожну картинку.
        // Наявність шляху в БД є достатньою під час рендерингу; цілісність файлів
        // перевіряється в процесі завантаження або фоновим завданням.
        return $path ?: null;
    }

    /**
     * Get public URL for a generated image size with fallback to the original URL.
     *
     * @param string $size
     * @return string
     */
    public function getSizeUrl(string $size): string
    {
        $path = $this->getSizePath($size);

        if (!$path) {
            return $this->url;
        }

        return Storage::disk('public')->url($path);
    }

    /**
     * Build an image srcset string from generated sizes.
     *
     * @param array $sizes
     * @return string
     */
    public function getSrcset(array $sizes = ['card_sm', 'card_lg', 'cover']): string
    {
        $srcset = [];

        foreach ($sizes as $size) {
            $metadata = $this->getSizeMetadata($size);
            $path = $this->getSizePath($size);
            $width = $metadata['width'] ?? null;

            if (!$path || !$width) {
                continue;
            }

            $srcset[] = Storage::disk('public')->url($path) . ' ' . (int) $width . 'w';
        }

        return implode(', ', $srcset);
    }

    /**
     * Get file extension
     *
     * @return string
     */
    public function getExtensionAttribute(): string
    {
        return pathinfo($this->filename, PATHINFO_EXTENSION);
    }

    /**
     * Get file name without extension
     *
     * @return string
     */
    public function getNameAttribute(): string
    {
        return pathinfo($this->filename, PATHINFO_FILENAME);
    }

    /**
     * Get formatted file size
     *
     * @return string
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Check if attachment is an image
     *
     * @return bool
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Get image width from metadata
     *
     * @return int|null
     */
    public function getWidth(): ?int
    {
        return $this->metadata['width'] ?? null;
    }

    /**
     * Get image height from metadata
     *
     * @return int|null
     */
    public function getHeight(): ?int
    {
        return $this->metadata['height'] ?? null;
    }


    /**
     * @return void
     */
    public function deleteFiles(): void
    {
        $paths = collect($this->metadata['sizes'] ?? [])
            ->pluck('path')
            ->push($this->path)
            ->filter()
            ->unique()
            ->values()
            ->all();

        Storage::disk('public')->delete($paths);
    }

    public function isOriginal(): bool
    {
        return $this->parent_id === null;
    }

    private function isCoverSize(string $size): bool
    {
        return $size === 'cover' && $this->isOriginal() && $this->size_key === 'cover';
    }

    private function findSizeAttachment(string $size): ?self
    {
        if ($this->relationLoaded('sizes')) {
            return $this->sizes->firstWhere('size_key', $size);
        }

        return $this->sizes()->where('size_key', $size)->first();
    }
}
