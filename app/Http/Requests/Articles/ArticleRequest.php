<?php

namespace App\Http\Requests\Articles;

use App\Editor\EditorJsContentAnalyzer;
use App\Models\Articles\Translate\ArticleTranslation;
use App\Models\User\User;
use App\Rules\EditorJsContentRule;
use App\Rules\EditorJsMinTextLengthRule;
use App\Services\Article\ArticleFieldConfigurationResolver;
use App\Services\Article\ArticlePermissionService;
use App\Services\Article\BreakingNews;
use App\Services\Article\YouTubeVideoResolver;
use App\Services\Article\Validation\PublishWorkflowValidator;
use App\Support\Permissions\CrudOperation;
use App\Support\Validation\SeoValidation;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use App\Models\Articles\Article;
use App\Models\Articles\ArticleMeta;
use App\Models\Articles\ArticleType;


class ArticleRequest extends TranslatableCrudRequest
{
    protected array $defaultRules;
    private ?int $resolvedTypeIdForConfiguration = null;
    private bool $typeIdForConfigurationResolved = false;

    public function __construct()
    {
        parent::__construct();

        $this->defaultRules = [
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'type_id'     => ['required', 'integer', 'exists:article_types,id'],
            'status'      => ['required', Rule::in(['draft', 'pending', 'published', 'rejected', 'moderation'])],
            'published_at' => ['nullable', 'date'],
            'thumbnail'   => ['nullable'],
            'sites'       => ['nullable', 'array'],
            'sites.*'     => ['exists:sites,id'],
            'authors'     => ['nullable', 'array'],
            'editors'     => ['nullable', 'array'],
            'tags'        => ['nullable', 'array'],
            'markers'     => ['nullable', 'array'],
            'breaking_news' => ['nullable', 'boolean'],
        ];
    }

    public function authorize(): bool
    {
        return backpack_auth()->check();
    }


    /**
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        $this->injectType($data);

        $this->ensureAuthors($data);

        $this->cleanupTranslations($data);

        $this->generateTranslationSlugs(
            data: $data,
            field: 'title',
            table: 'article_translations',
            ownerColumn: 'article_id',
            ownerId: $this->route('id'),
        );

        $this->replace($data);
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        $rules = [];
        $fieldConfigs = collect();

        // Get dynamic validation rules from configuration
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('article_field_configurations')) {
                $typeId = $this->resolveTypeIdForConfiguration();
                $fieldConfigs = app(ArticleFieldConfigurationResolver::class)->forType($typeId);

                // Article main fields
                foreach (['category_id', 'type_id', 'status', 'published_at', 'thumbnail'] as $field) {

                    $config = $fieldConfigs->get($field);

                    if ($config) {

                        if (! $config->is_visible) {
                            $rules[$field] = ['nullable'];
                            continue;
                        }

                        $rules[$field] = $this->applyFieldConfiguration($field, $config);

                    } else {
                        $rules[$field] = $this->getDefaultRules($field);
                    }
                }


                // Add manual (custom) rules ===================================================================
                foreach ($fieldConfigs as $field => $config) {

                    /*
                     |--------------------------------------------------------------------------
                     | Translated fields handled separately
                     |--------------------------------------------------------------------------
                     */

                    if (in_array($field, ArticleTranslation::TRANSLATABLE, true)) {
                        continue;
                    }

                    $configRules = $this->buildRulesFromConfig($config);

                    if (! isset($rules[$field])) {

                        $rules[$field] = $configRules;

                        continue;
                    }

                    /*
                     |--------------------------------------------------------------------------
                     | Merge safely
                     |--------------------------------------------------------------------------
                     */

