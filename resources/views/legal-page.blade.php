<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="OO-Pilot 一人衝，AI 攻。{{ $title }}">
    <title>{{ $title }} — OO-Pilot</title>
    <style>
        :root {
            --bg: #0b0f1a;
            --bg-soft: #111827;
            --card: #1a2234;
            --border: #2a3550;
            --text: #f3f4f6;
            --muted: #9aa3b5;
            --accent: #ff6b35;
            --accent-2: #4f8cff;
            --gradient: linear-gradient(135deg, #ff6b35 0%, #ff9a5a 100%);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "PingFang TC", "Microsoft JhengHei", "Noto Sans TC", sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.8;
            -webkit-font-smoothing: antialiased;
        }

        .container {
            width: 100%;
            max-width: 820px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* ===== Topbar (fixed) ===== */
        .topbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            background: rgba(11, 15, 26, .85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
        }

        .topbar .container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding-top: 14px;
            padding-bottom: 14px;
            max-width: 1080px;
        }

        .topbar .brand {
            font-size: 16px;
            font-weight: 800;
            letter-spacing: .5px;
            color: var(--text);
            white-space: nowrap;
        }

        .topbar .brand .grad {
            background: var(--gradient);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .topbar .back {
            color: var(--accent);
            font-size: 14px;
            text-decoration: none;
            font-weight: 600;
            white-space: nowrap;
        }

        .topbar .back:hover { text-decoration: underline; }

        /* ===== Main ===== */
        main { padding: 120px 0 64px; }

        .doc-header {
            margin-bottom: 40px;
            padding: 40px 0 32px;
            border-bottom: 1px solid var(--border);
            background:
                radial-gradient(600px 200px at 80% -20%, rgba(79, 140, 255, .2), transparent 60%),
                radial-gradient(400px 200px at 10% -10%, rgba(255, 107, 53, .15), transparent 60%);
        }

        .doc-header h1 {
            font-size: clamp(30px, 6vw, 44px);
            font-weight: 800;
            letter-spacing: 1px;
        }

        .doc-header .updated {
            display: inline-block;
            margin-top: 12px;
            padding: 4px 12px;
            border: 1px solid var(--border);
            border-radius: 999px;
            font-size: 13px;
            color: var(--muted);
        }

        .section {
            margin-bottom: 36px;
        }

        .section h2 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid var(--gradient, var(--accent));
            border-bottom-color: var(--accent);
        }

        .section p {
            color: var(--text);
            font-size: 16px;
            margin-bottom: 10px;
        }

        .section p.muted { color: var(--muted); }

        .section ul {
            margin: 8px 0 8px 20px;
            color: var(--muted);
            font-size: 15px;
        }

        .section li { padding: 3px 0; }

        .section a {
            color: var(--accent-2);
            text-decoration: none;
            word-break: break-all;
        }

        .section a:hover { text-decoration: underline; }

        /* ===== Footer ===== */
        footer {
            padding: 32px 0;
            text-align: center;
            color: var(--muted);
            font-size: 14px;
            border-top: 1px solid var(--border);
        }

        footer .links {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
            margin-bottom: 12px;
        }

        footer .links a {
            color: var(--muted);
            text-decoration: none;
        }

        footer .links a:hover { color: var(--text); text-decoration: underline; }

        /* ===== RWD ===== */
        @media (max-width: 480px) {
            .topbar .back { font-size: 13px; }
        }
    </style>
</head>
<body>

    <!-- Topbar -->
    <nav class="topbar">
        <div class="container">
            <a class="brand" href="/">OO-<span class="grad">Pilot</span> 一人衝，AI 攻</a>
            <a class="back" href="/">&larr; 返回首頁</a>
        </div>
    </nav>

    <!-- Content -->
    <main>
        <div class="container">
            <header class="doc-header">
                <h1>{{ $title }}</h1>
                <span class="updated">最後更新：{{ $updatedAt ?? now()->format('Y 年 m 月 d 日') }}</span>
            </header>

            @foreach ($sections as $section)
                <section class="section">
                    <h2>{{ $section['heading'] }}</h2>
                    @foreach ($section['body'] as $paragraph)
                        @if (is_array($paragraph))
                            <ul>
                                @foreach ($paragraph as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p>{!! $paragraph !!}</p>
                        @endif
                    @endforeach
                </section>
            @endforeach
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="links">
                <a href="{{ url('/privacy-policy') }}">隱私政策</a>
                <a href="{{ url('/terms-of-service') }}">服務條款</a>
            </div>
            © {{ date('Y') }} OO-Pilot — One Operator. One AI Attack.<br>
            oo-pilot.com
        </div>
    </footer>

</body>
</html>
