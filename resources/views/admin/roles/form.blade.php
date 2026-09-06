{{--
    حقول الدور — مشتركة بين شاشتي الإضافة والتعديل.
    $role يكون null في حالة الإضافة.
--}}
@php
    $isEdit   = isset($role) && $role;
    $selected = old('permissions', $isEdit ? ($role->roles ?? []) : []);
@endphp

<div class="row">

    <div class="col-md-4 mb-4">
        <label for="name" class="form-label">
            {{ __('role.name') }} <span class="text-danger">*</span>
        </label>
        <input type="text" id="name" name="name" required maxlength="100"
               class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $isEdit ? $role->name : '') }}">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-12 mb-4">
        <label for="description" class="form-label">{{ __('role.description') }}</label>
        <textarea id="description"
                name="description"
                maxlength="255"
                rows="2"
                class="form-control @error('description') is-invalid @enderror"
                placeholder="{{ __('role.description_hint') }}">{{ old('description', $isEdit ? $role->description : '') }}</textarea>

        @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- مصفوفة الصلاحيات --}}
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
            <h6 class="fw-bold text-primary mb-0">
                <i class="fa-solid fa-key me-1"></i>{{ __('role.permissions') }}
                <span class="text-danger">*</span>
            </h6>
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" id="toggle_all_permissions">
                <label class="form-check-label small" for="toggle_all_permissions">
                    {{ __('global.select_all') }}
                </label>
            </div>
        </div>

        @error('permissions')
            <div class="alert alert-danger py-2">{{ $message }}</div>
        @enderror

        <div class="row g-4">
            @foreach ($groupedRoles as $groupKey => $permGroup)
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="border rounded p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold text-primary mb-0">{{ $permGroup['label'] }}</h6>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input group-toggle" type="checkbox"
                                       data-group="{{ $groupKey }}">
                            </div>
                        </div>

                        @foreach ($permGroup['permissions'] as $perm)
                            <div class="form-check">
                                <input class="form-check-input permission-check"
                                       type="checkbox"
                                       name="permissions[]"
                                       value="{{ $perm['value'] }}"
                                       id="perm_{{ $perm['value'] }}"
                                       data-group="{{ $groupKey }}"
                                       @checked(in_array($perm['value'], $selected, true))>
                                <label class="form-check-label" for="perm_{{ $perm['value'] }}">
                                    {{ $perm['label'] }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

</div>

@push('js')
<script>
    $(function () {
        // تحديد الكل
        $('#toggle_all_permissions').on('change', function () {
            $('.permission-check, .group-toggle').prop('checked', $(this).is(':checked'));
        });

        // تحديد كل صلاحيات مجموعة واحدة
        $('.group-toggle').on('change', function () {
            var group = $(this).data('group');
            $('.permission-check[data-group="' + group + '"]').prop('checked', $(this).is(':checked'));
        });

        // مزامنة مفاتيح المجموعات مع الحالة الحالية
        function syncGroupToggles() {
            $('.group-toggle').each(function () {
                var group = $(this).data('group');
                var boxes = $('.permission-check[data-group="' + group + '"]');
                $(this).prop('checked', boxes.length > 0 && boxes.filter(':checked').length === boxes.length);
            });

            var all = $('.permission-check');
            $('#toggle_all_permissions').prop('checked', all.length > 0 && all.filter(':checked').length === all.length);
        }

        $('.permission-check').on('change', syncGroupToggles);
        syncGroupToggles();
    });
</script>
@endpush
