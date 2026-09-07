<?php

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
| Login/logout and password recovery screens + their related mail templates.
|
| Note: 'welcome' takes a :name placeholder instead of calling setting() here.
| Language files are loaded before the helpers are ready, so calling
| setting() inside the file used to break any artisan command reading
| translations.
|
|     __('auth.welcome', ['name' => setting('site_name')])
*/

return [

    /*
    | Core Laravel messages — the framework calls these itself
    | on failed login or when the attempt limit is exceeded.
    */
    'failed'   => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',

    /*
    | Login screens
    */
    'welcome'         => 'Welcome to :name',
    'please_signin'   => 'Please sign in to your account and start the adventure',
    'remember_me'     => 'Remember me',
    'forgot_password' => 'Forgot your password?',
    'logout'          => 'Log out',
    'back_to_login'   => 'Back to login',

    'reset_password'       => 'Reset password',
    'enter_email_to_reset' => 'Enter your email to reset your password',

    'done_login'          => 'Logged in successfully',
    'done_logout'         => 'Logged out successfully',
    'done_send_password'  => 'The new password has been sent to your email',
    'invalid_credentials' => 'These credentials do not match our records.',
    'login_alert'         => 'Please log in to continue.',
    'email_send_failed'   => 'Failed to send the email. Please try again later.',
    'too_many_attempts'   => 'Too many attempts. Please try again in :time.',

    /*
    | Password reset flow using a temporary link
    */
    'reset_link_sent'       => 'If that email is registered with us, a password reset link will arrive within a few minutes.',
    'reset_link_invalid'    => 'This reset link is invalid or has expired. Please request a new one.',
    'password_reset_done'   => 'Your password has been changed successfully. You can log in now.',
    'set_new_password'      => 'Set a new password',
    'set_new_password_hint' => 'Choose a strong password for your account, and never share it with anyone.',
    'new_password'          => 'New password',
    'confirm_password'      => 'Confirm password',
    'save_new_password'     => 'Save password',
    'password_rules'        => 'At least 8 characters, containing letters and numbers.',

    /*
    | Mail templates
    */
    'email' => [
        'reset_link' => [
            'title'       => 'Password reset link',
            'welcome'     => 'Welcome',
            'message'     => 'We received a request to reset your account password. Click the button below to set a new one. This link is valid for :minutes minutes only.',
            'action'      => 'Set a new password',
            'fallback'    => 'Or copy the following link and paste it into your browser',
            'alert'       => 'If you did not request a password reset, ignore this email — your account is safe and nothing will change.',
            'footer_note' => 'This link works only once and expires automatically after the stated period.',
        ],

        'reset_password' => [
            'title'        => 'Reset password',
            'welcome'      => 'Welcome',
            'new_password' => 'New password',
            'login_now'    => 'Log in now',
            'alert'        => 'For your account security, we recommend changing your password right after your first login. Do not share these details with anyone.',
            'message'      => 'Your account password has been reset successfully.<br>Below are your new login details, please keep them somewhere safe.',
            'footer_note'  => 'If you did not request a password reset, no action is required. You can safely ignore this email.',
        ],
    ],

];
