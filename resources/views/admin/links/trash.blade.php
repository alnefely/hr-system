@extends('layouts.admin.app')
@section('title', __('link.trash'))

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h5 class="card-title mb-0">
            <i class="fa-solid fa-trash me-2 text-danger"></i> {{ __('link.trash') }}
        </h5>
        <a href="{{ route('admin.links.index') }}" class="btn btn-label-secondary waves-effect">
            <i class="fa-solid fa-arrow-right icon-back me-1" aria-hidden="true"></i> {{ __('link.back') }}
        </a>
    </div>

    <div class="card-body pt-4">
        @php
            $columns = [
                ['data' => 'id', 'name' => 'id'],
                ['data' => 'short_code', 'name' => 'short_code'],
                ['data' => 'original_url', 'name' => 'original_url'],
                ['data' => 'branch_name', 'name' => 'branch.name'],
                ['data' => 'clicks', 'name' => 'clicks_count'],
                ['data' => 'creator_name', 'name' => 'creator.name'],
                ['data' => 'deleted_at', 'name' => 'deleted_at'],
                ['data' => 'actions', 'name' => 'actions', 'orderable' => false, 'searchable' => false],
            ];
        @endphp

        <x-dataTable.table
            :columns="[
                __('global.id'),
                __('link.fields.short_code'),
                __('link.fields.original_url'),
                __('link.fields.branch'),
                __('link.fields.clicks_count'),
                __('link.fields.creator'),
                __('link.fields.deleted_at'),
                __('global.actions'),
            ]"
            :fields="$columns"
            :url="route('admin.links.trash')"
        />
    </div>
</div>
@endsection
