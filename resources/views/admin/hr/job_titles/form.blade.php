@php
    $isEdit = isset($jobTitle) && $jobTitle;
@endphp

<div class="row">
    <div class="col-md-8 mb-3">
        <label for="name" class="form-label">{{ __('job_title.fields.name') }} <span class="text-danger">*</span></label>
        <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $isEdit ? $jobTitle->name : '') }}" required maxlength="150"
               placeholder="مثال: مدير مشروع، محلل مالي، مصمم UI/UX">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-3 d-flex align-items-center mt-3">
        <div class="form-check form-switch">
            <input type="checkbox" id="is_active" name="is_active" value="1" class="form-check-input"
                   @checked(old('is_active', $isEdit ? $jobTitle->is_active : true))>
            <label class="form-check-label fw-bold" for="is_active">{{ __('job_title.fields.status') }}</label>
        </div>
    </div>

    <div class="col-md-12 mb-3">
        <label for="description" class="form-label">{{ __('job_title.fields.description') }}</label>
        <textarea id="description" name="description" rows="3" class="form-control @error('description') is-invalid @enderror"
                  placeholder="الوصف التفصيلي للمسؤوليات والمهام الأساسية للمنصب">{{ old('description', $isEdit ? $jobTitle->description : '') }}</textarea>
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="mt-3">
    <button type="submit" class="btn btn-primary me-2"><i class="fa-solid fa-save me-1"></i> {{ __('global.save') }}</button>
    <a href="{{ route('admin.job_titles') }}" class="btn btn-label-secondary">{{ __('global.cancel') }}</a>
</div>
