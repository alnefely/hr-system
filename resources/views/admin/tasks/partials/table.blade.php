{{-- جدول المهام (DataTables) — شاشة المهام وشاشة تفاصيل المشروع. $url = رابط البيانات بالفلاتر. --}}
<x-dataTable.table
    :columns="[
        __('task.fields.title'),
        __('task.fields.assignees'),
        __('task.fields.priority'),
        __('task.fields.status'),
        __('task.fields.progress'),
        __('task.fields.due_at'),
        __('global.actions'),
    ]"
    :fields="[
        ['data' => 'title_html', 'name' => 'title'],
        ['data' => 'assignees_html', 'name' => 'assignees', 'orderable' => false, 'searchable' => false],
        ['data' => 'priority_badge', 'name' => 'priority', 'searchType' => 'select', 'searchOptions' => \App\Enums\TaskPriority::options()],
        ['data' => 'status_badge', 'name' => 'status', 'searchType' => 'select', 'searchOptions' => \App\Enums\TaskStatus::options()],
        ['data' => 'progress_html', 'name' => 'progress', 'searchable' => false],
        ['data' => 'due_html', 'name' => 'due_at', 'searchType' => 'date'],
        ['data' => 'actions', 'name' => 'actions', 'orderable' => false, 'searchable' => false],
    ]"
    :order="[]"
    :url="$url"
/>
