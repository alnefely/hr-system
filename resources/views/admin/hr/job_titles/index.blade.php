@extends('layouts.admin.app')
@section('title', __('job_title.title'))

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="card-title mb-0">{{ __('job_title.title') }}</h4>
        <div class="d-flex gap-2">
            @if (admin_can('job_title_trash'))
                <a href="{{ route('admin.job_titles.trash') }}" class="btn btn-label-danger">
                    <i class="fa-solid fa-trash me-1"></i> {{ __('job_title.trash') }}
                    @if ($trashCount > 0)
                        <span class="badge bg-danger ms-1">{{ $trashCount }}</span>
                    @endif
                </a>
            @endif
        </div>
    </div>
    <div class="card-body">
        @php
            $actions = [];
            if (admin_can('job_title_update')) {
                $actions[] = ['type' => 'edit', 'url' => url('admin/job-titles')];
            }
            if (admin_can('job_title_delete')) {
                $actions[] = ['type' => 'delete', 'url' => url('admin/job-titles')];
            }
        @endphp

        <x-dataTable.table
            :columns="[
                __('global.id'),
                __('job_title.fields.name'),
                __('job_title.fields.description'),
                __('job_title.fields.employees_count'),
                __('job_title.fields.status'),
                __('global.created_at'),
            ]"
            :fields="[
                'id',
                'name',
                ['data' => 'description', 'orderable' => false],
                ['data' => 'employees_count', 'searchable' => false],
                ['data' => 'status_badge', 'name' => 'is_active', 'searchType' => 'select', 'searchOptions' => ['1' => __('job_title.fields.active'), '0' => __('job_title.fields.inactive')]],
                'created_at',
            ]"
            :actions="$actions"
            add-new="{{ admin_can('job_title_create') ? __('job_title.add_new') : null }}"
            add-new-url="{{ admin_can('job_title_create') ? route('admin.job_titles.create') : null }}"
        />
    </div>
</div>
@endsection
