{{-- إلغاء طلب معلّق — بعد البت الزرار مابيظهرش (والسيرفر بيرفض برضه) --}}
@if ($pending)
    <form action="{{ $action }}" method="POST" class="d-inline" data-confirm-title="{{ __('portal.requests.cancel') }}" data-confirm-text="{{ __('portal.requests.confirm_cancel') }}">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-sm btn-label-danger">
            <i class="fa-solid fa-xmark me-1"></i>{{ __('portal.requests.cancel') }}
        </button>
    </form>
@else
    <span class="text-muted">—</span>
@endif
