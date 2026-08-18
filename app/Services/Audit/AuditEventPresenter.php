<?php

namespace App\Services\Audit;

use App\Models\Articles\Article;
use App\Models\Articles\ArticleActivityLog;
use App\Models\Articles\ArticleMeta;
use App\Models\Articles\Category;
use App\Models\Articles\Tag;
use App\Models\Articles\Translate\CategoryTranslation;
use App\Models\Articles\Translate\ArticleTranslation;
use App\Models\Articles\Translate\TagTranslation;
use App\Models\Settings\Setting;
use App\Models\Seo\SeoMeta;
use App\Models\Seo\Translate\SeoMetaTranslation;
use App\Models\User\User;
use App\Models\User\Translate\UserTranslation;
use OwenIt\Auditing\Models\Audit;

class AuditEventPresenter
{
    private const PUBLISHED_STATUS = Article::STATUS_PUBLISHED;

    private const STATUS_LABEL_KEYS = [
        Article::STATUS_DRAFT      => 'draft',
        Article::STATUS_PENDING    => 'pending',
        Article::STATUS_PUBLISHED  => 'published',
        Article::STATUS_REJECTED   => 'rejected',
        Article::STATUS_MODERATION => 'moderation',
    ];

    /** @var array<string, string> */
    private array $articleLabelCache = [];

    /** @var array<int, string> */
    private array $articleTranslationLabelCache = [];

    /**
     * @return array<int, array{field: string, label: string, old: mixed, new: mixed}>
     */
    public function changes(Audit $audit): array
    {
        $oldValues = $this->auditValues($audit->old_values);
        $newValues = $this->auditValues($audit->new_values);
        $fields = array_values(array_unique(array_merge(array_keys($oldValues), array_keys($newValues))));

        $changes = [];

        foreach ($fields as $field) {
            if ($this->shouldHideField($audit, $field)) {
                continue;
            }

            $oldValue = $oldValues[$field] ?? null;
            $newValue = $newValues[$field] ?? null;

            if ($this->bothValuesBlank($oldValue, $newValue)) {
                continue;
            }

            $changes[] = [
                'field' => $field,
                'label' => $this->fieldLabel($field),
                'old' => $this->formatValue($field, $oldValue),
                'new' => $this->formatValue($field, $newValue),
            ];
        }

        return $changes;
    }

    /**
     * @return array<int, array{field: string, label: string, old: mixed, new: mixed}>
     */
    public function activityChanges(ArticleActivityLog $activity): array
    {
        $fields = $activity->metadata['fields'] ?? ['content'];
        $fields = is_array($fields) ? $fields : ['content'];

        return array_map(function (string $field) use ($activity): array {
            return [
                'field' => $field,
                'label' => $this->activityContentFieldLabel($field, $activity),
                'old' => null,
                'new' => __('audit.values.changed'),
            ];
        }, $fields);
    }

    public function actionLabel(Audit $audit): string
    {
        if ($this->isPublished($audit)) {
            return __('audit.actions.published_article');
        }

        if ($this->isUnpublished($audit)) {
            return __('audit.actions.unpublished_article');
        }

        return match ($audit->event) {
            'created'  => $this->createdLabel($audit),
            'updated'  => $this->updatedLabel($audit),
            'deleted'  => $this->deletedLabel($audit),
            'restored' => $this->restoredLabel($audit),
            default => __('audit.actions.changed_record'),
        };
    }

    public function activityActionLabel(ArticleActivityLog $activity): string
    {
        return match ($activity->event) {
            ArticleActivityLog::EVENT_CONTENT_UPDATED => __('audit.actions.changed_article_content'),
            default => __('audit.actions.changed_article'),
        };
    }

    public function auditableLabel(Audit $audit): string
    {
        if ($audit->auditable_type === Article::class) {
            return $this->articleLabel((int) $audit->auditable_id);
        }

        if ($audit->auditable_type === ArticleTranslation::class) {
            return $this->articleTranslationLabel((int) $audit->auditable_id);
        }

        return $this->modelLabel($audit->auditable_type) . ' #' . $audit->auditable_id;
    }

    public function activityAuditableLabel(ArticleActivityLog $activity): string
    {
        return $this->articleLabel((int) $activity->article_id, $activity->locale);
    }

