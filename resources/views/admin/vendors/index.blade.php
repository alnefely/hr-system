@extends('layouts.admin.app')
@section('title', __('vendor.title'))

@section('content')
{{-- Stats Widgets --}}
<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-6">
        <div class="card h-100 border-start border-primary border-4 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="avatar avatar-md rounded bg-label-primary d-flex align-items-center justify-content-center">
                        <i class="fa-solid fa-handshake fs-4"></i>
                    </div>
                    <span class="badge bg-label-primary" data-bs-toggle="tooltip" title="إجمالي عدد الموردين المسجلين في النظام">موردين وشركات</span>
                </div>
                <h4 class="mb-1 text-primary fw-bold"><span dir="ltr">{{ $vendorsCount }}</span></h4>
                <p class="mb-0 text-muted small" data-bs-toggle="tooltip" title="إجمالي الشركات ومزودي الخدمات الخارجية المسجلين">{{ __('vendor.title') }}</p>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-6">
        <div class="card h-100 border-start border-success border-4 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="avatar avatar-md rounded bg-label-success d-flex align-items-center justify-content-center">
                        <i class="fa-solid fa-circle-check fs-4"></i>
                    </div>
                    <span class="badge bg-label-success" data-bs-toggle="tooltip" title="الموردون المتاحون للتعاقد والاشتراكات">مفعل</span>
                </div>
                <h4 class="mb-1 text-success fw-bold"><span dir="ltr">{{ $activeVendorsCount }}</span></h4>
                <p class="mb-0 text-muted small" data-bs-toggle="tooltip" title="الشركات والموردون المتاحون للتعامل حالياً">الموردون النشطون</p>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center border-bottom flex-wrap gap-2">
        <h5 class="card-title mb-0">
            <i class="fa-solid fa-handshake text-primary me-2"></i> {{ __('vendor.title') }}
        </h5>
        @if(admin_can('vendor_create'))
            <button type="button" class="btn btn-primary waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#createVendorModal">
                <i class="fa-solid fa-plus me-1"></i> {{ __('vendor.actions.add_new') }}
            </button>
        @endif
    </div>
    <div class="card-body pt-4">
        @php
            $columns = [
                __('global.id'),
                __('vendor.fields.code'),
                __('vendor.fields.name'),
                __('vendor.fields.contact_person'),
                __('vendor.fields.phone'),
                __('vendor.fields.email'),
                __('vendor.fields.subscriptions_count'),
                __('vendor.fields.is_active'),
                __('global.actions'),
            ];

            $fields = [
                'id',
                'code',
                'name',
                'contact_person',
                ['data' => 'phone_formatted', 'name' => 'phone'],
                'email',
                ['data' => 'subscriptions_badge', 'name' => 'subscriptions_count', 'orderable' => false, 'searchable' => false],
                ['data' => 'status_badge', 'name' => 'is_active', 'searchType' => 'select', 'searchOptions' => ['1' => __('global.active'), '0' => __('global.inactive')]],
                ['data' => 'actions', 'name' => 'actions', 'orderable' => false, 'searchable' => false],
            ];
        @endphp

        <x-dataTable.table
            :columns="$columns"
            :fields="$fields"
            :url="route('admin.vendors.index')"
        />
    </div>
</div>

