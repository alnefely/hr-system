@extends('layouts.admin.app')
@section('title', __('sales_channel.title'))

@section('content')
<div class="card shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center border-bottom flex-wrap gap-2">
        <h5 class="card-title mb-0">
            <i class="fa-solid fa-layer-group text-primary me-2"></i> {{ __('sales_channel.title') }}
        </h5>
        @if(admin_can('sales_channel_create'))
            <button type="button" class="btn btn-primary waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#createChannelModal">
                <i class="fa-solid fa-plus me-1"></i> {{ __('sales_channel.actions.add_new') }}
            </button>
        @endif
    </div>

    <div class="card-body pt-4">
        @php
            $columns = [
                __('global.id'),
                __('sales_channel.fields.code'),
                __('sales_channel.fields.name'),
                __('sales_channel.fields.type'),
                __('sales_channel.fields.sales_count'),
                __('sales_channel.fields.is_active'),
                __('global.actions'),
            ];

            $fields = [
                'id',
                'code',
                'name',
                ['data' => 'type_badge', 'name' => 'type', 'searchType' => 'select', 'searchOptions' => __('sales_channel.type_enums')],
                ['data' => 'sales_badge', 'name' => 'sales_count', 'orderable' => false, 'searchable' => false],
                ['data' => 'status_badge', 'name' => 'is_active', 'searchType' => 'select', 'searchOptions' => ['1' => __('global.active'), '0' => __('global.inactive')]],
                ['data' => 'actions', 'name' => 'actions', 'orderable' => false, 'searchable' => false],
            ];
        @endphp

        <x-dataTable.table
            :columns="$columns"
            :fields="$fields"
            :url="route('admin.sales-channels.index')"
        />
    </div>
</div>

{{-- Modal: Create Channel --}}
@if(admin_can('sales_channel_create'))
    <div class="modal fade" id="createChannelModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('admin.sales-channels.store') }}" method="POST">
                    @csrf
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold">
                            <i class="fa-solid fa-plus me-2 text-primary"></i> {{ __('sales_channel.create_title') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-3">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">{{ __('sales_channel.fields.code') }} <span class="text-danger">*</span></label>
                                <input type="text" name="code" class="form-control" required placeholder="CH-001" dir="ltr" />
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">{{ __('sales_channel.fields.name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required placeholder="اسم التطبيق أو المنصة..." />
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">{{ __('sales_channel.fields.type') }} <span class="text-danger">*</span></label>
                                <select name="type" class="form-select select2" required>
                                    @foreach (__('sales_channel.type_enums') as $typeKey => $typeLabel)
                                        <option value="{{ $typeKey }}">{{ $typeLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">{{ __('sales_channel.fields.notes') }}</label>
                                <textarea name="notes" class="form-control" rows="2" placeholder="ملاحظات توضيحية..."></textarea>
                            </div>
                            <div class="col-md-12">
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="is_active" class="form-check-input" id="chan_create_is_active" value="1" checked />
                                    <label class="form-check-label" for="chan_create_is_active">{{ __('sales_channel.enums.active') }}</label>
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

{{-- Modal: Edit Channel --}}
@if(admin_can('sales_channel_update'))
    <div class="modal fade" id="editChannelModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="editChannelForm" action="" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold">
                            <i class="fa-solid fa-pen-to-square me-2 text-primary"></i> {{ __('sales_channel.edit_title') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-3">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">{{ __('sales_channel.fields.code') }} <span class="text-danger">*</span></label>
                                <input type="text" name="code" id="edit_chan_code" class="form-control" required dir="ltr" />
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">{{ __('sales_channel.fields.name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="edit_chan_name" class="form-control" required />
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">{{ __('sales_channel.fields.type') }} <span class="text-danger">*</span></label>
                                <select name="type" id="edit_chan_type" class="form-select select2" required>
                                    @foreach (__('sales_channel.type_enums') as $typeKey => $typeLabel)
                                        <option value="{{ $typeKey }}">{{ $typeLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">{{ __('sales_channel.fields.notes') }}</label>
                                <textarea name="notes" id="edit_chan_notes" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="col-md-12">
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="is_active" class="form-check-input" id="edit_chan_is_active" value="1" />
                                    <label class="form-check-label" for="edit_chan_is_active">{{ __('sales_channel.enums.active') }}</label>
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
    $(document).on('click', '.edit-channel-btn', function() {
        var id = $(this).data('id');
        var baseUrl = "{{ url('admin/sales-channels') }}";
        $('#editChannelForm').attr('action', baseUrl + '/' + id);
        $('#edit_chan_code').val($(this).data('code'));
        $('#edit_chan_name').val($(this).data('name'));
        var typeVal = $(this).data('type');
        if ($('#edit_chan_type option[value="' + typeVal + '"]').length === 0 && typeVal) {
            $('#edit_chan_type').append(new Option(typeVal, typeVal, true, true));
        }
        $('#edit_chan_type').val(typeVal).trigger('change');
        $('#edit_chan_notes').val($(this).data('notes'));
        $('#edit_chan_is_active').prop('checked', $(this).data('active') == 1);
        $('#editChannelModal').modal('show');
    });
});
</script>
@endpush
