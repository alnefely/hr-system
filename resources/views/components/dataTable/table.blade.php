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
        $jsColumns[] = ['data' => 'action', 'orderable' => false, 'searchable' => false];
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

            $actionItems .= "<li {$dataAttrs}><form action=\"{$baseUrl}/\${row.id}\" method=\"post\">{$csrf}{$method}<button onclick=\"return confirm('" . __('alert.confirm_delete') . "')\" class=\"dropdown-item btn-delete\"><i class=\"{$icon} fa-fw\"></i> {$label}</button></form></li>";
        } elseif ($type === 'custom') {
            // custom: يتوقع 'render' => JS string تحتوي على ${row.id}
            $actionItems .= $action['html'] ?? '';
        }
    }
@endphp

{{-- ================== HTML ================== --}}
<div class="table-responsive">
    <table id="{{ $tableId }}" class="datatables-basic table table-bordered table-hover table-striped0 {{ $class }}">
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
        <style>
/* وضع الـ Light */
        [data-bs-theme="light"] table tr:nth-child(even) {
            background-color: #f9f9f9 !important;
        }

        /* وضع الـ Dark */
        [data-bs-theme="dark"] table tr:nth-child(even) {
            background-color: #3a405a !important;
        }
        </style>
    @endpush

    {{-- js --}}
    @push('js')
        <script src="/admin/vendor/libs/datatables-bs5/datatables-bootstrap5.js"></script>
        <script src="/admin/vendor/libs/datatables-bs5/dtHelpers.js"></script>
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
                           data-bs-toggle="dropdown">
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
                this.api().columns().every(function () {
                    var that = this;
                    $('input, select', this.footer()).on('input change', dtDebounce(function () {
                        if (that.search() !== this.value) {
                            that.search(this.value).draw();
                        }
                    }, 400));
                });
            },
            @endif
        });

        @if($searchable)
        $(tableId + ' thead th').each(function (i, v) {
            var title = $(this).text().trim();
            var colOpts = columns[i] || {};
            
            if ($(this).hasClass('except') || colOpts.searchable === false) {
                $(tableId + ' tfoot tr').append('<th></th>');
            } else if (colOpts.searchType === 'select' && colOpts.searchOptions) {
                var selectHtml = '<select class="form-select form-control"><option value="">{{ __("global.all") }}</option>';
                $.each(colOpts.searchOptions, function(val, text) {
                    selectHtml += '<option value="' + val + '">' + text + '</option>';
                });
                selectHtml += '</select>';
                $(tableId + ' tfoot tr').append('<th>' + selectHtml + '</th>');
            } else if (colOpts.searchType === 'date') {
                $(tableId + ' tfoot tr').append(
                    '<th><input type="date" class="form-control" /></th>'
                );
            } else {
                $(tableId + ' tfoot tr').append(
                    '<th><input type="text" class="form-control form-control" placeholder="{{ __("global.search") }}: ' + title + '" /></th>'
                );
            }
        });
        @endif

        return table;
    });
})();
</script>
@endpush