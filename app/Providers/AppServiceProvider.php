<?php

namespace App\Providers;

use App\Services\Activity\ActivityLogger;
use App\Support\BranchScopeResolver;
use Illuminate\Support\Facades\Broadcast;
use App\Services\Cache\CacheService;
use App\Services\Role\RoleService;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CacheService::class);
        $this->app->singleton(RoleService::class);

        // لازم singleton: withoutLogging() بتغيّر حالة داخلية، ولو كل
        // استدعاء رجّع نسخة جديدة كان الإيقاف مش هيوصل للـ trait.
        $this->app->singleton(ActivityLogger::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
        $this->detectLazyLoading();

        // مزوّد بوابة الموظف (config/auth.php → providers.employees)
        \Illuminate\Support\Facades\Auth::provider('employee_eloquent', function ($app, array $config) {
            return new \App\Auth\EmployeeUserProvider($app['hash'], $config['model']);
        });
        Paginator::useBootstrap();
        $this->resetBranchScopeOnAuthChange();

        // تعريفات قنوات البث.
        //
        // بنسجّلها هنا مباشرة مش عبر withRouting(channels:) لأن ده كان
        // بيسجّل مسار broadcasting/auth على الـ guard الافتراضي (web)
        // بدل admin، والنتيجة 403 على كل محاولة اشتراك.
        // المسار نفسه متسجّل في routes/admin.php جوّه مجموعة الأدمن.
        //
        // Broadcast::routes() مش مستخدمة عشان مانرجعش نسجّل المسار
        // الافتراضي تاني.
        $this->registerBroadcastChannels();

        // الأوامر المجدولة والطابور مالهمش طلب متصفح، فلارافيل كان
        // بياخد الدومين من APP_URL. بقى بياخد آخر دومين اتفتح منه النظام.
        if ($this->app->runningInConsole()) {
            \App\Support\RootUrl::applyToConsole();
        }
    }

    /**
     * تصفير فرع العزل المخزّن لما المستخدم يتغيّر.
     *
     * BranchScopeResolver بتخزّن الفرع مرة واحدة لكل request عشان تتفادى
     * استعلام متكرر. في الاختبارات والعمليات الطويلة (queue, octane) ممكن
     * يتبدّل المستخدم جوّه نفس العملية، فلازم نصفّر الكاش وقتها.
     */
    protected function resetBranchScopeOnAuthChange(): void
    {
        Event::listen(Login::class, fn () => BranchScopeResolver::forget());
        Event::listen(Logout::class, fn () => BranchScopeResolver::forget());
        Event::listen(Authenticated::class, fn () => BranchScopeResolver::forget());
    }


    /**
     * قنوات البث وتفويضها.
     *
     * كل مشرف له قناة خاصة برقمه بس. القناة بتقرا المشرف من guard
     * الـ admin مباشرة مش من المُعامل اللي بيمرره الفريمورك، لأن
     * الأخير بيتقرا من الـ guard الافتراضي (web) وبيطلع null.
     */
    protected function registerBroadcastChannels(): void
    {
        // ['guards' => ['admin']] ضرورية: PusherBroadcaster::auth بتنادي
        // retrieveUser() قبل الـ callback، ولو مالقتش مستخدم على الـ guard
        // المحدد بترمي AccessDeniedHttpException من غير ما توصل للمنطق.
        Broadcast::channel('admin.{adminId}', function ($admin, $adminId) {
            return $admin !== null && (int) $admin->id === (int) $adminId;
        }, ['guards' => ['admin']]);
    }

    /**
     * كشف مشكلة N+1 (تحميل علاقة جوّه لوب) أثناء التطوير.
     *
     * في الإنتاج مقفول تماماً. في التطوير بيكتب تحذير في اللوج بدل ما
     * يوقع الصفحة، عشان تشوف العلاقة الناقصة في with() وتصلّحها.
     */
    protected function detectLazyLoading(): void
    {
        if ($this->app->isProduction()) {
            return;
        }

        Model::preventLazyLoading();

        Model::handleLazyLoadingViolationUsing(function (Model $model, string $relation) {
            $message = sprintf('N+1: علاقة [%s] اتحمّلت lazy على [%s] — ضيفها في with().', $relation, $model::class);

            if (env('LAZY_LOADING_THROW', false)) {
                throw new \Illuminate\Database\LazyLoadingViolationException($model, $relation);
            }

            \Illuminate\Support\Facades\Log::warning($message);
        });
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('WebRateLimiter', function (Request $request) {
            // المشرف المسجّل له حد خاص بيه مش بالـ IP: موظفين مكتب واحد
            // بيطلعوا من نفس الـ IP، وشاشة واحدة فيها جداول AJAX ولوحة
            // وإشعارات — فـ 60 طلب مشتركة كانت بتخلص في ثواني وتطلّع 429.
            if ($adminId = auth('admin')->id()) {
                return Limit::perMinute(300)->by('admin:' . $adminId);
            }

            if ($employeeId = auth('employee')->id()) {
                return Limit::perMinute(300)->by('employee:' . $employeeId);
            }

            return Limit::perMinute(60)->by($request->ip());
        });

        RateLimiter::for('ApiRateLimiter', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip())
            ->response(fn() => response()->json([
                'message' => 'لقد تجاوزت الحد المسموح به من الطلبات. يرجى المحاولة مرة أخرى لاحقًا.',
            ], 429));
        });
    }
    
}
