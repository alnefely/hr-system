@extends('layouts.admin.app')
@section('title', __('role.edit'))

@section('content')

    <h4 class="fw-semibold mb-4">
        @yield('title') — {{ $role->name }}
        <a href="{{ route('admin.roles') }}" class="btn btn-sm btn-label-secondary text-nowrap">
            <i class="fa-solid fa-arrow-right-long me-1"></i>{{ __('role.back') }}
        </a>
    </h4>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.roles.update', $role->id) }}" method="POST">
                @csrf
                @method('PUT')

                @include('admin.roles.form', ['role' => $role])

                <div class="mt-4">
                    <x-inputs.btn type="submit" status="primary" action="update" text="{{ __('global.update') }}" />
                    <a href="{{ route('admin.roles') }}" class="btn btn-label-secondary">{{ __('global.cancel') }}</a>
                </div>
            </form>
        </div>
    </div>

@endsection
