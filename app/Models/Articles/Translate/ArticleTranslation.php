<?php

namespace App\Models\Articles\Translate;

use App\Models\Articles\Marker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Laravel\Scout\Searchable;
use OwenIt\Auditing\Contracts\Auditable;

class ArticleTranslation extends Model implements Auditable
{
    use CrudTrait;
    use HasFactory;
    use Searchable;
    use SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'id',
        'article_id',
        'locale',
        'title',
        'title_with_markers',
        'excerpt',
        'content',
        'content_html',
        'slug',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'article_id' => 'integer',
    ];

    protected $auditInclude = [
        'article_id',
        'locale',
        'title',
        'excerpt',
        'slug',
    ];

    public const TRANSLATABLE = [
        'title',
//        'title_with_markers',
        'excerpt',
        'content',
//        'content_html',
        'slug',
    ];

    protected static function booted(): void
    {
        static::saving(function (ArticleTranslation $translation): void {
            if (!empty($translation->slug)) {
                return;
            }

            $translation->generateSlugFromTitle();
        });
    }

    public function toSearchableArray(): array
    {
        $hasTitle = filled($this->title);
        $text = $this->extractSearchText();

        if (! $hasTitle && $text === null) {
            return [];
        }

        return [
            'id'           => $this->id,
            'article_id'   => $this->article_id,
            'type_id'      => $this->article->type_id,
            'locale'       => $this->locale,
            'title'        => $this->title,
            'text'         => $text ?? '',
            'created_at'   => $this->article->created_at->timestamp,
            'published_at' => $this->article->published_at?->timestamp,
        ];
    }

    public function shouldBeSearchable(): bool
    {
        return $this->article !== null
            && (filled($this->title) || $this->extractSearchText() !== null);
    }

    protected function makeAllSearchableUsing(Builder $query): Builder
    {
        return $query->with('article');
    }

    public function extractSearchText(): ?string
    {
        // 1. ПРИОРИТЕТ — JSON (content)
        if (!empty($this->content)) {
            $text = $this->extractFromJson();

            if ($text) {
                return $this->normalizeText($text);
            }
        }

        // 2. fallback — HTML
        if (!empty($this->content_html)) {
            $text = $this->extractFromHtml();

            if ($text) {
                return $this->normalizeText($text);
            }
        }

        // 3. ничего нет
        return null;
    }


    protected function extractFromJson(): ?string
    {
        $data = json_decode($this->content, true);

        if (empty($data['blocks'])) {
            return null;
        }

        foreach ($data['blocks'] as $block) {

            // пропускаем всё кроме paragraph
            if (($block['type'] ?? null) !== 'paragraph') {
                continue;
            }

            $text = trim(strip_tags($block['data']['text'] ?? ''));

            if ($text !== '') {
                return $text;
            }
        }

        return null;
    }


    protected function extractFromHtml(): ?string
    {
        libxml_use_internal_errors(true);

        $dom = new \DOMDocument();

        if (!$dom->loadHTML('<?xml encoding="utf-8" ?>' . $this->content_html)) {
            return null;
        }

        $xpath = new \DOMXPath($dom);

        $paragraphs = $xpath->query('//p');

        foreach ($paragraphs as $p) {
            $text = trim($p->textContent);

            if ($text !== '') {
                return $text;
            }
        }

        return null;
    }


    protected function normalizeText(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $text = preg_replace('/\s+/u', ' ', $text);

        return mb_substr(trim($text), 0, 1000);
    }


    private function getMarkerNameForCurrentLocale(Marker $marker): ?string
    {
        $markerName = $marker->translate($this->locale)?->name ?? '';

        return $markerName !== '' ? $markerName : null;
    }


    public function generateSlugFromTitle(): void
    {
        $title = $this->title ?? '';
        $title = is_string($title) ? trim($title) : '';

        if ($title === '') {
            return;
        }

        $slug = Str::slug($title);
        if ($slug === '') {
            $slug = 'article-' . uniqid();
        }

        $this->slug = $slug;
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */


    /**
     * Title with markers is stored in DB (generated on save). Always use stored value.
     *
     * @return string
     */
    public function getTitleWithMarkerAttribute(): string
    {
        if ($this->title_with_markers !== null && $this->title_with_markers !== '') {
            return (string) $this->title_with_markers;
        }

        return (string) ($this->title ?? '');
    }

    /**
     * Get marker HTML for display in content
     *
     * @return string|null
     */
    public function getMarkerHtmlAttribute(): ?string
    {
        if (!$this->article) {
            return null;
        }
        
        $marker = $this->article->markers()->where('is_active', true)->first();
        
        if (!$marker) {
            return null;
        }
        
        $markerName = $this->getMarkerNameForCurrentLocale($marker);

        if ($markerName === null) {
            return null;
        }
        
        $iconHtml = '';
        if ($marker->icon) {
            if (str_starts_with($marker->icon, '<svg') || str_starts_with($marker->icon, '<img')) {
                $iconHtml = $marker->icon;
            } else {
                $iconHtml = '<i class="' . htmlspecialchars($marker->icon) . '"></i>';
            }
        }
        
        $markerHtml = '<span class="article-marker">';
        if ($iconHtml) {
            $markerHtml .= $iconHtml . ' ';
        }
        $markerHtml .= htmlspecialchars($markerName);
        $markerHtml .= '</span>';
        
        return $markerHtml;
    }

    /**
     * Get content with marker inserted before first paragraph
     *
     * @return string
     */
    public function getContentWithMarkerAttribute(): string
    {
        $content = $this->content;
        
        if (!$content) {
            return $content ?? '';
        }
        
        $markerHtml = $this->marker_html;
        if (!$markerHtml) {
            return $content;
        }
        
        // Parse EditorJS content
        try {
            $contentData = json_decode($content, true);
            if (!$contentData || !isset($contentData['blocks']) || !is_array($contentData['blocks'])) {
                return $content;
            }
            
            // Find first paragraph block
            $firstParagraphIndex = null;
            foreach ($contentData['blocks'] as $index => $block) {
                if (isset($block['type']) && $block['type'] === 'paragraph') {
                    $firstParagraphIndex = $index;
                    break;
                }
            }
            
            // If found, insert marker block before it
            if ($firstParagraphIndex !== null) {
                // Create marker block (as paragraph with marker HTML)
                $markerBlock = [
                    'type' => 'paragraph',
                    'data' => [
                        'text' => $markerHtml
                    ]
                ];
                
                array_splice($contentData['blocks'], $firstParagraphIndex, 0, [$markerBlock]);
                return json_encode($contentData);
            }
        } catch (\Exception $e) {
            // If JSON parsing fails, return original content
            return $content;
        }
        
        return $content;
    }

    public function article()
    {
        return $this->belongsTo(\App\Models\Articles\Article::class);
    }

    public function contentCheck()
    {
        return $this->hasOne(\App\Models\Articles\ArticleContent::class, 'article_translation_id');
    }


    /**
     * Формує видимі поля перекладу з урахуванням ефективних налаштувань типу статті.
     *
     * @return array|array[]
     */
    public static function getFieldsConfig(?Collection $fieldConfigs = null): array
    {
        $defaultConfig = [
            'title' => [
                'label' => __('article-translate.fields.title'),
                'type' => 'text',
            ],
            'excerpt' => [
                'label' => __('article-translate.fields.excerpt'),
                'type' => 'text',
            ],
            'content' => [
                'label' => __('article-translate.fields.content'),
                'type' => 'editorjs',
                'options' => [
                    'height' => 500,
                ],
            ],
        ];

        $fieldConfigs ??= collect();
        $result = [];

        foreach ($defaultConfig as $fieldName => $fieldConfig) {
            $config = $fieldConfigs->get($fieldName);

            if ($config && ! $config->is_visible) {
                continue;
            }

            if ($config && $config->max_length !== null) {
                $fieldConfig['max_length'] = (int) $config->max_length;
            }

            if ($config && $config->min_length !== null) {
                $fieldConfig['min_length'] = (int) $config->min_length;
            }

            $result[$fieldName] = $fieldConfig;
        }

        $defaultOrder = array_flip(array_keys($defaultConfig));

        return collect($result)
            ->sortBy(fn (array $_, string $field): array => [
                $fieldConfigs->get($field)?->position ?? PHP_INT_MAX,
                $defaultOrder[$field],
            ])
            ->all();
    }

    public static function getFieldPrefix(): string
    {
        return 'translations';
    }
}
