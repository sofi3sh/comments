<?php

namespace App\SEO\Pages;

use App\Models\User\User;
use App\SEO\Data\SeoPage;
use Illuminate\Support\Str;

final class ContributorSeo extends AbstractSeo
{
    public function __construct(
        private readonly User $user,
        private readonly string $type,
    ) {
    }

    public static function make(User $user, string $type): self
    {
        return new self($user, $type);
    }

    public function toSeoPage(): SeoPage
    {
        $user = $this->user;

        return new SeoPage(
            title: $this->resolveTitle($user),
            description: $this->resolveDescription($user),
            keywords: __('app-meta.common.keywords'),
            canonicalUrl: $this->resolveUrl($user, app()->getLocale()),
            alternateUrls: $this->urlsForAvailableLocales(
                fn (string $locale): string => $this->resolveUrl($user, $locale)
            ),
            imageUrl: asset($user->avatarUrl), // todo: add dedicated Facebook
        );
    }

    private function resolveTitle(User $user): string
    {
        $typeTitle = match ($this->type) {
            'editor' => __('page.editor.title'),
            'author' => __('page.author.title'),
        };

        return $typeTitle . ' ' . $user->fullName . __('app-meta.title_short');
    }

    private function resolveDescription(User $user): string
    {
        $description = $user->getPosition();

        $description = $description !== null ? Str::limit($description, 300, '') : null;

        return $user->fullName . ' | ' . $description;
    }

    private function resolveUrl(User $user, string $locale): string
    {
        return route('locale.' . $this->type, [
            'locale' => $locale,
            'slug'   => $user->slug,
            'id'     => $user->id,
        ]);
    }
}
