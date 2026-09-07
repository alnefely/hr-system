@extends('layouts.admin.app')
@section('title', __('branch.title'))

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="card-title mb-0">{{ __('branch.title') }}</h4>
        <div class="d-flex gap-2">
            @if (admin_can('branch_trash'))
                <a href="{{ route('admin.branches.trash') }}" class="btn btn-label-danger">
                    <i class="fa-solid fa-trash me-1"></i> {{ __('branch.trash') }}
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
            if (admin_can('branch_update')) {
                $actions[] = ['type' => 'edit', 'url' => url('admin/branches')];
            }
            if (admin_can('branch_delete')) {
                $actions[] = ['type' => 'delete', 'url' => url('admin/branches')];
            }
        @endphp

        <x-dataTable.table
            :columns="[
                __('global.id'),
                __('branch.fields.name'),
                __('branch.fields.code'),
                __('branch.fields.country'),
                __('branch.fields.city'),
                __('branch.fields.currency'),
                __('branch.fields.timezone'),
                __('branch.fields.employees_count'),
                __('branch.fields.status'),
            ]"
            :fields="[
                'id',
                'name',
                'code',
                'country',
                'city',
                'currency',
                'timezone',
                ['data' => 'employees_count', 'searchable' => false],
                ['data' => 'status_badge', 'name' => 'is_active', 'searchType' => 'select', 'searchOptions' => ['1' => __('global.active'), '0' => __('global.inactive')]],
            ]"
            :actions="$actions"
            add-new="{{ admin_can('branch_create') ? __('branch.add_new') : null }}"
            add-new-url="{{ admin_can('branch_create') ? route('admin.branches.create') : null }}"
        />
    </div>
</div>
@endsection
