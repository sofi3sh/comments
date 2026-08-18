<?php

namespace App\Http\Controllers\Admin\User;

use App\Http\Controllers\Traits\ValidatesPhoneNumber;
use App\Http\Requests\User\MyAccountRequest;
use App\Models\Articles\Article;
use App\Models\Articles\ArticleMeta;
use App\Models\Articles\ArticleType;
use App\Models\Articles\Attachment;
use App\Models\Settings\Locale;
use App\Models\User\User;
use App\Services\Attachment\AttachmentUploadService;
use App\Services\User\AvatarUploadService;
use Backpack\CRUD\app\Http\Controllers\MyAccountController as BackpackMyAccountController;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MyAccountController extends BackpackMyAccountController
{
    use ValidatesPhoneNumber;

    /**
     * Show the user a form to change their personal information & password.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function getAccountInfoForm()
    {
        $this->data['title'] = trans('backpack::base.my_account');
        $user = $this->guard()->user()->load('translations');
        $this->data['user'] = $user;
        $this->data['locales'] = Locale::active()->orderByDesc('is_default')->orderBy('id')->get();

        $isCompanyApp = $user->hasRole('Customer') && session('dashboard_role_chosen') === 'company_application';
        if ($isCompanyApp || $user->hasRole('Company Representative')) {
            $selectedCompany = null;

            if ($user->company_id) {
                $companyType = ArticleType::where('code', ArticleType::COMPANY)->first();

                $selectedCompanyQuery = Article::query()
                    ->where('id', $user->company_id)
                    ->where('status', Article::STATUS_PUBLISHED)
                    ->with('translations');

                if ($companyType) {
                    $selectedCompanyQuery->where('type_id', $companyType->id);
                }

                $selectedCompany = $selectedCompanyQuery->first();
            }

            $this->data['companies'] = $selectedCompany ? collect([$selectedCompany]) : collect();
        }

        return view('vendor.backpack.theme-coreuiv2.my_account', $this->data);
    }

    public function searchCompanies(Request $request)
    {
        $user = $this->guard()->user();

        $isCompanyApp = $user->hasRole('Customer') && session('dashboard_role_chosen') === 'company_application';
        if (! $isCompanyApp && ! $user->hasRole('Company Representative')) {
            abort(403);
        }

        $term = (string) $request->input('term', $request->input('q'));

        if (mb_strlen($term) < 2) {
            return response()->json(['results' => []]);
        }

        $companyType = ArticleType::where('code', ArticleType::COMPANY)->first();

        $companiesQuery = Article::query()
            ->where('status', Article::STATUS_PUBLISHED);

        if ($companyType) {
            $companiesQuery->where('type_id', $companyType->id);
        }

        $companies = $companiesQuery
            ->whereHas('translations', function ($q) use ($term) {
                $q->where('locale', 'uk')
                    ->where('title', 'like', $term.'%');
            })
            ->with(['translations' => function ($q) {
                $q->where('locale', 'uk');
            }])
            ->orderBy('id')
            ->limit(20)
            ->get();

        $results = $companies->map(function ($company) {
            return [
                'id' => $company->id,
                'text' => $company->title,
            ];
        })->values();

        return response()->json(['results' => $results]);
    }

    public function postAccountInfoForm(Request $request)
    {
        $myAccountRequest = app(MyAccountRequest::class);
        $myAccountRequest->replace($request->all());
        $myAccountRequest->setMethod($request->getMethod());
        $myAccountRequest->setUserResolver($request->getUserResolver());
        $myAccountRequest->setRouteResolver($request->getRouteResolver());
        $myAccountRequest->headers->replace($request->headers->all());

        $validated = $myAccountRequest->validated();

        /** @var User $user */
        $user = $this->guard()->user();

        $baseName = trim((string) ($validated['name'] ?? ''));
        $baseSurname = isset($validated['surname']) ? trim((string) $validated['surname']) : null;

        $data = collect($validated)->except(['avatar', 'name', 'surname'])->toArray();

        // Remove translated fields from main user update.
        $data = collect($data)
            ->filter(fn ($v, $key) => !preg_match('/^(bio|name|surname)_/', (string) $key))
            ->toArray();

        if (isset($validated['phone']) && !empty($validated['phone'])) {
            $data['phone'] = $this->normalizePhoneNumber($validated['phone']);
        } else {
            $data['phone'] = null;
        }

        $avatarUploadService = app(AvatarUploadService::class);

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $avatarUploadService->upload($request->file('avatar'), $user->avatar);
        }

        $user->update($data);
        $user->setRawAttributes(array_merge($user->getAttributes(), [
            'name' => $baseName,
            'surname' => $baseSurname,
        ]));
        $user->save();

        // Sync user translations
        $localeCodes = Locale::active()->pluck('code')->toArray();
        foreach ($localeCodes as $code) {
            $name = $validated['name_' . $code] ?? null;
            $surname = $validated['surname_' . $code] ?? null;
            $bio = $validated['bio_' . $code] ?? null;

            $user->translations()->updateOrCreate(
                ['user_id' => $user->id, 'locale' => $code],
                [
                    'name' => $name ? trim($name) : null,
                    'surname' => $surname ? trim($surname) : null,
                    'bio' => $bio ? trim($bio) : null,
                ]
            );
        }

        if ($user->hasRole('Customer')) {
            if (session('dashboard_role_chosen') === 'blogger_application') {
                $user->removeRole('Customer');
                $user->assignRole('Blogger Candidate');
                session()->forget('dashboard_role_chosen');
                \Alert::success(__('admin.dashboard.application_submitted'))->flash();
                return redirect()->route('backpack.account.info');
            }

            if (session('dashboard_role_chosen') === 'company_application') {
                if ($request->filled('company_id')) {
                    $user->company_id = $request->input('company_id');
                    $user->save();
                } elseif ($request->filled('company_title')) {

                    $companyType = ArticleType::where('code', ArticleType::COMPANY)->first();
                    $existingQuery = Article::query();
                    if ($companyType) {
                        $existingQuery->where('type_id', $companyType->id);
                    }

                    $exists = $existingQuery
                        ->whereHas('translations', function ($q) use ($request) {
                            $q->where('locale', 'uk')
                                ->where('title', $request->input('company_title'));
                        })
                        ->exists();

                    if ($exists) {
                        return redirect()
                            ->back()
                            ->withErrors(['company_title' => __('admin.account.company.title_exists')])
                            ->withInput();
                    }

                    $companyType = ArticleType::where('code', ArticleType::COMPANY)->first();
                    
                    $article = new Article();
                    $article->type_id = $companyType ? $companyType->id : null;
                    $article->status = Article::STATUS_PENDING;
                    $article->category_id = null;
                    $article->save();

                    $this->saveCompanyLogoToArticle($article, $request);

                    $article->translations()->create([
                        'locale' => 'uk',
                        'title' => $request->input('company_title'),
                        'slug' => Str::slug($request->input('company_title')) . '-' . uniqid(),
                    ]);

                    foreach (ArticleMeta::companyMetaRequestFields() as $dbField => $requestField) {
                        if ($request->filled($requestField)) {
                            ArticleMeta::create([
                                'article_id' => $article->id,
                                'locale' => 'uk',
                                'field' => $dbField,
                                'value' => $request->input($requestField)
                            ]);
                        }
                    }
                    
                    $user->company_id = $article->id;
                    $user->save();
                }

                $user->removeRole('Customer');
                $user->assignRole('Company Representative Candidate');
                session()->forget('dashboard_role_chosen');
                \Alert::success(__('admin.dashboard.application_submitted'))->flash();
                return redirect()->route('backpack.account.info');
            }
        }

        \Alert::success(trans('backpack::base.account_updated'))->flash();

        return redirect()->back();
    }

    private function saveCompanyLogoToArticle(Article $article, Request $request): void
    {
        if (! $request->hasFile('company_logo')) {
            return;
        }

        $attachment = app(AttachmentUploadService::class)->upload(
            $request->file('company_logo'),
            [
                'title' => $request->input('company_title'),
                'alt'   => $request->input('company_title'),
            ]
        );

        $article
            ->thumbnailAttachment()
            ->attach(
                $attachment->id,
                [
                    'type' => Attachment::THUMBNAIL_TYPE,
                    'order' => 0,
                ]
            );
    }
}
