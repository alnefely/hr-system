<?php

namespace App\Models\Concerns;

use App\Support\BranchScopeResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * عزل البيانات حسب الفرع.
 *
 * الـ trait ده بيضيف Global Scope، مش scope عادي — والفرق مهم:
 * الـ scope العادي (visibleTo) كان بيحمي القوائم بس، لأنه لازم يتنادى صراحةً.
 * أما Route Model Binding (‏Sale $sale في الكنترولر) فكان بيجيب السجل بالـ id
 * من غير أي فلترة، فمشرف الفرع كان يقدر يفتح ويعدّل ويحذف سجل فرع تاني
 * بمجرد تغيير الرقم في الرابط (ثغرة IDOR).
 *
 * الـ Global Scope بيتطبّق على كل استعلام تلقائياً — بما فيه find() و
 * Route Model Binding — فالسجل بيرجع null والـ binding بيرمي 404 من نفسه.
 *
 * الموديل بيحدد طريقة ربطه بالفرع عبر $branchScopeVia:
 *   'own'      => عمود branch_id على الجدول نفسه (الافتراضي)
 *   'employee' => عبر عمود employee_id (للإجازات والسلف)
 *
 * لتجاوز العزل عمداً (تقرير للمدير العام مثلاً):
 *   Model::acrossAllBranches()->...
 *   أو: Model::withoutGlobalScope('branch')->...
 */
trait ScopedToBranch
{
    public static function bootScopedToBranch(): void
    {
        static::addGlobalScope('branch', function (Builder $builder) {
            $branchId = BranchScopeResolver::branchId();

            if ($branchId === null) {
                return;
            }

            $model = $builder->getModel();
            $via = property_exists($model, 'branchScopeVia') ? $model->branchScopeVia : 'own';

            if ($via === 'employee') {
                // الاستعلام الفرعي مكتوب يدوياً بدل whereHas عشان مايشغّلش
                // الـ Global Scope بتاع Employee ويدخل في حلقة لا نهائية.
                $builder->whereIn(
                    $model->getTable() . '.employee_id',
                    DB::table('employees')->where('branch_id', $branchId)->select('id')
                );

                return;
            }

            $builder->where($model->getTable() . '.branch_id', $branchId);
        });
    }

    /** استعلام متجاوز للعزل — للتقارير الإدارية الشاملة. */
    public function scopeAcrossAllBranches(Builder $query): Builder
    {
        return $query->withoutGlobalScope('branch');
    }

    /**
     * مُبقاة للتوافق مع النداءات القائمة في الكنترولرات.
     *
     * العزل بقى تلقائياً عبر الـ Global Scope، فالدالة دي مابقتش تضيف
     * فلترة جديدة — سيبناها عشان الكود القديم مايكسرش.
     */
    public function scopeVisibleTo(Builder $query, $admin = null): Builder
    {
        return $query;
    }
}
