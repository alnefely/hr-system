@props([
    'name',                                    // اسم الحقل (مطلوب)
    'id' => null,                              // معرف الحقل (اختياري - يستخدم الاسم افتراضياً)
    'value' => null,                           // القيمة الحالية
    'label' => null,                           // عنوان الحقل
    'type' => 'file',                          // نوع الملف: image, audio, file
    'required' => false,                       // هل الحقل مطلوب
    'disabled' => false,                       // هل الحقل معطل
    'buttonText' => null,                      // نص الزر (اختياري)
    'buttonIcon' => null,                      // أيقونة الزر (اختياري)
    'showClear' => true,                       // إظهار زر الحذف
    'showPreview' => true,                     // إظهار المعاينة
    'size' => 'normal',                        // حجم الحقل: normal, sm
    'placeholder' => '',                       // النص الافتراضي
    'helpText' => null,                        // نص مساعد
    'previewHeight' => '100px',                // ارتفاع المعاينة للصور
    'class' => '',                             // كلاسات إضافية للحاوية
    'inputClass' => '',                        // كلاسات إضافية للحقل
    'tooltip' => null,                         // نص المساعدة عند الهوفر
])

@php
    $inputId = $id ?? $name;
    $previewId = $inputId . '_preview';
    
    // First try to get old value (for form re-submission after validation error)
    // Then fallback to the passed value
    $oldValue = old($name);
    $currentValue = $oldValue !== null ? $oldValue : $value;
    
    // Trim any whitespace from the value
    $currentValue = $currentValue ? trim($currentValue) : null;
    
    $hasError = $errors->has($name);
    
    // تحديد الأيقونة والنص الافتراضي بناءً على النوع
    $defaultIcons = [
        'image' => 'fa-image',
        'audio' => 'fa-volume-up',
        'file' => 'fa-folder-open',
        'video' => 'fa-video',
        'pdf' => 'fa-file-pdf',
    ];
    
    $defaultTexts = [
        'image' => __('lfm.choose_image'),
        'audio' => __('lfm.choose_audio'),
        'file' => __('lfm.choose_file'),
        'video' => __('lfm.choose_video'),
        'pdf' => __('lfm.choose_pdf'),
    ];
    
    $icon = $buttonIcon ?? ($defaultIcons[$type] ?? 'fa-folder-open');
    $text = $buttonText ?? ($defaultTexts[$type] ?? __('lfm.choose_file'));
    
    $sizeClass = $size === 'sm' ? 'input-group-sm' : '';
    $btnSizeClass = $size === 'sm' ? 'btn-sm' : '';
@endphp

<div class="lfm-file-input {{ $class }}" {{ $attributes }}>
    @if($label)
        <label class="form-label" for="{{ $inputId }}">
            {{ $label }}
            @if($required)
                <span class="text-danger">*</span>
            @endif
            @if($tooltip)
                <i class="fa-solid fa-circle-question text-muted ms-1 cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $tooltip }}"></i>
            @endif
        </label>
    @endif
    
    <div class="input-group {{ $sizeClass }}">
        <input type="text" 
               name="{{ $name }}" 
               id="{{ $inputId }}" 
               class="form-control {{ $inputClass }} @error($name) is-invalid @enderror" 
               value="{{ $currentValue }}" 
               placeholder="{{ $placeholder }}"
               {{ $required ? 'required' : '' }}
               {{ $disabled ? 'disabled' : '' }}
               readonly>
        
        <button type="button" 
                class="btn btn-file-manager {{ $btnSizeClass }}" 
                data-input="{{ $inputId }}" 
                data-preview="{{ $showPreview ? $previewId : '' }}"
                {{ $disabled ? 'disabled' : '' }}>
            <i class="fas {{ $icon }}"></i>
            @if($size !== 'sm')
                {{ $text }}
            @endif
        </button>
        
        @if($showClear)
            <button type="button" 
                    class="btn btn-clear-file {{ $btnSizeClass }}" 
                    onclick="clearFileInput('{{ $inputId }}', '{{ $previewId }}')"
                    {{ $disabled ? 'disabled' : '' }}>
                <i class="fas fa-trash-alt"></i>
                {{-- @if($size !== 'sm')
                    {{ __('lfm.clear_file') }}
                @endif --}}
            </button>
        @endif
    </div>
    
    @if($showPreview)
        <div id="{{ $previewId }}" class="file-preview-container mt-2">
            @if($currentValue)
                @if($type === 'image')
                    <a href="{{ $currentValue }}" target="_blank">
                        <img src="{{ $currentValue }}" class="file-preview" style="max-height: {{ $previewHeight }};" alt="Preview">
                    </a>
                @elseif($type === 'audio')
                    <audio controls preload="none" class="audio-preview">
                        <source src="{{ $currentValue }}" type="audio/mpeg">
                        {{ __('lfm.audio_not_supported') }}
                    </audio>
                @else
                    <div class="alert alert-info py-1 px-2 mb-0" style="font-size: 0.85rem;">
                        <i class="fas fa-file me-2"></i>
                        <a href="{{ $currentValue }}" target="_blank">{{ basename($currentValue) }}</a>
                    </div>
                @endif
            @endif
        </div>
    @endif
    
    @if($helpText)
        <div class="form-text text-muted">{{ $helpText }}</div>
    @endif
    
    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>

