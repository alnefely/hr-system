@extends('layouts.admin.app')
@section('title', __('branch.add_new'))

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="card-title mb-0">{{ __('branch.add_new') }}</h4>
        <a href="{{ route('admin.branches') }}" class="btn btn-label-secondary">
            <i class="fa-solid fa-arrow-right me-1"></i> {{ __('branch.back') }}
        </a>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.branches.store') }}" method="POST">
            @csrf
            @include('admin.hr.branches.form')
        </form>
    </div>
</div>
@endsection
