@extends('layouts.portal.auth')
@section('title', __('portal.auth.title'))

@section('content')
    <h4 class="mb-1">{{ __('portal.auth.title') }}</h4>
    <p class="mb-6">{{ __('portal.auth.subtitle') }}</p>

    {{-- الحساب موقوف أو مش مفعّل: السبب بيظهر لصاحب الحساب بس (بعد كلمة مرور صحيحة) --}}
    @if (session('blocked'))
        <div class="alert alert-warning d-flex gap-2" role="alert">
            <i class="fa-solid fa-lock mt-1"></i>
            <div>{{ session('blocked') }}</div>
        </div>
    @endif

    @foreach (['success' => 'success', 'error' => 'danger'] as $key => $class)
        @if (session($key))
            <div class="alert alert-{{ $class }}">{{ session($key) }}</div>
        @endif
    @endforeach

    <form action="{{ route('portal.login.attempt') }}" method="POST" class="mb-4">
        @csrf

        <x-inputs.phone
            name="phone"
            :label="__('portal.auth.phone')"
            :value="old('phone')"
            initial-country="eg"
            :country-field="false"
            :required="true" />

        <div class="mb-6 form-password-toggle">
            <label class="form-label" for="password">{{ __('portal.auth.password') }} <strong class="text-danger">*</strong></label>
            <div class="input-group input-group-merge">
                <input type="password" id="password" class="form-control" name="password" required
                       placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" autocomplete="current-password" />
                <span class="input-group-text cursor-pointer"><i class="icon-base ti tabler-eye-off"></i></span>
            </div>
        </div>

        <div class="mb-6 form-check">
            <input class="form-check-input" type="checkbox" id="remember" name="remember" value="1" @checked(old('remember'))>
            <label class="form-check-label" for="remember">{{ __('portal.auth.remember') }}</label>
        </div>

        <button class="btn btn-primary d-grid w-100" type="submit">{{ __('portal.auth.login_button') }}</button>
    </form>

    <p class="text-center small text-muted mb-0">
        <i class="fa-solid fa-circle-info me-1"></i>{{ __('portal.auth.help') }}
    </p>
    <p class="text-center mb-0 mt-4">
        <a href="{{ route('home') }}" class="small"><i class="fa-solid fa-table-cells-large me-1"></i>{{ __('landing.choose_panel') }}</a>
    </p>
@endsection
