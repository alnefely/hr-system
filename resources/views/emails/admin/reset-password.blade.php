<!DOCTYPE html>
<html
    lang="{{ vuexy_setting('lang') }}"
    dir="{{ vuexy_setting('dir') }}"
>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/x-icon" href="{{ url(setting('site_favicon') ?? '/') }}" />
<title>{{ __('auth.email.reset_password.title') }}</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;900&display=swap');
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body {
    font-family: 'Cairo', sans-serif;
    background-color: #0a0f1e;
    color: #e8eaf0;
    padding: 40px 20px;
    min-height: 100vh;
    direction: {{ vuexy_setting('dir') }};
  }
  .wrapper {
    max-width: 600px;
    margin: 0 auto;
  }

  /* Header */
  .header {
    text-align: center;
    margin-bottom: 8px;
  }
  .header .logo-ring {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 72px;
    height: 72px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6c63ff, #3ecfcf);
    margin-bottom: 20px;
    box-shadow: 0 0 40px rgba(108,99,255,0.5);
  }
  .header .logo-ring img {
    width: 36px;
    height: 36px;
  }

  /* Card */
  .card {
    background: #111827;
    border-radius: 20px;
    overflow: hidden;
    border: 1px solid rgba(108,99,255,0.2);
    box-shadow: 0 30px 80px rgba(0,0,0,0.5);
  }

  /* Top stripe */
  .card-stripe {
    height: 5px;
    background: linear-gradient(90deg, #6c63ff, #3ecfcf, #6c63ff);
    background-size: 200% 100%;
  }

  .card-body {
    padding: 44px 48px;
  }

  /* Greeting */
  .greeting {
    font-size: 13px;
    font-weight: 300;
    color: #7c8fa6;
    letter-spacing: 2px;
    text-transform: uppercase;
    margin-bottom: 6px;
  }
  .name {
    font-size: 26px;
    font-weight: 700;
    color: #ffffff;
    margin-bottom: 28px;
  }

  /* Divider */
  .divider {
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(108,99,255,0.4), transparent);
    margin-bottom: 28px;
  }

  /* Message */
  .message {
    font-size: 15px;
    font-weight: 300;
    color: #9ba8b8;
    line-height: 1.9;
    margin-bottom: 32px;
  }

  /* Credentials box */
  .credentials {
    background: #0d1525;
    border: 1px solid rgba(62,207,207,0.2);
    border-radius: 14px;
    padding: 28px 32px;
    margin-bottom: 36px;
    position: relative;
    overflow: hidden;
  }
  .credentials::before {
    content: '';
    position: absolute;
    top: 0; right: 0;
    width: 120px; height: 120px;
    background: radial-gradient(circle, rgba(108,99,255,0.1) 0%, transparent 70%);
    border-radius: 50%;
  }
  .cred-row {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 18px;
  }
  .cred-row:last-child { margin-bottom: 0; }
  .cred-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: rgba(108,99,255,0.15);
    border: 1px solid rgba(108,99,255,0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }
  .cred-icon svg {
    width: 18px; height: 18px;
    stroke: #6c63ff;
    fill: none;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
  }
  .cred-content { flex: 1; }
  .cred-label {
    font-size: 11px;
    color: #4a5568;
    font-weight: 600;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    margin-bottom: 3px;
  }
  .cred-value {
    font-size: 15px;
    color: #e2e8f0;
    font-weight: 600;
    direction: ltr;
    text-align: right;
  }
  .cred-value.password {
    font-family: 'Courier New', monospace;
    font-size: 16px;
    color: #3ecfcf;
    letter-spacing: 2px;
    background: rgba(62,207,207,0.08);
    padding: 4px 10px;
    border-radius: 6px;
    display: inline-block;
  }

  /* CTA Button */
  .btn-wrap { text-align: center; margin-bottom: 36px; }
  .btn {
    display: inline-block;
    padding: 15px 44px;
    background: linear-gradient(135deg, #6c63ff, #3ecfcf);
    color: #ffffff !important;
    text-decoration: none;
    border-radius: 50px;
    font-size: 15px;
    font-weight: 700;
    font-family: 'Cairo', sans-serif;
    letter-spacing: 0.5px;
    box-shadow: 0 8px 30px rgba(108,99,255,0.4);
  }

  /* Warning */
  .warning {
    background: rgba(245,158,11,0.08);
    border: 1px solid rgba(245,158,11,0.2);
    border-radius: 10px;
    padding: 14px 18px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 32px;
  }
  .warning svg {
    width: 18px; height: 18px;
    stroke: #f59e0b;
    fill: none;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
    flex-shrink: 0;
    margin-top: 2px;
  }
  .warning p {
    font-size: 13px;
    color: #d4a847;
    line-height: 1.7;
  }

  /* Footer */
  .card-footer {
    border-top: 1px solid rgba(255,255,255,0.05);
    padding: 24px 48px;
    text-align: center;
  }
  .footer-site {
    font-size: 13px;
    font-weight: 700;
    color: #6c63ff;
    margin-bottom: 6px;
  }
  .footer-note {
    font-size: 11px;
    color: #374151;
    line-height: 1.6;
  }
</style>
</head>
<body>
<div class="wrapper">

  <!-- Header Logo -->
  <div class="header">
    <div class="logo-ring">
      <img src="{{ url(setting('site_logo') ?? '/' ) }}" alt="{{ setting('site_name') }}">
    </div>
  </div>

  <div class="card">
    <div class="card-stripe"></div>

    <div class="card-body">

      <p class="greeting">{{ __('auth.email.reset_password.welcome') }}</p>
      <h1 class="name">{{ $name }} 👋</h1>

      <div class="divider"></div>

      <p class="message">{!! __('auth.email.reset_password.message', ['name' => $name, 'new_password' => $new_password, 'login_url' => $login_url]) !!}</p>

      <!-- Credentials -->
      <div class="credentials">
        <div class="cred-row">
          <div class="cred-icon">
            <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          </div>
          <div class="cred-content">
            <div class="cred-label">{{ __('auth.email.reset_password.new_password') }}</div>
            <div class="cred-value password">{{ $new_password }}</div>
          </div>
        </div>
      </div>

      <!-- CTA -->
      <div class="btn-wrap">
        <a href="{{ $login_url }}" class="btn">{{ __('auth.email.reset_password.login_now') }}</a>
      </div>

      <!-- Warning -->
      <div class="warning">
        <svg viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        <p>{{ __('auth.email.reset_password.alert') }}</p>
      </div>

    </div>

    <!-- Footer -->
    <div class="card-footer">
      <div class="footer-site">{{ setting('site_name') }}</div>
      <div class="footer-note">{{ __('auth.email.reset_password.footer_note') }}</div>
    </div>
  </div>

</div>
</body>
</html>