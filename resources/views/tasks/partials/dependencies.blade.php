{{--
    ارتباط المهام: المهام اللي لازم تخلص الأول، والمهام المستنية المهمة دي.
    $task (dependencies و dependents محمّلين), $routes, $abilities, $dependencyCandidates
--}}
@if ($task->dependencies->isNotEmpty() || $task->dependents->isNotEmpty() || $dependencyCandidates->isNotEmpty())
    <div class="card mb-4" id="dependencies">
        <div class="card-header"><h6 class="mb-0 fw-bold"><i class="fa-solid fa-link me-2 text-primary"></i>{{ __('task.dependencies.title') }} <x-tasks.help key="dependencies" /></h6></div>
        <div class="card-body">
            <div class="small fw-semibold mb-2">{{ __('task.dependencies.waits_on') }}</div>
            @forelse ($task->dependencies as $dep)
                <div class="d-flex align-items-center gap-2 py-1">
                    <i class="fa-solid {{ $dep->status === 'completed' ? 'fa-circle-check text-success' : 'fa-lock text-warning' }}"></i>
                    <a href="{{ route($routes . '.show', $dep->id) }}" class="flex-grow-1 text-break">{{ $dep->title }}</a>
                    {!! \App\Enums\TaskStatus::badgeFor($dep->status) !!}
                    @if ($abilities['update'])
                        <form action="{{ route($routes . '.dependencies.destroy', [$task->id, $dep->id]) }}" method="POST" class="m-0" data-confirm-text="{{ $dep->title }}">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-icon btn-text-danger" aria-label="{{ __('task.dependencies.remove') }}" title="{{ __('task.dependencies.remove') }}"><i class="fa-solid fa-xmark"></i></button>
                        </form>
                    @endif
                </div>
            @empty
                <p class="small text-body-secondary mb-0">{{ __('task.dependencies.none') }}</p>
            @endforelse

            @if ($dependencyCandidates->isNotEmpty())
                <form action="{{ route($routes . '.dependencies.store', $task->id) }}" method="POST" class="d-flex gap-2 mt-3" data-no-confirm>
                    @csrf
                    <div class="flex-grow-1" style="min-width: 0">
                        <select name="depends_on_id" class="form-select select2" required data-placeholder="{{ __('task.dependencies.choose') }}" aria-label="{{ __('task.dependencies.choose') }}">
                            <option value=""></option>
                            @foreach ($dependencyCandidates as $candidate)
                                <option value="{{ $candidate->id }}">{{ $candidate->code }} — {{ $candidate->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-label-primary text-nowrap"><i class="fa-solid fa-link me-1"></i><span class="d-none d-sm-inline">{{ __('task.dependencies.add') }}</span></button>
                </form>
                <small class="text-body-secondary d-block mt-1">{{ __('task.dependencies.hint') }}</small>
            @endif

            @if ($task->dependents->isNotEmpty())
                <div class="small fw-semibold mt-4 mb-2">{{ __('task.dependencies.blocks') }}</div>
                @foreach ($task->dependents as $dep)
                    <div class="d-flex align-items-center gap-2 py-1">
                        <i class="fa-solid fa-hourglass-half text-body-secondary"></i>
                        <a href="{{ route($routes . '.show', $dep->id) }}" class="flex-grow-1 text-break">{{ $dep->title }}</a>
                        {!! \App\Enums\TaskStatus::badgeFor($dep->status) !!}
                    </div>
                @endforeach
            @endif
        </div>
    </div>
@endif
