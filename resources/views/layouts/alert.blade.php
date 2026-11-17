<link rel="stylesheet" href="/admin/vendor/libs/sweetalert2/sweetalert2.css" />
<script src="/admin/vendor/libs/sweetalert2/sweetalert2.js"></script>
@include('layouts.partials.confirm-submit')
<script>
    function sweetAlert2(icon, text, customClass) {
        return Swal.fire({
            icon: icon,
            text: text,
            confirmButtonText: "{{__('global.ok')}}",
            customClass: {
                confirmButton: `btn btn-${customClass}`
            },
            buttonsStyling: false
        });
    }

    // Global SweetAlert2 handler for any button/link with .btn-swal-confirm or data-confirm
    $(document).on('click', '.btn-swal-confirm, [data-confirm]', function (e) {
        e.preventDefault();
        let btn = $(this);
        let form = btn.closest('form');
        let title = btn.data('title') || btn.data('confirm-title') || 'تأكيد العملية';
        let text = btn.data('text') || btn.data('confirm') || 'هل أنت متأكد من الاستمرار؟';
        let icon = btn.data('icon') || 'warning';
        let btnText = btn.data('btn-text') || btn.data('confirm-btn') || 'نعم، تابع';
        let btnClass = btn.data('btn-class') || 'btn-primary';

        Swal.fire({
            title: title,
            text: text,
            icon: icon,
            showCancelButton: true,
            confirmButtonText: btnText,
            cancelButtonText: 'إلغاء',
            customClass: {
                confirmButton: 'btn ' + btnClass + ' me-2',
                cancelButton: 'btn btn-label-secondary'
            },
            buttonsStyling: false
        }).then(function (result) {
            if (result.isConfirmed) {
                if (form.length) {
                    form.submit();
                } else if (btn.is('a') && btn.attr('href') && btn.attr('href') !== '#' && btn.attr('href') !== 'javascript:void(0);') {
                    window.location.href = btn.attr('href');
                }
            }
        });
    });

    // ملاحظة: كان في هنا معترض بيحوّل تأكيدات المتصفح المدمجة لـ SweetAlert.
    // اتشال لأن كل الأزرار في المشروع بقت تستخدم btn-swal-confirm أو
    // data-confirm مباشرة — والمعترض كان بيسبب ظهور تنبيهين ورا بعض.
</script>

@if( session('error') )
    <script>
        sweetAlert2('error', "{{ session('error') }}", 'danger');
    </script>
@endif

@if( session('info') )
    <script>
        sweetAlert2('info', "{{ session('info') }}", 'info');
    </script>
@endif

@if( session('success') )
    <script>
        sweetAlert2('success', "{{ session('success') }}", 'success');
    </script>
@endif

@if( session('warning') )
    <script>
        sweetAlert2('warning', "{{ session('warning') }}", 'warning');
    </script>
@endif
