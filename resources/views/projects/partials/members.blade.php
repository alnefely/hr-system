{{-- قائمة أعضاء المشروع بأدوارهم. $project مع members (pivot role). --}}
<ul class="list-group list-group-flush">
    @forelse ($project->members->sortBy(fn ($m) => ['manager' => 0, 'member' => 1, 'viewer' => 2][$m->pivot->role] ?? 3) as $member)
        <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
            <span class="text-break">{{ $member->name }}</span>
            {!! \App\Enums\ProjectMemberRole::badgeFor($member->pivot->role) !!}
        </li>
    @empty
        <li class="list-group-item text-body-secondary small">{{ __('project.labels_text.no_members') }}</li>
    @endforelse
</ul>
