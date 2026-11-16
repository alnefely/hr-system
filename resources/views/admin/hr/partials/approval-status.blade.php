{{--
    بادج حالة الاعتماد (جزاءات ومكافآت) مع تلميح سبب الرفض.
    $row لازم يكون فيه statusEnum() و rejection_reason.
--}}
@php $status = $row->statusEnum(); @endphp
@if ($status)
    <span class="badge {{ $status->badge() }}"><i class="{{ $status->icon() }} me-1"></i>{{ $status->label() }}</span>
@else
    <span class="badge bg-label-secondary">{{ $row->status }}</span>
@endif
@if ($row->rejection_reason)
    <i class="fa-solid fa-circle-info text-danger ms-1" data-bs-toggle="tooltip" title="{{ $row->rejection_reason }}"></i>
@endif
