<?php

namespace App\Http\Controllers\Admin\StaticCache;

use App\Http\Controllers\Controller;
use App\Jobs\ManualArticleStaticInvalidationJob;
use App\Jobs\ManualStaticPublicInvalidationJob;
use App\Services\StaticCache\ManualArticleStaticInvalidator;
use App\Services\StaticCache\ManualStaticPublicInvalidator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class ManualStaticPublicInvalidationController extends Controller
{
    private const TARGET_PUBLIC = 'public';

    private const TARGET_ARTICLE = 'article';

    public function index(
        Request $request,
        ManualStaticPublicInvalidator $publicInvalidator,
        ManualArticleStaticInvalidator $articleInvalidator,
    ): View
    {
        $this->authorizeManualInvalidation();

        $target = (string) $request->query('target', $this->targetValue(self::TARGET_PUBLIC, ManualStaticPublicInvalidator::TYPE_ALL));
        [$targetKind, $type] = $this->parseTarget($target);
        $target = $this->targetValue($targetKind, $type);
        $preview = null;
        $previewCounts = [];

        if ($request->boolean('preview')) {
            try {
                if ($targetKind === self::TARGET_ARTICLE) {
                    $preview = $articleInvalidator->preview($type);
                    $previewCounts = $articleInvalidator->previewCounts($type);
                } else {
                    $preview = $publicInvalidator->preview($type);
                    $previewCounts = $publicInvalidator->previewCounts($type);
                }
            } catch (InvalidArgumentException $e) {
                \Alert::error($e->getMessage())->flash();
                $targetKind = self::TARGET_PUBLIC;
                $type = ManualStaticPublicInvalidator::TYPE_ALL;
                $target = $this->targetValue($targetKind, $type);
            }
        }

        return view('admin.static-cache.manual-public-invalidation', [
            'title' => 'Manual static cache invalidation',
            'publicTypes' => $publicInvalidator->types(),
            'articleTypes' => $articleInvalidator->types(),
            'selectedTarget' => $target,
            'selectedTargetKind' => $targetKind,
            'selectedType' => $type,
            'preview' => $preview,
            'previewCounts' => $previewCounts,
        ]);
    }

    public function store(
        Request $request,
        ManualStaticPublicInvalidator $publicInvalidator,
        ManualArticleStaticInvalidator $articleInvalidator,
    ): RedirectResponse
    {
        $this->authorizeManualInvalidation();

        $validated = $request->validate([
            'target' => ['required', 'string'],
        ]);

        $target = $validated['target'];
        [$targetKind, $type] = $this->parseTarget($target);
        $target = $this->targetValue($targetKind, $type);

        if ($targetKind === self::TARGET_ARTICLE) {
            if (!in_array($type, $articleInvalidator->types(), true)) {
                return back()
                    ->withInput()
                    ->withErrors(['target' => 'Unknown article static invalidation type.']);
            }

            ManualArticleStaticInvalidationJob::dispatch($type);

            \Alert::success("Manual article static invalidation job queued for [{$type}].")->flash();

            return redirect()->route('static-cache.manual-public.index', ['target' => $target]);
        }

        if (!in_array($type, $publicInvalidator->types(), true)) {
            return back()
                ->withInput()
                ->withErrors(['target' => 'Unknown manual static invalidation type.']);
        }

        ManualStaticPublicInvalidationJob::dispatch($type);

        \Alert::success("Manual public static invalidation job queued for [{$type}].")->flash();

        return redirect()->route('static-cache.manual-public.index', ['target' => $target]);
    }

    private function parseTarget(string $target): array
    {
        if (!str_contains($target, ':')) {
            return [self::TARGET_PUBLIC, $target];
        }

        [$kind, $type] = explode(':', $target, 2);

        if ($kind !== self::TARGET_ARTICLE) {
            $kind = self::TARGET_PUBLIC;
        }

        return [$kind, $type];
    }

    private function targetValue(string $kind, string $type): string
    {
        return "{$kind}:{$type}";
    }

    private function authorizeManualInvalidation(): void
    {
        $user = backpack_user();

        if (!$user) {
            abort(403);
        }

        if ($user->hasRole('Admin', 'web')) {
            return;
        }

        if (!$user->hasPermissionTo('static-cache.delete', 'web')) {
            abort(403);
        }
    }
}
