@extends('layouts.admin.app')
@section('title', __('employee.edit') . ': ' . $employee->name)

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="card-title mb-0">{{ __('employee.edit') }}: <span class="text-primary">{{ $employee->name }}</span> ({{ $employee->employee_code }})</h4>
        <div>
            <a href="{{ route('admin.employees.show', $employee->id) }}" class="btn btn-label-info me-2">
                <i class="fa-solid fa-id-card me-1"></i> {{ __('employee.profile') }}
            </a>
            <a href="{{ route('admin.employees') }}" class="btn btn-label-secondary">
                <i class="fa-solid fa-arrow-right icon-back me-1" aria-hidden="true"></i> {{ __('employee.back') }}
            </a>
        </div>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.employees.update', $employee->id) }}" method="POST">
            @csrf
            @method('PUT')
            @include('admin.hr.employees.form')
        </form>
    </div>
</div>
@endsection
