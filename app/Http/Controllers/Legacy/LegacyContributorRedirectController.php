<?php

namespace App\Http\Controllers\Legacy;

use App\Http\Controllers\Controller;
use App\Models\User\User;
use App\Support\LegacyRedirectRoutes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LegacyContributorRedirectController extends Controller
{
    public function editors(): RedirectResponse
    {
        return redirect()->route(LegacyRedirectRoutes::EDITORS, [
            'locale' => app()->getLocale(),
        ], 301);
    }

    public function editor(Request $request): RedirectResponse
    {
        $id = (int) $request->route('id');

        $editor = $this->findContributor($id);

        $routeParams = [
            'locale' => app()->getLocale(),
            'slug' => $editor->slug,
            'id' => $editor->id,
        ];

        if ($request->has('page')) {
            $routeParams['page'] = (int) $request->query('page');
        }

        return redirect()->route(LegacyRedirectRoutes::EDITOR, $routeParams, 301);
    }

    public function author(Request $request): RedirectResponse
    {
        $id = (int) $request->route('id');
        $author = $this->findContributor($id);

        return redirect()->route(LegacyRedirectRoutes::AUTHOR, [
            'locale' => app()->getLocale(),
            'slug' => $author->slug,
            'id' => $author->id,
        ], 301);
    }

    private function findContributor(int $id): User
    {
        return User::withTrashed()
            ->where('old_id', $id)
            ->firstOrFail();
    }
}
