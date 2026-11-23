{{--
    التعليقات: النقاش على المهمة، مع الرد والإشارة (@) والملفات.
    $task (comments محمّلة), $routes, $actor, $abilities, $mentionable
--}}
@php
    $cfg = config('tasks.attachments');
@endphp
<div class="card mb-4" id="comments">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold"><i class="fa-regular fa-comments me-2 text-primary"></i>{{ __('task.comments.title') }} <x-tasks.help key="comments" /></h6>
        @if ($task->comments_count > 0)<span class="badge bg-label-secondary">{{ $task->comments_count }}</span>@endif
    </div>
    <div class="card-body">
        @forelse ($task->comments as $comment)
            <div class="{{ ! $loop->last ? 'border-bottom pb-3 mb-3' : '' }}">
                @include('tasks.partials.comment-item', ['comment' => $comment])
            </div>
        @empty
            <p class="text-body-secondary small mb-0">{{ __('task.comments.empty') }}</p>
        @endforelse

        <form action="{{ route($routes . '.comments.store', $task->id) }}" method="POST" enctype="multipart/form-data" class="mt-4 pt-3 border-top" id="comment-form" data-no-confirm>
            @csrf
            <input type="hidden" name="parent_id" id="comment_parent_id" value="">
            <div class="alert alert-secondary py-2 px-3 small d-none d-flex justify-content-between align-items-center" id="replying-to">
                <span></span>
                <button type="button" class="btn-close btn-sm" aria-label="{{ __('task.comments.cancel') }}" id="cancel-reply"></button>
            </div>
            <textarea name="body" id="comment_body" class="form-control @error('body') is-invalid @enderror" rows="3" maxlength="5000"
                      placeholder="{{ __('task.comments.placeholder') }}" aria-label="{{ __('task.comments.placeholder') }}">{{ old('body') }}</textarea>
            @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="row g-2 mt-1">
                @if ($mentionable->isNotEmpty())
                    <div class="col-md-6">
                        <select name="mentions[]" id="comment_mentions" class="form-select select2" multiple data-placeholder="{{ __('task.comments.mention') }}" aria-label="{{ __('task.comments.mention') }}">
                            @foreach ($mentionable as $person)
                                <option value="{{ $person->id }}">{{ $person->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="{{ $mentionable->isNotEmpty() ? 'col-md-6' : 'col-12' }}">
                    <input type="file" name="files[]" class="form-control @error('files.*') is-invalid @enderror @error('files') is-invalid @enderror" multiple
                           accept=".{{ implode(',.', $cfg['mimes']) }}" aria-label="{{ __('task.comments.attach') }}">
                    @error('files.*')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @error('files')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center gap-2 mt-2">
                <small class="text-body-secondary">{{ __('task.attachments.hint', ['count' => $cfg['max_files'], 'size' => round($cfg['max_kb'] / 1024)]) }}</small>
                <button type="submit" class="btn btn-primary text-nowrap"><i class="fa-solid fa-paper-plane me-1"></i>{{ __('task.comments.send') }}</button>
            </div>
        </form>
    </div>
</div>

@push('js')
<script>
    (function () {
        const box = document.getElementById('comments');
        if (!box) return;
        const parent = document.getElementById('comment_parent_id');
        const banner = document.getElementById('replying-to');
        const body = document.getElementById('comment_body');
        const label = @json(__('task.comments.replying_to', ['name' => '__NAME__']));

        box.addEventListener('click', function (e) {
            const reply = e.target.closest('[data-reply]');
            if (reply) {
                e.preventDefault();
                parent.value = reply.dataset.reply;
                banner.querySelector('span').textContent = label.replace('__NAME__', reply.dataset.replyName || '');
                banner.classList.remove('d-none');
                body.focus();
                return;
            }

            const edit = e.target.closest('[data-edit]');
            if (edit) {
                e.preventDefault();
                const item = edit.closest('[id^="comment-"]');
                item.querySelector('[data-edit-form]').classList.remove('d-none');
                item.querySelector('[data-body]')?.classList.add('d-none');
                return;
            }

            const cancel = e.target.closest('[data-edit-cancel]');
            if (cancel) {
                const item = cancel.closest('[id^="comment-"]');
                item.querySelector('[data-edit-form]').classList.add('d-none');
                item.querySelector('[data-body]')?.classList.remove('d-none');
            }
        });

        document.getElementById('cancel-reply')?.addEventListener('click', function () {
            parent.value = '';
            banner.classList.add('d-none');
        });
    })();
</script>
@endpush
