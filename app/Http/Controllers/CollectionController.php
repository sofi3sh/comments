<?php

namespace App\Http\Controllers;

use App\Models\Articles\ArticlesBlockSetting;
use App\Services\Article\ArticlesBlockSettingsService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class CollectionController extends Controller
{
    public function show(
        Request $request,
        ArticlesBlockSettingsService $service
    ) {

        $code = $request->route('code');

        abort_unless(
            in_array($code, ArticlesBlockSetting::AVAILABLE_BLOCKS, true),
            404
        );

        /** @var LengthAwarePaginator  $articlesAll */
        $articlesAll = $service->getArticlesForBlock(
            $code,
            config('article.article.per_page')
        );

        abort_if(
            $articlesAll->isEmpty(),
            404
        );

        return view('list', [
            'title' => __('admin.articles_block_settings.blocks.' . $code),
            'articles' => $articlesAll,
            'paginate' => true,
        ]);
    }
}