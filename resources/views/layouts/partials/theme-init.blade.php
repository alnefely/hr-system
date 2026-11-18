{{--
    تطبيق الوضع (ليلي / نهاري) المحفوظ قبل ما الصفحة تترسم.

    زرار تبديل الوضع (main.js) بيحفظ الاختيار في localStorage، لكن اللي
    بيطبّقه عند تحميل الصفحة هو template-customizer.js — ولوحة الإدارة بس
    اللي بتحمّله. من غيره البوابة كانت بترجع للنهاري مع كل تحديث.

    نفس المفتاح بتاع لوحة الإدارة (data-template واحد) فالاختيار مشترك بين
    اللوحتين. لازم ييجي بعد config.js وقبل أي CSS بيعتمد على الوضع يترسم.
--}}
<script>
    (function () {
        try {
            var root = document.documentElement;
            var theme = localStorage.getItem('templateCustomizer-' + root.getAttribute('data-template') + '--Theme');
            if (!theme) return;
            if (theme === 'system') {
                theme = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            if (theme === 'dark' || theme === 'light') {
                root.setAttribute('data-bs-theme', theme);
            }
        } catch (e) { /* localStorage مقفول (تصفح خاص) — يفضل الافتراضي */ }
    })();
</script>
