@extends('layouts.admin.app')
@section('title', __('link.edit_title'))

@section('content')
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header border-bottom d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">
                    <i class="fa-solid fa-pen-to-square me-2 text-primary"></i> {{ __('link.edit_title') }}
                </h5>
                <a href="{{ route('admin.links.index') }}" class="btn btn-label-secondary waves-effect">
                    <i class="fa-solid fa-arrow-right me-1"></i> {{ __('link.back') }}
                </a>
            </div>

            <div class="card-body pt-4">
                {{-- الرابط المختصر الحالي --}}
                <div class="alert alert-primary d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <span>
                        <i class="fa-solid fa-link me-1"></i>
                        <code dir="ltr">{{ $link->short_url }}</code>
                    </span>
                    <span class="badge bg-label-info">
                        <span dir="ltr">{{ number_format($link->clicks_count) }}</span>
                        {{ __('link.fields.clicks_count') }}
                    </span>
                </div>

                <form action="{{ route('admin.links.update', $link->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    @include('admin.links.partials.form')

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-check me-1"></i> {{ __('global.update') }}
                        </button>
                        <a href="{{ route('admin.links.index') }}" class="btn btn-label-secondary">
                            {{ __('global.cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
