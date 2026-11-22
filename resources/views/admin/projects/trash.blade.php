@extends('layouts.admin.app')
@section('title', __('project.trash'))

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="card-title mb-0 text-danger"><i class="fa-solid fa-trash me-2"></i>{{ __('project.trash') }}</h4>
        <a href="{{ route('admin.projects.index') }}" class="btn btn-label-secondary"><i class="fa-solid fa-arrow-right icon-back me-1" aria-hidden="true"></i> {{ __('project.actions.back') }}</a>
    </div>
    <div class="card-body">
        @php
            $csrf = csrf_field();
            $deleteMethod = method_field('DELETE');
            $actions = [];
            if (admin_can('project_restore')) {
                $txt = e(__('global.restore'));
                $actions[] = ['type' => 'custom', 'html' => "<li><form action=\"" . url('admin/projects') . "/\${row.id}/restore\" method=\"post\">{$csrf}<button type=\"submit\" class=\"dropdown-item text-success\"><i class=\"fa-solid fa-rotate-left fa-fw\"></i> {$txt}</button></form></li>"];
            }
            if (admin_can('project_force_delete')) {
                $txt = e(__('global.force_delete'));
                $actions[] = ['type' => 'custom', 'html' => "<li><form action=\"" . url('admin/projects') . "/\${row.id}/force-delete\" method=\"post\">{$csrf}{$deleteMethod}<button type=\"submit\" class=\"dropdown-item text-danger\"><i class=\"fa-solid fa-trash-can fa-fw\"></i> {$txt}</button></form></li>"];
            }
        @endphp
        <x-dataTable.table
            :columns="['#', __('project.fields.code'), __('project.fields.name'), __('project.fields.manager'), __('project.fields.status'), __('project.fields.deleted_at')]"
            :fields="['id', 'code', 'name', ['data' => 'manager_name', 'name' => 'manager.name', 'orderable' => false], ['data' => 'status_badge', 'name' => 'status', 'orderable' => false, 'searchable' => false], 'deleted_at']"
            :actions="$actions"
        />
    </div>
</div>
@endsection
