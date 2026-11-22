@extends('layouts.admin.app')
@section('title', __('link.create_title'))

@section('content')
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="card-title mb-0">
                    <i class="fa-solid fa-plus me-2 text-primary"></i> {{ __('link.create_title') }}
                </h5>
                <a href="{{ route('admin.links.index') }}" class="btn btn-label-secondary waves-effect">
                    <i class="fa-solid fa-arrow-right icon-back me-1" aria-hidden="true"></i> {{ __('link.back') }}
                </a>
            </div>

            <div class="card-body pt-4">
                <form action="{{ route('admin.links.store') }}" method="POST">
                    @csrf
                    @include('admin.links.partials.form')

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-check me-1"></i> {{ __('global.save') }}
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

@push('js')
<script>
$(document).ready(function () {
    $('#regenerateCode').on('click', function () {
        var btn  = $(this);
        var icon = btn.find('i');

        icon.addClass('fa-spin');

        $.get("{{ route('admin.links.generate-code') }}")
            .done(function (res) {
                if (res && res.code) {
                    $('#short_code').val(res.code);
                }
            })
            .always(function () {
                icon.removeClass('fa-spin');
            });
    });
});
</script>
@endpush
