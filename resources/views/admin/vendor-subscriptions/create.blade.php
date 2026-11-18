@extends('layouts.admin.app')
@section('title', __('vendor_subscription.create_title'))

@section('content')
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header border-bottom d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">
                    <i class="fa-solid fa-plus me-2 text-primary"></i> {{ __('vendor_subscription.create_title') }}
                </h5>
                <a href="{{ route('admin.vendor-subscriptions.index') }}" class="btn btn-label-secondary waves-effect">
                    <i class="fa-solid fa-arrow-right me-1"></i> {{ __('vendor_subscription.actions.back_to_list') }}
                </a>
            </div>

            <div class="card-body pt-4">
                <form action="{{ route('admin.vendor-subscriptions.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('vendor_subscription.fields.title') }} <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" required value="{{ old('title') }}" placeholder="مثال: اشتراك سيرفرات AWS / إعلانات فيسبوك / ترخيص برامج..." />
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">{{ __('vendor_subscription.fields.vendor') }} <span class="text-danger">*</span></label>
                            <select name="vendor_id" class="form-select select2" required>
                                <option value="">{{ __('vendor_subscription.fields.select_vendor') }}</option>
                                @foreach($vendors as $v)
                                    <option value="{{ $v->id }}" {{ old('vendor_id') == $v->id ? 'selected' : '' }}>{{ $v->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">{{ __('vendor_subscription.fields.branch') }} <x-inputs.tip :text="__('vendor_subscription.hints.branch')" /></label>
                            <select name="branch_id" class="form-select select2">
                                <option value="">{{ __('vendor_subscription.fields.all_branches') }}</option>
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}" {{ old('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }} ({{ $b->currency }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">{{ __('vendor_subscription.fields.type') }} <span class="text-danger">*</span> <x-inputs.tip :text="__('vendor_subscription.hints.type')" /></label>
                            <select name="type" class="form-select select2" required>
                                <option value="subscription" {{ old('type') == 'subscription' ? 'selected' : '' }}>{{ __('vendor_subscription.type_enums.subscription') }}</option>
                                <option value="contract" {{ old('type') == 'contract' ? 'selected' : '' }}>{{ __('vendor_subscription.type_enums.contract') }}</option>
                                <option value="one_time" {{ old('type') == 'one_time' ? 'selected' : '' }}>{{ __('vendor_subscription.type_enums.one_time') }}</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">{{ __('vendor_subscription.fields.billing_cycle') }} <span class="text-danger">*</span> <x-inputs.tip :text="__('vendor_subscription.hints.billing_cycle')" /></label>
                            <select name="billing_cycle" class="form-select select2" required>
                                <option value="monthly" {{ old('billing_cycle') == 'monthly' ? 'selected' : '' }}>{{ __('vendor_subscription.cycle_enums.monthly') }}</option>
                                <option value="quarterly" {{ old('billing_cycle') == 'quarterly' ? 'selected' : '' }}>{{ __('vendor_subscription.cycle_enums.quarterly') }}</option>
                                <option value="semi_annually" {{ old('billing_cycle') == 'semi_annually' ? 'selected' : '' }}>{{ __('vendor_subscription.cycle_enums.semi_annually') }}</option>
                                <option value="annually" {{ old('billing_cycle') == 'annually' ? 'selected' : '' }}>{{ __('vendor_subscription.cycle_enums.annually') }}</option>
                                <option value="one_time" {{ old('billing_cycle') == 'one_time' ? 'selected' : '' }}>{{ __('vendor_subscription.cycle_enums.one_time') }}</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">{{ __('vendor_subscription.fields.cost') }} <span class="text-danger">*</span> <x-inputs.tip :text="__('vendor_subscription.hints.cost')" /></label>
                            <input type="number" step="0.01" min="0" name="cost" class="form-control" required value="{{ old('cost') }}" placeholder="0.00" dir="ltr" />
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">{{ __('vendor_subscription.fields.currency') }} <span class="text-danger">*</span></label>
                            <select name="currency" class="form-select select2" required>
                                <option value="EGP" {{ old('currency', 'EGP') == 'EGP' ? 'selected' : '' }}>EGP - جنيه مصري</option>
                                <option value="USD" {{ old('currency') == 'USD' ? 'selected' : '' }}>USD - دولار أمريكي</option>
                                <option value="SAR" {{ old('currency') == 'SAR' ? 'selected' : '' }}>SAR - ريال سعودي</option>
                                <option value="EUR" {{ old('currency') == 'EUR' ? 'selected' : '' }}>EUR - يورو</option>
                                <option value="AED" {{ old('currency') == 'AED' ? 'selected' : '' }}>AED - درهم إماراتي</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">{{ __('vendor_subscription.fields.status') }} <span class="text-danger">*</span> <x-inputs.tip :text="__('vendor_subscription.hints.status')" /></label>
                            <select name="status" class="form-select select2" required>
                                <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>{{ __('vendor_subscription.statuses.active') }}</option>
                                <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>{{ __('vendor_subscription.statuses.pending') }}</option>
                                <option value="expired" {{ old('status') == 'expired' ? 'selected' : '' }}>{{ __('vendor_subscription.statuses.expired') }}</option>
                                <option value="cancelled" {{ old('status') == 'cancelled' ? 'selected' : '' }}>{{ __('vendor_subscription.statuses.cancelled') }}</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">{{ __('vendor_subscription.fields.start_date') }} <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" class="form-control" required value="{{ old('start_date', date('Y-m-d')) }}" />
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">{{ __('vendor_subscription.fields.next_billing_date') }} <x-inputs.tip :text="__('vendor_subscription.hints.next_billing_date')" /></label>
                            <input type="date" name="next_billing_date" class="form-control" value="{{ old('next_billing_date') }}" />
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">{{ __('vendor_subscription.fields.attachment') }}</label>
                            <input type="file" name="attachment_file" class="form-control" accept=".pdf,.png,.jpg,.jpeg,.zip" />
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">{{ __('vendor_subscription.fields.notes') }}</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="ملاحظات وشروط الاشتراك وخدمات الدعم الفني...">{{ old('notes') }}</textarea>
                        </div>

                        <div class="col-md-12">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="auto_renew" class="form-check-input" id="auto_renew_switch" value="1" {{ old('auto_renew', 1) ? 'checked' : '' }} />
                                <label class="form-check-label" for="auto_renew_switch">{{ __('vendor_subscription.fields.auto_renew') }} <x-inputs.tip :text="__('vendor_subscription.hints.auto_renew')" /></label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="{{ route('admin.vendor-subscriptions.index') }}" class="btn btn-label-secondary waves-effect">{{ __('global.cancel') }}</a>
                        <button type="submit" class="btn btn-primary waves-effect waves-light">
                            <i class="fa-solid fa-check me-1"></i> {{ __('global.save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
