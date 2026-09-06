@extends('layouts.admin.app')
@section('title', __('admin.add_new'))

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">@yield('title')</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.admins.store') }}" class="row" method="POST">@csrf
                <div class="col-md-3 mb-4">
                    <x-inputs.field
                        name="name"
                        label="{{__('admin.name')}}"
                        :value="old('name')"
                        type="text"
                        required
                    />
                </div>

                <div class="col-md-3 mb-4">
                    <x-inputs.field
                        name="email"
                        label="{{__('global.email')}}"
                        :value="old('email')"
                        type="email"
                        dir="ltr"
                        required
                    />
                </div>

                <div class="col-md-3 mb-4">
                    <x-inputs.field
                        name="password"
                        label="{{__('global.password')}}"
                        type="password"
                        :required="true"
                    />
                </div>

                <div class="col-md-3 mb-4">
                    <x-inputs.select2
                        name="role_id"
                        label="{{__('role.name')}}"
                        :value="old('role_id')"
                        :data="$roles"
                        data_key="name"
                        data_value="id"
                        :required="true"
                    />
                </div>

                <div class="col-md-4 mb-4">
                    <x-inputs.lfm
                        name="img"
                        label="{{__('admin.img')}}"
                        type="image"
                        :showClear="false"
                        :value="old('img', demo('img'))"
                    />
                </div>

                <div class="col-md-12">
                    <x-inputs.btn
                        type="submit"
                        status="primary"
                        text="{{__('global.save')}}"
                        action="save"
                    />
                </div>
            </form>
        </div>
    </div>
@endsection