@extends('layouts.admin.app')
@section('title', __('role.title'))

@section('content')

    <h4 class="fw-semibold mb-4">
        @yield('title')
        @if (admin_can('role_create'))
            <a href="{{ route('admin.roles.create') }}" class="btn btn-sm btn-primary text-nowrap waves-effect waves-light">
                {{__('role.add_new')}}
            </a>
        @endif
    </h4>

    <div class="alert alert-info" role="alert">{{__('role.alert_delete_role')}}</div>

    <div class="row g-6">
        @foreach ($roles as $role)
            <div class="col-xl-4 col-lg-6 col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h6 class="fw-normal mb-0 text-body">{{__('role.count_admins',['count'=>$role['admins_count']])}}</h6>
                            <ul class="list-unstyled d-flex align-items-center avatar-group mb-0">
                                @foreach ($role['admins'] as $admin)
                                    <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top" class="avatar pull-up" aria-label="{{ $admin['name'] }}" data-bs-original-title="{{ $admin['name'] }}">
                                        <img class="rounded-circle" src="{{ $admin['img'] }}" alt="{{ $admin['name'] }}">
                                    </li>

                                    @if($loop->index == 3 && $role['admins_count'] > 4)
                                        <li class="avatar">
                                            <span class="avatar-initial bg-primary text-white border-transparent rounded-circle pull-up" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="{{($role['admins_count']-4)." ".__('global.more')}}">+{{($role['admins_count']-4)}}</span>
                                        </li>
                                        @break
                                    @endif
                                @endforeach
                            </ul>
                        </div>

                        @php
                            $canEdit   = admin_can('role_update') && !$role['main'];
                            $canDelete = admin_can('role_delete') && !$role['main'] && $role['id'] != 2;
                        @endphp

                        @if (!empty($role['description']))
                            <p class="text-body-secondary small mb-3">{{ $role['description'] }}</p>
                        @endif

                        <div class="d-flex justify-content-between align-items-end">
                            <div class="role-heading">
                                <h5 class="mb-1">{{ $role['name'] }}</h5>

                                @if ($canEdit)
                                    <a href="{{ route('admin.roles.edit', $role['id']) }}" class="role-edit">
                                        <span>{{ __('role.edit') }}</span>
                                    </a>
                                @endif
                            </div>

                            @if ($canDelete)
                                <form action="{{ route('admin.roles.destroy', $role['id']) }}" method="post">
                                    @csrf
                                    {{ method_field('DELETE') }}
                                    <x-inputs.btn
                                        type="submit"
                                        status="danger"
                                        class="btn-label-danger"
                                        text="<i class='fa-solid fa-trash-can'></i>"
                                        action="delete"
                                    />
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

        <div class="col-12">
            {{ $roles->links() }}
        </div>

    </div>

@endsection
