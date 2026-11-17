<?php

namespace App\Http\Controllers\Portal;

use App\Http\Requests\Portal\ChangePasswordRequest;
use App\Http\Requests\Portal\LoginRequest;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * دخول وخروج الموظف، وتغيير كلمة المرور.
 */
class AuthController extends PortalController
{
    /** محاولات فاشلة قبل الإيقاف المؤقت، ومدة الإيقاف بالثواني. */
    private const MAX_ATTEMPTS = 5;
    private const DECAY_SECONDS = 300;

    public function showLogin()
    {
        if (auth('employee')->check()) {
            return redirect()->route('portal.home');
        }

        return view('portal.auth.login');
    }

    public function login(LoginRequest $request)
    {
        $key = $request->throttleKey();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            return back()->withInput($request->only('phone'))->with('error', __('portal.auth.too_many_attempts', [
                'time' => gmdate('i:s', RateLimiter::availableIn($key)),
            ]));
        }

        // من غير عزل الفروع: لو فيه جلسة مشرف فرع في نفس المتصفح
        $employee = Employee::withoutGlobalScope('branch')
            ->where('phone', $request->phone())
            ->first();

        // نفس الرسالة سواء الرقم مش موجود أو كلمة المرور غلط — مانكشفش المسجّلين.
        if (! $employee || ! $employee->password || ! Hash::check($request->input('password'), $employee->password)) {
            RateLimiter::hit($key, self::DECAY_SECONDS);

            return back()->withInput($request->only('phone'))->with('error', __('portal.auth.invalid_credentials'));
        }

        RateLimiter::clear($key);

        // السبب بيظهر بس لصاحب الحساب اللي عرف كلمة المرور
        if ($reason = $employee->portalBlockReason()) {
            return back()->withInput($request->only('phone'))->with('blocked', $reason);
        }

        auth('employee')->login($employee, $request->boolean('remember'));
        $request->session()->regenerate();

        $employee->forceFill(['last_login_at' => now()])->saveQuietly();

        return redirect()->intended(route('portal.home'));
    }

    /**
     * الخروج بيقفل جلسة الموظف بس — لو نفس الشخص فاتح لوحة الإدارة
     * في نفس المتصفح، جلسته هناك تفضل شغالة.
     */
    public function logout(Request $request)
    {
        auth('employee')->logout();

        // الخروج من البوابة مايخرجش من لوحة الإدارة لو مفتوحة في نفس المتصفح
        \App\Support\PanelSession::end($request, otherGuard: 'admin');

        return redirect()->route('portal.login')->with('success', __('portal.auth.logged_out'));
    }

    public function editPassword()
    {
        return view('portal.auth.change-password', [
            'forced' => $this->employee()->must_change_password,
        ]);
    }

    public function updatePassword(ChangePasswordRequest $request)
    {
        $employee = $this->employee();

        // توكن "تذكرني" جديد: أي جهاز تاني كان متذكّر الدخول القديم بيخرج
        $employee->forceFill([
            'password'             => $request->validated('password'),
            'must_change_password' => false,
            'remember_token'       => Str::random(60),
        ])->save();

        $request->session()->regenerate();

        return redirect()->route('portal.home')->with('success', __('portal.password.changed'));
    }
}
