<?php

namespace App\Observers;

use App\Models\User\Translate\UserTranslation;
use App\Repositories\ContributorRepository;

class UserTranslationObserver
{
    public function __construct(
        private readonly ContributorRepository $contributors,
    ) {}

    public function saved(UserTranslation $translation): void
    {
        $this->contributors->invalidateEditors();
    }

    public function deleted(UserTranslation $translation): void
    {
        $this->contributors->invalidateEditors();
    }
}
