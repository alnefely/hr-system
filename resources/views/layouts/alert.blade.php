<link rel="stylesheet" href="/admin/vendor/libs/sweetalert2/sweetalert2.css" />
<script src="/admin/vendor/libs/sweetalert2/sweetalert2.js"></script>
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

@if( session('question') )
    <script>
        sweetAlert2('question', "{{ session('question') }}", 'secondary');
    </script>
@endif
