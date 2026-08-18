<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SlugGenerator
{
    public static function generateUnique(
        string  $title,
        string  $table,
        string  $locale,
        string  $column = 'slug',
        ?string $ignoreColumn = null,
        ?int    $ignoreId = null,
    ): string
    {

        $slug = Str::slug($title, '-', $locale);

        $original = $slug;

        $i = 1;

        while (
            DB::table($table)
                ->where($column, $slug)
                ->where('locale', $locale)
                ->when(
                    $ignoreColumn && $ignoreId !== null,
                    fn ($q) => $q->where($ignoreColumn, '!=', $ignoreId)
                )
                ->exists()
        ) {

            $slug = "{$original}-{$i}";

            $i++;
        }

        return $slug;
    }
}
