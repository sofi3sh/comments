<?php

namespace App\Services\User;

use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade;
use Backpack\CRUD\app\Library\Uploaders\SingleFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class UserAvatarUploader extends SingleFile
{
    public function uploadFiles(Model $entry, $value = null)
    {
        $value = $value ?? CrudPanelFacade::getRequest()->file($this->getName());
        $previousFile = $this->getPreviousFiles($entry);

        if ($value === false && $previousFile) {
            Storage::disk($this->getDisk())->delete($previousFile);

            return null;
        }

        if ($value && is_file($value) && $value->isValid()) {
            return app(AvatarUploadService::class)->upload($value, $previousFile);
        }

        if (! $value && CrudPanelFacade::getRequest()->has($this->getNameForRequest()) && $previousFile) {
            Storage::disk($this->getDisk())->delete($previousFile);

            return null;
        }

        return $previousFile;
    }
}
