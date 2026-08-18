<?php

namespace App\Http\Controllers\Admin\Audit;

use App\Http\Controllers\Controller;
use App\Models\Articles\Article;
use App\Models\Articles\ArticleActivityLog;
use App\Models\Articles\ArticleMeta;
use App\Models\Articles\Category;
use App\Models\Articles\Tag;
use App\Models\Articles\Translate\ArticleTranslation;
use App\Models\Articles\Translate\CategoryTranslation;
use App\Models\Articles\Translate\TagTranslation;
use App\Models\Settings\Setting;
use App\Models\Seo\SeoMeta;
use App\Models\Seo\Translate\SeoMetaTranslation;
use App\Models\User\User;
use App\Models\User\Translate\UserTranslation;
use App\Services\Audit\AuditEventPresenter;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use OwenIt\Auditing\Models\Audit;

class AuditController extends Controller
{
    private const PER_PAGE = 50;

    private const AUDIT_FETCH_LIMIT = 1000;

    private const ENTITY_CLASSES = [
        Article::class => 'Article',
        ArticleTranslation::class => 'ArticleTranslation',
        Category::class => 'Category',
        CategoryTranslation::class => 'CategoryTranslation',
        Tag::class => 'Tag',
        TagTranslation::class => 'TagTranslation',
        SeoMeta::class => 'SeoMeta',
        SeoMetaTranslation::class => 'SeoMetaTranslation',
        ArticleMeta::class => 'ArticleMeta',
        Setting::class => 'Setting',
        User::class => 'User',
        UserTranslation::class => 'UserTranslation',
    ];

    private const ACTION_KEYS = [
        'created',
        'updated',
        'published',
        'unpublished',
        'content_updated',
        'deleted',
        'restored',
    ];

