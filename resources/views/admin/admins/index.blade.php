@extends('layouts.admin.app')
@section('title', __('admin.title'))

@section('content')
  <div class="card">
    {{-- cart title --}}
    <div class="card-header">
      <h3 class="card-title">{{ __('admin.title') }}</h3>
    </div>
        <div class="card-body">

            @php
              $extra = [
                  [
                      'targets' => 2,
                      'render'  => 'function(data, type, row, meta){ 
                          return data ? `<img loading="lazy" src="${data}" alt="${row.name}" width="50" height="50" class="rounded avatar-sm" />` : "";
                      }',
                  ]
                ];
            @endphp

            <x-dataTable.table
                :columns="[
                  __('global.id'),
                  __('admin.name'),
                  __('admin.img'),
                  __('global.email'),
                  __('admin.role'),
                  __('global.created_at'),
                ]"
                :fields="[
                  'id',
                  'name',
                  ['data' => 'img','searchable' => false],
                  'email',
                  [
                    'data' => 'role.name', 
                    'searchType' => 'select', 
                    'searchOptions' => $roles->pluck('name', 'name')->toArray(),
                  ],
                  'created_at'
                ]"
                :extra="$extra"
                :actions="[
                    ['type' => 'edit', 'url' => url('admin/admins'),],
                    ['type' => 'delete', 'url' => url('admin/admins'),],
                ]"
                add-new="{{ __('admin.add_new') }}"
                add-new-url="{{ route('admin.admins.create') }}"
            />
        </div>
    </div>

@endsection


@push('css')
@endpush

@push('js')
@endpush