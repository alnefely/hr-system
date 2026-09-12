{{--
    صفحة عامة — بيشوفها أي حد معاه الرابط، من غير تسجيل دخول.
    فمابتمدّش layouts.admin.app لأنه بيفترض مشرف مسجّل.
--}}
@include('links.partials.message', [
    'heading' => __('link.public.not_found_title'),
    'text'    => __('link.public.not_found_text'),
])
