{{-- جدول أداء الموظفين. $employees من TaskMetricsService::employees، $title، $exportUrl (اختياري) --}}
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0 fw-bold"><i class="fa-solid fa-users text-primary me-2"></i>{{ $title }}</h6>
        @if (! empty($exportUrl))
            <a href="{{ $exportUrl }}" class="btn btn-sm btn-label-success"><i class="fa-solid fa-file-excel me-1"></i>{{ __('task.reports.export') }}</a>
        @endif
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>{{ __('task.reports.employee') }}</th>
                    <th class="text-center">{{ __('task.reports.open') }}</th>
                    <th class="text-center">{{ __('task.reports.overdue') }}</th>
                    <th class="text-center">{{ __('task.reports.completed') }}</th>
                    <th style="min-width: 9rem">{{ __('task.reports.on_time') }}</th>
                    <th class="text-center">{{ __('task.reports.time_logged') }}</th>
                    <th class="text-center">{{ __('task.reports.returned') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($employees as $row)
                    <tr>
                        <td class="fw-semibold text-nowrap">
                            @if (! empty($employeeLink))
                                <a href="{{ $employeeLink($row['employee']) }}">{{ $row['employee']->name }}</a>
                            @else
                                {{ $row['employee']->name }}
                            @endif
                        </td>
                        <td class="text-center">{{ $row['open'] }}</td>
                        <td class="text-center {{ $row['overdue'] ? 'text-danger fw-bold' : '' }}">{{ $row['overdue'] }}</td>
                        <td class="text-center">{{ $row['completed'] }}</td>
                        <td>
                            @if ($row['on_time_rate'] === null)
                                <span class="text-body-secondary">—</span>
                            @else
                                <div class="d-flex align-items-center gap-2">
                                    <div class="task-progress flex-grow-1"><div class="bar {{ $row['on_time_rate'] >= 80 ? 'bg-success' : ($row['on_time_rate'] >= 50 ? 'bg-warning' : 'bg-danger') }}" style="width: {{ $row['on_time_rate'] }}%"></div></div>
                                    <span class="small fw-semibold" dir="ltr">{{ $row['on_time_rate'] }}%</span>
                                </div>
                            @endif
                        </td>
                        <td class="text-center text-nowrap">{{ \App\Support\Duration::format($row['minutes']) }}</td>
                        <td class="text-center {{ $row['returned'] ? 'text-warning fw-semibold' : '' }}">{{ $row['returned'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-body-secondary py-4">{{ __('task.reports.no_data') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
