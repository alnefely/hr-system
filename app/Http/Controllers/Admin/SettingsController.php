<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class SettingsController extends Controller
{

    /**
     * عرض صفحة الإعدادات
     */
    public function index(Request $request)
    {
        $group = in_array($request->group, Settings::getAllGroups()->toArray()) ? $request->query('group') : 'general';
        $settings = Settings::getGrouped()->get($group, []);
        $settings = collect([$group => $settings]);
        return view('admin.settings.index', compact('settings'));
    }

    /**
     * تحديث الإعدادات
     */
    public function update(Request $request)
    {
        $settings = Settings::whereNotNull('key')->get()->keyBy('key');

        $rules = [];
        foreach ($settings as $key => $setting) {
            $rule = [];

            // sometimes → لو الحقل مش موجود في الـ request خالص تجاهله
            $rule[] = 'sometimes';
            $rule[] = $setting->is_required ? 'required' : 'nullable';

            match ($setting->type) {
                'email'          => $rule[] = 'email',
                'url'            => $rule[] = 'url',   // ← سامبتايمز + nullable كفاية
                'number'         => $rule[] = 'numeric',
                'checkbox'       => $rule[] = 'boolean',
                'date'           => $rule[] = 'date',
                'time'           => $rule[] = 'date_format:H:i',
                'datetime-local' => $rule[] = 'date_format:Y-m-d\TH:i',
                'file'           => $rule[] = 'string',
                'select'         => !empty($setting->options) ? (function() use ($setting, &$rule) {
                    $first    = array_key_first($setting->options);
                    $inValues = is_int($first)
                        ? array_values($setting->options)
                        : array_keys($setting->options);
                    $rule[] = 'in:' . implode(',', $inValues);
                })() : null,
                default => null,
            };

            $rules["settings.{$key}"] = array_filter($rule);
        }

        $request->validate($rules);

        foreach ($request->input('settings', []) as $key => $value) {
            if (!$settings->has($key)) continue;
            if ($settings[$key]->type === 'password' && empty($value)) continue;
            Settings::set($key, $value);
        }

        setEnvVal('APP_NAME', Settings::get('site_name', 'Rhmany'));
        setEnvVal('APP_TIMEZONE', Settings::get('app_timezone'));
        setEnvVal('APP_ENV', Settings::get('app_env'));
        setEnvVal('APP_DEBUG', Settings::get('app_env') === 'production' ? "false" : "true");
        setEnvVal('MAIL_MAILER', Settings::get('mail_driver', 'smtp'));
        setEnvVal('MAIL_HOST', Settings::get('mail_host', 'smtp.mailtrap.io'));
        setEnvVal('MAIL_PORT', Settings::get('mail_port', '587'));
        setEnvVal('MAIL_USERNAME', Settings::get('mail_username', 'info@rhmany.com'));
        setEnvVal('MAIL_PASSWORD', Settings::get('mail_password', 'password'));
        setEnvVal('MAIL_SCHEME', Settings::get('mail_encryption', 'none'));
        setEnvVal('MAIL_FROM_NAME', Settings::get('mail_from_name', 'Rhmany'));
        setEnvVal('MAIL_FROM_ADDRESS', Settings::get('mail_from_address', 'hello@example.com'));
        setEnvVal('MAIL_FROM_ADDRESS', Settings::get('mail_from_address', 'hello@example.com'));
        return back()->with('success', __('alert.done_update'));
    }

    /**
     * مسح الكاش
     */
    public function clearCache()
    {
        Role::updateOrCreate(
        [
            'name' => 'صلاحية كاملة'
        ],
        [
            'main'      => true,
            'roles'     => all_roles(),
            'created_at'=> now(),
            'updated_at'=> now(),
        ]);
        Artisan::call('optimize:clear');
        return back()->with('success', __('alert.done_clear_cache'));
    }
}
