@extends('layouts.admin.app')
@section('title', __('notification.title'))

@section('content')

<div class="card mb-4 shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center border-bottom flex-wrap gap-2">
        <h5 class="card-title mb-0">
            <i class="fa-solid fa-bell text-primary me-2"></i> {{ __('notification.title') }}
            @if($unreadCount > 0)
                <span class="badge bg-danger ms-1">{{ $unreadCount }}</span>
            @endif
        </h5>

        @if($unreadCount > 0)
            <form action="{{ route('admin.notifications.read-all') }}" method="POST" class="d-inline" data-no-confirm>
                @csrf
                <button type="submit" class="btn btn-label-primary waves-effect">
                    <i class="fa-solid fa-check-double me-1"></i> {{ __('notification.mark_all') }}
                </button>
            </form>
        @endif
    </div>

    {{-- الفلاتر --}}
    <div class="card-body border-bottom pb-3">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label" for="filter_type">{{ __('notification.filters.type') }}</label>
                <select name="type" id="filter_type" class="form-select">
                    <option value="">{{ __('notification.filters.all') }}</option>
                    @foreach($types as $value => $label)
                        <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label" for="filter_status">{{ __('notification.filters.status') }}</label>
                <select name="status" id="filter_status" class="form-select">
                    <option value="">{{ __('notification.filters.all') }}</option>
                    <option value="unread" @selected(request('status') === 'unread')>{{ __('notification.filters.unread') }}</option>
                    <option value="read" @selected(request('status') === 'read')>{{ __('notification.filters.read') }}</option>
                </select>
            </div>

            <div class="col-md-5 d-flex gap-2">
                <button type="submit" class="btn btn-primary waves-effect waves-light">
                    <i class="fa-solid fa-filter me-1"></i> {{ __('activity.filters.apply') }}
                </button>
                <a href="{{ route('admin.notifications.index') }}" class="btn btn-label-secondary waves-effect">
                    <i class="fa-solid fa-rotate-left me-1"></i> {{ __('activity.filters.reset') }}
                </a>
            </div>
        </form>
    </div>

    <div class="card-body pt-4">
        @forelse($notifications as $notification)
            @php $payload = $notification->toPayload(); @endphp

            <div class="d-flex align-items-start gap-3 p-3 mb-2 border rounded
                        {{ $notification->isRead() ? '' : 'border-primary' }}"
                 style="{{ $notification->isRead() ? '' : 'background: rgba(115,103,240,0.04);' }}">

                <span class="badge bg-label-{{ $notification->level }} rounded p-3 flex-shrink-0">
                    <i class="{{ $payload['icon'] }}"></i>
                </span>

                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                        <h6 class="mb-1 fw-bold">
                            {{ $notification->title }}
                            @unless($notification->isRead())
                                <span class="badge bg-danger ms-1">{{ __('notification.unread') }}</span>
                            @endunless
                        </h6>
                        <small class="text-muted flex-shrink-0" dir="ltr">
                            {{ $notification->created_at?->format('Y-m-d H:i') }}
                        </small>
                    </div>

                    @if($notification->body)
                        <p class="mb-2 text-muted small">{{ $notification->body }}</p>
                    @endif

                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="badge {{ $notification->typeEnum()?->badge() ?? 'bg-label-secondary' }}">
                            {{ $notification->typeEnum()?->label() ?? $notification->type }}
                        </span>

                        @if($notification->causer_name)
                            <small class="text-muted">
                                <i class="fa-solid fa-user fa-xs me-1"></i>{{ $notification->causer_name }}
                            </small>
                        @endif

                        @if($notification->url)
                            <form action="{{ route('admin.notifications.read', $notification->id) }}" method="POST" class="d-inline" data-no-confirm>
                                @csrf
                                <button type="submit" class="btn btn-sm btn-label-primary waves-effect">
                                    <i class="fa-solid fa-arrow-left me-1"></i> {{ __('global.view') }}
                                </button>
                            </form>
                        @elseif(!$notification->isRead())
                            <form action="{{ route('admin.notifications.read', $notification->id) }}" method="POST" class="d-inline" data-no-confirm>
                                @csrf
                                <button type="submit" class="btn btn-sm btn-label-secondary waves-effect">
                                    {{ __('notification.mark_read') }}
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-5">
                <i class="fa-solid fa-bell-slash text-muted mb-3" style="font-size: 3rem; opacity: 0.3;"></i>
                <h6 class="text-muted">{{ __('notification.empty') }}</h6>
                <p class="small text-muted mb-0">{{ __('notification.empty_hint') }}</p>
            </div>
        @endforelse

        @if($notifications->hasPages())
            <div class="mt-4">{{ $notifications->links() }}</div>
        @endif
    </div>
</div>

@endsection
