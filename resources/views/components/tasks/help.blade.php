{{--
    أيقونة شرح (؟) لجزء من شاشات المهام — النص من lang/*/task.php ← help.

    <x-tasks.help key="progress" />
    <x-tasks.help key="attachments" :replace="['count' => 5, 'size' => 10]" />

    كل مفتاح ليه نص للموظف (employee) وللمدير (admin) أو نص واحد للاتنين (all).
    المكان بيتحدد من المسار: شاشات admin.* = المدير، والبوابة = الموظف.
    لو مفيش نص للمكان ده، الأيقونة مابتظهرش.
--}}
@props(['key', 'replace' => []])

@php
    $area = request()->routeIs('admin.*') ? 'admin' : 'employee';
    $base = 'task.help.' . $key;
    $helpText = match (true) {
        \Illuminate\Support\Facades\Lang::has("{$base}.{$area}") => __("{$base}.{$area}", $replace),
        \Illuminate\Support\Facades\Lang::has("{$base}.all")     => __("{$base}.all", $replace),
        default                                                   => null,
    };
@endphp

<x-inputs.tip :text="$helpText" />