                    $rules[$field] = array_values(
                        array_unique(
                            array_merge(
                                $rules[$field],
                                $configRules
                            )
                        )
                    );
                }


                // Handle 'sites' field (many-to-many, but validate as array) ===================================
                $sitesConfig = $fieldConfigs->get('site_id') ?? $fieldConfigs->get('sites');

                if ($sitesConfig) {

                    if (! $sitesConfig->is_visible) {
                        $rules['sites'] = ['nullable', 'array'];
                    } else {
                        $sitesRules = $this->applyFieldConfiguration('sites', $sitesConfig);
                        // Ensure 'array' rule is present for many-to-many fields
                        if (!in_array('array', $sitesRules)) {
                            // Insert 'array' after required/nullable
                            $newRules = [];
                            $arrayAdded = false;
                            foreach ($sitesRules as $rule) {
                                $newRules[] = $rule;
                                if (($rule === 'required' || $rule === 'nullable') && !$arrayAdded) {
                                    $newRules[] = 'array';
                                    $arrayAdded = true;
                                }
                            }
                            if (!$arrayAdded) {
                                array_unshift($newRules, 'array');
                            }
                            $sitesRules = $newRules;
                        }
                        $rules['sites'] = $sitesRules;
                        // Add validation for each element in sites array (only if sites is not empty)
                        $rules['sites.*'] = ['exists:sites,id'];

                    }

                } else {

                    $rules['sites'] = $this->getDefaultRules('sites');
                    $rules['sites.*'] = ['exists:sites,id'];

                }

                // Handle 'category_id' field - check if it exists in form ===================================
                $categoryConfig = $fieldConfigs->get('category_id');
                if ($categoryConfig) {

                    if (! $categoryConfig->is_visible) {
                        $rules['category_id'] = ['nullable'];
                    } else {
                        $rules['category_id'] = $this->applyFieldConfiguration('category_id', $categoryConfig);
                    }

                } else {
                    $rules['category_id'] = $this->getDefaultRules('category_id');
                }


                // Many-to-many fields =========================================================================
                foreach (['authors', 'editors', 'tags', 'markers'] as $field) {

                    $config = $fieldConfigs->get($field);

                    if ($config && !$config->is_visible) {
                        // Skip hidden fields
                        continue;
                    }

                    if ($config) {

                        $rules[$field] = $this->applyFieldConfiguration($field, $config);

                    } else {

                        $rules[$field] = $this->getDefaultRules($field);
                    }
                }

            } else {
                // Default rules if table doesn't exist
                $rules = $this->getAllDefaultRules();
            }

        } catch (\Exception $e) {

            Log::error('ArticleRequest - Error getting field configs', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Default rules if error
            $rules = $this->getAllDefaultRules();
        }

        $typeId = (int) $this->resolveTypeIdForConfiguration();
        if ($typeId > 0) {
            $videoTypeId = (int) ArticleType::query()->where('code', ArticleType::VIDEO)->value('id');
            $companyTypeId = (int) ArticleType::query()->where('code', ArticleType::COMPANY)->value('id');

            if ($videoTypeId > 0 && $typeId === $videoTypeId) {
                $youtubeConfig = $fieldConfigs->get('youtube_id') ?? null;
                $rules['youtube_id'] = ['bail', 'required', 'string', 'max:500'];
                if ($youtubeConfig) {
                    $rules['youtube_id'] = $this->applyFieldConfiguration('youtube_id', $youtubeConfig);
                    $rules['youtube_id'] = array_values(array_filter(
                        $rules['youtube_id'],
                        static fn ($rule): bool => $rule !== 'nullable',
                    ));
                    if (! $this->hasRule($rules['youtube_id'], 'required')) {
                        $rules['youtube_id'][] = 'required';
                    }
                    if (! $this->hasRule($rules['youtube_id'], 'string')) {
                        $rules['youtube_id'][] = 'string';
                    }
                    if (! $this->hasRulePrefix($rules['youtube_id'], 'max:')) {
                        $rules['youtube_id'][] = 'max:500';
                    }
                }

                $rules['youtube_id'][] = static function (string $attribute, mixed $value, \Closure $fail): void {
                    if (YouTubeVideoResolver::idFromUrlOrId($value) === null) {
                        $fail(__('article.validation.youtube_id'));
                    }
                };
            }

            if ($companyTypeId > 0 && $typeId === $companyTypeId) {
                $companyDefaults = [
                    'company_edrpou' => ['nullable', 'string', 'max:255'],
                    'company_website' => ['nullable', 'string', 'max:255'],
                    'company_social' => ['nullable', 'string', 'max:255'],
                    'company_phone' => ['nullable', 'string', 'max:255'],
                    'company_director' => ['nullable', 'string', 'max:255'],
                    'company_position' => ['nullable', 'string', 'max:255'],
                    'company_type' => ['nullable', 'string', Rule::in(ArticleMeta::companyTypeSlugs())],
                ];

                foreach ($companyDefaults as $field => $defaultRules) {
                    $config = $fieldConfigs->get($field);
                    if ($config && ! $config->is_visible) {
                        continue;
                    }

                    if (! $config) {
                        $rules[$field] = $defaultRules;
                        continue;
                    }

                    $rules[$field] = $this->applyFieldConfiguration($field, $config);
                    if (! $this->hasRule($rules[$field], 'string')) {
                        $rules[$field][] = 'string';
                    }
                    if (! $this->hasRulePrefix($rules[$field], 'max:')) {
                        $rules[$field][] = 'max:255';
                    }
                    if ($field === 'company_type') {
                        $rules[$field][] = Rule::in(ArticleMeta::companyTypeSlugs());
                    }
                }
            }
        }

        /* Translated fields rules */
        $translationRules = $this->buildTranslationRules($fieldConfigs);

        $rules = array_merge(
            $rules,
            SeoValidation::rules(), // Seo fields rules
            $translationRules
        );

        if (! $this->canManageArticleAuthorsAndEditors()) {
            unset($rules['authors'], $rules['authors.*'], $rules['editors'], $rules['editors.*']);
        }

        $this->constrainTypeIdToRoute($rules);

        $rules['breaking_news'] = $this->getDefaultRules('breaking_news');

        return $rules;
    }


    /**
     *  Simple validator if status Published
     *
     * @param $validator
     * @return void
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $user = backpack_user();

            if (! $user) {
                return;
            }

            $typeId = (int) $this->input('type_id', 0);
            if ($typeId <= 0) {
                return;
            }

            if (app(ArticlePermissionService::class)->canAccessTypeId($user, $typeId)) {
                return;
            }

            $validator->errors()->add('type_id', __('article.validation.type_access_denied'));
        });

        $validator->after(function ($validator) {
            $user = backpack_user();

            if (! $user || (string) $this->input('status') !== Article::STATUS_PUBLISHED) {
                return;
            }

            if (! $this->canCurrentUserPublishArticle($user)) {
                $validator->errors()->add('status', __('article.validation.publish_access_denied'));
            }
        });

//        $validator->after(function ($validator) {   //todo  temporary
//            app(PublishWorkflowValidator::class)
//                ->validate($this->all(), $validator);
//        });
    }

    /**
     * Визначає, чи має поточний користувач право опублікувати статтю.
     *
     * Користувач із правом публікації будь-яких статей може опублікувати
     * матеріал без додаткових перевірок. Для права на публікацію власних
     * статей перевіряється авторство: для існуючої статті — у зв'язку
     * авторів, для нової — серед авторів, переданих у запиті.
     */
    private function canCurrentUserPublishArticle(User $user): bool
    {
        $permissions = app(ArticlePermissionService::class);

        if ($permissions->canUseAnyOperation($user, CrudOperation::PUBLISH)) {
            return true;
        }

        if (! $permissions->canUseOwnOperation($user, CrudOperation::PUBLISH)) {
            return false;
        }

        $article = $this->routeArticleForPublishCheck();
        if ($article instanceof Article) {
            return $article->authors()
                ->where('users.id', $user->getKey())
                ->exists();
        }

        return in_array((int) $user->getKey(), $this->submittedAuthorIds(), true);
    }

    /**
     * Повертає статтю з параметра маршруту для перевірки права публікації.
     */
    private function routeArticleForPublishCheck(): ?Article
    {
        $id = $this->route('id');

        return $id ? Article::query()->find($id) : null;
    }

    /**
     * Нормалізує ідентифікатори авторів, передані у запиті.
     *
     * Метод приводить одиночне значення до масиву, відкидає порожні та
     * некоректні ідентифікатори й повертає унікальний список додатних ID.
     */
    private function submittedAuthorIds(): array
    {
        $authors = $this->input('authors', []);

        if (! is_array($authors)) {
            $authors = [$authors];
        }

        return collect($authors)
            ->filter(fn ($authorId) => $authorId !== null && $authorId !== '')
            ->map(fn ($authorId) => (int) $authorId)
            ->filter(fn (int $authorId) => $authorId > 0)
            ->unique()
            ->values()
            ->all();
    }


    /*
     |--------------------------------------------------------------------------
     |  HELPERS
     |--------------------------------------------------------------------------
     */

    /**
     * Визначає тип для конфігурації один раз за запит і повторно використовує результат.
     */
    private function resolveTypeIdForConfiguration(): ?int
    {
        if ($this->typeIdForConfigurationResolved) {
            return $this->resolvedTypeIdForConfiguration;
        }

        $this->typeIdForConfigurationResolved = true;

        $articleId = (int) $this->route('id');
        if ($articleId > 0) {
            return $this->resolvedTypeIdForConfiguration = (int) Article::query()
                ->whereKey($articleId)
                ->value('type_id');
        }

        $typeCode = $this->route('type');

        return $this->resolvedTypeIdForConfiguration = is_string($typeCode)
            ? (int) ArticleType::query()->where('code', $typeCode)->value('id')
            : null;
    }

    /**
     * Обмежує тип, переданий у формі, типом із маршруту або збереженої статті.
     *
     * @param array<string, array<int, mixed>> $rules
     */
    private function constrainTypeIdToRoute(array &$rules): void
    {
        $typeId = $this->resolveTypeIdForConfiguration();

        if ($typeId === null || $typeId <= 0) {
            return;
        }

        $typeRules = array_values(array_filter(
            $rules['type_id'] ?? [],
            static fn ($rule): bool => $rule !== 'nullable',
        ));

        if (! in_array('required', $typeRules, true)) {
            $typeRules[] = 'required';
        }

        if (! in_array('integer', $typeRules, true)) {
            $typeRules[] = 'integer';
        }

        if (! in_array('exists:article_types,id', $typeRules, true)) {
            $typeRules[] = 'exists:article_types,id';
        }

        $typeRules[] = Rule::in([$typeId]);
        $rules['type_id'] = $this->uniqueRules($typeRules);
    }
    /**
     * Apply field configuration to validation rules
     */
    private function applyFieldConfiguration(string $field, $config): array
    {
        $rules = $config->getValidationRulesArray();

        // Check if 'required' or 'nullable' is already in rules
        $hasRequired = in_array('required', $rules);
        $hasNullable = in_array('nullable', $rules);

        // Apply specific rules for certain fields
        switch ($field) {
            case 'category_id':
                if (!in_array('exists:categories,id', $rules)) {
                    $rules[] = 'exists:categories,id';
                }
                if (!in_array('integer', $rules)) {
                    $rules[] = 'integer';
                }
                break;
            case 'type_id':
                // Type_id is foreign key, validate against article_types table
                if (!in_array('exists:article_types,id', $rules)) {
                    $rules[] = 'exists:article_types,id';
                }
                if (!in_array('integer', $rules)) {
                    $rules[] = 'integer';
                }
                break;
            case 'status':
                // Check if Rule::in is already in rules
                $hasInRule = false;
                foreach ($rules as $rule) {
                    if (is_object($rule) && $rule instanceof Rule && method_exists($rule, 'getRules')) {
                        $hasInRule = true;
                        break;
                    }
                }
                if (!$hasInRule && !in_array('in:draft,pending,published,rejected,moderation', $rules)) {
                    $rules[] = Rule::in(['draft', 'pending', 'published', 'rejected', 'moderation']);
                }
                break;
            case 'published_at':
                if (!in_array('date', $rules)) {
                    $rules[] = 'date';
                }
                break;
            case 'sites':
            case 'authors':
            case 'editors':
            case 'tags':
            case 'markers':
                // For array fields, ensure 'array' rule is present
                if (!in_array('array', $rules)) {
                    // Insert 'array' after required/nullable
                    $newRules = [];
                    $arrayAdded = false;
                    foreach ($rules as $rule) {
                        $newRules[] = $rule;
                        if (($rule === 'required' || $rule === 'nullable') && !$arrayAdded) {
                            $newRules[] = 'array';
                            $arrayAdded = true;
                        }
                    }
                    // If no required/nullable found, add array at the beginning
                    if (!$arrayAdded) {
                        array_unshift($newRules, 'array');
                    }
                    $rules = $newRules;
                }
                break;
        }

        return $rules;
    }


    /**
     * Get default rules for a field
     */
    private function getDefaultRules(string $field): array
    {
        return $this->defaultRules[$field] ?? ['nullable'];
    }


    /**
     * Get all default rules
     */
    private function getAllDefaultRules(): array
    {
        return $this->defaultRules;
    }


    /**
     * @return array|string[]
     */
    public function attributes(): array
    {
        $attributes = [
            'sites'       => __('article.fields.sites'),
            'sites.*'     => __('article.fields.sites'),
            'category_id' => __('article.fields.category_id'),
            'author_id'   => __('article.fields.author_id'),
            'editor_id'   => __('article.fields.editor_id'),
            'thumbnail'   => __('article.fields.thumbnail'),
            'type_id'     => __('article.fields.type'),
            'status'      => __('article.fields.status'),
            'published_at'=> __('article.fields.published_at'),
            'markers'     => __('article.fields.markers'),
            'source_url'  => __('article.fields.source_url'),
        ];


        $localeLabels = config('locales.available');

        foreach ($this->input('translations', []) as $locale => $translation) {

            $localeLabel = $localeLabels[$locale] ?? strtoupper($locale);

            $attributes["translations.$locale.title"] =
                __('Title') . " ($localeLabel)";

            $attributes["translations.$locale.excerpt"] =
                __('Excerpt') . " ($localeLabel)";

            $attributes["translations.$locale.content"] =
                __('Content') . " ($localeLabel)";

            $attributes["translations.$locale.slug"] =
                __('Slug') . " ($localeLabel)";
        }

        return $attributes;
    }


    /**
     * @return array|string[]
     */
    public function messages(): array
    {
        return [
            'sites.required' => __('article.validation.sites_required'),
            'sites.array'    => __('article.validation.sites_array'),
            'sites.*.exists' => __('article.validation.sites_exists'),

            'category_id.required' => __('article.validation.category_required'),
            'category_id.integer' => __('article.validation.category_integer'),
            'category_id.exists'  => __('article.validation.category_exists'),

            'author_id.required' => __('article.validation.author_required'),
            'author_id.integer'  => __('article.validation.author_integer'),
            'author_id.exists'   => __('article.validation.author_exists'),

            'editor_id.integer' => __('article.validation.editor_integer'),
            'editor_id.exists'  => __('article.validation.editor_exists'),

            'thumbnail.string' => __('article.validation.thumbnail_string'),
            'thumbnail.max'    => __('article.validation.thumbnail_max'),

            'type_id.required' => __('article.validation.type_required'),
            'type_id.integer'  => __('article.validation.type_integer'),
            'type_id.exists'   => __('article.validation.type_exists'),

            'status.required' => __('article.validation.status_required'),
            'status.in'       => __('article.validation.status_in'),

            'published_at.date' => __('article.validation.published_at_date'),
        ];
    }


    public function hasRule(array $rules, string $needle): bool
    {
        return in_array($needle, $rules, true);
    }


    public function hasRulePrefix(array $rules, string $prefix): bool
    {
        foreach ($rules as $rule) {
            if (is_string($rule) && str_starts_with($rule, $prefix)) {
                return true;
            }
        }

        return false;
    }


    /**
     * For check translation content
     *
     * @param string|null $content
     * @return bool
     */
    public function isEmptyEditorJs(?string $content): bool
    {
        return app(EditorJsContentAnalyzer::class)
            ->isEmpty($content);
    }


    private function getDefaultTranslatableRule(string $field): array
    {
        return $this->getDefaultTranslatableRules()[$field] ?? [];
    }

    private function getDefaultTranslatableRules(): array
    {
        return [
            'translations.*.title' => [
                'nullable',
                'string',
                'max:255',
            ],
            'translations.*.excerpt' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'translations.*.content' => [
                'nullable',
                'string',
            ],
        ];
    }


    private function buildRulesFromConfig($config): array
    {
        $rules = [];

        if ($config->is_required) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        if ($config->min_length) {
            $rules[] = 'min:' . $config->min_length;
        }

        if ($config->max_length) {
            $rules[] = 'max:' . $config->max_length;
        }

        if (!empty($config->validation_rules)) {
            $extra = is_array($config->validation_rules)
                ? $config->validation_rules
                : explode('|', $config->validation_rules);

            $rules = array_merge($rules, $extra);
        }

        return $rules;
    }


    private function buildTranslationRules($fieldConfigs): array
    {
        $rules = [];

        $translatedFields = ArticleTranslation::TRANSLATABLE;

        foreach ($translatedFields as $field) {

            $ruleKey = "translations.*.$field";

            $config = $fieldConfigs->get($field);

            if ($config) {

                if (! $config->is_visible) {
                    continue;
                }

                $fieldRules = $this->buildRulesFromConfig($config);

            } else {

                $fieldRules = $this->getDefaultTranslatableRule($field);
            }

            /*
             |--------------------------------------------------------------------------
             | Special rules
             |--------------------------------------------------------------------------
             */

            if ($field === 'content') {
                $minimum = $this->translationMinimum($fieldRules);
                $fieldRules = $this->removeMinRules($fieldRules);

                if ($this->hasRule($fieldRules, 'required')) {

                    $fieldRules[] = new EditorJsContentRule();
                }

                if (! $this->hasRule($fieldRules, 'string')) {
                    $fieldRules[] = 'string';
                }

                if ($minimum > 0) {
                    $fieldRules[] = new EditorJsMinTextLengthRule($minimum);
                }
            }

            if (in_array($field, ['title', 'excerpt'], true)) {
                $minimum = $this->translationMinimum($fieldRules);
                $fieldRules = $this->removeMinRules($fieldRules);

                if ($minimum > 0) {
                    $fieldRules[] = 'min:' . $minimum;
                }
            }

            $rules[$ruleKey] = $this->uniqueRules($fieldRules);
        }



        return $rules;
    }

    /**
     * Прибирає стандартні правила min, щоб застосувати єдиний обчислений мінімум.
     */
    private function removeMinRules(array $rules): array
    {
        return array_values(array_filter($rules, static function ($rule): bool {
            return ! is_string($rule) || ! str_starts_with($rule, 'min:');
        }));
    }

    /**
     * Визначає найсуворіший мінімум із налаштування поля та додаткових правил.
     */
    private function translationMinimum(array $rules): int
    {
        if ($this->isBreakingNews()) {
            return 0;
        }

        $ruleMinimum = 0;

        foreach ($rules as $rule) {
            if (is_string($rule) && preg_match('/^min:(\d+)$/', $rule, $matches)) {
                $ruleMinimum = max($ruleMinimum, (int) $matches[1]);
            }
        }

        return $ruleMinimum;
    }

    /**
     * Перевіряє, чи потрібно зняти мінімальні обмеження для срочної новини.
     */
    private function isBreakingNews(): bool
    {
        return app(BreakingNews::class)->isEnabled(
            $this->resolveTypeIdForConfiguration(),
            $this->boolean('breaking_news'),
            (array) $this->input('markers', []),
        );
    }


    public function uniqueRules(array $rules): array
    {
        $unique = [];

        foreach ($rules as $rule) {

            if (is_object($rule)) {
                $unique[] = $rule;
                continue;
            }

            if (! in_array($rule, $unique, true)) {
                $unique[] = $rule;
            }
        }

        return array_values($unique);
    }


    /**
     * Default authors.
     */
    protected function ensureAuthors(array &$data): void
    {
        if ($this->route('id') || $this->route('article')) {
            return;
        }

        if (empty($data['authors'] ?? null)) {

            $data['authors'] = [
                backpack_auth()->id()
            ];
        }
    }

    /**
     * Inject type from route.
     */
    protected function injectType(array &$data): void
    {
        if (!isset($data['type'])) {

            $data['type'] = $this->route('type');
        }
    }

    private function canManageArticleAuthorsAndEditors(): bool
    {
        $user = backpack_user();

        return $user && $user->hasRole('Admin', 'web');
    }

}
