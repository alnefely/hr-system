@extends('layouts.admin.app')
@section('title', __('payroll.create_title'))

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0"><i class="fa-solid fa-plus-circle text-primary me-2"></i> {{ __('payroll.create_title') }}</h5>
            </div>
            <div class="card-body pt-4">
                <form action="{{ route('admin.payrolls.store') }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-12">
                            {{-- مكوّن موحّد: الشهور بالعربي والسنين نسبية للسنة الحالية --}}
                            <x-inputs.month-year
                                :month="old('month', $currentMonth)"
                                :year="old('year', $currentYear)"
                                :label="__('payroll.fields.period')"
                                :help="__('payroll.hints.period')"
                                required />

                            @error('year') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            @error('month') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label">{{ __('payroll.fields.branch') }}</label>
                            <select name="branch_id" class="form-select select2 @error('branch_id') is-invalid @enderror">
                                <option value="">{{ __('payroll.fields.all_branches') }}</option>
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}" {{ old('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">{{ __('payroll.messages.branch_select_hint') }}</div>
                            @error('branch_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label">{{ __('payroll.fields.notes') }}</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="ملاحظات توجيهية اختيارية...">{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    <div class="alert alert-info d-flex align-items-center mt-4" role="alert">
                        <i class="fa-solid fa-circle-info me-2 fs-4"></i>
                        <div>
                            {{ __('payroll.messages.create_hint') }}
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 border-top pt-3">
                        <a href="{{ route('admin.payrolls.index') }}" class="btn btn-label-secondary waves-effect">
                            <i class="fa-solid fa-xmark me-1"></i> {{ __('global.cancel') }}
                        </a>
                        <button type="submit" class="btn btn-primary waves-effect waves-light">
                            <i class="fa-solid fa-gear me-1"></i> {{ __('payroll.actions.generate') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
