@extends('layouts.admin.app')
@section('title', __('expense_category.title'))

@section('content')
<div class="card mb-4 shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center border-bottom flex-wrap gap-2">
        <h5 class="card-title mb-0">
            <i class="fa-solid fa-tags text-primary me-2"></i> {{ __('expense_category.title') }}
        </h5>
        <div class="d-flex gap-2">
            @if(admin_can('expense_category_trash'))
                <a href="{{ route('admin.expense-categories.trash') }}" class="btn btn-label-danger waves-effect">
                    <i class="fa-solid fa-trash me-1"></i> {{ __('expense_category.trash') }}
                    @if(isset($trashCount) && $trashCount > 0)
                        <span class="badge bg-danger ms-1">{{ $trashCount }}</span>
                    @endif
                </a>
            @endif

            <a href="{{ route('admin.expenses.index') }}" class="btn btn-label-secondary waves-effect">
                <i class="fa-solid fa-arrow-right icon-back me-1" aria-hidden="true"></i> {{ __('expense.title') }}
            </a>
            @if(admin_can('expense_category_create'))
                <button type="button" class="btn btn-primary waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#createExpenseCategoryModal">
                    <i class="fa-solid fa-plus me-1"></i> {{ __('expense_category.actions.add_new') }}
                </button>
            @endif
        </div>
    </div>
    <div class="card-body pt-4">

        @php
            $activeOptions = ['1' => __('global.active'), '0' => __('global.inactive')];
            $columns = [
                ['data' => 'id', 'name' => 'id'],
                ['data' => 'name', 'name' => 'name'],
                ['data' => 'code', 'name' => 'code'],
                ['data' => 'description', 'name' => 'description'],
                ['data' => 'expenses_count', 'name' => 'expenses_count', 'searchable' => false],
                ['data' => 'is_active_badge', 'name' => 'is_active', 'orderable' => false, 'searchType' => 'select', 'searchOptions' => $activeOptions],
                ['data' => 'actions', 'name' => 'actions', 'orderable' => false, 'searchable' => false],
            ];
        @endphp

        <x-dataTable.table
            :columns="[
                __('global.id'),
                __('expense_category.fields.name'),
                __('expense_category.fields.code'),
                __('expense_category.fields.description'),
                __('expense_category.fields.expenses_count'),
                __('expense_category.fields.is_active'),
                __('global.actions'),
            ]"
            :fields="$columns"
            :url="route('admin.expense-categories.index')"
        />
    </div>
</div>

{{-- Modal for Creating Expense Category --}}
@if(admin_can('expense_category_create'))
    <div class="modal fade" id="createExpenseCategoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('admin.expense-categories.store') }}" method="POST">
                    @csrf
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold">
                            <i class="fa-solid fa-plus me-2 text-primary"></i> {{ __('expense_category.create_title') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-3">
                        <div class="mb-3">
                            <x-inputs.field name="name" label="{{ __('expense_category.fields.name') }}" required />
                        </div>
                        <div class="mb-3">
                            <x-inputs.field name="code" label="{{ __('expense_category.fields.code') }}" :tooltip="__('expense_category.hints.code')" />
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('expense_category.fields.description') }}</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input type="checkbox" name="is_active" class="form-check-input" id="create_is_active" value="1" checked />
                            <label class="form-check-label" for="create_is_active">{{ __('expense_category.enums.active') }} <x-inputs.tip :text="__('expense_category.hints.is_active')" /></label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('global.cancel') }}</button>
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-1"></i> {{ __('global.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

{{-- Modal for Editing Expense Category --}}
@if(admin_can('expense_category_update'))
    <div class="modal fade" id="editExpenseCategoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="edit_expense_category_form" action="" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold">
                            <i class="fa-solid fa-pen-to-square me-2 text-primary"></i> {{ __('expense_category.edit_title') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-3">
                        <div class="mb-3">
                            <label class="form-label">{{ __('expense_category.fields.name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="edit_name_input" class="form-control" required />
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('expense_category.fields.code') }} <x-inputs.tip :text="__('expense_category.hints.code')" /></label>
                            <input type="text" name="code" id="edit_code_input" class="form-control" />
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('expense_category.fields.description') }}</label>
                            <textarea name="description" id="edit_description_input" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input type="checkbox" name="is_active" class="form-check-input" id="edit_is_active_input" value="1" />
                            <label class="form-check-label" for="edit_is_active_input">{{ __('expense_category.enums.active') }} <x-inputs.tip :text="__('expense_category.hints.is_active')" /></label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('global.cancel') }}</button>
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-1"></i> {{ __('global.save') }}</button>
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
    $(document).on('click', '.edit-category-btn', function() {
        let id = $(this).data('id');
        let name = $(this).data('name');
        let code = $(this).data('code');
        let description = $(this).data('description');
        let active = $(this).data('active');

        $('#edit_expense_category_form').attr('action', `/admin/expense-categories/${id}`);
        $('#edit_name_input').val(name);
        $('#edit_code_input').val(code);
        $('#edit_description_input').val(description);
        $('#edit_is_active_input').prop('checked', active == 1);
        $('#editExpenseCategoryModal').modal('show');
    });
});
</script>
@endpush
