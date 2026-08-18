<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Articles\Article;
use App\Services\Article\ArticleContentService;
use App\Support\RedisConst;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class ArticleViewsController extends Controller
{
    /**
     * @param int $articleId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getViews(int $articleId)
    {
        $views = Redis::hget(RedisConst::ARTICLES, $articleId);

        if ($views === false) {

            $lockKey = "lock:views:$articleId";

            $lock = Redis::set($lockKey, 1, 'NX', 'EX', 5);

            if ($lock) {

                $article = Article::find($articleId);

                if (!$article) {

                    Redis::del($lockKey);

                    return response()->json(['views' => 0]);
                }

                $views = $article->views;
                Redis::hset(RedisConst::ARTICLES, $articleId, $views);

                Redis::del($lockKey);

            } else {

                usleep(50000); // 50ms
                $views = Redis::hget(RedisConst::ARTICLES, $articleId) ?? 0;
            }
        }

        $views = (int) $views;

        $showLimit = config('views.show_limit', 10);

        return response()->json([
            'views' => $views > $showLimit ? $views : 0,
        ]);
    }


    /**
     * @param Request $request
     * @param string $locale
     * @param int $article
     * @return \Illuminate\Http\JsonResponse
     */
    public function setView(Request $request, string $locale, int $article)
    {
        $ip = $request->ip();
        $userId = $request->cookie('view_id');

        if (!$userId) {
            $userId = Str::uuid();
        }

        $hash = md5($ip . $userId . $article);

        $key = "u_$hash";

        $count = Redis::incr($key);

        if ($count === 1) {
            Redis::expire($key, config('views.view_ttl'));
        }

        if ($count > config('views.daily_limit')) {
            return response()->json([
                'status' => 'ignored',
            ]);
        }

        Redis::xadd(RedisConst::STREAM, '*', [
            'a' => $article,
            'l' => $locale,
//            'i' => inet_pton($ip),
        ]);

        return response()
            ->json([
                'status' => 'ok',
            ])
            ->cookie(
                'view_id',
                $userId,
                config('views.view_cookie_ttl')
            );
    }


    /**
     * For special cases
     *
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function restContent(Request $request)
    {
        $locale = $request->route('locale');

        if ($locale !== '') {
            app()->setLocale($locale);
        }

        $article = Article::find(
            (int) $request->route('id')
        );

        if (!$article) {
            return response()->json([
                'content' => '',
            ], 404);
        }

        $contentService = new ArticleContentService();
        $parts = $contentService->splitContent($article);
        $restContent = $parts->rest;

        if ($restContent === null || trim($restContent) === '') {

            return response()->noContent();
        }

        return response()->json([
            'content' => $restContent,
        ]);
    }

}
