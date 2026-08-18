<?php

namespace App\Services\Article\Audit;

use App\Models\Articles\Article;
use App\Models\Articles\ArticleActivityLog;
use App\Models\Articles\ArticleMeta;
use App\Models\Articles\Translate\ArticleTranslation;
use App\Models\Seo\SeoMeta;
use App\Models\Seo\Translate\SeoMetaTranslation;
use App\Models\User\User;
use App\Services\Audit\AuditEventPresenter;
use Illuminate\Support\Collection;
use OwenIt\Auditing\Models\Audit;

class ArticleAuditHistoryService
{
    public function __construct(
        private readonly AuditEventPresenter $presenter,
    ) {}

    /**
     * Builds the rows shown in the article edit form history tab.
     * Includes package audit records and custom content activity records for the same article.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function rows(int $articleId, int $limit = 20): Collection
    {
        $article = Article::query()
            // History must stay available across site scopes and soft-deleted articles.
            ->withoutGlobalScopes()
            ->with(['translations', 'seoMeta.translations', 'meta'])
            ->find($articleId);

        if (!$article) {
            return collect();
        }

        return $this->auditRows($article)
            ->merge($this->activityRows($article->id))
            ->sortByDesc('created_at')
            ->take($limit)
            ->values();
    }

    /**
     * Collects package audit records for the article and directly related editable records.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function auditRows(Article $article): Collection
    {
        $articleTranslationIds = $article->translations->pluck('id')->filter()->values();
        $seoMetaId = $article->seoMeta?->id;
        $seoMetaTranslationIds = $seoMetaId
            ? SeoMetaTranslation::query()->where('seo_meta_id', $seoMetaId)->pluck('id')
            : collect();
        $articleMetaIds = $article->meta->pluck('id')->filter()->values();

        return Audit::query()
            ->with('user')
            ->where(function ($query) use ($article, $articleTranslationIds, $seoMetaId, $seoMetaTranslationIds, $articleMetaIds) {
                $query->where(function ($articleQuery) use ($article) {
                    $articleQuery
                        ->where('auditable_type', Article::class)
                        ->where('auditable_id', $article->id);
                });

                if ($articleTranslationIds->isNotEmpty()) {
                    $query->orWhere(function ($translationQuery) use ($articleTranslationIds) {
                        $translationQuery
                            ->where('auditable_type', ArticleTranslation::class)
                            ->whereIn('auditable_id', $articleTranslationIds);
                    });
                }

                if ($seoMetaId) {
                    $query->orWhere(function ($seoQuery) use ($seoMetaId) {
                        $seoQuery
                            ->where('auditable_type', SeoMeta::class)
                            ->where('auditable_id', $seoMetaId);
                    });
                }

                if ($seoMetaTranslationIds->isNotEmpty()) {
                    $query->orWhere(function ($seoTranslationQuery) use ($seoMetaTranslationIds) {
                        $seoTranslationQuery
                            ->where('auditable_type', SeoMetaTranslation::class)
                            ->whereIn('auditable_id', $seoMetaTranslationIds);
                    });
                }

                if ($articleMetaIds->isNotEmpty()) {
                    $query->orWhere(function ($metaQuery) use ($articleMetaIds) {
                        $metaQuery
                            ->where('auditable_type', ArticleMeta::class)
                            ->whereIn('auditable_id', $articleMetaIds);
                    });
                }
            })
            ->latest()
            ->limit(50)
            ->get()
            ->toBase()
            ->map(function (Audit $audit): array {
                $changes = $this->presenter->changes($audit);

                return [
                    'created_at' => $audit->created_at,
                    'user' => $this->userLabel($audit->user),
                    'action' => $this->presenter->actionLabel($audit),
                    'changes' => $changes,
                    'ip_address' => $audit->ip_address,
                    'url' => $audit->url,
                    'show_url' => route('audit.show', $audit->id),
                ];
            })
            ->filter(fn (array $row): bool => !empty($row['changes']))
            ->values();
    }

    /**
     * Collects custom content-change activity rows that are stored outside the package audit table.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function activityRows(int $articleId): Collection
    {
        return ArticleActivityLog::query()
            ->with('user')
            ->where('article_id', $articleId)
            ->latest()
            ->limit(50)
            ->get()
            ->toBase()
            ->map(function (ArticleActivityLog $activity): array {
                return [
                    'created_at' => $activity->created_at,
                    'user' => $this->userLabel($activity->user),
                    'action' => $this->presenter->activityActionLabel($activity),
                    'changes' => $this->presenter->activityChanges($activity),
                    'ip_address' => $activity->ip_address,
                    'url' => $activity->url,
                    'show_url' => null,
                ];
            });
    }

    /**
     * Formats the actor shown in history rows, falling back to the localized system label.
     */
    private function userLabel(mixed $user): string
    {
        if (!$user instanceof User) {
            return __('audit.system_user');
        }

        $name = trim($user->fullname);

        return $name !== '' ? $name : $user->email;
    }
}
