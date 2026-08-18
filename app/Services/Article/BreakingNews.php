<?php

namespace App\Services\Article;

use App\Models\Articles\ArticleType;
use App\Models\Articles\Marker;

final class BreakingNews
{
    private ?int $markerId = null;

    private bool $markerIdResolved = false;

    /**
     * Повертає технічний код системного маркера «Срочна новина».
     */
    public function markerCode(): string
    {
        return (string) config('article.breaking_news.marker_code');
    }

    /**
     * Повертає ідентифікатор системного маркера «Срочна новина» за його кодом.
     */
    public function markerId(): ?int
    {
        if ($this->markerIdResolved) {
            return $this->markerId;
        }

        $this->markerIdResolved = true;
        $markerId = Marker::query()
            ->where('code', $this->markerCode())
            ->value('id');

        return $this->markerId = $markerId !== null ? (int) $markerId : null;
    }

    /**
     * Визначає, чи доступна срочна новина для коду типу статті.
     */
    public function supportsTypeCode(?string $typeCode): bool
    {
        return in_array(
            $typeCode,
            config('article.breaking_news.type_codes', []),
            true,
        );
    }

    /**
     * Визначає, чи доступна срочна новина для ідентифікатора типу статті.
     */
    public function supportsTypeId(?int $typeId): bool
    {
        if ($typeId === null || $typeId <= 0) {
            return false;
        }

        return $this->supportsTypeCode(
            ArticleType::query()->whereKey($typeId)->value('code'),
        );
    }

    /**
     * Перевіряє, чи є поточна стаття срочною за перемикачем або маркером.
     */
    public function isEnabled(?int $typeId, bool $switchEnabled, array $markers): bool
    {
        $markerId = $this->markerId();

        return $markerId !== null
            && $this->supportsTypeId($typeId)
            && ($switchEnabled || in_array($markerId, array_map('intval', $markers), true));
    }
}