    private function createdLabel(Audit $audit): string
    {
        return match ($audit->auditable_type) {
            Article::class             => __('audit.actions.created.article'),
            ArticleTranslation::class  => __('audit.actions.created.article_translation'),
            Category::class            => __('audit.actions.created.category'),
            CategoryTranslation::class => __('audit.actions.created.category_translation'),
            Tag::class                 => __('audit.actions.created.tag'),
            TagTranslation::class      => __('audit.actions.created.tag_translation'),
            SeoMeta::class, SeoMetaTranslation::class => __('audit.actions.created.seo'),
            ArticleMeta::class         => __('audit.actions.created.article_meta'),
            Setting::class             => __('audit.actions.created.setting'),
            User::class                => __('audit.actions.created.user'),
            UserTranslation::class     => __('audit.actions.created.user_translation'),
            default                    => __('audit.actions.created.record'),
        };
    }

    private function updatedLabel(Audit $audit): string
    {
        return match ($audit->auditable_type) {
            Article::class             => __('audit.actions.updated.article'),
            ArticleTranslation::class  => __('audit.actions.updated.article_translation'),
            Category::class            => __('audit.actions.updated.category'),
            CategoryTranslation::class => __('audit.actions.updated.category_translation'),
            Tag::class                 => __('audit.actions.updated.tag'),
            TagTranslation::class      => __('audit.actions.updated.tag_translation'),
            SeoMeta::class, SeoMetaTranslation::class => __('audit.actions.updated.seo'),
            ArticleMeta::class         => __('audit.actions.updated.article_meta'),
            Setting::class             => __('audit.actions.updated.setting'),
            User::class                => __('audit.actions.updated.user'),
            UserTranslation::class     => __('audit.actions.updated.user_translation'),
            default                    => __('audit.actions.updated.record'),
        };
    }

    private function deletedLabel(Audit $audit): string
    {
        return match ($audit->auditable_type) {
            Article::class             => __('audit.actions.deleted.article'),
            ArticleTranslation::class  => __('audit.actions.deleted.article_translation'),
            Category::class            => __('audit.actions.deleted.category'),
            CategoryTranslation::class => __('audit.actions.deleted.category_translation'),
            Tag::class                 => __('audit.actions.deleted.tag'),
            TagTranslation::class      => __('audit.actions.deleted.tag_translation'),
            ArticleMeta::class         => __('audit.actions.deleted.article_meta'),
            Setting::class             => __('audit.actions.deleted.setting'),
            User::class                => __('audit.actions.deleted.user'),
            default                    => __('audit.actions.deleted.record'),
        };
    }

    private function restoredLabel(Audit $audit): string
    {
        return match ($audit->auditable_type) {
            Article::class => __('audit.actions.restored.article'),
            User::class    => __('audit.actions.restored.user'),
            default        => __('audit.actions.restored.record'),
        };
    }

    private function isPublished(Audit $audit): bool
    {
        if ($audit->event !== 'updated') {
            return false;
        }

        $oldStatus = $this->auditValues($audit->old_values)['status'] ?? null;
        $newStatus = $this->auditValues($audit->new_values)['status'] ?? null;

        return $newStatus === self::PUBLISHED_STATUS && $oldStatus !== self::PUBLISHED_STATUS;
    }

    private function isUnpublished(Audit $audit): bool
    {
        if ($audit->event !== 'updated') {
            return false;
        }

        $oldStatus = $this->auditValues($audit->old_values)['status'] ?? null;
        $newStatus = $this->auditValues($audit->new_values)['status'] ?? null;

        return $oldStatus === self::PUBLISHED_STATUS && $newStatus !== self::PUBLISHED_STATUS;
    }

    /**
     * @return array<string, mixed>
     */
    private function auditValues(mixed $values): array
    {
        return is_array($values) ? $values : [];
    }

    private function fieldLabel(string $field): string
    {
        $label = __("audit.fields.{$field}");

        return $label !== "audit.fields.{$field}" ? $label : $field;
    }

    private function shouldHideField(Audit $audit, string $field): bool
    {
        if ($field === 'title_with_markers') {
            return true;
        }

        return match ($audit->auditable_type) {
            SeoMeta::class => in_array($field, ['entity_type', 'entity_id'], true),
            SeoMetaTranslation::class => $field === 'seo_meta_id',
            default => false,
        };
    }

