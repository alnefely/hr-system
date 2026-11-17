@extends('layouts.portal.app')
@section('title', __('portal.password.title'))

@section('content')
<div class="row justify-content-center">
    <div class="col-md-7 col-lg-5">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title fw-bold mb-1"><i class="fa-solid fa-key me-2 text-primary"></i>{{ __('portal.password.title') }}</h5>

                @if ($forced)
                    <div class="alert alert-warning small mt-3">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i>{{ __('portal.password.forced_hint') }}
                    </div>
                @endif

                <form action="{{ route('portal.password.update') }}" method="POST" class="mt-3">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label" for="current_password">{{ __('portal.password.current') }}</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required autocomplete="current-password">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="password">{{ __('portal.password.new') }}</label>
                        <input type="password" class="form-control" id="password" name="password" required autocomplete="new-password">
                        <div class="form-text">{{ __('portal.password.rules') }}</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="password_confirmation">{{ __('portal.password.confirm') }}</label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
                    </div>

                    <button type="submit" class="btn btn-primary w-100">{{ __('portal.password.submit') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
