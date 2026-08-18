@extends(backpack_view('blank'))

@section('after_styles')
    <style media="screen">
        .backpack-profile-form .required::after {
            content: ' *';
            color: red;
        }
        .avatar-preview {
            width: 200px;
            height: 200px;
            margin-top: 10px;
            border-radius: 50%;
            object-fit: cover;
            display: block;
        }
        .avatar-container {
            margin-bottom: 20px;
        }
        #new-company-fields {
            background: var(--tblr-bg-surface, #f8f9fa);
            border: 1px solid var(--tblr-border-color, rgba(98, 105, 118, 0.25));
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .translation-block {
            width: 100%;
            border-top: 1px solid var(--tblr-border-color, rgba(98, 105, 118, 0.25));
            padding-top: 16px;
            margin-top: 8px;
        }
        .translation-block:first-child {
            border-top: 0;
            padding-top: 0;
            margin-top: 0;
        }
        .translation-block__label-note {
            color: var(--tblr-secondary, #6c757d);
            font-size: 12px;
            font-weight: 400;
        }
    </style>
@endsection

@php
    $user = backpack_user();
    $currentRoleName = trans($user?->roles?->first()?->name) ?? __('admin.account.breadcrumb.admin');

    $breadcrumbs = [
        $currentRoleName => url(config('backpack.base.route_prefix'), 'dashboard'),
        __('admin.account.breadcrumb.account') => false,
    ];

    $isBloggerApp = $user && $user->hasRole('Customer') && session('dashboard_role_chosen') === 'blogger_application';
    $isCompanyApp = $user && $user->hasRole('Customer') && session('dashboard_role_chosen') === 'company_application';
    $isApp = $isBloggerApp || $isCompanyApp;
    $isCandidate = $user && ($user->hasRole('Blogger Candidate') || $user->hasRole('Company Representative Candidate'));
    $baseName    = $user?->getRawOriginal('name') ?? '';
    $baseSurname = $user?->getRawOriginal('surname') ?? '';
@endphp

@section('header')
    <section class="content-header">
        <div class="container-fluid mb-3">
            <h1>{{ __('admin.account.title') }}</h1>
        </div>
    </section>
@endsection

@section('content')
    <div class="row">

        @if($isCandidate)
            <div class="col-lg-8 offset-lg-2">
                <div class="card mt-5">
                    <div class="card-body text-center p-5">
                        <i class="la la-clock-o text-primary mb-3" style="font-size: 4rem;"></i>
                        <h4>{{ __('admin.dashboard.candidate_waiting') }}</h4>
                    </div>
                </div>
            </div>
        @else

            @if (session('success'))
                <div class="col-lg-8">
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                </div>
            @endif

            @if ($errors->count())
                <div class="col-lg-8">
                    <div class="alert alert-danger">
                        <ul class="mb-1">
                            @foreach ($errors->all() as $e)
                                <li>{{ $e }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            {{-- UPDATE INFO FORM --}}
            <div class="col-lg-8">
                <form class="form" action="{{ route('backpack.account.info.store') }}" method="post" enctype="multipart/form-data">

                    {!! csrf_field() !!}

                    <div class="card padding-10">

                        <div class="card-header">
                            {{ __('admin.account.update_account_info') }}
                        </div>

                        <div class="card-body backpack-profile-form bold-labels">
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    @php
                                        $label = __('admin.account.name');
                                        $field = 'name';
                                    @endphp
                                    <label class="required">{{ $label }}</label>
                                    <input required class="form-control" type="text" name="{{ $field }}" value="{{ old($field, $baseName) }}">
                                </div>

                                <div class="col-md-6 form-group">
                                    @php
                                        $label = __('admin.account.surname');
                                        $field = 'surname';
                                    @endphp
                                    <label>{{ $label }}</label>
                                    <input class="form-control" type="text" name="{{ $field }}" value="{{ old($field, $baseSurname) }}">
                                </div>

                                <div class="col-md-6 form-group">
                                    @php
                                        $label = __('admin.account.email');
                                        $field = backpack_authentication_column();
                                    @endphp
                                    <label class="required">{{ $label }}</label>
                                    <input required class="form-control" type="{{ backpack_authentication_column()==backpack_email_column()?'email':'text' }}" name="{{ $field }}" value="{{ old($field, $user->$field) }}">
                                </div>

                                @if(!$user->hasRole('Customer') || $isApp)
                                    <div class="col-md-6 form-group">
                                        @php
                                            $label = __('admin.account.phone');
                                            $field = 'phone';
                                        @endphp
                                        <label>{{ $label }}</label>
                                        <input class="form-control" type="text" name="{{ $field }}" value="{{ old($field, $user->$field ?? '') }}" placeholder="{{ __('admin.account.phone_placeholder') }}">
                                    </div>

                                    <div class="col-md-12 form-group">
                                        @php
                                            $label = __('admin.account.facebook_url');
                                            $field = 'facebook_url';
                                        @endphp
                                        <label>{{ $label }}</label>
                                        <input class="form-control" type="url" name="{{ $field }}" value="{{ old($field, $user->$field ?? '') }}" placeholder="{{ __('admin.account.facebook_placeholder') }}">
                                    </div>

                                    <div class="col-md-6 form-group">
                                        @php
                                            $label = __('admin.account.linkedin_url');
                                            $field = 'linkedin_url';
                                        @endphp
                                        <label>{{ $label }}</label>
                                        <input class="form-control" type="url" name="{{ $field }}" value="{{ old($field, $user->$field ?? '') }}" placeholder="{{ __('admin.account.linkedin_placeholder') }}">
                                    </div>

                                    <div class="col-md-6 form-group">
                                        @php
                                            $label = __('admin.account.twitter_url');
                                            $field = 'twitter_url';
                                        @endphp
                                        <label>{{ $label }}</label>
                                        <input class="form-control" type="url" name="{{ $field }}" value="{{ old($field, $user->$field ?? '') }}" placeholder="{{ __('admin.account.twitter_placeholder') }}">
                                    </div>

                                    <div class="col-md-12 form-group avatar-container">
                                        @php
                                            $label = __('admin.account.avatar');
                                            $field = 'avatar';
                                            $currentAvatar = $user->avatar_url;
                                        @endphp
                                        <label>{{ $label }}</label>
                                        <input class="form-control" type="file" name="{{ $field }}" accept="image/*" onchange="previewAvatar(this)">
                                        @if($currentAvatar)
                                            <div class="mt-2">
                                                <p>{{ __('admin.account.current_avatar') }}:</p>
                                                <img src="{{ $currentAvatar }}" alt="Avatar" class="avatar-preview" id="current-avatar">
                                            </div>
                                        @endif
                                        <div class="mt-2" id="avatar-preview-container" style="display: none;">
                                            <p>{{ __('admin.account.new_avatar') }}:</p>
                                            <img id="avatar-preview" class="avatar-preview" alt="Preview">
                                        </div>
                                    </div>

                                    @if($isCompanyApp)
                                        @php
                                            $selectedCompanyId = old('company_id');
                                            $companyFieldNames = [
                                                'company_title',
                                                'company_edrpou',
                                                'company_director',
                                                'company_position',
                                                'company_type',
                                                'company_website',
                                                'company_social',
                                                'company_phone',
                                            ];
                                            $hasOldCompanyFields = collect($companyFieldNames)->contains(fn ($field) => old($field));
                                            $hasCompanyFieldErrors = collect($companyFieldNames)->contains(fn ($field) => $errors->has($field));
                                            $showNewCompanyFields = $hasOldCompanyFields || $hasCompanyFieldErrors || $errors->has('company_logo');
                                        @endphp

                                        <div class="col-md-12 mt-4 mb-2">
                                            <hr>
                                            <h4 class="mb-3">{{ __('admin.account.company.data') }}</h4>
                                        </div>

                                        <div class="col-md-12 form-group">
                                            <label>{{ __('admin.account.company.select_label') }}</label>
                                            <select name="company_id" id="company_id" class="form-control">
                                                <option value="">{{ __('admin.account.company.select_placeholder') }}</option>
                                                @if(isset($companies))
                                                    @foreach($companies as $company)
                                                        <option value="{{ $company->id }}" {{ (string) $selectedCompanyId === (string) $company->id ? 'selected' : '' }}>
                                                            {{ $company->title }}
                                                        </option>
                                                    @endforeach
                                                @endif
                                            </select>
                                        </div>

                                        <div class="col-md-12 mb-3">
                                            <button type="button" class="btn btn-outline-primary btn-sm" id="toggle-new-company">
                                                <i class="la la-plus"></i> {{ __('admin.account.company.add_new') }}
                                            </button>
                                        </div>

                                        <div id="new-company-fields" class="w-100" style="{{ $showNewCompanyFields ? '' : 'display: none;' }}">
                                            <div class="row m-0">
                                                <div class="col-md-12 form-group">
                                                    <label class="required">{{ __('admin.account.company.title') }}</label>
                                                    <input class="form-control" type="text" name="company_title" id="company_title" value="{{ old('company_title') }}">
                                                </div>

                                                <div class="col-md-6 form-group">
                                                    <label>{{ __('admin.account.company.edrpou') }}</label>
                                                    <input class="form-control" type="text" name="company_edrpou" value="{{ old('company_edrpou') }}">
                                                </div>

                                                <div class="col-md-6 form-group">
                                                    <label>{{ __('admin.account.company.director') }}</label>
                                                    <input class="form-control" type="text" name="company_director" value="{{ old('company_director') }}">
                                                </div>

                                                <div class="col-md-6 form-group">
                                                    <label>{{ __('admin.account.company.position') }}</label>
                                                    <input class="form-control" type="text" name="company_position" value="{{ old('company_position') }}" placeholder="{{ __('admin.account.company.position_placeholder') }}">
                                                </div>

                                                <div class="col-md-6 form-group">
                                                    <label>{{ __('admin.account.company.type') }}</label>
                                                    @php
                                                        $companyTypeOptions = \App\Models\Articles\ArticleMeta::companyTypeOptions();
                                                        $selectedCompanyType = old('company_type');
                                                    @endphp
                                                    <select class="form-control" name="company_type">
                                                        <option value="">{{ __('admin.account.company.type_placeholder') }}</option>
                                                        @foreach($companyTypeOptions as $typeOption)
                                                            <option value="{{ $typeOption['value'] }}" {{ $selectedCompanyType === $typeOption['value'] ? 'selected' : '' }}>
                                                                {{ $typeOption['label'] }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="col-md-6 form-group">
                                                    <label>{{ __('admin.account.company.website') }}</label>
                                                    <input class="form-control" type="url" name="company_website" value="{{ old('company_website') }}" placeholder="{{ __('admin.account.company.url_placeholder') }}">
                                                </div>

                                                <div class="col-md-6 form-group">
                                                    <label>{{ __('admin.account.company.social') }}</label>
                                                    <input class="form-control" type="url" name="company_social" value="{{ old('company_social') }}" placeholder="{{ __('admin.account.company.url_placeholder') }}">
                                                </div>

                                                <div class="col-md-6 form-group">
                                                    <label>{{ __('admin.account.company.phone') }}</label>
                                                    <input class="form-control" type="text" name="company_phone" value="{{ old('company_phone') }}" placeholder="{{ __('admin.account.company.phone_placeholder') }}">
                                                </div>

                                                <div class="col-md-12 form-group">
                                                    <label>{{ __('admin.account.company.logo') }}</label>
                                                    <input class="form-control" type="file" name="company_logo" accept="image/*">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-12 mb-2"><hr></div>
                                    @endif

                                    @isset($locales)
                                        @foreach($locales as $locale)
                                            @php
                                                // Use translate() method with specific locale
                                                $translation = $user->translate($locale->code);
                                                $bioValue = old('bio_' . $locale->code, $translation?->bio ?? '');
                                            @endphp
                                            <div class="col-md-12 form-group">
                                                <label>{{ __('admin.account.bio') }} ({{ $locale->name }})</label>
                                                <textarea class="form-control" name="bio_{{ $locale->code }}" rows="4" maxlength="5000">{{ $bioValue }}</textarea>
                                            </div>
                                        @endforeach
                                    @endisset
                                @endif
                            </div>
                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn-success"><i class="la la-save"></i> {{ $isApp ? __('admin.dashboard.submit_application') : __('admin.account.save') }}</button>
                            <a href="{{ backpack_url() }}" class="btn">{{ __('admin.account.cancel') }}</a>
                        </div>
                    </div>

                </form>
            </div>

            {{-- CHANGE PASSWORD FORM --}}
            @if(!$isApp)
                <div class="col-lg-8">
                    <form class="form" action="{{ route('backpack.account.password') }}" method="post">

                        {!! csrf_field() !!}

                        <div class="card padding-10">

                            <div class="card-header">
                                {{ __('admin.account.password_form.title') }}
                            </div>

                            <div class="card-body backpack-profile-form bold-labels">
                                <div class="row">
                                    <div class="col-md-4 form-group">
                                        @php
                                            $label = __('admin.account.password_form.old_password');
                                            $field = 'old_password';
                                        @endphp
                                        <label class="required">{{ $label }}</label>
                                        <input autocomplete="new-password" required class="form-control" type="password" name="{{ $field }}" id="{{ $field }}" value="">
                                    </div>

                                    <div class="col-md-4 form-group">
                                        @php
                                            $label = __('admin.account.password_form.new_password');
                                            $field = 'new_password';
                                        @endphp
                                        <label class="required">{{ $label }}</label>
                                        <input autocomplete="new-password" required class="form-control" type="password" name="{{ $field }}" id="{{ $field }}" value="">
                                    </div>

                                    <div class="col-md-4 form-group">
                                        @php
                                            $label = __('admin.account.password_form.confirm_new_password');
                                            $field = 'confirm_password';
                                        @endphp
                                        <label class="required">{{ $label }}</label>
                                        <input autocomplete="new-password" required class="form-control" type="password" name="{{ $field }}" id="{{ $field }}" value="">
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer">
                                <button type="submit" class="btn btn-success"><i class="la la-save"></i> {{ __('admin.account.password_form.save') }}</button>
                                <a href="{{ backpack_url() }}" class="btn">{{ __('admin.account.password_form.cancel') }}</a>
                            </div>

                        </div>

                    </form>
                </div>
            @endif

        @endif
    </div>
@endsection

@section('after_scripts')
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        function previewAvatar(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();

                reader.onload = function(e) {
                    var previewContainer = document.getElementById('avatar-preview-container');
                    var preview = document.getElementById('avatar-preview');
                    preview.src = e.target.result;
                    previewContainer.style.display = 'block';
                }

                reader.readAsDataURL(input.files[0]);
            } else {
                document.getElementById('avatar-preview-container').style.display = 'none';
            }
        }

        $(document).ready(function() {
            if ($('#company_id').length) {
                $('#company_id').select2({
                    theme: "bootstrap",
                    width: '100%',
                    minimumInputLength: 2,
                    ajax: {
                        url: '{{ route('companies.search') }}',
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return {
                                term: params.term
                            };
                        },
                        processResults: function (data) {
                            return data;
                        },
                        cache: true
                    }
                });

                if ($('#new-company-fields').is(':visible')) {
                    $('#company_title').attr('required', 'required');
                }

                $('#toggle-new-company').on('click', function() {
                    $('#new-company-fields').slideToggle();
                    // Clear the select when opening new company form
                    $('#company_id').val('').trigger('change');

                    // Make title required when form is visible
                    if ($('#new-company-fields').is(':visible')) {
                        $('#company_title').attr('required', 'required');
                    } else {
                        $('#company_title').removeAttr('required');
                    }
                });

                // Clear new company fields when selecting an existing company
                $('#company_id').on('change', function() {
                    if ($(this).val()) {
                        $('#new-company-fields').slideUp();
                        $('#company_title').removeAttr('required');
                        $('#new-company-fields input').val('');
                    }
                });
            }
        });
    </script>
@endsection
