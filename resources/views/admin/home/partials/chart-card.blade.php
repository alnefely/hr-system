{{--
    كارت رسم بياني موحّد: عنوان + شرح + شارة العملة + قائمة تغيير الشكل.
    القائمة كانت مكررة 4 مرات (10 أشكال لكل رسم).

    $target (trend/expenses/channels/branches — نفس اللي الـ JS بيستخدمه)
    $title, $icon, $chartId
    اختياري: $hint، $badgeId (شارة العملة اللي الـ JS بيحدّثها)
--}}
<div class="card h-100">
    <div class="card-header d-flex justify-content-between align-items-center gap-2">
        <h6 class="card-title mb-0 fw-bold text-truncate">
            <i class="fa-solid {{ $icon }} text-primary me-2"></i>{{ $title }}
            @if (! empty($hint))
                <x-inputs.tip :text="$hint" />
            @endif
        </h6>

        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            @if (! empty($badgeId))
                <span class="badge bg-label-secondary" id="{{ $badgeId }}">{{ __('dashboard.all_currencies') }}</span>
            @endif

            <div class="dropdown">
                <button class="btn btn-sm btn-icon btn-text-secondary rounded-pill dropdown-toggle hide-arrow chart-type-btn"
                        type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                        data-target="{{ $target }}" aria-expanded="false"
                        title="{{ __('dashboard.chart_types.change') }}">
                    <i class="fa-solid fa-ellipsis-vertical"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><h6 class="dropdown-header">{{ __('dashboard.chart_types.change') }}</h6></li>
                    @foreach ([
                        'line' => 'fa-chart-line', 'area' => 'fa-chart-area', 'column' => 'fa-chart-column',
                        'bar' => 'fa-chart-bar', 'donut' => 'fa-chart-pie', 'pie' => 'fa-circle-half-stroke',
                        'pieLabeled' => 'fa-share-nodes', 'radar' => 'fa-spider', 'treemap' => 'fa-table-cells-large',
                        'polarArea' => 'fa-bullseye',
                    ] as $type => $typeIcon)
                        <li>
                            <button type="button" class="dropdown-item chart-type-option d-flex align-items-center gap-2"
                                    data-target="{{ $target }}" data-type="{{ $type }}">
                                <i class="fa-solid {{ $typeIcon }} fa-fw"></i>
                                <span>{{ __('dashboard.chart_types.' . $type) }}</span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    <div class="card-body pt-2">
        <div id="{{ $chartId }}"></div>
    </div>
</div>
