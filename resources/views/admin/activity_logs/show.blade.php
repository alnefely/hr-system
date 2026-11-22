@extends('layouts.admin.app')
@section('title', __('activity.details.title'))

@section('content')

<div class="card mb-4 shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center border-bottom flex-wrap gap-2">
        <h5 class="card-title mb-0">
            <i class="fa-solid fa-clock-rotate-left text-primary me-2"></i>
            {{ __('activity.details.title') }}
            <span class="badge {{ activity_event_badge($activityLog->event) }} ms-2">
                {{ activity_events()[$activityLog->event] ?? $activityLog->event }}
            </span>
        </h5>

        <a href="{{ route('admin.activity-logs.subject', $activityLog->subject_type) }}"
           class="btn btn-label-secondary waves-effect">
            <i class="fa-solid fa-arrow-right icon-back me-1" aria-hidden="true"></i> {{ __('activity.actions.view_module') }}
        </a>
    </div>

    <div class="card-body pt-4">

        {{-- بيانات العملية --}}
        <h6 class="fw-bold mb-3">{{ __('activity.details.operation') }}</h6>
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <small class="text-muted d-block">{{ __('activity.fields.subject') }}</small>
                <div class="fw-semibold">{{ activity_subject_label($activityLog->subject_type) }}</div>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">{{ __('activity.fields.subject_name') }}</small>
                <div class="fw-semibold">
                    {{ $activityLog->subject_label ?? '#' . $activityLog->subject_id }}
                </div>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">{{ __('activity.fields.causer') }}</small>
                <div class="fw-semibold">
                    @if($activityLog->causer_name)
                        {{ $activityLog->causer_name }}
                    @else
                        <span class="badge bg-label-secondary">{{ __('activity.system') }}</span>
                    @endif
                </div>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">{{ __('activity.fields.created_at') }}</small>
                <div class="fw-semibold" dir="ltr">{{ $activityLog->created_at?->format('Y-m-d H:i:s') }}</div>
            </div>
        </div>

        {{-- الحقول المتغيّرة --}}
        <h6 class="fw-bold mb-3">{{ __('activity.details.changes') }}</h6>

        @php $changes = $activityLog->changes_list; @endphp

        @if(empty($changes))
            <div class="alert alert-secondary">{{ __('activity.details.no_changes') }}</div>
        @else
            <div class="table-responsive mb-4">
                <table class="table table-sm table-bordered align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width:30%">{{ __('activity.fields.field') }}</th>
                            <th style="width:35%">{{ __('activity.fields.old_value') }}</th>
                            <th style="width:35%">{{ __('activity.fields.new_value') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($changes as $change)
                            <tr>
                                <td class="fw-semibold">{{ $change['label'] }}</td>
                                <td><span class="text-danger">{{ activity_value($change['old']) }}</span></td>
                                <td>
                                    <span class="{{ $change['old'] !== $change['new'] ? 'text-success fw-semibold' : 'text-muted' }}">
                                        {{ activity_value($change['new']) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- بيانات الطلب --}}
        <h6 class="fw-bold mb-3">{{ __('activity.details.request_info') }}</h6>
        <div class="row g-3">
            <div class="col-md-3">
                <small class="text-muted d-block">{{ __('activity.fields.ip_address') }}</small>
                <div class="fw-semibold" dir="ltr">{{ $activityLog->ip_address ?? '—' }}</div>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">{{ __('activity.fields.method') }}</small>
                <div class="fw-semibold" dir="ltr">{{ $activityLog->method ?? '—' }}</div>
            </div>
            <div class="col-md-6">
                <small class="text-muted d-block">{{ __('activity.fields.url') }}</small>
                <div class="fw-semibold text-break" dir="ltr">
                    <small>{{ $activityLog->url ?? '—' }}</small>
                </div>
            </div>
            <div class="col-12">
                <small class="text-muted d-block">{{ __('activity.fields.user_agent') }}</small>
                <div class="text-break text-muted" dir="ltr">
                    <small>{{ $activityLog->user_agent ?? '—' }}</small>
                </div>
            </div>
        </div>

    </div>
</div>

@endsection
