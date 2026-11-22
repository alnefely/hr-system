@extends('layouts.portal.app')
@section('title', __('project.edit_title'))

@include('tasks.partials.select2-assets')

@section('content')
    @include('projects.partials.form-fields')
@endsection