    public function index(Request $request, AuditEventPresenter $presenter): View
    {
        $this->authorizeAudit('show');

        $audits = $this->auditRows($request, $presenter);
        $activities = $this->activityRows($request, $presenter);
        $rows = $audits
            ->merge($activities)
            ->sortByDesc('created_at')
            ->values();

        $page = LengthAwarePaginator::resolveCurrentPage();
        $items = $rows->forPage($page, self::PER_PAGE)->values();

        $paginator = new LengthAwarePaginator(
            $items,
            $rows->count(),
            self::PER_PAGE,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('admin.audit.index', [
            'title' => __('audit.title'),
            'rows' => $paginator,
            'filters' => $request->query(),
            'entityOptions' => $this->entityOptions(),
            'actionOptions' => $this->actionOptions(),
            'users' => User::query()->orderBy('email')->get(['id', 'name', 'surname', 'email']),
        ]);
    }

    public function show(int $id, AuditEventPresenter $presenter): View
    {
        $this->authorizeAudit('show');

        $audit = Audit::query()->with('user')->findOrFail($id);

        return view('admin.audit.show', [
            'title' => __('audit.record_title', ['id' => $audit->id]),
            'audit' => $audit,
            'presenter' => $presenter,
        ]);
    }

    private function auditRows(Request $request, AuditEventPresenter $presenter): Collection
    {
        $query = Audit::query()
            ->with('user')
            ->latest();

        $this->applyCommonAuditFilters($query, $request);
        $this->applyActionFilter($query, $request);

        return $query
            ->limit(self::AUDIT_FETCH_LIMIT)
            ->get()
            ->toBase()
            ->map(function (Audit $audit) use ($presenter): array {
                $changes = $presenter->changes($audit);

                return [
                    'source' => 'audit',
                    'id' => $audit->id,
                    'created_at' => $audit->created_at,
                    'user' => $this->userLabel($audit->user),
                    'entity' => $presenter->auditableLabel($audit),
                    'action' => $presenter->actionLabel($audit),
                    'changes' => $changes,
                    'ip_address' => $audit->ip_address,
                    'url' => $audit->url,
                    'show_url' => route('audit.show', $audit->id),
                ];
            })
            ->filter(fn (array $row): bool => !empty($row['changes']))
            ->values();
    }

    private function activityRows(Request $request, AuditEventPresenter $presenter): Collection
    {
        $action = (string) $request->query('action', '');
        if ($action !== '' && $action !== 'content_updated') {
            return collect();
        }

        $entity = (string) $request->query('entity', '');
        if ($entity !== '' && $entity !== Article::class) {
            return collect();
        }

        $query = ArticleActivityLog::query()
            ->with('user')
            ->latest();

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date('date_from')?->startOfDay());
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date('date_to')?->endOfDay());
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->query('user_id'));
        }

        if ($request->filled('auditable_id')) {
            $query->where('article_id', (int) $request->query('auditable_id'));
        }

        if ($request->filled('ip')) {
            $query->where('ip_address', 'like', '%' . $request->query('ip') . '%');
        }

        return $query
            ->limit(self::AUDIT_FETCH_LIMIT)
            ->get()
            ->toBase()
            ->map(function (ArticleActivityLog $activity) use ($presenter): array {
                return [
                    'source' => 'activity',
                    'id' => $activity->id,
                    'created_at' => $activity->created_at,
                    'user' => $this->userLabel($activity->user),
                    'entity' => $presenter->activityAuditableLabel($activity),
                    'action' => $presenter->activityActionLabel($activity),
                    'changes' => $presenter->activityChanges($activity),
                    'ip_address' => $activity->ip_address,
                    'url' => $activity->url,
                    'show_url' => null,
                ];
            });
    }

    private function applyCommonAuditFilters($query, Request $request): void
    {
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date('date_from')?->startOfDay());
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date('date_to')?->endOfDay());
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->query('user_id'));
        }

        if ($request->filled('entity')) {
            $query->where('auditable_type', (string) $request->query('entity'));
        }

        if ($request->filled('auditable_id')) {
            $query->where('auditable_id', (int) $request->query('auditable_id'));
        }

        if ($request->filled('ip')) {
            $query->where('ip_address', 'like', '%' . $request->query('ip') . '%');
        }
    }

    private function applyActionFilter($query, Request $request): void
    {
        $action = (string) $request->query('action', '');

        if ($action === '') {
            return;
        }

        if (in_array($action, ['created', 'updated', 'deleted', 'restored'], true)) {
            $query->where('event', $action);
            return;
        }

        if ($action === 'published') {
            $query->where('event', 'updated')
                ->where('new_values->status', Article::STATUS_PUBLISHED)
                ->where('old_values->status', '!=', Article::STATUS_PUBLISHED);
            return;
        }

        if ($action === 'unpublished') {
            $query->where('event', 'updated')
                ->where('old_values->status', Article::STATUS_PUBLISHED)
                ->where('new_values->status', '!=', Article::STATUS_PUBLISHED);
            return;
        }

        if ($action === 'content_updated') {
            $query->whereRaw('1 = 0');
        }
    }

    private function userLabel(mixed $user): string
    {
        if (!$user instanceof User) {
            return __('audit.system_user');
        }

        $name = trim($user->fullname);

        return $name !== '' ? $name : $user->email;
    }

    /**
     * @return array<class-string, string>
     */
    private function entityOptions(): array
    {
        return self::ENTITY_CLASSES;
    }

    /**
     * @return array<string, string>
     */
    private function actionOptions(): array
    {
        return collect(self::ACTION_KEYS)
            ->mapWithKeys(fn (string $action): array => [$action => __("audit.actions_filter.{$action}")])
            ->all();
    }

    private function authorizeAudit(string $operation): void
    {
        $user = backpack_user();

        if (!$user) {
            abort(403);
        }

        if ($user->hasRole('Admin', 'web')) {
            return;
        }

        if (!$user->hasPermissionTo("audit.{$operation}", 'web')) {
            abort(403);
        }
    }
}
