@extends('layouts.admin.app')
@section('title', __('setting.title'))


@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.settings.update') }}" class="row" method="POST">@csrf
                @foreach ($settings as $group => $section)
                    <div class="col-md-12 mb-4">
                        <div class="divider divider-primary">
                            <div class="divider-text text-primary">{{ __('setting.groups.' . $group) }}</div>
                        </div>
                    </div>
                    
                    @foreach ($section as $setting)
                        <div class="col-md-{{ $setting->col_size ?? 12 }} mb-6">

                            @if( $setting->type == 'divider' )
                                <div class="divider divider-warning">
                                    <div class="divider-text text-warning">{{ __('setting.' . $setting->key . '.label') }}</div>
                                </div>
                            @elseif( $setting->type == 'file' )
                                <x-inputs.lfm
                                    name="settings[{{ $setting->key }}]"
                                    label="{{ __('setting.' . $setting->key . '.label') }}"
                                    type="image"
                                    :showClear="false"
                                    :value="$setting->value"
                                    />
                            @elseif($setting->key == 'cart_status' || $setting->key == 'point_redemption_is_active')
                                <label for="settings[{{ $setting->key }}]">{{ __('setting.' . $setting->key . '.label') }} <strong class="text-danger">{{ $setting->is_required ? '*' : '' }}</strong></label>
                                <select name="settings[{{ $setting->key }}]" @if($setting->is_required) required @endif class="form-select select2">
                                    <option value="1" {{ $setting->value == 1 ? 'selected' : '' }}>{{ __('setting.options.1') }}</option>
                                    <option value="0" {{ $setting->value == 0 ? 'selected' : '' }}>{{ __('setting.options.0') }}</option>
                                </select>
                                <div class="form-text text-muted">{{ __("setting." . $setting->key . ".hint") }}</div>
                            @else
                                <x-inputs.field
                                    name="settings[{{ $setting->key }}]"
                                    label="{{ __('setting.' . $setting->key . '.label') }}"
                                    :value="$setting->value"
                                    :type="$setting->type"
                                    :options="is_array($setting->options) ? $setting->options : []"
                                    :required="$setting->is_required"
                                    :placeholder="__('setting.' . $setting->key . '.placeholder')"
                                    :hint="__('setting.' . $setting->key . '.hint')"
                                    :dir="( $setting->dir == 'ltr' || $setting->dir == 'rtl' ) ? $setting->dir : null"
                                />
                            @endif
                        </div>
                    @endforeach
                    
                @endforeach
                
                <div class="col-md-12">
                    {{-- <button type="submit" class="btn btn-primary waves-effect waves-light">Send</button> --}}

                    <x-inputs.btn
                        type="submit"
                        status="info"
                        text="{{__('global.update')}}"
                        action="update"
                    />
                </div>
            </form>
        </div>
    </div>
@endsection
