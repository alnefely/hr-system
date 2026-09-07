<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AuthAdminController extends Controller
{
    protected $guard = 'admin';

    //
    public function login()
    {
        if (auth()->guard($this->guard)->check()):
            return redirect()->route('admin.home');
        endif;
        return view('admin.auth.login');
    }

    // ckeck login
    public function checkLogin(Request $request)
    {
        $request->validate([
            'email'    => 'required|email:filter',
            'password' => 'required|string',
        ]);

        $throttleKey = Str::transliterate(Str::lower($request->input('email')) . '|' . $request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->with('error', __('auth.too_many_attempts', ['time' => gmdate('i:s', $seconds)]))->withInput();
        }

        $credentials = $request->only('email', 'password');
        $remember = $request->has('remember');

        if (auth()->guard($this->guard)->attempt($credentials, $remember)):
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();
            return redirect()->route('admin.home')->with('success', __('auth.done_login'));
        endif;

        RateLimiter::hit($throttleKey, 300);

        return back()->with('warning', __('auth.invalid_credentials'))->withInput();
    }

    // logout
    public function logout()
    {
        auth()->guard($this->guard)->logout();
        return redirect()->route('admin.login')->with('success', __('auth.done_logout'));
    }

    // forgotPassword
    public function forgotPassword()
    {
        if (auth()->guard($this->guard)->check()):
            return redirect()->route('admin.home');
        endif;
        return view('admin.auth.forgot-password');
    }

    /**
     * إرسال رابط إعادة تعيين مؤقت.
     *
     * الطريقة القديمة كانت بتولّد كلمة مرور وتغيّرها فوراً وتبعتها بالإيميل،
     * يعني أي حد يعرف إيميل مشرف يقدر يقفل حسابه من غير ما يوصل للإيميل نفسه.
     * دلوقتي كلمة المرور مابتتغيّرش غير لما صاحب الحساب يفتح الرابط بنفسه.
     *
     * الرد دايماً واحد سواء الإيميل موجود أو لأ، عشان مايتسربش مين مسجّل عندنا.
     */
    public function sendPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email:filter|max:100',
        ]);

        $status = Password::broker('admins')->sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_THROTTLED) {
            return redirect()->route('admin.login')
                ->with('error', __('auth.too_many_attempts', ['time' => '01:00']));
        }

        // مابنفرّقش بين النجاح والإيميل غير الموجود عشان مانكشفش المسجّلين.
        return redirect()->route('admin.login')
            ->with('success', __('auth.reset_link_sent'));
    }

    /** شاشة تحديد كلمة مرور جديدة (بتتفتح من الرابط في الإيميل). */
    public function showResetForm(Request $request, string $token)
    {
        if (auth()->guard($this->guard)->check()):
            return redirect()->route('admin.home');
        endif;

        return view('admin.auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    /** حفظ كلمة المرور الجديدة بعد التحقق من التوكن. */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'    => ['required'],
            'email'    => ['required', 'email:filter'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ]);

        $status = Password::broker('admins')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (Admin $admin, string $password) {
                $admin->forceFill([
                    'password'       => bcrypt($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('admin.login')->with('success', __('auth.password_reset_done'));
        }

        return back()->withInput($request->only('email'))
            ->with('error', __('auth.reset_link_invalid'));
    }
}
