{{--
    صفحة رسالة عامة لحالات الرابط (غير موجود / منتهي / موقوف).

    مستقلة تمامًا عن layouts.admin.app: الزائر هنا مش مشرف وغالبًا
    مش مسجّل دخول أصلاً، والصفحة لازم تشتغل من غير أي أصول إدارية.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $heading }}</title>

    <style>
        :root {
            --bg: #f5f5f9;
            --card: #ffffff;
            --text: #444050;
            --muted: #8a8996;
            --border: #e6e6ee;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #25293c;
                --card: #2f3349;
                --text: #d5d6de;
                --muted: #9295a6;
                --border: #444661;
            }
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: var(--bg);
            color: var(--text);
            font-family: "Segoe UI", Tahoma, system-ui, -apple-system, sans-serif;
        }

        .box {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 40px 32px;
            max-width: 440px;
            width: 100%;
            text-align: center;
            box-shadow: 0 2px 12px rgba(0, 0, 0, .06);
        }

        .icon {
            width: 72px;
            height: 72px;
            margin: 0 auto 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            background: rgba(138, 137, 150, .12);
            color: var(--muted);
        }

        h1 {
            font-size: 1.35rem;
            margin: 0 0 10px;
            font-weight: 600;
        }

        p {
            margin: 0;
            color: var(--muted);
            line-height: 1.7;
            font-size: .95rem;
        }
    </style>
</head>
<body>
    <div class="box">
        <div class="icon">&#128279;</div>
        <h1>{{ $heading }}</h1>
        <p>{{ $text }}</p>
    </div>
</body>
</html>
