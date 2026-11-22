@props([
    'id'         => 'datatable-' . uniqid(),   // ID فريد للجدول
    'columns'    => [],                          // أعمدة الجدول (headers)
    'fields'     => [],                          // fields البيانات المقابلة
    'actions'    => [],                          // روابط الإجراءات: [view, edit, delete] أو custom
    'url'        => null,                        // Ajax URL (null = window.location.href)
    'serverSide' => true,                       // Server-side processing
    'order'      => [[0, 'DESC']],              // ترتيب افتراضي
    'addNew'     => null,                        // نص زر إضافة جديد
    'addNewUrl'  => null,                        // رابط زر إضافة جديد
    'exportable' => true,                        // إظهار أزرار التصدير
    'searchable' => true,                        // إظهار بحث في footer
    'exportCols' => null,                        // عمود export (null = كل الأعمدة)
    'extra'      => [],                          // columnDefs إضافية
    'class'      => '',                          // class إضافية للجدول
])

@php
    $tableId = $id;
    $ajaxUrl = $url ?? 'window.location.href';
    $ajaxUrlJs = $url ? "'{$url}'" : 'window.location.href';

    // بناء columns JS
    $jsColumns = [];
    foreach ($fields as $field) {
        if (is_array($field)) {
            $jsColumns[] = $field; // مرّر كـ-is
        } else {
            $jsColumns[] = ['data' => $field];
        }
    }

    // هل في عمود action؟
    $hasActions = !empty($actions);
    if ($hasActions) {
        $jsColumns[] = ['data' => 'action', 'defaultContent' => '', 'orderable' => false, 'searchable' => false];
    }

    // بناء action HTML
    $actionItems = '';
    foreach ($actions as $action) {
        $type = $action['type'] ?? 'custom';

        if ($type === 'view') {
            $href   = $action['url'] ?? '#';
            $label  = $action['label'] ?? __('global.view');
            $icon   = $action['icon'] ?? 'fa-regular fa-eye';
            $dataAttrs = '';
            foreach ($action['data'] ?? [] as $attr => $field) {
                $dataAttrs .= " data-{$attr}=\"\${row.{$field}}\"";
            }
            $actionItems .= "<li><a {$dataAttrs} href=\"{$href}/\${row.id}\" class=\"dropdown-item\"><i class=\"{$icon} fa-fw\"></i> {$label}</a></li>";
        } elseif ($type === 'edit') {
            $baseUrl = $action['url'] ?? '';
            $label   = $action['label'] ?? __('global.edit');
            $icon    = $action['icon'] ?? 'fa-regular fa-edit';
            $dataAttrs = '';
            foreach ($action['data'] ?? [] as $attr => $field) {
                $dataAttrs .= " data-{$attr}=\"\${row.{$field}}\"";
            }
            $actionItems .= "<li><a {$dataAttrs} href=\"{$baseUrl}/\${row.id}/edit\" class=\"dropdown-item\"><i class=\"{$icon} fa-fw\"></i> {$label}</a></li>";
        } elseif ($type === 'delete') {
            $baseUrl = $action['url'] ?? '';
            $label   = $action['label'] ?? __('global.delete');
            $icon    = $action['icon'] ?? 'fa-solid fa-trash';
            $csrf    = csrf_field();
            $method  = method_field('DELETE');
            $dataAttrs = '';
            foreach ($action['data'] ?? [] as $attr => $field) {
                $dataAttrs .= " data-{$attr}=\"\${row.{$field}}\"";
            }

            $actionItems .= "<li {$dataAttrs}><form action=\"{$baseUrl}/\${row.id}\" method=\"post\">{$csrf}{$method}<button type=\"button\" class=\"dropdown-item btn-delete\"><i class=\"{$icon} fa-fw\"></i> {$label}</button></form></li>";
        } elseif ($type === 'custom') {
            // custom: يتوقع 'render' => JS string تحتوي على ${row.id}
            $actionItems .= $action['html'] ?? '';
        }
    }
@endphp

{{-- ================== HTML ================== --}}
{{-- الجدول بيعمل scroll أفقي جوّه الكارت (dtDom بيلفّه في .dt-scroll-x) بدل ما الصفحة كلها تفيض --}}
<div class="dt-card-wrapper">
    <table id="{{ $tableId }}" class="datatables-basic table table-bordered table-hover table-striped {{ $class }}">
        <thead>
            <tr>
                @foreach ($columns as $col)
                    @if(is_array($col))
                        <th class="{{ $col['class'] ?? '' }}">{{ $col['label'] }}</th>
                    @else
                        <th>{{ $col }}</th>
                    @endif
                @endforeach
                @if($hasActions)
                    <th class="except">{{ __('global.actions') }}</th>
                @endif
            </tr>
        </thead>
        <tfoot><tr></tr></tfoot>
    </table>
</div>

