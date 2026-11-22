{{-- التبديل بين القائمة واللوحة والتقويم بنفس الفلاتر. $routes, $current, $query --}}
@php
    $views = [
        'index'    => ['fa-list', __('task.views.list')],
        'board'    => ['fa-table-columns', __('task.views.board')],
        'calendar' => ['fa-calendar-days', __('task.views.calendar')],
    ];
@endphp
<div class="btn-group" role="group" aria-label="{{ __('task.views.title') }}">
    @foreach ($views as $name => [$icon, $label])
        @if (\Illuminate\Support\Facades\Route::has($routes . '.' . $name) && ($name !== 'board' || ($canBoard ?? true)))
            <a href="{{ route($routes . '.' . $name, $query ?? []) }}" class="btn {{ $current === $name ? 'btn-primary' : 'btn-label-primary' }}"
               @if ($current === $name) aria-current="page" @endif>
                <i class="fa-solid {{ $icon }}"></i><span class="d-none d-md-inline ms-1">{{ $label }}</span>
            </a>
        @endif
    @endforeach
</div>
