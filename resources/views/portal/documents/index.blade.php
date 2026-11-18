@extends('layouts.portal.app')
@section('title', __('portal.nav.documents'))

@section('content')
<div class="card">
    <div class="card-header">
        <h6 class="mb-1 fw-bold"><i class="fa-solid fa-folder-open me-2 text-primary"></i>{{ __('portal.documents.title') }}</h6>
        <small class="text-muted">{{ __('portal.documents.hint') }}</small>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>{{ __('portal.documents.document') }}</th>
                    <th>{{ __('portal.documents.type') }}</th>
                    <th>{{ __('portal.documents.issue_date') }}</th>
                    <th>{{ __('portal.documents.expiry_date') }}</th>
                    <th>{{ __('portal.documents.verified') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($documents as $doc)
                    @php
                        $days = $doc->expiry_date ? (int) now()->startOfDay()->diffInDays($doc->expiry_date, false) : null;
                    @endphp
                    <tr>
                        <td class="fw-medium">{{ $doc->title ?: __('document.types.' . $doc->document_type) }}</td>
                        <td>{{ __('document.types.' . $doc->document_type) }}</td>
                        <td class="text-nowrap"><span dir="ltr">{{ $doc->issue_date?->format('Y-m-d') ?? '—' }}</span></td>
                        <td class="text-nowrap">
                            @if ($days === null)
                                <span class="text-muted">{{ __('portal.documents.no_expiry') }}</span>
                            @else
                                <span dir="ltr">{{ $doc->expiry_date->format('Y-m-d') }}</span>
                                @if ($days < 0)
                                    <span class="badge bg-label-danger ms-1">{{ __('portal.documents.expired') }}</span>
                                @elseif ($days <= \App\Http\Controllers\Portal\DocumentController::EXPIRY_WARNING_DAYS)
                                    <span class="badge bg-label-warning ms-1">{{ __('portal.documents.expires_in', ['days' => $days]) }}</span>
                                @else
                                    <span class="badge bg-label-success ms-1">{{ __('portal.documents.valid') }}</span>
                                @endif
                            @endif
                        </td>
                        <td>
                            @if ($doc->is_verified)
                                <i class="fa-solid fa-circle-check text-success" title="{{ __('portal.documents.verified') }}"></i>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if ($doc->file_path)
                                <a href="{{ $doc->file_path }}" target="_blank" rel="noopener" class="btn btn-sm btn-label-primary">
                                    <i class="fa-solid fa-file-arrow-down me-1"></i>{{ __('portal.documents.open') }}
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">{{ __('portal.documents.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
