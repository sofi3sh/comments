<?php

namespace App\Http\Controllers\Admin\User;

use App\Http\Controllers\Traits\ChecksCrudPermissions;
use App\Models\Articles\Article;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class UserApplicationCrudController extends CrudController
{
    use ChecksCrudPermissions;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     */
    public function setup(): void
    {
        CRUD::setModel(\App\Models\User\User::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/user-application');
        CRUD::setEntityNameStrings(
            __('user-application.admin.title_in_singular'),
            __('user-application.admin.title_in_plural')
        );

        $this->setupCrudPermissions('user-application');
    }

    protected function setupListOperation(): void
    {
        // Show only candidates (blogger or company representative)
        CRUD::addClause('whereHas', 'roles', function ($q) {
            $q->whereIn('name', [
                'Blogger Candidate',
                'Company Representative Candidate',
            ]);
        });

        CRUD::addColumn([
            'name' => 'avatar',
            'label' => __('user.fields.avatar'),
            'type' => 'image',
            'height' => '50px',
            'width' => '50px',
            'disk' => 'public',
        ]);

        CRUD::addColumn([
            'name' => 'name',
            'label' => __('user.fields.name'),
            'type' => 'text',
        ]);

        CRUD::addColumn([
            'name' => 'surname',
            'label' => __('user.fields.surname'),
            'type' => 'text',
        ]);

        CRUD::addColumn([
            'name' => 'email',
            'label' => __('user.fields.email'),
            'type' => 'email',
        ]);

        CRUD::addColumn([
            'name' => 'phone',
            'label' => __('user.fields.phone'),
            'type' => 'text',
        ]);

        CRUD::addColumn([
            'name' => 'company_name',
            'label' => __('user-application.fields.company_name'),
            'type' => 'closure',
            'escaped' => false,
            'function' => function ($entry) {
                if (! $entry->company_id || ! $entry->company) {
                    return '-';
                }

                $company = $entry->company()->with('translations')->first();

                if (! $company) {
                    return '-';
                }

                $locale = app()->getLocale() ?? 'uk';
                $translation = $company->translate($locale)
                    ?? $company->translate('uk')
                    ?? $company->translations->first();

                return e($translation?->title ?? '-');
            },
        ]);

        CRUD::addColumn([
            'name' => 'application_type',
            'label' => __('user-application.fields.application_type'),
            'type' => 'closure',
            'escaped' => false,
            'function' => function ($entry) {
                if ($entry->hasRole('Blogger Candidate')) {
                    return e(__('user-application.fields.types.blogger'));
                }

                if ($entry->hasRole('Company Representative Candidate')) {
                    return e(__('user-application.fields.types.company_representative'));
                }

                return '-';
            },
        ]);

        CRUD::addButtonFromView('line', 'user_application_actions', 'user_application_actions');
    }

    protected function setupShowOperation(): void
    {
        $this->setupListOperation();

        CRUD::addColumn([
            'name' => 'facebook_url',
            'label' => __('user.fields.facebook_url'),
            'type' => 'text',
            'limit' => 10000,
        ]);

        CRUD::addColumn([
            'name' => 'linkedin_url',
            'label' => __('user.fields.linkedin_url'),
            'type' => 'text',
            'limit' => 10000,
        ]);

        CRUD::addColumn([
            'name' => 'twitter_url',
            'label' => __('user.fields.twitter_url'),
            'type' => 'text',
            'limit' => 10000,
        ]);

        CRUD::addColumn([
            'name' => 'bio',
            'label' => __('user.fields.bio'),
            'type' => 'textarea',
            'limit' => 10000,
        ]);

        CRUD::addColumn([
            'name' => 'company_data',
            'label' => __('user-application.fields.company_data'),
            'type' => 'closure',
            'escaped' => false,
            'function' => function ($entry) {
                if (! $entry->company_id || ! $entry->company) {
                    return '-';
                }

                $company = $entry->company()->with(['meta', 'translations'])->first();

                if (! $company) {
                    return '-';
                }

                $currentLocale = app()->getLocale();
                $availableMetaLocales = $company->meta->pluck('locale')->filter()->unique()->values();

                $locale = $currentLocale;
                if (! $availableMetaLocales->contains($locale)) {
                    if ($availableMetaLocales->contains('uk')) {
                        $locale = 'uk';
                    } else {
                        $locale = $availableMetaLocales->first() ?? 'uk';
                    }
                }

                $translation = $company->translate($locale) 
                    ?? $company->translate('uk') 
                    ?? $company->translations->first();

                $thumbnailUrl = $company->thumbnail;

                $metaCollection = $company->meta->where('locale', $locale);
                if ($metaCollection->isEmpty()) {
                    $metaCollection = $company->meta->where('locale', 'uk');
                }
                if ($metaCollection->isEmpty()) {
                    $metaCollection = $company->meta;
                }
                $meta = $metaCollection->keyBy('field');

                $rows = [];

                if ($thumbnailUrl) {
                    $rows[] = view('admin.shared.company-avatar', ['thumbnailUrl' => $thumbnailUrl])->render();
                }

                $rows[] = '<strong>' . e(__('admin.account.company.title')) . ':</strong> ' . e($translation?->title ?? '');

                $labelMap = \App\Models\Articles\ArticleMeta::companyMetaLabels();

                foreach (\App\Models\Articles\ArticleMeta::companyMetaRequestFields() as $field => $requestField) {
                    $label = $labelMap[$field] ?? null;
                    if (! $label) {
                        continue;
                    }

                    $value = $meta[$field]->value ?? null;
                    if ($value !== null && $value !== '') {
                        if ($field === 'company_type') {
                            $value = \App\Models\Articles\ArticleMeta::formatCompanyTypeValue($value);
                        }

                        $rows[] = '<strong>' . e($label) . ':</strong> ' . e($value);
                    }
                }

                return implode('<br>', $rows);
            },
        ]);
    }

    public function approve(int $id)
    {
        /** @var \App\Models\User\User $user */
        $user = $this->crud->getEntry($id);

        if ($user->hasRole('Blogger Candidate')) {
            $user->removeRole('Blogger Candidate');
            $user->assignRole('Blogger');

            \Alert::success(__('user-application.messages.approve_blogger_success'))->flash();
        } elseif ($user->hasRole('Company Representative Candidate')) {
            $user->removeRole('Company Representative Candidate');
            $user->assignRole('Company Representative');

            if ($user->company && $user->company->status === Article::STATUS_PENDING) {
                $user->company->status = Article::STATUS_PUBLISHED;
                $user->company->save();
            }

            \Alert::success(__('user-application.messages.approve_company_success'))->flash();
        } else {
            \Alert::warning(__('user-application.messages.reject_success'))->flash();
        }

        return $this->redirectToNextApplication();
    }

    public function reject(int $id)
    {
        /** @var \App\Models\User\User $user */
        $user = $this->crud->getEntry($id);

        if ($user->hasRole('Company Representative Candidate') && $user->company) {
            $company = $user->company;

            if ($company->status === Article::STATUS_PENDING) {

                $company->meta()->delete();
                $company->translations()->delete();
                $company->delete();
            }

            $user->company_id = null;
            $user->save();
        }

        $user->syncRoles(['Customer']);

        \Alert::success(__('user-application.messages.reject_success'))->flash();

        return $this->redirectToNextApplication();
    }

    private function redirectToNextApplication(): \Illuminate\Http\RedirectResponse
    {
        $nextUser = \App\Models\User\User::query()
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', [
                    'Blogger Candidate',
                    'Company Representative Candidate',
                ]);
            })
            ->orderBy('id')
            ->first();

        if (! $nextUser) {
            return redirect()->route('user-application.index');
        }

        return redirect()->route('user-application.show', $nextUser->id);
    }
}