@once
@push('css')
<style>
    .btn-file-manager {
        background: #6366f1;
        color: white;
        border: none;
    }
    .btn-file-manager:hover {
        background: #4f46e5;
        color: white;
    }
    .btn-file-manager:disabled {
        background: #9ca3af;
    }
    .btn-clear-file {
        background: #ef4444;
        color: white;
        border: none;
    }
    .btn-clear-file:hover {
        background: #dc2626;
        color: white;
    }
    .btn-clear-file:disabled {
        background: #fca5a5;
    }
    .file-preview {
        max-width: 200px;
        max-height: 150px;
        border-radius: 8px;
        border: 2px solid var(--bs-border-color, #e5e7eb);
    }
    .audio-preview {
        margin-top: 10px;
        width: 100%;
    }
</style>
@endpush

@push('js')
<script>
    // متغيرات عامة للـ File Manager
    var currentInputTarget = null;
    var currentPreviewTarget = null;
    
    // دالة استقبال الملف من File Manager
    function SetUrl(items) {
        if (!items || items.length === 0) return;
        
        const fileUrl = items[0].url;
        
        if (currentInputTarget) {
            currentInputTarget.value = fileUrl;
            
            if (currentPreviewTarget) {
                showFilePreview(fileUrl, currentPreviewTarget);
            }
            
            // إطلاق حدث change
            currentInputTarget.dispatchEvent(new Event('change', { bubbles: true }));
        }
        
        currentInputTarget = null;
        currentPreviewTarget = null;
    }
    
    // عرض معاينة الملف
    function showFilePreview(fileUrl, previewElement) {
        const fileExtension = fileUrl.split('.').pop().toLowerCase().trim();
        previewElement.innerHTML = '';
        
        const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'];
        const audioExtensions = ['mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac'];
        const videoExtensions = ['mp4', 'webm', 'ogg', 'avi', 'mkv'];
        const pdfExtensions = ['pdf'];
        const filesExtensions = ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', 'rar'];
        
        if (imageExtensions.includes(fileExtension)) {
            previewElement.innerHTML = `
                <img src="${fileUrl.trim()}" class="file-preview" style="max-height: 150px;" alt="Preview">
            `;
        } else if (audioExtensions.includes(fileExtension)) {
            previewElement.innerHTML = `
                <audio controls preload="none" class="audio-preview">
                    <source src="${fileUrl.trim()}" type="audio/mpeg">
                </audio>
            `;
        } else if (videoExtensions.includes(fileExtension)) {
            previewElement.innerHTML = `
                <video controls preload="none" class="audio-preview">
                    <source src="${fileUrl.trim()}" type="video/mp4">
                </video>
            `;
        } else if (pdfExtensions.includes(fileExtension)) {
            previewElement.innerHTML = `
                <div class="alert alert-info py-1 px-2 mb-0" style="font-size: 0.85rem;">
                    <i class="fas fa-file-pdf me-2"></i>
                    <a href="${fileUrl.trim()}" target="_blank">${fileUrl.split('/').pop()}</a>
                </div>
            `;
        } else if (filesExtensions.includes(fileExtension)) {
            const fileName = fileUrl.split('/').pop();
            previewElement.innerHTML = `
                <div class="alert alert-info py-1 px-2 mb-0" style="font-size: 0.85rem;">
                    <i class="fas fa-file me-2"></i>
                    <a href="${fileUrl.trim()}" target="_blank">${fileName}</a>
                </div>
            `;
        }
    }
    
    // حذف الملف من الحقل
    function clearFileInput(inputId, previewId) {
        const input = document.getElementById(inputId);
        const preview = document.getElementById(previewId);

        if(confirm("{{ __('lfm.confirm_clear') }}")) {
            if (input) {
                input.value = '';
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
            
            if (preview) {
                preview.innerHTML = '';
            }
        }
    }
    
    // تهيئة أزرار File Manager
    function initializeFileManager() {
        $(document).off('click', '.btn-file-manager');
        
        $(document).on('click', '.btn-file-manager', function(e) {
            e.preventDefault();
            
            const inputId = $(this).data('input');
            const previewId = $(this).data('preview');
            
            currentInputTarget = document.getElementById(inputId);
            currentPreviewTarget = previewId ? document.getElementById(previewId) : null;
            
            const isTeacher = window.location.pathname.includes('/teacher');
            const url = isTeacher ? '/teacher-filemanager?type=files' : '/filemanager?type=files';
            window.open(url, 'File Manager', 'width=900,height=600');

            console.log(`isTeacher: ${isTeacher} \nOpening URL: ${url}`);
            
            
            return false;
        });
    }
    
    // تهيئة عند تحميل الصفحة
    $(document).ready(function() {
        initializeFileManager();
    });
</script>
@endpush
@endonce
