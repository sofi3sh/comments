@extends(backpack_view('blank'))

@php
    /** @var \App\Models\User\User|null $user */
    $user = backpack_user();
    $isCustomer = $user && method_exists($user, 'hasRole') && $user->hasRole('Customer');
@endphp

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8 text-center mt-5">
            @if($isCustomer && !session()->has('dashboard_role_chosen'))
                <div class="card shadow-lg border-0 rounded-lg mt-5">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0 font-weight-light">{{ __('admin.dashboard.thanks_for_registration') }}</h3>
                    </div>
                    <div class="card-body p-5">
                        <h4 class="mb-4">{{ __('admin.dashboard.choose_role_prompt') }}</h4>
                        
                        <div class="d-grid gap-3 d-sm-flex justify-content-sm-center mt-4 mb-4" style="gap: 1rem; display: flex; flex-direction: column;">
                            <form action="{{ url('admin/choose-role') }}" method="POST" class="w-100">
                                @csrf
                                <input type="hidden" name="role" value="blogger">
                                <button type="submit" class="btn btn-outline-primary btn-lg w-100 p-4 mb-3" style="font-size: 1.2rem;">
                                    <i class="la la-pencil-square fs-2 mb-2 d-block"></i>
                                    {{ __('admin.dashboard.role_blogger') }}
                                </button>
                            </form>

                            <form action="{{ url('admin/choose-role') }}" method="POST" class="w-100">
                                @csrf
                                <input type="hidden" name="role" value="company">
                                <button type="submit" class="btn btn-outline-success btn-lg w-100 p-4 mb-3" style="font-size: 1.2rem;">
                                    <i class="la la-building fs-2 mb-2 d-block"></i>
                                    {{ __('admin.dashboard.role_company') }}
                                </button>
                            </form>

                            <form action="{{ url('admin/choose-role') }}" method="POST" class="w-100">
                                @csrf
                                <input type="hidden" name="role" value="user">
                                <button type="submit" class="btn btn-outline-secondary btn-lg w-100 p-4" style="font-size: 1.2rem;">
                                    <i class="la la-user fs-2 mb-2 d-block"></i>
                                    {{ __('admin.dashboard.role_user') }}
                                </button>
                            </form>
                        </div>
                        <p class="text-muted small mt-4">{{ __('admin.dashboard.role_restriction_notice') }}</p>
                    </div>
                </div>
            @else
                <div class="card shadow-lg border-0 rounded-lg mt-5">
                    <div class="card-body p-5">
                        <h1 class="display-4">{{ trans('backpack::base.welcome') }}</h1>
                        <p class="lead">{{ trans('backpack::base.use_sidebar') }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
