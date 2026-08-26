<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="OO-Pilot 一人衝，AI 攻。專為一人公司打造的最佳助手，集中管理多個 Threads 帳號，智慧排程發文，讓 AI 透過系統發文回覆，讓內容建立、發布與互動都能自動化。">
    <title>OO-Pilot 一人衝，AI 攻</title>
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

        html { scroll-behavior: smooth; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "PingFang TC", "Microsoft JhengHei", "Noto Sans TC", sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.7;
            -webkit-font-smoothing: antialiased;
        }

        .container {
            width: 100%;
            max-width: 1080px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* ===== Hero ===== */
        .hero {
            position: relative;
            padding: 140px 0 64px;
            text-align: center;
            background:
                radial-gradient(600px 300px at 80% -10%, rgba(79, 140, 255, .25), transparent 60%),
                radial-gradient(500px 300px at 10% 10%, rgba(255, 107, 53, .18), transparent 60%),
                var(--bg);
            overflow: hidden;
        }

        .hero .badge {
            display: inline-block;
            padding: 6px 16px;
            border: 1px solid var(--border);
            border-radius: 999px;
            font-size: 13px;
            color: var(--muted);
            margin-bottom: 24px;
            letter-spacing: 1px;
        }

        .hero h1 {
            font-size: clamp(34px, 8vw, 60px);
            font-weight: 800;
            line-height: 1.2;
            letter-spacing: 1px;
        }

        .hero h1 .grad {
            background: var(--gradient);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .hero .sub {
            max-width: 560px;
            margin: 20px auto 0;
            color: var(--muted);
            font-size: clamp(16px, 4vw, 19px);
        }

        .hero .cta {
            margin-top: 36px;
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            justify-content: center;
        }

        .btn {
            display: inline-block;
            padding: 14px 32px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            text-decoration: none;
            transition: transform .15s ease, box-shadow .15s ease;
        }

        .btn-primary {
            background: var(--gradient);
            color: #fff;
            box-shadow: 0 8px 24px rgba(255, 107, 53, .35);
        }

        .btn-ghost {
            border: 1px solid var(--border);
            color: var(--text);
        }

        .btn:hover { transform: translateY(-2px); }

        /* ===== Section ===== */
        section { padding: 64px 0; }

        .section-title {
            text-align: center;
            font-size: clamp(24px, 6vw, 34px);
            font-weight: 800;
            margin-bottom: 12px;
        }

        .section-desc {
            text-align: center;
            color: var(--muted);
            max-width: 560px;
            margin: 0 auto 40px;
        }

        /* ===== Features ===== */
        .features {
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px;
        }

        .feature {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
        }

        .feature .icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            margin-bottom: 14px;
            background: rgba(255, 107, 53, .12);
        }

        .feature h3 { font-size: 18px; margin-bottom: 6px; }
        .feature p { color: var(--muted); font-size: 15px; }

        /* ===== Why ===== */
        .why {
            background: var(--bg-soft);
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
        }

        .why .quote {
            text-align: center;
            font-size: clamp(20px, 5vw, 28px);
            font-weight: 700;
            max-width: 640px;
            margin: 0 auto;
        }

        .why .quote .grad {
            background: var(--gradient);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .why .quote-sub {
            text-align: center;
            color: var(--muted);
            margin-top: 16px;
        }

        /* ===== Pricing ===== */
        .pricing {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            max-width: 1080px;
            margin: 0 auto;
        }

        .plan {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 32px 28px;
            position: relative;
        }

        .plan.popular {
            border-color: var(--accent);
            box-shadow: 0 12px 40px rgba(255, 107, 53, .18);
        }

        .plan.custom {
            border-color: var(--accent-2);
            box-shadow: 0 12px 40px rgba(79, 140, 255, .15);
        }

        .plan-btn {
            margin-top: 20px;
            width: 100%;
            text-align: center;
        }

        .plan .tag {
            position: absolute;
            top: -14px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--gradient);
            color: #fff;
            font-size: 13px;
            font-weight: 700;
            padding: 5px 16px;
            border-radius: 999px;
            white-space: nowrap;
        }

        .plan h3 { font-size: 20px; margin-bottom: 4px; }

        .plan .price {
            font-size: 34px;
            font-weight: 800;
            margin: 12px 0 4px;
        }

        .plan .price small {
            font-size: 15px;
            font-weight: 400;
            color: var(--muted);
        }

        .plan .plan-desc { color: var(--muted); font-size: 14px; margin-bottom: 20px; }

        .plan ul { list-style: none; }

        .plan li {
            padding: 8px 0;
            border-top: 1px solid var(--border);
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .plan li::before {
            content: "✓";
            color: var(--accent);
            font-weight: 700;
            flex-shrink: 0;
        }

        /* ===== CTA ===== */
        .cta-section { text-align: center; }

        .cta-section h2 {
            font-size: clamp(24px, 6vw, 34px);
            font-weight: 800;
            margin-bottom: 12px;
        }

        .cta-section p { color: var(--muted); max-width: 520px; margin: 0 auto 32px; }

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

        .topbar .contact {
            display: flex;
            align-items: center;
            gap: 10px;
            white-space: nowrap;
        }

        .topbar .contact .label {
            color: var(--muted);
            font-size: 13px;
        }

        .topbar .contact .phone {
            font-size: 15px;
            font-weight: 800;
            color: var(--accent);
            text-decoration: none;
            letter-spacing: .5px;
        }

        /* 手機版隱藏「業務洽詢」文字，只留號碼 */
        @media (max-width: 480px) {
            .topbar .contact .label { display: none; }
            .topbar .brand { font-size: 14px; }
        }

        /* ===== Footer ===== */
        footer {
            padding: 32px 0;
            text-align: center;
            color: var(--muted);
            font-size: 14px;
            border-top: 1px solid var(--border);
        }

        /* ===== RWD: 平板以上 ===== */
        @media (min-width: 640px) {
            .features { grid-template-columns: repeat(2, 1fr); }
        }

        @media (min-width: 900px) {
            .features { grid-template-columns: repeat(3, 1fr); }
            .pricing { grid-template-columns: repeat(3, 1fr); }
        }
    </style>
</head>
<body>

    <!-- Topbar -->
    <nav class="topbar">
        <div class="container">
            <span class="brand">OO-<span class="grad">Pilot</span> 一人衝，AI 攻</span>
            <div class="contact">
                <span class="label">業務洽詢 Donnie</span>
                <a class="phone" href="tel:0921515408">0982-321356</a>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <header class="hero">
        <div class="container">
            <span class="badge">One Operator. One AI Attack.</span>
            <h1>我挺<br><span class="grad">你</span></h1>
            <p class="sub">
                專為單打獨鬥的你打造的最佳助手，<br>
                集中管理多個 Threads 帳號，輕鬆排程發文，<br>
                讓 AI 透過系統發文回覆，內容建立、發布與互動全面自動化。
            </p>
            <div class="cta">
                <a href="tel:0921515408" class="btn btn-primary">業務洽詢 Donnie 0982-321356</a>
            </div>
        </div>
    </header>

    <!-- Features -->
    <section id="features">
        <div class="container">
            <h2 class="section-title">一個平台，管理你的 Threads 軍隊</h2>
            <p class="section-desc">從內容建立、排程發布，到留言互動，把繁瑣的 Threads 日常工作集中在一個平台，然後透過 AI 操作它!</p>
            <div class="features">
                <div class="feature">
                    <div class="icon">👥</div>
                    <h3>多帳號管理</h3>
                    <p>一次管理多個 Threads 帳號，不必反覆切換。</p>
                </div>
                <div class="feature">
                    <div class="icon">📅</div>
                    <h3>智慧排程</h3>
                    <p>提前安排貼文，讓內容按照計畫自動發布。</p>
                </div>
                <div class="feature">
                    <div class="icon">🖼️</div>
                    <h3>圖片發文</h3>
                    <p>支援圖文貼文，單篇最多 10 張圖片。</p>
                </div>
                <div class="feature">
                    <div class="icon">💬</div>
                    <h3>回覆管理</h3>
                    <p>查看貼文回覆，快速掌握讀者互動。</p>
                </div>
                <div class="feature">
                    <div class="icon">⚡</div>
                    <h3>自動回覆</h3>
                    <p>直接管理並回覆 Threads 留言，提升互動效率。</p>
                </div>
                <div class="feature">
                    <div class="icon">🤖</div>
                    <h3>AI 透過系統發文回覆</h3>
                    <p>讓 AI 直接在系統中建立貼文、發布內容與回覆留言，不只是幫你寫內容。</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Why -->
    <section class="why">
        <div class="container">
            <p class="quote">少一點操作，<span class="grad">多一點產出。</span></p>
        </div>
    </section>

    <!-- Pricing -->
    <section id="pricing">
        <div class="container">
            <h2 class="section-title">選擇適合你的方案</h2>
            <p class="section-desc">無論是個人創作者還是品牌行銷人，都有適合你的方案。</p>
            <div class="pricing">
                <div class="plan">
                    <h3>入門版</h3>
                    <div class="price">NT$1,000 <small>/ 月</small></div>
                    <p class="plan-desc">適合個人創作者與單一品牌</p>
                    <ul>
                        <li>1 個 Threads 帳號</li>
                        <li>每帳號每日最多 5 篇</li>
                        <li>發文排程</li>
                        <li>圖片發文</li>
                        <li>回覆管理</li>
                        <li>AI 透過系統發文回覆</li>
                    </ul>
                </div>
                <div class="plan popular">
                    <span class="tag">最推薦</span>
                    <h3>專業版</h3>
                    <div class="price">NT$2,000 <small>/ 月</small></div>
                    <p class="plan-desc">適合品牌、行銷人與多帳號經營</p>
                    <ul>
                        <li>3 個 Threads 帳號</li>
                        <li>每帳號每日最多 10 篇</li>
                        <li>發文排程</li>
                        <li>圖片發文</li>
                        <li>回覆管理</li>
                        <li>AI 透過系統發文回覆</li>
                    </ul>
                </div>
                <div class="plan custom">
                    <h3>訂製版</h3>
                    <div class="price">客製化報價</div>
                    <p class="plan-desc">適合大型品牌、企業與特殊需求</p>
                    <ul>
                        <li>帳號數量彈性調整</li>
                        <li>發文上限客製化</li>
                        <li>專屬功能開發</li>
                        <li>優先技術支援</li>
                        <li>詳細與業務討論</li>
                    </ul>
                    <a class="btn btn-ghost plan-btn" href="tel:0921515408">立即洽詢</a>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="cta-section">
        <div class="container">
            <h2>一人衝，AI 攻，<br>一人公司也能高效運轉。</h2>
        </div>
    </section>

    <footer>
        <div class="container">
            © {{ date('Y') }} OO-Pilot — One Operator. One AI Attack.<br>
            oo-pilot.com
        </div>
    </footer>

</body>
</html>
