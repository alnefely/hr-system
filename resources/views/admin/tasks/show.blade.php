@extends('layouts.admin.app')
@section('title', $task->title)

@section('content')
    @include('tasks.partials.show-body', [
        'routes'     => 'admin.tasks',
        'editUrl'    => route('admin.tasks.edit', $task->id),
        'deleteUrl'  => route('admin.tasks.destroy', $task->id),
        'backUrl'    => route('admin.tasks.index'),
        'subtaskUrl' => admin_can('task_create') ? route('admin.tasks.create', ['parent_id' => $task->id]) : null,
    ])
@endsection
