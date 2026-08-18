<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Requests\Settings\SettingRequest;
use App\Models\Settings\Locale;
use App\Models\Settings\Setting;
use App\Models\Site\Site;
use App\Services\Settings\SettingsDefinition;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class SettingCrudController extends CrudController
{
    /**
     * Create:
     * site_id + key + is_active
     * → заполнить дефолты
     * → сохранить
     * → redirect на edit
     *
     * Edit:
     * readonly site/key/group/type
     * label/description/value по type
     */

    use ListOperation;
    use CreateOperation {
        store as traitStore;
    }
    use UpdateOperation;
    use DeleteOperation;
    use ShowOperation;

    public function setup(): void
    {
        CRUD::setModel(Setting::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/settings');
        CRUD::setEntityNameStrings('настройку', 'настройки');

        CRUD::setValidation(SettingRequest::class);
    }

    protected function setupListOperation(): void
    {
        CRUD::column('site_id')
            ->label('Site')
            ->type('select')
            ->entity('site')
            ->attribute('name')
            ->model(Site::class);

        CRUD::column('key')->label('Key');

        CRUD::addColumn([
            'name' => 'group',
            'label' => 'Group',
            'type' => 'closure',
            'function' => function (Setting $entry) {
                $definition = app(SettingsDefinition::class);
                $group = $definition->group($entry->key);

                return $definition->groupLabel($group);
            },
        ]);

        CRUD::addColumn([
            'name' => 'label_current',
            'label' => 'Label',
            'type' => 'closure',
            'function' => function (Setting $entry) {
                return app(SettingsDefinition::class)->label($entry->key);
            },
        ]);

        CRUD::column('is_active')
            ->label('Active')
            ->type('boolean');

        CRUD::column('updated_at')->label('Updated');
    }

    protected function setupCreateOperation(): void
    {
        CRUD::field('site_id')
            ->label('Site')
            ->type('select2')
            ->entity('site')
            ->attribute('name')
            ->model(Site::class)
            ->allows_null(true);

        CRUD::addField([
            'name' => 'key',
            'label' => 'Setting',
            'type' => 'select_from_array',
            'options' => $this->settingOptions(),
            'allows_null' => false,
        ]);

        CRUD::field('is_active')
            ->label('Active')
            ->type('checkbox')
            ->default(true);
    }

    public function store()
    {
        $definition = app(SettingsDefinition::class);

        $key = request()->input('key');
        $data = $definition->get($key);

        request()->merge([
            'value' => $data['default'] ?? [],
        ]);

        $this->traitStore();

        $entry = $this->crud->entry;

        return redirect(backpack_url('settings/' . $entry->id . '/edit'));
    }

    protected function setupUpdateOperation(): void
    {
        /** @var Setting|null $entry */
        $entry = $this->crud->getCurrentEntry();

        if (! $entry instanceof Setting) {
            abort(404);
        }

        $definition = app(SettingsDefinition::class);

        $this->addReadonlySystemFields($entry, $definition);
        $this->addStaticDescriptionBlock($entry, $definition);
        $this->addValueFields($entry, $definition);

        CRUD::field('is_active')
            ->label('Active')
            ->type('checkbox');
    }

    private function addStaticDescriptionBlock(Setting $entry, SettingsDefinition $definition): void
    {
        CRUD::addField([
            'name' => 'setting_info',
            'type' => 'custom_html',
            'value' => '
            <div class="alert alert-info">
                <strong>' . e($definition->label($entry->key)) . '</strong><br>
                <small>' . e($definition->description($entry->key)) . '</small>
            </div>
        ',
        ]);
    }

    protected function setupShowOperation(): void
    {
        $this->setupListOperation();

        CRUD::addColumn([
            'name' => 'description_current',
            'label' => 'Description',
            'type' => 'closure',
            'function' => function (Setting $entry) {
                return app(SettingsDefinition::class)->description($entry->key);
            },
        ]);

        CRUD::addColumn([
            'name' => 'value_preview',
            'label' => 'Value',
            'type' => 'closure',
            'function' => function (Setting $entry) {
                return json_encode($entry->value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            },
        ]);
    }

    private function settingOptions(): array
    {
        return collect(app(SettingsDefinition::class)->all())
            ->mapWithKeys(fn(array $item, string $key) => [$key => $key])
            ->all();
    }

    private function addReadonlySystemFields(Setting $entry, SettingsDefinition $definition): void
    {
        CRUD::addField([
            'name'  => 'site_display',
            'label' => __('Site'),
            'type'  => 'text',
            'value' => $entry->site?->name ?? 'Global',
            'fake'  => true,
            'store_in' => 'extras',
            'attributes' => [
                'readonly' => 'readonly',
                'disabled' => 'disabled',
            ],
        ]);

        CRUD::addField([
            'name'  => 'key_display',
            'label' => __('Key'),
            'type'  => 'text',
            'value' => $entry->key,
            'fake'  => true,
            'store_in' => 'extras',
            'attributes' => [
                'readonly' => 'readonly',
                'disabled' => 'disabled',
            ],
        ]);

        CRUD::addField([
            'name'  => 'group_display',
            'label' => __('Group'),
            'type'  => 'text',
            'value' => $definition->groupLabel($definition->group($entry->key)),
            'fake'  => true,
            'store_in' => 'extras',
            'attributes' => [
                'readonly' => 'readonly',
                'disabled' => 'disabled',
            ],
        ]);

        CRUD::addField([
            'name'  => 'type_display',
            'label' => __('Type'),
            'type'  => 'text',
            'value' => $definition->type($entry->key),
            'fake'  => true,
            'store_in' => 'extras',
            'attributes' => [
                'readonly' => 'readonly',
                'disabled' => 'disabled',
            ],
        ]);
    }

    private function addValueFields(Setting $entry, SettingsDefinition $definition): void
    {
        match ($definition->type($entry->key)) {
            'localized_html' => $this->addLocalizedValueFields('summernote', $entry),
            'localized_text' => $this->addLocalizedValueFields('textarea', $entry),
            'phone' => CRUD::addField([
                'name' => 'value.value',
                'label' => 'Phone',
                'type' => 'text',
                'value' => $entry->value['value'] ?? null,
            ]),

            'email' => CRUD::addField([
                'name' => 'value.value',
                'label' => 'Email',
                'type' => 'email',
                'value' => $entry->value['value'] ?? null,
            ]),

            'boolean' => CRUD::addField([
                'name' => 'value.value',
                'label' => $definition->label($entry->key),
                'type' => 'checkbox',
                'value' => (bool) ($entry->value['value'] ?? false),
            ]),

            'social_links' => $this->addSocialLinksFields($definition->default($entry->key), $entry),
            default => null,
        };
    }

    private function addLocalizedValueFields(string $type, Setting $entry): void
    {
        foreach (Locale::getAvailableAsArr() as $locale) {
            CRUD::addField([
                'name' => "value.$locale",
                'label' => __('Content')." ".$locale,
                'type' => $type,
                'value' => $entry->value[$locale] ?? null,
            ]);
        }
    }

    private function addSocialLinksFields(array $default, Setting $entry): void
    {
        foreach (array_keys($default) as $network) {
            CRUD::addField([
                'name' => "value.$network.enabled",
                'label' => ucfirst($network) . ' enabled',
                'type' => 'checkbox',
                'value' => $entry->value[$network]['enabled'] ?? false,
            ]);

            CRUD::addField([
                'name' => "value.$network.url",
                'label' => ucfirst($network) . ' URL',
                'type' => 'url',
                'value' => $entry->value[$network]['url'] ?? null,
            ]);
        }
    }
}