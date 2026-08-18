<?php

namespace App\Models\Traits;

use App\Models\Settings\Locale;
use App\Services\Article\ArticleUrlBuilder;
use Illuminate\Support\Facades\Storage;

trait ArticleCrudPresenter
{
    /**
     *  For Article CRUD status
     *
     * @return string
     */
    public function getStatusIcon(): string
    {
        $statusCode = $this->status;
        $label = $this->status_label ?? $statusCode;

        return match($statusCode) {
            self::STATUS_DRAFT      => '<i class="la la-pencil-alt text-secondary" title="'.$label.'"></i>',
            self::STATUS_PENDING    => '<i class="la la-clock text-warning" title="'.$label.'"></i>',
            self::STATUS_PUBLISHED  => '<i class="la la-check-circle text-success" title="'.$label.'"></i>',
            self::STATUS_REJECTED   => '<i class="la la-times-circle text-danger" title="'.$label.'"></i>',
            self::STATUS_MODERATION => '<i class="la la-search text-info" title="'.$label.'"></i>',
            default                 => '<i class="la la-question-circle text-muted" title="'.$label.'"></i>',
        };
    }

    /**
     * @return string
     */
    public function getCategoryFull(): string
    {
        if (!$this->category) {
            return '-';
        }

        $category = $this->category;
        $parentName = $category->parent ? $category->parent->display_name : $category->display_name;
        $subName = $category->parent ? $category->display_name : '-';

        return '<p class="text-muted small">' . $parentName . '<br>'.$subName.'</p>';
    }

    /**
     * @return string
     */
    public function getSiteName(): string
    {
        if ($this->sites) {
            return $this->sites->pluck('name')->implode(', ');
        }
        return '-';
    }

    /**
     * @return string
     */
    public function getAuthors(): string
    {
        if ($this->authors && $this->authors->count()) {
            return $this->authors->pluck('name')->implode('<br>');
        }
        return '-';
    }

    public function getEditors()
    {
        if ($this->editors) {
            return $this->editors->pluck('name')->implode('<br>');
        }
        return '-';
    }


    /**
     * @return string
     */
    public function getTitles()
    {
        $titles = $this->translations->pluck('title', 'locale')->toArray();

        if (empty($titles)) {
            return '-';
        }

        $locales = Locale::query()
//            ->active()   //@todo  may be should use
            ->whereIn('code', array_keys($titles))
            ->get()
            ->keyBy('code');

        $result = [];
        $urlBuilder = app(ArticleUrlBuilder::class);
        $isPublished = $this->status === self::STATUS_PUBLISHED;

        foreach ($titles as $localeCode => $title) {
            if (!$title) continue;

            $locale = $locales[$localeCode] ?? null;
            $path = $isPublished ? $this->getTitleAdminUrlPath($urlBuilder, $localeCode) : null;

            $result[] = [
                'code' => $localeCode,
                'name' => $locale?->name ?? strtoupper($localeCode),
                'icon_url' => $locale?->icon
                    ? Storage::disk('public')->url($locale->icon)
                    : null,
                'title' => $title,
                'url' => $path ? rtrim(config('app.url'), '/') . '/' . ltrim($path, '/') : null,
            ];
        }

        if (empty($result)) {
            return '-';
        }

        return view('admin.blocks.titles-with-locales', [
            'locales' => $result
        ])->render();
    }

    private function getTitleAdminUrlPath(ArticleUrlBuilder $urlBuilder, string $localeCode): ?string
    {
        $slug = $urlBuilder->slugFor($this, $localeCode);
        $type = $urlBuilder->typeCodeFor($this);
        $id = $this->id;

        if (empty($localeCode) || empty($type) || empty($slug) || empty($id)) {
            return null;
        }

        $segments = [$localeCode, $type];
        $categorySlug = $urlBuilder->categorySlugFor($this);

        if (!empty($categorySlug)) {
            $segments[] = $categorySlug;
        }

        $segments[] = $slug . '-' . $id . '.html';

        return implode('/', $segments);
    }
}
