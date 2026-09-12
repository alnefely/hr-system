<?php

namespace App\Services\Links;

use App\Models\Admin;
use App\Models\Link;
use InvalidArgumentException;

/**
 * إنشاء وتعديل الروابط.
 *
 * الكنترولر مابيبنيش الرابط بنفسه عشان قواعد الكود المخصص
 * وتغيير الوجهة تفضل في مكان واحد.
 */
class LinkService
{
    public function __construct(
        protected ShortCodeGenerator $generator
    ) {}

    public function create(array $data, ?Admin $admin = null): Link
    {
        $code = $data['short_code'] ?? null;

        if ($code) {
            $errors = $this->generator->validateCustom($code);

            if ($errors) {
                throw new InvalidArgumentException($errors[0]);
            }
        } else {
            $code = $this->generator->generate();
        }

        return Link::create([
            'short_code'   => $code,
            'original_url' => $data['original_url'],
            'title'        => $data['title'] ?? null,
            'notes'        => $data['notes'] ?? null,
            'branch_id'    => $data['branch_id'] ?? null,
            'created_by'   => $admin?->id ?? auth('admin')->id(),
            'is_active'    => $data['is_active'] ?? true,
            'expires_at'   => $data['expires_at'] ?? null,
            'max_clicks'   => $data['max_clicks'] ?? null,
        ]);
    }

    /**
     * تعديل رابط قائم.
     *
     * الكود المخصص بيتحقّق منه بس لو اتغيّر فعلاً — وإلا الرابط
     * هيرفض حفظ نفسه لأن كوده مستخدم (بواسطته هو).
     */
    public function update(Link $link, array $data): Link
    {
        $payload = [
            'original_url' => $data['original_url'] ?? $link->original_url,
            'title'        => $data['title'] ?? null,
            'notes'        => $data['notes'] ?? null,
            'branch_id'    => $data['branch_id'] ?? null,
            'is_active'    => $data['is_active'] ?? false,
            'expires_at'   => $data['expires_at'] ?? null,
            'max_clicks'   => $data['max_clicks'] ?? null,
        ];

        $code = $data['short_code'] ?? null;

        if ($code && $code !== $link->short_code) {
            $errors = $this->generator->validateCustom($code);

            if ($errors) {
                throw new InvalidArgumentException($errors[0]);
            }

            $payload['short_code'] = $code;
        }

        $link->update($payload);

        // الحفظ بيمسح الكاش عبر booted()، بما فيه الكود القديم لو اتغيّر.
        return $link->fresh();
    }
}
