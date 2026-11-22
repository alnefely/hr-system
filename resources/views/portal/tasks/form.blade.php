@extends('layouts.portal.app')
@section('title', $task->exists ? __('task.edit_title') : __('task.create_title'))

@include('tasks.partials.select2-assets')

@section('content')
    @include('tasks.partials.form-fields')
@endsection
