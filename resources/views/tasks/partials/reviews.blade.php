{{-- سجل المراجعات: كل تسليم وقراره. بيظهر بس لو المهمة اتسلّمت قبل كده. $task (reviews محمّلة) --}}
@if ($task->reviews->isNotEmpty())
    <div class="card mb-4" id="reviews">
        <div class="card-header"><h6 class="mb-0 fw-bold"><i class="fa-solid fa-clipboard-check me-2 text-primary"></i>{{ __('task.review.title') }} <x-tasks.help key="reviews" /></h6></div>
        <ul class="list-group list-group-flush">
            @foreach ($task->reviews as $review)
                <li class="list-group-item py-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <span class="small">
                            <strong>{{ __('task.review.submitted_by', ['name' => $review->submitter?->name ?? '—']) }}</strong>
                            <span class="text-body-secondary">· {{ $review->created_at->diffForHumans() }}</span>
                        </span>
                        {!! \App\Enums\TaskReviewStatus::badgeFor($review->status) !!}
                    </div>
                    @if ($review->submission_note)
                        <div class="small mt-1 text-break"><span class="text-body-secondary">{{ __('task.review.note') }}:</span> {{ $review->submission_note }}</div>
                    @endif
                    @if ($review->status === 'pending')
                        <div class="small text-warning mt-1"><i class="fa-regular fa-hourglass-half me-1"></i>{{ __('task.review.awaiting', ['name' => $review->reviewer?->name ?? '—']) }}</div>
                    @else
                        <div class="small mt-1 {{ $review->status === 'approved' ? 'text-success' : 'text-danger' }}">
                            {{ __('task.review.decided_by', ['name' => $review->reviewer?->name ?? '—']) }}
                            @if ($review->decided_at)<span class="text-body-secondary">· {{ $review->decided_at->diffForHumans() }}</span>@endif
                        </div>
                        @if ($review->decision_note)
                            <div class="small mt-1 p-2 rounded bg-body-tertiary text-break">{{ $review->decision_note }}</div>
                        @endif
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
@endif
