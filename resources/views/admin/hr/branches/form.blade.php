@php
    $isEdit = isset($branch) && $branch;
@endphp

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="name" class="form-label">{{ __('branch.fields.name') }} <span class="text-danger">*</span></label>
        <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $isEdit ? $branch->name : '') }}" required maxlength="150">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3 mb-3">
        <label for="code" class="form-label">{{ __('branch.fields.code') }} <x-inputs.tip :text="__('branch.hints.code')" /></label>
        <input type="text" id="code" name="code" class="form-control @error('code') is-invalid @enderror"
               value="{{ old('code', $isEdit ? $branch->code : '') }}" placeholder="EG-01">
        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3 mb-3">
        <label for="country" class="form-label">{{ __('branch.fields.country') }} <span class="text-danger">*</span></label>
        <select id="country" name="country" class="form-select select2 @error('country') is-invalid @enderror" required>
            <option value="">-- {{ __('global.select_all') }} --</option>
            @foreach (__('branch.countries') as $key => $countryName)
                <option value="{{ $key }}" @selected(old('country', $isEdit ? $branch->country : '') == $key)>
                    {{ $countryName }}
                </option>
            @endforeach
        </select>
        @error('country')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="city" class="form-label">{{ __('branch.fields.city') }}</label>
        <input type="text" id="city" name="city" class="form-control @error('city') is-invalid @enderror"
               value="{{ old('city', $isEdit ? $branch->city : '') }}" maxlength="100">
        @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="currency" class="form-label">{{ __('branch.fields.currency') }} <span class="text-danger">*</span> <x-inputs.tip :text="__('branch.hints.currency')" /></label>
        <input type="text" id="currency" name="currency" class="form-control @error('currency') is-invalid @enderror"
               value="{{ old('currency', $isEdit ? $branch->currency : 'EGP') }}" required maxlength="10">
        @error('currency')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="timezone" class="form-label">{{ __('branch.fields.timezone') }} <span class="text-danger">*</span> <x-inputs.tip :text="__('branch.hints.timezone')" /></label>
        <input type="text" id="timezone" name="timezone" class="form-control @error('timezone') is-invalid @enderror"
               value="{{ old('timezone', $isEdit ? $branch->timezone : 'Africa/Cairo') }}" required maxlength="50">
        @error('timezone')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="phone" class="form-label">{{ __('branch.fields.phone') }}</label>
        <input type="text" id="phone" name="phone" class="form-control @error('phone') is-invalid @enderror"
               value="{{ old('phone', $isEdit ? $branch->phone : '') }}" maxlength="50">
        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="email" class="form-label">{{ __('branch.fields.email') }}</label>
        <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror"
               value="{{ old('email', $isEdit ? $branch->email : '') }}" maxlength="100">
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-3 d-flex align-items-center mt-3">
        <div class="form-check form-switch">
            <input type="checkbox" id="is_active" name="is_active" value="1" class="form-check-input"
                   @checked(old('is_active', $isEdit ? $branch->is_active : true))>
            <label class="form-check-label fw-bold" for="is_active">{{ __('branch.fields.status') }} ({{ __('branch.fields.active') }}) <x-inputs.tip :text="__('branch.hints.is_active')" /></label>
        </div>
    </div>

    <div class="col-md-12 mb-3">
        <label for="address" class="form-label">{{ __('branch.fields.address') }}</label>
        <textarea id="address" name="address" rows="2" class="form-control @error('address') is-invalid @enderror"
                  maxlength="255">{{ old('address', $isEdit ? $branch->address : '') }}</textarea>
        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="mt-3">
    <button type="submit" class="btn btn-primary me-2"><i class="fa-solid fa-save me-1"></i> {{ __('global.save') }}</button>
    <a href="{{ route('admin.branches') }}" class="btn btn-label-secondary">{{ __('global.cancel') }}</a>
</div>

@push('js')
<script>
    $('#country').on('change', function () {
        var country = $(this).val();
        if (country === 'Saudi Arabia') {
            $('#currency').val('SAR');
            $('#timezone').val('Asia/Riyadh');
        } else if (country === 'Oman') {
            $('#currency').val('OMR');
            $('#timezone').val('Asia/Muscat');
        } else if (country === 'Egypt') {
            $('#currency').val('EGP');
            $('#timezone').val('Africa/Cairo');
        }
    });
</script>
@endpush
