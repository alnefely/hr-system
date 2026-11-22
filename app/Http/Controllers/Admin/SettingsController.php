<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

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
    public function update(UpdateSettingsRequest $request)
    {
        $settings = $request->definitions();

        // بنحفظ اللي اتغيّر بس، ومن غير events عشان مانلغيش الكاش مع كل حقل —
        // وبعدين نلغيه مرة واحدة في الآخر.
        $changed = false;

        DB::transaction(function () use ($request, $settings, &$changed) {
            foreach ($request->input('settings', []) as $key => $value) {
                if (!$settings->has($key)) continue;
                if ($settings[$key]->type === 'password' && empty($value)) continue;

                $setting = $settings[$key];
                $setting->value = $value;

                if ($setting->isDirty('value')) {
                    $setting->saveQuietly();
                    $changed = true;
                }
            }
        });

        if ($changed) {
            Settings::clearCache();
        }

        $this->writeEnv([
            'APP_NAME'          => Settings::get('site_name', 'Rhmany'),
            'APP_TIMEZONE'      => Settings::get('app_timezone'),
            'APP_ENV'           => Settings::get('app_env'),
            'APP_DEBUG'         => Settings::get('app_env') === 'production' ? 'false' : 'true',
            'MAIL_MAILER'       => Settings::get('mail_driver', 'smtp'),
            'MAIL_HOST'         => Settings::get('mail_host', 'smtp.mailtrap.io'),
            'MAIL_PORT'         => Settings::get('mail_port', '587'),
            'MAIL_USERNAME'     => Settings::get('mail_username', 'info@rhmany.com'),
            'MAIL_PASSWORD'     => Settings::get('mail_password', 'password'),
            'MAIL_SCHEME'       => Settings::get('mail_encryption', 'none'),
            'MAIL_FROM_NAME'    => Settings::get('mail_from_name', 'Rhmany'),
            'MAIL_FROM_ADDRESS' => Settings::get('mail_from_address', 'hello@example.com'),
        ]);

        return back()->with('success', __('alert.done_update'));
    }

    /**
     * كتابة كل متغيرات الـ .env مرة واحدة (قراءة + كتابة واحدة للملف)
     * بدل ما setEnvVal() تفتح الملف وتكتبه ١٣ مرة. نفس شكل القيم بتاعها.
     */
    protected function writeEnv(array $values): void
    {
        $path = base_path('.env');

        if (!file_exists($path) || !is_writable($path)) {
            throw new \Exception('The .env file does not exist or is not writable.');
        }

        $env = file($path, FILE_IGNORE_NEW_LINES);

        foreach ($values as $key => $value) {
            $line = $key . '="' . addslashes((string) $value) . '"';
            $found = false;

            foreach ($env as $index => $existing) {
                if (strpos($existing, "{$key}=") === 0) {
                    $env[$index] = $line;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $env[] = $line;
            }
        }

        $content = implode("\n", $env) . "\n";

        // مانكتبش لو مفيش تغيير فعلي
        if ($content !== file_get_contents($path)) {
            file_put_contents($path, $content, LOCK_EX);
        }
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
