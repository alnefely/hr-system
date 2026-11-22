{{-- رابط مرفق مختصر: صورة مصغرة للصور، وأيقونة حسب النوع لغيرها. $file, $routes, $task --}}
@php
    $url = route($routes . '.attachments.download', [$task->id, $file->id]);
    $ext = strtolower(pathinfo($file->original_name, PATHINFO_EXTENSION));
    $icon = match (true) {
        $file->isImage()                     => 'fa-file-image text-info',
        $ext === 'pdf'                       => 'fa-file-pdf text-danger',
        in_array($ext, ['doc', 'docx'])      => 'fa-file-word text-primary',
        in_array($ext, ['xls', 'xlsx', 'csv']) => 'fa-file-excel text-success',
        in_array($ext, ['ppt', 'pptx'])      => 'fa-file-powerpoint text-warning',
        $ext === 'zip'                       => 'fa-file-zipper text-secondary',
        default                              => 'fa-file-lines text-secondary',
    };
@endphp
<a href="{{ $url }}" class="d-inline-flex align-items-center gap-2 border rounded px-2 py-1 small text-body text-decoration-none" style="max-width: 16rem" title="{{ $file->original_name }}">
    @if ($file->isImage())
        <img src="{{ $url }}?inline=1" alt="" loading="lazy" class="rounded" style="width: 1.75rem; height: 1.75rem; object-fit: cover">
    @else
        <i class="fa-solid {{ $icon }} fa-lg"></i>
    @endif
    <bdi class="text-truncate">{{ $file->original_name }}</bdi>
</a>