@once
    @push('css')
        {{-- CSS --}}
        <link rel="stylesheet" href="/admin/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
        <link rel="stylesheet" href="/admin/vendor/libs/select2/select2.css" />
        <style>
        /* الصفوف المتبادلة بتعتمد على متغيّر بوتستراب فبتتظبط لوحدها في الوضع الداكن */
        .dt-card-wrapper { max-width: 100%; min-width: 0; }
        /* position: relative عشان العناصر الـ absolute جوّه الجدول (أيقونات الترتيب) تتقص جوّه الـ scroll بدل ما توسّع الصفحة */
        .dt-card-wrapper .dt-scroll-x { position: relative; overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .dt-card-wrapper .dt-scroll-x > table.dataTable { margin-top: 0 !important; margin-bottom: 0 !important; }
        /* بحث الأعمدة في الـ footer مالوش مكان على الموبايل — البحث العام كفاية */
        @media (max-width: 767.98px) {
            .dt-card-wrapper table.dataTable tfoot { display: none; }
        }

        /* Select2 Dropdown Z-Index & Table Footer Fixes */
        .select2-container--open,
        .select2-dropdown {
            z-index: 99999 !important;
        }
        table.dataTable tfoot th {
            vertical-align: middle !important;
            padding: 6px !important;
        }
        table.dataTable tfoot th .select2-container {
            width: 100% !important;
            min-width: 130px !important;
        }
        </style>
    @endpush

    {{-- js --}}
    @push('js')
        <script src="/admin/vendor/libs/datatables-bs5/datatables-bootstrap5.js"></script>
        <script src="/admin/vendor/libs/datatables-bs5/dtHelpers.js"></script>
        <script src="/admin/vendor/libs/select2/select2.js"></script>
    @endpush
@endonce

@push('js')
{{-- ================== JS ================== --}}
<script>
(function () {
    var tableId = '#{{ $tableId }}';

    var columns = {!! json_encode($jsColumns, JSON_UNESCAPED_UNICODE) !!};

    var columnDefs = [
        @if($hasActions)
        {
            targets: columns.length - 1,
            orderable: false,
            searchable: false,
            render: function (data, type, row) {
                return `
                    <div class="d-inline-block">
                        <a href="javascript:;" class="btn btn-sm btn-icon dropdown-toggle hide-arrow"
                           data-bs-toggle="dropdown" role="button" aria-expanded="false"
                           aria-label="{{ __('global.actions') }}" title="{{ __('global.actions') }}">
                            <i class="icon-base ti tabler-dots-vertical icon-22px"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end m-0">
                            {!! $actionItems !!}
                        </ul>
                    </div>`;
            }
        },
        @endif
        @foreach ($extra as $def)
        {
            @foreach ($def as $key => $value)
                {!! $key !!}: {!! is_string($value) && str_starts_with(trim($value), 'function') ? $value : json_encode($value, JSON_UNESCAPED_UNICODE) !!},
            @endforeach
        },
        @endforeach
    ];

    $(document).ready(function () {

        @if($searchable)
        var $tfootTr = $(tableId + ' tfoot tr');
        $tfootTr.empty();
        $(tableId + ' thead th').each(function (i, v) {
            var title = $(this).text().trim();
            var colOpts = columns[i] || {};
            
            if ($(this).hasClass('except') || colOpts.searchable === false) {
                $tfootTr.append('<th></th>');
            } else if (colOpts.searchType === 'select' && colOpts.searchOptions) {
                var selectHtml = '<select class="form-select dt-select2" style="width: 100%;"><option value="">{{ __("global.all") }}</option>';
                $.each(colOpts.searchOptions, function(val, text) {
                    selectHtml += '<option value="' + val + '">' + text + '</option>';
                });
                selectHtml += '</select>';
                $tfootTr.append('<th style="min-width: 130px;">' + selectHtml + '</th>');
            } else if (colOpts.searchType === 'date') {
                $tfootTr.append(
                    '<th><input type="date" class="form-control" /></th>'
                );
            } else {
                $tfootTr.append(
                    '<th><input type="text" class="form-control" placeholder="{{ __("global.search") }}: ' + title + '" /></th>'
                );
            }
        });
        @endif

        var table = $(tableId).DataTable({
            processing: true,
            serverSide: {{ $serverSide ? 'true' : 'false' }},
            orderCellsTop: true,
            order: {!! json_encode($order) !!},
            lengthMenu: dtLengthMenu(),
            language:   dtLang(),
            dom:        dtDom(),
            buttons:    dtButtons(
                @if($addNew) "{{ $addNew }}", "{{ $addNewUrl }}" @else "", "" @endif,
                {{ $exportable ? 'true' : 'false' }}
            ),
            ajax: {!! $ajaxUrlJs !!},
            columns: columns,
            columnDefs: columnDefs,

            @if($searchable)
            initComplete: function () {
                var api = this.api();
                api.columns().every(function () {
                    var that = this;
                    $('input:not(.select2-search__field), select', this.footer()).on('input change', dtDebounce(function () {
                        if (that.search() !== this.value) {
                            that.search(this.value).draw();
                        }
                    }, 400));
                });

                if ($.fn.select2) {
                    $(tableId + ' tfoot select.dt-select2').select2({
                        dir: $('html').attr('dir') || 'rtl',
                        dropdownAutoWidth: false,
                        width: '100%'
                    }).on('change', function () {
                        var colIdx = $(this).closest('th').index();
                        var val = $(this).val();
                        api.column(colIdx).search(val).draw();
                    });
                }
            },
            @endif
        });

        // SweetAlert2 Confirmation for delete actions in table
        $(document).on('click', tableId + ' .btn-delete', function (e) {
            e.preventDefault();
            var form = $(this).closest('form');

            // SweetAlert متحمّلة دايماً من layouts/alert.blade.php، فمفيش
            // خطة بديلة بـ confirm() المدمج — وجودها كان بيسبب تنبيهين.
            Swal.fire({
                    title: "{{ __('global.confirm_delete') ?? 'تأكيد الحذف' }}",
                    text: "{{ __('alert.confirm_delete') ?? 'هل أنت متأكد من رغبتك في حذف هذا السجل؟' }}",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: "{{ __('global.yes_delete') ?? 'نعم، احذف' }}",
                    cancelButtonText: "{{ __('global.cancel') ?? 'إلغاء' }}",
                    customClass: {
                        confirmButton: 'btn btn-danger me-2',
                        cancelButton: 'btn btn-label-secondary'
                    },
                    buttonsStyling: false
            }).then(function (result) {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });

        return table;
    });
})();
</script>
@endpush