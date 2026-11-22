/**
 * DataTable Helpers — dtHelpers.js
 * شارك الملف دا في layout الرئيسي مرة واحدة بس
 */

function dtDebounce(fn, delay) {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => fn.apply(this, args), delay);
    };
}

function dtLengthMenu() {
    return [
        [10, 20, 30, 50, 100, -1],
        [10, 20, 30, 50, 100, dtAllText()]
    ];
}

function dtAllText() {
    // غيّر النص حسب لغة الموقع
    var lang = document.documentElement.getAttribute('lang');
    return lang === 'ar' ? 'الكل' : 'All';
}

function dtLang() {
    var lang = document.documentElement.getAttribute('lang');
    if (lang === 'ar') {
        return { url: '/admin/vendor/libs/datatables-bs5/Arabic.json' };
    }
    return null;
}

function dtDom() {
    // الصفوف بـ g-2 (من غير me-2/mx-2 اللي كانت بتفيض)، والأدوات بتنزل تحت بعض
    // على الموبايل. الجدول نفسه ملفوف في .dt-scroll-x فبيعمل scroll جوّه الكارت.
    return '<"row g-2 align-items-center mb-3"' +
        '<"col-12 col-md-3"l>' +
        '<"col-12 col-md-9"<"dt-action-buttons d-flex flex-wrap align-items-center justify-content-start justify-content-md-end gap-2"fB>>' +
        '>' +
        '<"dt-scroll-x"t>' +
        '<"row g-2 align-items-center mt-2"' +
        '<"col-12 col-md-6"i>' +
        '<"col-12 col-md-6"p>' +
        '>';
}

/**
 * @param {string}  title       - نص زر الإضافة (فاضي = مفيش زر)
 * @param {string}  href        - رابط زر الإضافة
 * @param {boolean} exportable  - إظهار أزرار التصدير
 */
function dtButtons(title, href, exportable) {
    var dir  = document.documentElement.getAttribute('dir') || 'ltr';
    var lang = document.documentElement.getAttribute('lang');

    var labels = {
        export : lang === 'ar' ? 'تصدير'    : 'Export',
        print  : lang === 'ar' ? 'طباعة'    : 'Print',
        csv    : lang === 'ar' ? 'CSV'       : 'CSV',
        excel  : lang === 'ar' ? 'Excel'     : 'Excel',
        copy   : lang === 'ar' ? 'نسخ'      : 'Copy',
    };

    var addBtn = title
        ? {
            text: `<i class="fa-solid fa-plus me-0 me-sm-1 ti-xs"></i><span class="d-none d-sm-inline-block">${title}</span>`,
            className: 'btn-custom',
            attr: { onclick: `location.href="${href}"` }
          }
        : null;

    var exportBtn = exportable
        ? {
            extend: 'collection',
            className: 'btn btn-label-info dropdown-toggle',
            text: `<i class='fa-solid fa-download me-1 ti-xs'></i> ${labels.export}`,
            buttons: [
                {
                    extend: 'print',
                    text: `<i class="fa-solid fa-print me-2 fa-fw"></i> ${labels.print}`,
                    className: 'dropdown-item',
                    exportOptions: { columns: ':visible(.exp-col)' },
                    customize: function (win) {
                        $(win.document.body)
                            .attr('dir', dir)
                            .find('table')
                            .removeClass('table-bordered table-hover')
                            .find('tr td:last-child')
                            .addClass('text-nowrap');
                    }
                },
                {
                    extend: 'csv',
                    charset: 'utf-8', fieldSeparator: ';', bom: true,
                    text: `<i class="fa-solid fa-file-csv me-2 fa-fw"></i> ${labels.csv}`,
                    className: 'dropdown-item',
                    exportOptions: { columns: ':visible(.exp-col)' },
                },
                {
                    extend: 'excel',
                    charset: 'utf-8', fieldSeparator: ';', bom: true,
                    text: `<i class="fa-solid fa-table me-2 fa-fw"></i> ${labels.excel}`,
                    className: 'dropdown-item',
                    exportOptions: { columns: ':visible(.exp-col)' },
                },
                {
                    extend: 'copy',
                    text: `<i class="fa-solid fa-copy me-2 fa-fw"></i> ${labels.copy}`,
                    className: 'dropdown-item',
                    exportOptions: { columns: ':visible(.exp-col)' },
                }
            ]
          }
        : null;

    return [exportBtn, addBtn].filter(Boolean);
}
