@extends('layouts.admin.app')
@section('title', $project->exists ? __('project.edit_title') : __('project.create_title'))

@section('content')
    @include('projects.partials.form-fields')
@endsection
