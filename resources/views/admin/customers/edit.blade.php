@extends('layouts.admin.app')
@section('title', __('customer.edit_title'))

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-xl-10">
        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center border-bottom flex-wrap gap-2">
                <h5 class="card-title mb-0">
                    <i class="fa-solid fa-user-pen text-primary me-2"></i> {{ __('customer.edit_title') }}: <span class="text-primary">{{ $customer->name }}</span>
                </h5>
                <a href="{{ route('admin.customers.index') }}" class="btn btn-label-secondary waves-effect">
                    <i class="fa-solid fa-arrow-right me-1"></i> {{ __('global.back') }}
                </a>
            </div>

            <form action="{{ route('admin.customers.update', $customer->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card-body pt-4">
                    {{-- القسم الأول: البيانات الأساسية --}}
                    <div class="divider divider-primary text-start mb-4">
                        <div class="divider-text fw-bold fs-6 text-primary">
                            <i class="fa-solid fa-id-card me-2"></i> البيانات الأساسية
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">{{ __('customer.fields.code') }} <span class="text-danger">*</span> <x-inputs.help :text="__('customer.hints.code')" /></label>
                            <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" 
                                   value="{{ old('code', $customer->code) }}" required dir="ltr" />
                            @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-bold">{{ __('customer.fields.name') }} <span class="text-danger">*</span> <x-inputs.help :text="__('customer.hints.name')" /></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" 
                                   value="{{ old('name', $customer->name) }}" required placeholder="اسم العميل أو الجهة..." />
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">{{ __('customer.fields.customer_type') }} <x-inputs.help :text="__('customer.hints.customer_type')" /></label>
                            <select name="customer_type" class="form-select select2">
                                <option value="company" {{ old('customer_type', $customer->customer_type) == 'company' ? 'selected' : '' }}>{{ __('customer.enums.company') }}</option>
                                <option value="individual" {{ old('customer_type', $customer->customer_type) == 'individual' ? 'selected' : '' }}>{{ __('customer.enums.individual') }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('customer.fields.company_name') }} <x-inputs.help :text="__('customer.hints.company_name')" /></label>
                            <input type="text" name="company_name" class="form-control @error('company_name') is-invalid @enderror" 
                                   value="{{ old('company_name', $customer->company_name) }}" placeholder="اسم المؤسسة أو الشركة التجارية..." />
                            @error('company_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('customer.fields.tax_number') }} <x-inputs.help :text="__('customer.hints.tax_number')" /></label>
                            <input type="text" name="tax_number" class="form-control @error('tax_number') is-invalid @enderror" 
                                   value="{{ old('tax_number', $customer->tax_number) }}" placeholder="الرقم الضريبي أو السجل التجاري..." dir="ltr" />
                            @error('tax_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    {{-- القسم الثاني: بيانات التواصل والمسؤول --}}
                    <div class="divider divider-info text-start mb-4">
                        <div class="divider-text fw-bold fs-6 text-info">
                            <i class="fa-solid fa-address-book me-2"></i> بيانات التواصل ومسؤول الاتصال
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">{{ __('customer.fields.contact_person') }} <x-inputs.help :text="__('customer.hints.contact_person')" /></label>
                            <input type="text" name="contact_person" class="form-control" 
                                   value="{{ old('contact_person', $customer->contact_person) }}" placeholder="اسم الشخص المسؤول للتواصل..." />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('customer.fields.contact_position') }} <x-inputs.help :text="__('customer.hints.contact_position')" /></label>
                            <input type="text" name="contact_position" class="form-control" 
                                   value="{{ old('contact_position', $customer->contact_position) }}" placeholder="المسمى الوظيفي (مدير مشتريات، مالك...)" />
                        </div>
                        <x-inputs.phone
                            name="phone"
                            :label="__('customer.fields.phone')"
                            :tooltip="__('customer.hints.phone')"
                            :value="$customer->phone"
                            col="4"
                        />
                        <x-inputs.phone
                            name="secondary_phone"
                            :label="__('customer.fields.secondary_phone')"
                            :tooltip="__('customer.hints.secondary_phone')"
                            :value="$customer->secondary_phone"
                            col="4"
                        />
                        <div class="col-md-4">
                            <label class="form-label">{{ __('customer.fields.email') }} <x-inputs.help :text="__('customer.hints.email')" /></label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" 
                                   value="{{ old('email', $customer->email) }}" placeholder="client@example.com" dir="ltr" />
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('customer.fields.website') }} <x-inputs.help :text="__('customer.hints.website')" /></label>
                            <input type="url" name="website" class="form-control" 
                                   value="{{ old('website', $customer->website) }}" placeholder="https://example.com" dir="ltr" />
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('customer.fields.address') }}</label>
                            <input type="text" name="address" class="form-control" 
                                   value="{{ old('address', $customer->address) }}" placeholder="العنوان التفصيلي (المدينة، الحي، الشارع)..." />
                        </div>
                    </div>

                    {{-- القسم الثالث: البيانات المالية والتجارية --}}
                    <div class="divider divider-success text-start mb-4">
                        <div class="divider-text fw-bold fs-6 text-success">
                            <i class="fa-solid fa-briefcase me-2"></i> الشروط التجارية والحد الائتماني
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label">
                                {{ __('customer.fields.source') }}
                                <x-inputs.help :text="__('customer.hints.source')" />
                            </label>
                            <select name="customer_source_id" class="form-select select2">
                                <option value="">{{ __('global.choose') }}</option>
                                @foreach ($sources as $source)
                                    <option value="{{ $source->id }}" @selected(old('customer_source_id', $customer->customer_source_id) == $source->id)>
                                        {{ $source->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('customer.fields.industry') }} <x-inputs.help :text="__('customer.hints.industry')" /></label>
                            <input type="text" name="industry" class="form-control" 
                                   value="{{ old('industry', $customer->industry) }}" placeholder="مثل: تقنية معلومات، عقارات، مقاولات..." />
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('customer.fields.credit_limit') }} <x-inputs.help :text="__('customer.hints.credit_limit')" /></label>
                            <input type="number" step="0.01" min="0" name="credit_limit" class="form-control" 
                                   value="{{ old('credit_limit', $customer->credit_limit ?? '0.00') }}" placeholder="0.00" dir="ltr" />
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('customer.fields.payment_terms') }} <x-inputs.help :text="__('customer.hints.payment_terms')" /></label>
                            <select name="payment_terms" class="form-select select2">
                                <option value="">{{ __('global.choose') }}...</option>
                                <option value="prepaid" {{ old('payment_terms', $customer->payment_terms) == 'prepaid' ? 'selected' : '' }}>{{ __('customer.enums.prepaid') }}</option>
                                <option value="on_delivery" {{ old('payment_terms', $customer->payment_terms) == 'on_delivery' ? 'selected' : '' }}>{{ __('customer.enums.on_delivery') }}</option>
                                <option value="net_30" {{ old('payment_terms', $customer->payment_terms) == 'net_30' ? 'selected' : '' }}>{{ __('customer.enums.net_30') }}</option>
                                <option value="net_60" {{ old('payment_terms', $customer->payment_terms) == 'net_60' ? 'selected' : '' }}>{{ __('customer.enums.net_60') }}</option>
                            </select>
                        </div>
                    </div>

                    {{-- القسم الرابع: الملاحظات والحالة --}}
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">{{ __('customer.fields.notes') }} <x-inputs.help :text="__('customer.hints.notes')" /></label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="ملاحظات إضافية أو تعليمات خاصة بالعميل...">{{ old('notes', $customer->notes) }}</textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch mt-2">
                                <input type="checkbox" name="is_active" class="form-check-input" id="cust_edit_is_active" value="1" {{ old('is_active', $customer->is_active ? '1' : '0') == '1' ? 'checked' : '' }} />
                                <label class="form-check-label fw-bold" for="cust_edit_is_active">{{ __('customer.fields.is_active') }} <x-inputs.help :text="__('customer.hints.is_active')" /></label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer border-top d-flex justify-content-between align-items-center">
                    <a href="{{ route('admin.customers.index') }}" class="btn btn-label-secondary waves-effect">
                        {{ __('global.cancel') }}
                    </a>
                    <button type="submit" class="btn btn-primary waves-effect waves-light px-4">
                        <i class="fa-solid fa-check me-1"></i> {{ __('global.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
