@extends('layouts.admin.app')
@section('title', __('job_title.trash'))

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="card-title mb-0 text-danger">
            <i class="fa-solid fa-trash me-2"></i>{{ __('job_title.trash') }}
        </h4>
        <a href="{{ route('admin.job_titles') }}" class="btn btn-label-secondary">
            <i class="fa-solid fa-arrow-right icon-back me-1" aria-hidden="true"></i> {{ __('job_title.back') }}
        </a>
    </div>
    <div class="card-body">
        @php
            $csrf = csrf_field();
            $deleteMethod = method_field('DELETE');
            $actions = [];

            if (admin_can('job_title_restore')) {
                $actions[] = [
                    'type' => 'custom',
                    'html' => "<li><form action=\"/admin/job-titles/\${row.id}/restore\" method=\"post\">{$csrf}<button type=\"button\" class=\"dropdown-item text-success btn-swal-confirm\" data-text=\"" . __('global.confirm_restore') . "\" data-icon=\"warning\" data-btn-class=\"btn-success\"><i class=\"fa-solid fa-rotate-left fa-fw\"></i> " . __('global.restore') . "</button></form></li>",
                ];
            }

            if (admin_can('job_title_force_delete')) {
                $actions[] = [
                    'type' => 'custom',
                    'html' => "<li><form action=\"/admin/job-titles/\${row.id}/force-delete\" method=\"post\">{$csrf}{$deleteMethod}<button type=\"button\" class=\"dropdown-item text-danger btn-swal-confirm\" data-text=\"" . __('global.confirm_force_delete') . "\" data-icon=\"warning\" data-btn-class=\"btn-danger\"><i class=\"fa-solid fa-trash-can fa-fw\"></i> " . __('global.force_delete') . "</button></form></li>",
                ];
            }
        @endphp

        <x-dataTable.table
            :columns="[
                __('global.id'),
                __('job_title.fields.name'),
                __('job_title.fields.description'),
                __('job_title.fields.deleted_at'),
            ]"
            :fields="[
                'id',
                'name',
                ['data' => 'description', 'orderable' => false],
                'deleted_at',
            ]"
            :actions="$actions"
        />
    </div>
</div>
@endsection
