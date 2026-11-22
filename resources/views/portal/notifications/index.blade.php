@extends('layouts.portal.app')
@section('title', __('notification.title'))

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom">
        <h5 class="mb-0 fw-bold"><i class="fa-solid fa-bell text-primary me-2"></i>{{ __('notification.title') }}
            @if ($unreadCount)<span class="badge bg-danger ms-1">{{ $unreadCount }}</span>@endif
        </h5>
        <div class="d-flex gap-2 flex-wrap">
            <ul class="nav nav-pills gap-1">
                <li class="nav-item"><a class="nav-link {{ request('status') !== 'unread' ? 'active' : '' }}" href="{{ route('portal.notifications.index') }}">{{ __('global.all') }}</a></li>
                <li class="nav-item"><a class="nav-link {{ request('status') === 'unread' ? 'active' : '' }}" href="{{ route('portal.notifications.index', ['status' => 'unread']) }}">{{ __('notification.unread') }}</a></li>
            </ul>
            @if ($unreadCount)
                <form action="{{ route('portal.notifications.read-all') }}" method="POST" data-no-confirm>
                    @csrf
                    <button type="submit" class="btn btn-label-primary"><i class="fa-solid fa-check-double me-1"></i>{{ __('notification.mark_all') }}</button>
                </form>
            @endif
        </div>
    </div>
    <div class="list-group list-group-flush">
        @forelse ($notifications as $n)
            <form action="{{ route('portal.notifications.read', $n->id) }}" method="POST" class="m-0" data-no-confirm>
                @csrf
                <button type="submit" class="list-group-item list-group-item-action d-flex align-items-start gap-3 py-3 text-start w-100 border-0 border-bottom {{ $n->isRead() ? '' : 'bg-label-primary bg-opacity-10' }}">
                    <span class="badge bg-label-{{ $n->level }} rounded p-2 flex-shrink-0"><i class="{{ $n->icon ?: 'fa-solid fa-bell' }}"></i></span>
                    <span class="flex-grow-1" style="min-width: 0">
                        <span class="d-block fw-semibold">{{ $n->title }}</span>
                        @if ($n->body)<span class="d-block small text-body-secondary text-break">{{ $n->body }}</span>@endif
                        <span class="d-block small text-body-secondary mt-1">{{ $n->created_at->diffForHumans() }}</span>
                    </span>
                    @unless ($n->isRead())<span class="badge badge-dot bg-primary mt-2"></span>@endunless
                </button>
            </form>
        @empty
            <div class="text-center text-body-secondary py-5">
                <i class="fa-solid fa-bell-slash fa-2x mb-2 opacity-50"></i>
                <div class="fw-semibold">{{ __('notification.empty') }}</div>
            </div>
        @endforelse
    </div>
    @if ($notifications->hasPages())
        <div class="card-footer d-flex justify-content-center">{{ $notifications->links() }}</div>
    @endif
</div>
@endsection
