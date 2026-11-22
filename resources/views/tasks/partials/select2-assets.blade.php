{{--
    Select2 للبوابة (لوحة الإدارة بتحمّله وتشغّله تلقائياً في قالبها).
--}}
@once
@push('css')
    <link rel="stylesheet" href="/admin/vendor/libs/select2/select2.css" />
@endpush
@push('js')
    <script src="/admin/vendor/libs/select2/select2.js"></script>
    <script>
        $(function () {
            $('select.select2').each(function () {
                const $el = $(this);
                const parent = $el.data('dropdown-parent');
                $el.wrap('<div class="position-relative"></div>').select2({
                    dir: document.documentElement.getAttribute('dir') || 'rtl',
                    width: '100%',
                    placeholder: $el.data('placeholder') || '',
                    allowClear: !$el.prop('multiple') && !$el.prop('required'),
                    dropdownParent: parent ? $(parent) : $el.parent(),
                });
            });
        });
    </script>
@endpush
@endonce
