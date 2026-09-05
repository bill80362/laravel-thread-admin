<?php

use App\Http\Controllers\ThreadsOAuthController;
use App\Http\Controllers\ThreadsWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/privacy-policy', function () {
    return view('legal-page', [
        'title' => '隱私政策',
        'sections' => [
            [
                'heading' => '我們收集哪些資訊',
                'body' => [
                    '當你使用 OO-Pilot 服務時，我們可能收集以下資訊：',
                    [
                        '你提供的帳號資訊，例如名稱、Email 與綁定的 Threads 帳號。',
                        '你透過系統建立的貼文內容、排程設定、圖片與回覆內容。',
                        '系統運作產生的紀錄，例如發文／回覆紀錄、錯誤訊息與使用量。',
                    ],
                ],
            ],
            [
                'heading' => '我們如何使用這些資訊',
                'body' => [
                    '我們收集的資訊僅用於提供與改善 OO-Pilot 服務，包括：',
                    [
                        '建立、排程並發布你的 Threads 貼文與回覆。',
                        '管理你綁定的多個 Threads 帳號。',
                        '提供使用量統計與技術支援。',
                        '保障服務安全、防止濫用並遵循法律義務。',
                    ],
                ],
            ],
            [
                'heading' => '資訊分享與對外揭露',
                'body' => [
                    '我們不會出售你的個人資訊。我們僅在以下情況分享必要資訊：',
                    [
                        '當你授權我們連結 Threads 帳號時，與 Meta（Threads）API 交換必要資料。',
                        '依法律要求或主管機關命令。',
                        '為保護 OO-Pilot 的權利、安全或防詐欺而必要時。',
                    ],
                ],
            ],
            [
                'heading' => '資料保存',
                'body' => [
                    '我們保留你的資料以滿足服務需求與法律義務。你可以隨時透過我們的服務刪除貼文或綁定帳號，相關紀錄將依內部政策處理。',
                ],
            ],
            [
                'heading' => '你的權利',
                'body' => [
                    '你有權查閱、更正或刪除與你有關的個人資訊。你也可以要求停止處理你的資料，或取消授予 OO-Pilot 的 Threads 帳號連結權限。',
                ],
            ],
            [
                'heading' => '聯絡方式',
                'body' => [
                    '如對本隱私政策有任何疑問，歡迎聯繫我們：',
                    [
                        'OO-Pilot（OO-Pilot 一人衝，AI 攻）',
                        '聯絡電話：<a href="tel:0987653382">0987-653382</a>（業務洽詢 Donnie）',
                    ],
                ],
            ],
        ],
    ]);
});

Route::get('/terms-of-service', function () {
    return view('legal-page', [
        'title' => '服務條款',
        'sections' => [
            [
                'heading' => '接受條款',
                'body' => [
                    '當你使用 OO-Pilot 服務，即表示你已閱讀、了解並同意本服務條款。若你不同意任一條款，請勿使用本服務。',
                ],
            ],
            [
                'heading' => '帳號與服務使用',
                'body' => [
                    '你須依我們提供的方式建立並使用帳號，並負責維護帳號資訊的正確性與保密性。你同意將 Threads 帳號授權連結予本服務，並授權本服務代為建立貼文與回覆。',
                ],
            ],
            [
                'heading' => '使用者責任',
                'body' => [
                    '你應對透過本服務發布的所有內容與行為負責，並保證內容不侵害他人權利、不違反適用法律，也不含違法或有害之內容。',
                ],
            ],
            [
                'heading' => '付費與訂閱',
                'body' => [
                    '付費方案之費用、帳號數量與每日發文／回覆上限依你選擇的方案為準。除非另有約定，費用一經支付原則上不予退還；若服務無法正常提供，我們將依情況處理。',
                ],
            ],
            [
                'heading' => '免責聲明與責任限制',
                'body' => [
                    '本服務以「現況」提供，我們不對服務可用性、正確性或第三方（包括 Meta / Threads）之行為作任何擔保。在法律允許之最大範圍內，我們對因使用本服務所生之間接、特殊或衍生性損害不負責任。',
                ],
            ],
            [
                'heading' => '終止與暫停',
                'body' => [
                    '若你違反本條款、或有危害服務安全之行為，我們得暫停或終止你的帳號與服務使用權。你也可以隨時停止使用本服務並依你的需求刪除相關資料。',
                ],
            ],
            [
                'heading' => '準據法',
                'body' => [
                    '本服務條款以中華民國法律為準據法。因本條款所生之爭議，以台灣台中地方法院為第一審管轄法院（或其他適用管轄法院）。',
                ],
            ],
            [
                'heading' => '聯絡方式',
                'body' => [
                    '如對本服務條款有任何疑問，歡迎聯繫我們：',
                    [
                        'OO-Pilot（OO-Pilot 一人衝，AI 攻）',
                        '聯絡電話：<a href="tel:0987653382">0987-653382</a>（業務洽詢 Donnie）',
                    ],
                ],
            ],
        ],
    ]);
});

Route::prefix('threads/oauth')->group(function () {
    Route::get('redirect', [ThreadsOAuthController::class, 'redirect'])->name('threads.oauth.redirect');
    Route::get('callback', [ThreadsOAuthController::class, 'callback'])->name('threads.oauth.callback');
});

Route::prefix('threads')->group(function () {
    Route::match(['get', 'post'], 'webhook', [ThreadsWebhookController::class, 'handle'])->name('threads.webhook');
});
