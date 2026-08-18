<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Articles\Tag;
use App\Repositories\TagRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TagController extends Controller
{
    /**
     * Get homepage tags HTML for dynamic header rendering.
     *
     * @param Request $request
     * @param TagRepository $tagRepository
     * @return Response
     */
    public function header(Request $request, TagRepository $tagRepository): Response
    {
        $locale = $request->route('locale');

        app()->setLocale($locale);

        $tags = $tagRepository->getHomepageTags(
            $locale,
            config('tags.homepage.limit', 5)
        );

        return response()->view('components.others.tag-component', [
            'tags' => $tags,
        ]);
    }


    /**
     * Get list of tags (for gallery filter and upload form)
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $perPage = 20;

        $paginator = Tag::query()
            ->with(['translations' => function ($q) {
                $q->where('locale', app()->getLocale());
            }])
            ->orderBy('id')
            ->simplePaginate($perPage);

        $data = $paginator->getCollection()->map(fn (Tag $tag) => [
            'id' => $tag->id,
            'name' => $tag->name,
        ])->values();

        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'has_more' => $paginator->hasMorePages(),
                'current_page' => $paginator->currentPage(),
            ],
        ]);
    }


    /**
     *  tags for images
     *
     *
     * @return JsonResponse
     */
    public function fetch()
    {
        $search = trim((string) request()->input('q', ''));

        if (mb_strlen($search) < 3) {
            return response()->json([
                'data' => [],
                'pagination' => ['more' => false],
            ]);
        }

        $perPage = 10;
        $likeSearch = $search . '%';
        $locale = request()->input('locale', app()->getLocale());
        app()->setLocale($locale);

        $query = Tag::query()
            ->whereHas('translations', function ($q) use ($likeSearch, $locale) {
                $q->where('locale', $locale)
                    ->where('title', 'LIKE', $likeSearch);
            })
            ->with(['translations' => function ($q) use ($locale) {
                $q->where('locale', $locale);
            }]);

        $paginator = $query->simplePaginate($perPage);

        $paginator->getCollection()->each->append('display_name');

        return response()->json($paginator);
    }
}
