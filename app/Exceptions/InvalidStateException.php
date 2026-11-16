<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * عملية مش مسموحة في الحالة الحالية للسجل (اعتماد مسير مدفوع مثلاً).
 *
 * الرسالة مترجمة وجاهزة تتعرض للمستخدم، فالكنترولر بيمسكها ويرجّع
 * back()->with('error', $e->getMessage()) من غير ما يعرف القاعدة نفسها.
 */
class InvalidStateException extends RuntimeException
{
}
