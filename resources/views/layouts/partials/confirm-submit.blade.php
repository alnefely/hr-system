{{--
    تأكيد قبل أي إرسال — لوحة الإدارة وبوابة الموظف.

    بيمسك كل فورم POST/PUT/PATCH/DELETE على مستوى الصفحة كلها (حفظ، إرسال
    طلب، اعتماد، رفض، حذف، إلغاء...) فأي شاشة جديدة بتاخده تلقائياً من غير
    ما حد يفتكر يضيفه.

    - الحذف (_method=DELETE) بيظهر كتأكيد حذف أحمر.
    - نص مخصص: data-confirm-title / data-confirm-text على الفورم أو الزرار،
      ولون الزرار: data-confirm-class (مثال: btn-danger).
    - استثناء فورم أو زرار: data-no-confirm (فورمات GET زي الفلاتر مستثناة أصلاً).

    الأزرار اللي عليها .btn-swal-confirm (تأكيد قديم بنص خاص) بتبعت بـ
    form.submit() اللي مابيطلقش حدث submit، فمفيش تأكيد مكرر.

    بيشتغل في مرحلة الـ capture قبل أي معالج تاني للفورم (زي توحيد أرقام
    الهاتف)، وبعد الموافقة بيعيد الإرسال بـ requestSubmit فالمعالجات دي
    بتشتغل عادي.
--}}
<script>
    (function () {
        if (window.__confirmSubmitBound) return;
        window.__confirmSubmitBound = true;

        const T = @json(__('confirm'));

        function attr(name, form, submitter) {
            return (submitter && submitter.getAttribute(name)) || form.getAttribute(name) || null;
        }

        function resubmit(form, submitter) {
            form.dataset.confirmed = '1';
            if (typeof form.requestSubmit === 'function') {
                // الزرار نفسه بيتبعت (لو ليه name/value) زي الضغطة الأصلية
                submitter && submitter.form === form ? form.requestSubmit(submitter) : form.requestSubmit();
            } else {
                form.submit();
            }
        }

        document.addEventListener('submit', function (e) {
            const form = e.target;
            if (!(form instanceof HTMLFormElement)) return;

            // الإرسال التاني بعد الموافقة
            if (form.dataset.confirmed === '1') {
                delete form.dataset.confirmed;
                return;
            }

            const method = (form.getAttribute('method') || 'get').toLowerCase();
            const submitter = e.submitter || null;

            if (method === 'get' || form.hasAttribute('data-no-confirm')
                || (submitter && submitter.hasAttribute('data-no-confirm'))
                || typeof Swal === 'undefined') {
                return;
            }

            e.preventDefault();
            e.stopImmediatePropagation();

            const spoofed = form.querySelector('input[name="_method"]');
            const isDelete = !!spoofed && spoofed.value.toUpperCase() === 'DELETE';

            const btnClass = attr('data-confirm-class', form, submitter) || (isDelete ? 'btn-danger' : 'btn-primary');

            Swal.fire({
                title: attr('data-confirm-title', form, submitter) || (isDelete ? T.delete_title : T.title),
                text: attr('data-confirm-text', form, submitter) || (isDelete ? T.delete_text : T.text),
                icon: isDelete ? 'warning' : 'question',
                showCancelButton: true,
                confirmButtonText: isDelete ? T.delete_button : T.button,
                cancelButtonText: T.cancel,
                reverseButtons: false,
                focusCancel: isDelete,
                // جوّه نافذة Bootstrap: التأكيد يتحط جوّاها عشان حبس التركيز بتاعها مايقفلش الأزرار
                target: form.closest('.modal') || 'body',
                customClass: {
                    confirmButton: 'btn ' + btnClass + ' me-2',
                    cancelButton: 'btn btn-label-secondary',
                },
                buttonsStyling: false,
            }).then(function (result) {
                if (result.isConfirmed) resubmit(form, submitter);
            });
        }, true);
    })();
</script>
