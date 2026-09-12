{{-- صفحة عامة — رابط منتهي أو موقوف. --}}
@include('links.partials.message', [
    'heading' => __('link.public.expired_title'),
    'text'    => $reason ?? __('link.public.expired_text'),
])
