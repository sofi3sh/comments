<?php

namespace App\Observers;

use App\Models\User\User;
use App\Repositories\ContributorRepository;

class UserObserver
{
    public function __construct(
        private readonly ContributorRepository $contributors,
    ) {}

    public function saved(User $user): void
    {
        $this->contributors->invalidateEditors();
    }

    public function deleted(User $user): void
    {
        $this->contributors->invalidateEditors();
    }

    public function restored(User $user): void
    {
        $this->contributors->invalidateEditors();
    }

    public function forceDeleted(User $user): void
    {
        $this->contributors->invalidateEditors();
    }
}
