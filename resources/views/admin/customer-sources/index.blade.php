@extends('layouts.admin.app')
@section('title', __('customer_source.title'))

@section('content')
<div class="card shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center border-bottom flex-wrap gap-2">
        <h5 class="card-title mb-0">
            <i class="fa-solid fa-share-nodes text-primary me-2"></i> {{ __('customer_source.title') }}
            <x-inputs.help :text="__('customer_source.hints.module')" />
        </h5>

        <div class="d-flex gap-2">
            @if(admin_can('customer_source_trash'))
                <a href="{{ route('admin.customer-sources.trash') }}" class="btn btn-label-danger waves-effect">
                    <i class="fa-solid fa-trash me-1"></i> {{ __('customer_source.trash') }}
                    @if($trashCount > 0)
                        <span class="badge bg-danger ms-1">{{ $trashCount }}</span>
                    @endif
                </a>
            @endif

            @if(admin_can('customer_source_create'))
                <button type="button" class="btn btn-primary waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#createSourceModal">
                    <i class="fa-solid fa-plus me-1"></i> {{ __('customer_source.actions.add_new') }}
                </button>
            @endif
        </div>
    </div>

    <div class="card-body pt-4">
        @php
            $columns = [
                __('global.id'),
                __('customer_source.fields.code'),
                __('customer_source.fields.name'),
                __('customer_source.fields.customers_count'),
                __('customer_source.fields.sort_order'),
                __('customer_source.fields.is_active'),
                __('global.actions'),
            ];

            $fields = [
                'id',
                'code',
                'name',
                ['data' => 'customers_badge', 'name' => 'customers_count', 'orderable' => false, 'searchable' => false],
                'sort_order',
                ['data' => 'status_badge', 'name' => 'is_active', 'searchType' => 'select', 'searchOptions' => ['1' => __('global.active'), '0' => __('global.inactive')]],
                ['data' => 'actions', 'name' => 'actions', 'orderable' => false, 'searchable' => false],
            ];
        @endphp

        <x-dataTable.table
            :columns="$columns"
            :fields="$fields"
            :url="route('admin.customer-sources.index')"
        />
    </div>
</div>

{{-- Modal: إضافة مصدر --}}
@if(admin_can('customer_source_create'))
    <div class="modal fade" id="createSourceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('admin.customer-sources.store') }}" method="POST">
                    @csrf
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold">
                            <i class="fa-solid fa-plus me-2 text-primary"></i> {{ __('customer_source.create_title') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-3">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">
                                    {{ __('customer_source.fields.code') }} <span class="text-danger">*</span>
                                    <x-inputs.help :text="__('customer_source.hints.code')" />
                                </label>
                                <input type="text" name="code" class="form-control" required placeholder="SRC-001" dir="ltr" />
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">
                                    {{ __('customer_source.fields.name') }} <span class="text-danger">*</span>
                                    <x-inputs.help :text="__('customer_source.hints.name')" />
                                </label>
                                <input type="text" name="name" class="form-control" required />
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">
                                    {{ __('customer_source.fields.description') }}
                                    <x-inputs.help :text="__('customer_source.hints.description')" />
                                </label>
                                <textarea name="description" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">
                                    {{ __('customer_source.fields.sort_order') }}
                                    <x-inputs.help :text="__('customer_source.hints.sort_order')" />
                                </label>
                                <input type="number" name="sort_order" class="form-control" value="0" min="0" dir="ltr" />
                            </div>
                            <div class="col-md-12">
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="is_active" class="form-check-input" id="src_create_is_active" value="1" checked />
                                    <label class="form-check-label" for="src_create_is_active">{{ __('global.active') }}</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-label-secondary waves-effect" data-bs-dismiss="modal">{{ __('global.cancel') }}</button>
                        <button type="submit" class="btn btn-primary waves-effect waves-light"><i class="fa-solid fa-check me-1"></i> {{ __('global.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

{{-- Modal: تعديل مصدر --}}
@if(admin_can('customer_source_update'))
    <div class="modal fade" id="editSourceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="editSourceForm" action="" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold">
                            <i class="fa-solid fa-pen-to-square me-2 text-primary"></i> {{ __('customer_source.edit_title') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-3">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">{{ __('customer_source.fields.code') }} <span class="text-danger">*</span></label>
                                <input type="text" name="code" id="edit_src_code" class="form-control" required dir="ltr" />
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">{{ __('customer_source.fields.name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="edit_src_name" class="form-control" required />
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">{{ __('customer_source.fields.description') }}</label>
                                <textarea name="description" id="edit_src_description" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">{{ __('customer_source.fields.sort_order') }}</label>
                                <input type="number" name="sort_order" id="edit_src_sort" class="form-control" min="0" dir="ltr" />
                            </div>
                            <div class="col-md-12">
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="is_active" class="form-check-input" id="edit_src_is_active" value="1" />
                                    <label class="form-check-label" for="edit_src_is_active">{{ __('global.active') }}</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-label-secondary waves-effect" data-bs-dismiss="modal">{{ __('global.cancel') }}</button>
                        <button type="submit" class="btn btn-primary waves-effect waves-light"><i class="fa-solid fa-check me-1"></i> {{ __('global.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
@endsection

@push('js')
<script>
$(document).ready(function() {
    $(document).on('click', '.edit-source-btn', function() {
        var id = $(this).data('id');
        var baseUrl = "{{ url('admin/customer-sources') }}";

        $('#editSourceForm').attr('action', baseUrl + '/' + id);
        $('#edit_src_code').val($(this).data('code'));
        $('#edit_src_name').val($(this).data('name'));
        $('#edit_src_description').val($(this).data('description'));
        $('#edit_src_sort').val($(this).data('sort'));
        $('#edit_src_is_active').prop('checked', $(this).data('active') == 1);

        $('#editSourceModal').modal('show');
    });
});
</script>
@endpush
