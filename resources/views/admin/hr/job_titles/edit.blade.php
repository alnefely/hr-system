@extends('layouts.admin.app')
@section('title', __('job_title.edit') . ': ' . $jobTitle->name)

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="card-title mb-0">{{ __('job_title.edit') }}: <span class="text-primary">{{ $jobTitle->name }}</span></h4>
        <a href="{{ route('admin.job_titles') }}" class="btn btn-label-secondary">
            <i class="fa-solid fa-arrow-right icon-back me-1" aria-hidden="true"></i> {{ __('job_title.back') }}
        </a>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.job_titles.update', $jobTitle->id) }}" method="POST">
            @csrf
            @method('PUT')
            @include('admin.hr.job_titles.form')
        </form>
    </div>
</div>
@endsection
