<?php

namespace App\Services\Seo;

use App\Models\Seo\SeoMeta;
use Illuminate\Database\Eloquent\Model;

class SeoService
{
    /**
     * Зберігає SEO для сутності та її перекладів.
     *
     * Форма SEO надсилає дані для всіх активних локалей, навіть якщо частина вкладок порожня.
     * Перед збереженням відсікаємо порожні нові локалі, щоб не створювати службові
     * SeoMetaTranslation без meta_title/meta_description/meta_keywords.
     */
    public function save(Model $entity, array $seoData): ?SeoMeta
    {
        $attributes = [
            'entity_type' => $entity->getMorphClass(),
            'entity_id'   => $entity->id,
        ];

        $seo = SeoMeta::query()->where($attributes)->first();
        $existingLocales = $seo
            ? $seo->translations()->pluck('locale')->all()
            : [];

        $seoData = $this->filterEmptyNewTranslations($seoData, $existingLocales);

        if (empty($seoData)) {
            return $seo;
        }

        $seo ??= new SeoMeta($attributes);

        $seo->fill($seoData);
        $seo->save();

        return $seo;
    }

    /**
     * Залишає всі існуючі SEO-переклади та тільки заповнені нові локалі.
     *
     * Існуючі локалі не фільтруємо, щоб редактор міг навмисно очистити вже створені SEO-поля.
     * Нові порожні локалі відкидаємо, бо вони з'являються лише через те, що форма має вкладки
     * для всіх мов, інакше аудит отримує зайві "порожні" створення SEO.
     *
     * @param array<string, array<string, mixed>> $seoData
     * @param array<int, string> $existingLocales
     * @return array<string, array<string, mixed>>
     */
    private function filterEmptyNewTranslations(array $seoData, array $existingLocales): array
    {
        $existingLocales = array_flip($existingLocales);

        return array_filter(
            $seoData,
            fn (array $translation, string $locale): bool => isset($existingLocales[$locale]) || $this->hasFilledSeoValue($translation),
            ARRAY_FILTER_USE_BOTH
        );
    }

    /**
     * Перевіряє, чи є в локалі хоча б одне реально заповнене SEO-поле.
     *
     * @param array<string, mixed> $translation
     */
    private function hasFilledSeoValue(array $translation): bool
    {
        foreach (['meta_title', 'meta_description', 'meta_keywords'] as $field) {
            $value = $translation[$field] ?? null;

            if ($value !== null && (!is_string($value) || trim($value) !== '')) {
                return true;
            }
        }

        return false;
    }
}
