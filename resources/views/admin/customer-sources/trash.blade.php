@extends('layouts.admin.app')
@section('title', __('customer_source.trash'))

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="card-title mb-0 text-danger">
            <i class="fa-solid fa-trash me-2"></i>{{ __('customer_source.trash') }}
        </h4>
        <a href="{{ route('admin.customer-sources.index') }}" class="btn btn-label-secondary">
            <i class="fa-solid fa-arrow-right icon-back me-1" aria-hidden="true"></i> {{ __('global.back') }}
        </a>
    </div>
    <div class="card-body">
        @php
            $csrf         = csrf_field();
            $deleteMethod = method_field('DELETE');
            $msgRestore   = __('global.confirm_restore');
            $msgForce     = __('global.confirm_force_delete');
            $txtRestore   = __('global.restore');
            $txtForce     = __('global.force_delete');
            $actions      = [];

            if (admin_can('customer_source_restore')) {
                $actions[] = [
                    'type' => 'custom',
                    'html' => "<li><form action=\"/admin/customer-sources/\${row.id}/restore\" method=\"post\">{$csrf}<button type=\"button\" class=\"dropdown-item text-success btn-swal-confirm\" data-text=\"{$msgRestore}\" data-icon=\"warning\" data-btn-class=\"btn-success\"><i class=\"fa-solid fa-rotate-left fa-fw\"></i> {$txtRestore}</button></form></li>",
                ];
            }

            if (admin_can('customer_source_force_delete')) {
                $actions[] = [
                    'type' => 'custom',
                    'html' => "<li><form action=\"/admin/customer-sources/\${row.id}/force-delete\" method=\"post\">{$csrf}{$deleteMethod}<button type=\"button\" class=\"dropdown-item text-danger btn-swal-confirm\" data-text=\"{$msgForce}\" data-icon=\"warning\" data-btn-class=\"btn-danger\"><i class=\"fa-solid fa-trash-can fa-fw\"></i> {$txtForce}</button></form></li>",
                ];
            }
        @endphp

        <x-dataTable.table
            :columns="[
                __('global.id'),
                __('customer_source.fields.code'),
                __('customer_source.fields.name'),
                __('global.deleted_at'),
            ]"
            :fields="[
                'id',
                'code',
                'name',
                'deleted_at',
            ]"
            :actions="$actions"
            :url="route('admin.customer-sources.trash')"
        />
    </div>
</div>
@endsection
