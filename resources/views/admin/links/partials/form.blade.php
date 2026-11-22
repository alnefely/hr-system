{{--
    حقول الرابط — مشتركة بين الإنشاء والتعديل.
    $link موجود في التعديل بس.
--}}
@php
    $link = $link ?? null;

    // في الإنشاء بنعرض كود مولّد جاهز؛ في التعديل الكود الحالي.
    $codeValue = old('short_code', $link?->short_code ?? ($suggestedCode ?? ''));

    $codeMin = \App\Services\Links\ShortCodeGenerator::MIN_LENGTH;
    $codeMax = \App\Services\Links\ShortCodeGenerator::maxLength();
@endphp

<div class="row g-3 mb-4">

    <div class="col-md-12">
        <x-inputs.field
            name="original_url"
            type="url"
            dir="ltr"
            label="{{ __('link.fields.original_url') }}"
            :value="$link?->original_url"
            :hint="__('link.hints.original_url')"
            placeholder="https://example.com/page"
            required
        />
    </div>

    <div class="col-md-6">
        <label for="short_code" class="form-label">
            {{ __('link.fields.short_code') }}
            <x-inputs.help :text="__('link.hints.short_code')" />
        </label>

        {{-- المجموعة كلها ltr: البادئة فالحقل فالزر بترتيب القراءة
             الطبيعي للرابط. لو سابناها ترث RTL بتتبعتر. --}}
        <div class="input-group" dir="ltr">
            <span class="input-group-text text-muted">/{{ config('links.path_prefix', 's') }}/</span>

            <input type="text" name="short_code" id="short_code"
                   class="form-control font-monospace @error('short_code') is-invalid @enderror"
                   value="{{ $codeValue }}"
                   minlength="{{ $codeMin }}"
                   maxlength="{{ $codeMax }}"
                   pattern="[A-Za-z0-9_-]{{ '{' }}{{ $codeMin }},{{ $codeMax }}{{ '}' }}"
                   autocomplete="off"
                   spellcheck="false"
                   placeholder="{{ $codeValue ?: 'my-code' }}">

            @if (! $link)
                <button type="button" class="btn btn-outline-secondary" id="regenerateCode"
                        data-bs-toggle="tooltip" title="{{ __('link.actions.regenerate') }}" aria-label="{{ __('link.actions.regenerate') }}">
                    <i class="fa-solid fa-rotate"></i>
                </button>
            @endif
        </div>

        <small class="text-muted d-block mt-1">
            {{ __('link.hints.code_length', ['min' => $codeMin, 'max' => $codeMax]) }}
        </small>

        @error('short_code')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <x-inputs.field
            name="title"
            label="{{ __('link.fields.title') }}"
            :value="$link?->title"
        />
    </div>

    <div class="col-md-4">
        <x-inputs.select2
            name="branch_id"
            label="{{ __('link.fields.branch') }}"
            :data="$branches"
            data_key="name"
            data_value="id"
            :value="old('branch_id', $link?->branch_id)"
            :placeholder="__('link.filters.all_branches')"
        />
    </div>

    <div class="col-md-4">
        <label class="form-label">
            {{ __('link.fields.expires_at') }}
            <x-inputs.help :text="__('link.hints.expires_at')" />
        </label>
        <input type="date" name="expires_at" class="form-control @error('expires_at') is-invalid @enderror"
               value="{{ old('expires_at', $link?->expires_at?->format('Y-m-d')) }}" />
        @error('expires_at')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">
            {{ __('link.fields.max_clicks') }}
            <x-inputs.help :text="__('link.hints.max_clicks')" />
        </label>
        <input type="number" name="max_clicks" min="1" dir="ltr"
               class="form-control @error('max_clicks') is-invalid @enderror"
               value="{{ old('max_clicks', $link?->max_clicks) }}" />
        @error('max_clicks')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-12">
        <x-inputs.field
            name="notes"
            type="textarea"
            :rows="3"
            label="{{ __('link.fields.notes') }}"
            :value="$link?->notes"
        />
    </div>

    <div class="col-md-12">
        {{-- hidden قبل الـ checkbox: من غيره الـ checkbox المرفوع
             مابيتبعتش أصلاً فالحقل يفضل على قيمته القديمة --}}
        <input type="hidden" name="is_active" value="0">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1"
                   {{ old('is_active', $link?->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">{{ __('link.status.active') }}</label>
        </div>
    </div>

</div>
