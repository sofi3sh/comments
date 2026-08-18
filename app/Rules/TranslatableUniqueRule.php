<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class TranslatableUniqueRule implements ValidationRule
{
    use RuleTrait;

    public function __construct(
        protected string  $table,
        protected ?string $ownerColumn = null,
        protected ?int    $ownerId     = null,
        protected string  $column      = 'slug',
    ) {
    }

    public function validate(
        string $attribute,
        mixed $value,
        Closure $fail
    ): void {

        $locale = $this->extractLocaleFromTranslatable($attribute);

        if (!$locale) {
            return;
        }

        $query = DB::table($this->table)
            ->where($this->column, $value)
            ->where('locale', $locale);

        if ($this->ownerColumn && $this->ownerId) {
            $query->where(
                $this->ownerColumn,
                '!=',
                $this->ownerId
            );
        }

        if ($query->exists()) {
            $fail(__('validation.unique'));
        }
    }
}