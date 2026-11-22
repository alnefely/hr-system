<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * عمليات مدير الملفات اللي بتغيّر أو تمسح ملفات موجودة.
 *
 * كل مسارات مدير الملفات كانت ورا صلاحية "عرض" بس، فأي حد بيعرض
 * يقدر يحذف أو يعيد تسمية أو ينقل ملفات غيره. الرفع والعرض فاضلين
 * على filemanager_show.
 */
class FileManagerPermission
{
    private const DESTRUCTIVE = [
        'getDelete', 'getRename', 'move', 'doMove',
        'getCrop', 'getCropImage', 'getNewCropImage',
        'getResize', 'performResize', 'performResizeNew',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $action = (string) str($request->route()?->getName())->afterLast('.');

        if (in_array($action, self::DESTRUCTIVE, true) && ! admin_can('filemanager_delete')) {
            abort(403, __('alert.403_message'));
        }

        return $next($request);
    }
}
