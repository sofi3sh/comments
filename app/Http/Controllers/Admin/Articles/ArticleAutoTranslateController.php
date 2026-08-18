<?php

namespace App\Http\Controllers\Admin\Articles;

use App\Http\Controllers\Controller;
use App\Http\Requests\Articles\ArticleAutoTranslateRequest;
use App\Services\Translation\ArticleAutoTranslationService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class ArticleAutoTranslateController extends Controller
{
    public function __invoke(
        ArticleAutoTranslateRequest $request,
        ArticleAutoTranslationService $translationService
    ): JsonResponse {
        $validated = $request->validated();

        $source = $validated['source'];
        $target = $validated['target'] ?? [];

        $translationService->ensureWithinSyncLimit($source);

        try {
            $result = $translationService->translate(
                $source,
                $target,
                $validated['source_locale'],
                $validated['target_locale'],
                (bool) ($validated['overwrite'] ?? false)
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json($result);
    }
}
