{{-- المرفقات (كل ملفات المهمة شاملة اللي في التعليقات). $task, $routes, $actor, $abilities --}}
@php $cfg = config('tasks.attachments'); @endphp
<div class="card mb-4" id="attachments">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold"><i class="fa-solid fa-paperclip me-2 text-primary"></i>{{ __('task.attachments.title') }}</h6>
        @if ($task->attachments->isNotEmpty())<span class="badge bg-label-secondary">{{ $task->attachments->count() }}</span>@endif
    </div>
    <ul class="list-group list-group-flush">
        @forelse ($task->attachments as $file)
            @php
                $uploader = $file->uploader;
                $mine = $uploader && $uploader::class === $actor::class && $uploader->getKey() === $actor->getKey();
            @endphp
            <li class="list-group-item d-flex align-items-center gap-2">
                <div class="flex-grow-1" style="min-width: 0">
                    @include('tasks.partials.attachment-chip', ['file' => $file])
                    <div class="small text-body-secondary mt-1">
                        {{ $uploader?->name }} · <span dir="ltr">{{ \Illuminate\Support\Number::fileSize($file->size, 1) }}</span>
                    </div>
                </div>
                @if ($mine || $abilities['update'])
                    <form action="{{ route($routes . '.attachments.destroy', [$task->id, $file->id]) }}" method="POST" class="m-0" data-confirm-text="{{ $file->original_name }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-icon btn-text-danger" aria-label="{{ __('global.delete') }}" title="{{ __('global.delete') }}"><i class="fa-solid fa-trash"></i></button>
                    </form>
                @endif
            </li>
        @empty
            <li class="list-group-item text-body-secondary small">{{ __('task.attachments.empty') }}</li>
        @endforelse
    </ul>
    <div class="card-body border-top">
        <form action="{{ route($routes . '.attachments.store', $task->id) }}" method="POST" enctype="multipart/form-data" data-no-confirm>
            @csrf
            <label for="task_files" class="form-label small mb-1">{{ __('task.attachments.upload') }}</label>
            <div class="d-flex gap-2">
                <input type="file" name="files[]" id="task_files" class="form-control form-control-sm" multiple required accept=".{{ implode(',.', $cfg['mimes']) }}">
                <button type="submit" class="btn btn-sm btn-primary" aria-label="{{ __('task.attachments.upload') }}"><i class="fa-solid fa-upload"></i></button>
            </div>
            <small class="text-body-secondary d-block mt-1">{{ __('task.attachments.hint', ['count' => $cfg['max_files'], 'size' => round($cfg['max_kb'] / 1024)]) }}</small>
        </form>
    </div>
</div>
