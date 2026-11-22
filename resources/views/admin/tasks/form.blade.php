@extends('layouts.admin.app')
@section('title', $task->exists ? __('task.edit_title') : __('task.create_title'))

@section('content')
    @include('tasks.partials.form-fields')
@endsection
