@extends('layouts.admin.app')
@section('title', __('task.trash'))

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="card-title mb-0 text-danger"><i class="fa-solid fa-trash me-2"></i>{{ __('task.trash') }}</h4>
        <a href="{{ route('admin.tasks.index') }}" class="btn btn-label-secondary">
            <i class="fa-solid fa-arrow-right icon-back me-1" aria-hidden="true"></i> {{ __('task.actions.back') }}
        </a>
    </div>
    <div class="card-body">
        @php
            $csrf = csrf_field();
            $deleteMethod = method_field('DELETE');
            $actions = [];

            if (admin_can('task_restore')) {
                $txt = e(__('global.restore'));
                $msg = e(__('global.confirm_restore'));
                $actions[] = ['type' => 'custom', 'html' => "<li><form action=\"" . url('admin/tasks') . "/\${row.id}/restore\" method=\"post\" data-confirm-text=\"{$msg}\">{$csrf}<button type=\"submit\" class=\"dropdown-item text-success\"><i class=\"fa-solid fa-rotate-left fa-fw\"></i> {$txt}</button></form></li>"];
            }

            if (admin_can('task_force_delete')) {
                $txt = e(__('global.force_delete'));
                $msg = e(__('global.confirm_force_delete'));
                $actions[] = ['type' => 'custom', 'html' => "<li><form action=\"" . url('admin/tasks') . "/\${row.id}/force-delete\" method=\"post\" data-confirm-text=\"{$msg}\">{$csrf}{$deleteMethod}<button type=\"submit\" class=\"dropdown-item text-danger\"><i class=\"fa-solid fa-trash-can fa-fw\"></i> {$txt}</button></form></li>"];
            }
        @endphp

        <x-dataTable.table
            :columns="['#', __('task.fields.code'), __('task.fields.title'), __('task.fields.project'), __('task.fields.status'), __('task.fields.deleted_at')]"
            :fields="[
                'id',
                'code',
                'title',
                ['data' => 'project_name', 'name' => 'project.name', 'orderable' => false],
                ['data' => 'status_badge', 'name' => 'status', 'orderable' => false, 'searchable' => false],
                'deleted_at',
            ]"
            :actions="$actions"
        />
    </div>
</div>
@endsection
