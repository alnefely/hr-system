@extends('layouts.portal.app')
@section('title', $task->title)

@include('tasks.partials.select2-assets')

@section('content')
    @include('tasks.partials.show-body', [
        'routes'     => 'portal.tasks',
        'editUrl'    => route('portal.tasks.edit', $task->id),
        'deleteUrl'  => route('portal.tasks.destroy', $task->id),
        'backUrl'    => route('portal.tasks.index'),
        'subtaskUrl' => route('portal.tasks.create', ['parent_id' => $task->id]),
    ])
@endsection
