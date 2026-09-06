<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

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
        $credentials = $request->only('email', 'password');
        $remember = $request->has('remember');
        if (auth()->guard($this->guard)->attempt($credentials, $remember)):
            return redirect()->route('admin.home')->with('success', __('auth.done_login'));
        endif;
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

    // sendPassword
    public function sendPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email:filter|max:100|exists:admins',
        ],[
            'email.exists' => __('auth.invalid_credentials'),
        ]);

        $key = 'forgot-password:' . $request->email;

        if (RateLimiter::tooManyAttempts($key, maxAttempts: 2)) {
            $seconds = RateLimiter::availableIn($key);
            return redirect()->route('admin.login')->with('error', __('auth.too_many_attempts',['time' => gmdate('i:s', $seconds)]));
        }
        RateLimiter::hit($key, 300); // 5 minutes
        try {
            $newPassword = rand(1000000, 9999999);
            $admin = Admin::where('email', $request->email)->firstOrFail();
            sendEmail(
                to: $admin->email,
                subject: __('auth.reset_password'),
                markdownView: 'emails.admin.reset-password',
                data: [
                    'name'         => $admin->name,
                    'new_password' => $newPassword,
                    'login_url'    => route('admin.login'),
                ]
            );
            $admin->password = bcrypt($newPassword);
            $admin->save();
            return redirect()->route('admin.login')->with('success', __('auth.done_send_password'));
        } catch (Exception $e) {
            return redirect()->route('admin.login')->with('error', $e->getMessage());
        }
    }

}
