{{--
    Select2 لكل القوائم — مشترك بين لوحة الإدارة وبوابة الموظف.
    يتضمّن بعد jQuery. أي <select class="form-select"> بيتحوّل تلقائي
    إلا لو عليه .no-select2 (زي الصفوف اللي بتتنسخ بالـ JS).

    تنبيه: Select2 بيبعت change عن طريق jQuery بس، فأي سكريبت محتاج
    يسمع تغيير القائمة يستخدم $(el).on('change', ...) مش addEventListener.
    الـ onchange المكتوب inline بيشتغل عادي.
--}}
<script src="/admin/vendor/libs/select2/select2.js"></script>
<script>
  window.initSelect2 = function (context) {
      let $ctx = context ? $(context) : $(document);
      $ctx.find('select.select2, select.form-select:not(.no-select2):not(.dt-select2)').each(function () {
          let $el = $(this);
          if ($el.hasClass('select2-hidden-accessible')) {
              return;
          }
          let $modal = $el.closest('.modal');
          let placeholderText = $el.data('placeholder') || $el.find('option[value=""]').first().text() || 'اختر...';

          let config = {
              dir: $('html').attr('dir') === 'rtl' ? 'rtl' : 'ltr',
              width: '100%',
              placeholder: placeholderText,
              allowClear: !$el.prop('required')
          };

          if ($modal.length) {
              config.dropdownParent = $modal;
          }

          $el.select2(config);
      });
  };

  $(document).ready(function () {
      window.initSelect2();
  });

  // القوائم اللي جوّه modal بتتفعّل لما يفتح (عشان القائمة تفتح جوّاه)
  $(document).on('shown.bs.modal', function (e) {
      window.initSelect2(e.target);
  });
</script>
