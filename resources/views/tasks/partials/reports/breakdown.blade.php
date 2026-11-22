{{-- المفتوح حسب الحالة والأولوية — أشرطة HTML بسيطة بالأرقام (مش رسم). $breakdown --}}
@php
    $groups = [
        ['open_by_status', $breakdown['status'], fn ($k) => \App\Enums\TaskStatus::tryFrom($k)?->label() ?? $k, ['new', 'in_progress', 'in_review']],
        ['open_by_priority', $breakdown['priority'], fn ($k) => \App\Enums\TaskPriority::tryFrom($k)?->label() ?? $k, ['urgent', 'high', 'medium', 'low']],
    ];
@endphp
<div class="card h-100">
    <div class="card-body">
        @foreach ($groups as [$title, $data, $label, $order])
            @php $max = max(1, max($data ?: [0])); @endphp
            <h6 class="fw-bold {{ $loop->first ? '' : 'mt-4' }}">{{ __('task.reports.' . $title) }}</h6>
            @foreach ($order as $key)
                @php $count = (int) ($data[$key] ?? 0); @endphp
                <div class="mb-2">
                    <div class="d-flex justify-content-between small mb-1"><span>{{ $label($key) }}</span><strong>{{ $count }}</strong></div>
                    <div class="task-progress"><div class="bar bg-primary" style="width: {{ round($count / $max * 100) }}%"></div></div>
                </div>
            @endforeach
        @endforeach
    </div>
</div>