{{-- Modal: Create Vendor --}}
@if(admin_can('vendor_create'))
    <div class="modal fade" id="createVendorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('admin.vendors.store') }}" method="POST">
                    @csrf
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold">
                            <i class="fa-solid fa-plus me-2 text-primary"></i> {{ __('vendor.create_title') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-3">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vendor.fields.code') }} <span class="text-danger">*</span> <x-inputs.tip :text="__('vendor.hints.code')" /></label>
                                <input type="text" name="code" class="form-control" required placeholder="VEND-001" dir="ltr" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vendor.fields.name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required placeholder="اسم الشركة أو المورد..." />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vendor.fields.company_name') }}</label>
                                <input type="text" name="company_name" class="form-control" placeholder="الاسم التجاري أو المؤسسي..." />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vendor.fields.contact_person') }}</label>
                                <input type="text" name="contact_person" class="form-control" placeholder="الشخص المسؤول..." />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vendor.fields.phone') }}</label>
                                <input type="text" name="phone" class="form-control" placeholder="010..." dir="ltr" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vendor.fields.email') }}</label>
                                <input type="email" name="email" class="form-control" placeholder="vendor@example.com" dir="ltr" />
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">{{ __('vendor.fields.tax_number') }} <x-inputs.tip :text="__('vendor.hints.tax_number')" /></label>
                                <input type="text" name="tax_number" class="form-control" placeholder="السجل التجاري أو البطاقة الضريبية..." dir="ltr" />
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">{{ __('vendor.fields.notes') }}</label>
                                <textarea name="notes" class="form-control" rows="2" placeholder="ملاحظات وشروط التعامل ومعلومات السداد..."></textarea>
                            </div>
                            <div class="col-md-12">
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="is_active" class="form-check-input" id="vendor_create_is_active" value="1" checked />
                                    <label class="form-check-label" for="vendor_create_is_active">{{ __('vendor.enums.active') }} <x-inputs.tip :text="__('vendor.hints.is_active')" /></label>
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

{{-- Modal: Edit Vendor --}}
@if(admin_can('vendor_update'))
    <div class="modal fade" id="editVendorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form id="editVendorForm" action="" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold">
                            <i class="fa-solid fa-pen-to-square me-2 text-primary"></i> {{ __('vendor.edit_title') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-3">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vendor.fields.code') }} <span class="text-danger">*</span> <x-inputs.tip :text="__('vendor.hints.code')" /></label>
                                <input type="text" name="code" id="edit_vendor_code" class="form-control" required dir="ltr" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vendor.fields.name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="edit_vendor_name" class="form-control" required />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vendor.fields.company_name') }}</label>
                                <input type="text" name="company_name" id="edit_vendor_company" class="form-control" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vendor.fields.contact_person') }}</label>
                                <input type="text" name="contact_person" id="edit_vendor_contact" class="form-control" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vendor.fields.phone') }}</label>
                                <input type="text" name="phone" id="edit_vendor_phone" class="form-control" dir="ltr" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vendor.fields.email') }}</label>
                                <input type="email" name="email" id="edit_vendor_email" class="form-control" dir="ltr" />
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">{{ __('vendor.fields.tax_number') }} <x-inputs.tip :text="__('vendor.hints.tax_number')" /></label>
                                <input type="text" name="tax_number" id="edit_vendor_tax" class="form-control" dir="ltr" />
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">{{ __('vendor.fields.notes') }}</label>
                                <textarea name="notes" id="edit_vendor_notes" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="col-md-12">
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="is_active" class="form-check-input" id="edit_vendor_is_active" value="1" />
                                    <label class="form-check-label" for="edit_vendor_is_active">{{ __('vendor.enums.active') }} <x-inputs.tip :text="__('vendor.hints.is_active')" /></label>
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
    $(document).on('click', '.edit-vendor-btn', function() {
        var id = $(this).data('id');
        var baseUrl = "{{ url('admin/vendors') }}";
        $('#editVendorForm').attr('action', baseUrl + '/' + id);
        $('#edit_vendor_code').val($(this).data('code'));
        $('#edit_vendor_name').val($(this).data('name'));
        $('#edit_vendor_company').val($(this).data('company'));
        $('#edit_vendor_contact').val($(this).data('contact'));
        $('#edit_vendor_phone').val($(this).data('phone'));
        $('#edit_vendor_email').val($(this).data('email'));
        $('#edit_vendor_tax').val($(this).data('tax'));
        $('#edit_vendor_notes').val($(this).data('notes'));
        $('#edit_vendor_is_active').prop('checked', $(this).data('active') == 1);
        $('#editVendorModal').modal('show');
    });
});
</script>
@endpush