    private function bothValuesBlank(mixed $oldValue, mixed $newValue): bool
    {
        return $this->isBlankValue($oldValue) && $this->isBlankValue($newValue);
    }

    private function isBlankValue(mixed $value): bool
    {
        return $value === null || (is_string($value) && trim($value) === '');
    }

    private function activityContentFieldLabel(string $field, ArticleActivityLog $activity): string
    {
        $label = __("audit.fields.{$field}");
        $label = $label !== "audit.fields.{$field}" ? $label : $field;
        $locale = $this->localeLabel($activity->locale);

        return $locale !== '' ? "{$label} ({$locale})" : $label;
    }

    private function localeLabel(?string $locale): string
    {
        if (!$locale) {
            return '';
        }

        return match ($locale) {
            'uk', 'ua' => __('audit.locales.uk'),
            'ru' => __('audit.locales.ru'),
            'en' => __('audit.locales.en'),
            default => $locale,
        };
    }

    private function formatValue(string $field, mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if ($field === 'status' && is_string($value)) {
            $key = self::STATUS_LABEL_KEYS[$value] ?? null;

            return $key ? __("audit.statuses.{$key}") : $value;
        }

        if (is_bool($value)) {
            return $value ? __('audit.values.yes') : __('audit.values.no');
        }

        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        if (!is_string($value)) {
            return $value;
        }

        return $this->limit($value);
    }

    private function limit(string $value, int $limit = 300): string
    {
        if (mb_strlen($value) <= $limit) {
            return $value;
        }

        return mb_substr($value, 0, $limit) . '...';
    }

    private function modelLabel(?string $modelClass): string
    {
        return match ($modelClass) {
            Article::class             => 'Article',
            ArticleTranslation::class  => 'ArticleTranslation',
            Category::class            => 'Category',
            CategoryTranslation::class => 'CategoryTranslation',
            Tag::class                 => 'Tag',
            TagTranslation::class      => 'TagTranslation',
            SeoMeta::class             => 'SeoMeta',
            SeoMetaTranslation::class  => 'SeoMetaTranslation',
            ArticleMeta::class         => 'ArticleMeta',
            Setting::class             => 'Setting',
            User::class                => 'User',
            UserTranslation::class     => 'UserTranslation',
            default => $modelClass ? class_basename($modelClass) : 'Record',
        };
    }

    private function articleTranslationLabel(int $translationId): string
    {
        if (isset($this->articleTranslationLabelCache[$translationId])) {
            return $this->articleTranslationLabelCache[$translationId];
        }

        $translation = ArticleTranslation::query()
            ->with([
                'article' => fn ($query) => $query
                    ->withoutGlobalScopes()
                    ->withTrashed()
                    ->with('type.translations'),
            ])
            ->find($translationId);

        if (!$translation) {
            return $this->articleTranslationLabelCache[$translationId] = 'ArticleTranslation #' . $translationId;
        }

        return $this->articleTranslationLabelCache[$translationId] = $this->articleLabel(
            (int) $translation->article_id,
            $translation->locale,
            $translation->article
        );
    }

    private function articleLabel(int $articleId, ?string $locale = null, ?Article $article = null): string
    {
        $cacheKey = $articleId . ':' . ($locale ?? '');

        if (isset($this->articleLabelCache[$cacheKey])) {
            return $this->articleLabelCache[$cacheKey];
        }

        $article ??= Article::query()
            ->withoutGlobalScopes()
            ->withTrashed()
            ->with('type.translations')
            ->find($articleId);

        if (!$article) {
            return $this->articleLabelCache[$cacheKey] = 'Article #' . $articleId;
        }

        $label = $this->articleTypeLabel($article) . ' #' . $articleId;

        if ($locale) {
            $label .= ' (' . $this->localeLabel($locale) . ')';
        }

        return $this->articleLabelCache[$cacheKey] = $label;
    }

    private function articleTypeLabel(Article $article): string
    {
        $type = $article->type;

        if (!$type) {
            return $this->modelLabel(Article::class);
        }

        return $type->translate(app()->getLocale())?->name
            ?? $type->translate(config('app.fallback_locale'))?->name
            ?? $type->code
            ?? $this->modelLabel(Article::class);
    }
}
