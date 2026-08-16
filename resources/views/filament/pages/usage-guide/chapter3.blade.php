<div class="space-y-4">
    <div class="flex items-start gap-3">
        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400 text-sm font-semibold shrink-0 mt-0.5">1</span>
        <div>
            <p class="font-medium text-gray-900 dark:text-white">建立排程貼文</p>
            <p class="text-gray-600 dark:text-gray-400 mt-1">進入「排程發文」頁面 → 點擊「新增」→ 選擇目標帳號、輸入貼文內容（純文字，最多 500 字元）、設定發佈時間 → 點擊「建立」。</p>
        </div>
    </div>

    <div class="flex items-start gap-3">
        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400 text-sm font-semibold shrink-0 mt-0.5">2</span>
        <div>
            <p class="font-medium text-gray-900 dark:text-white">貼文狀態說明</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">貼文從建立到發佈會經過以下流程：</p>

            {{-- 狀態流程圖 --}}
            <div class="mt-4 flex flex-wrap items-center gap-2 text-sm">
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-gray-100 dark:bg-gray-700 px-3 py-2">
                    <span class="w-2 h-2 rounded-full bg-gray-400"></span>
                    <span class="font-medium text-gray-700 dark:text-gray-300">草稿</span>
                </span>
                <span class="text-gray-400 dark:text-gray-500">→</span>
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-amber-100 dark:bg-amber-900/40 px-3 py-2">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    <span class="font-medium text-amber-700 dark:text-amber-300">排程中</span>
                </span>
                <span class="text-gray-400 dark:text-gray-500">→</span>
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-blue-100 dark:bg-blue-900/40 px-3 py-2">
                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                    <span class="font-medium text-blue-700 dark:text-blue-300">發佈中</span>
                </span>
                <span class="text-gray-400 dark:text-gray-500">→</span>
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-green-100 dark:bg-green-900/40 px-3 py-2">
                    <span class="w-2 h-2 rounded-full bg-green-500"></span>
                    <span class="font-medium text-green-700 dark:text-green-300">已發佈</span>
                </span>
            </div>

            {{-- 失敗分支 --}}
            <div class="mt-2 flex flex-wrap items-center gap-2 text-sm">
                <span class="text-gray-400 dark:text-gray-500 ml-[88px]">↳ 失敗</span>
                <span class="text-gray-400 dark:text-gray-500">→</span>
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-red-100 dark:bg-red-900/40 px-3 py-2">
                    <span class="w-2 h-2 rounded-full bg-red-500"></span>
                    <span class="font-medium text-red-700 dark:text-red-300">失敗</span>
                </span>
                <span class="text-xs text-gray-500 dark:text-gray-400">（自動重試，最多 3 次）</span>
            </div>

            {{-- 各狀態說明 --}}
            <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-gray-400"></span>
                        <span class="font-medium text-gray-900 dark:text-white text-sm">草稿</span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">貼文已儲存但尚未排程</p>
                </div>
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        <span class="font-medium text-gray-900 dark:text-white text-sm">排程中</span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">已設定發佈時間，等待系統觸發</p>
                </div>
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                        <span class="font-medium text-gray-900 dark:text-white text-sm">發佈中</span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">系統正在發佈到 Threads（約需 30 秒）</p>
                </div>
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-green-500"></span>
                        <span class="font-medium text-gray-900 dark:text-white text-sm">已發佈</span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">貼文已成功發佈到 Threads</p>
                </div>
                <div class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50/50 dark:bg-red-900/10 p-3 sm:col-span-2">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-red-500"></span>
                        <span class="font-medium text-red-800 dark:text-red-300 text-sm">失敗</span>
                    </div>
                    <p class="text-xs text-red-600 dark:text-red-400 mt-1">發佈失敗，系統會自動重試（最多 3 次，間隔 60 / 120 / 180 秒）</p>
                </div>
            </div>
        </div>
    </div>

    <div class="flex items-start gap-3">
        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400 text-sm font-semibold shrink-0 mt-0.5">3</span>
        <div>
            <p class="font-medium text-gray-900 dark:text-white">自動發佈機制</p>
            <div class="mt-3 rounded-lg bg-gray-50 dark:bg-gray-700/30 p-4">
                <div class="flex items-start gap-2">
                    <span class="text-primary-600 dark:text-primary-400 mt-0.5">●</span>
                    <p class="text-gray-600 dark:text-gray-300 text-sm">系統每 <strong class="text-gray-900 dark:text-white">1 分鐘</strong> 自動檢查一次是否有到期的貼文</p>
                </div>
                <div class="flex items-start gap-2 mt-2">
                    <span class="text-primary-600 dark:text-primary-400 mt-0.5">●</span>
                    <p class="text-gray-600 dark:text-gray-300 text-sm">發佈流程：建立媒體容器 → 等待約 <strong class="text-gray-900 dark:text-white">30 秒</strong> → 正式發佈</p>
                </div>
                <div class="flex items-start gap-2 mt-2">
                    <span class="text-primary-600 dark:text-primary-400 mt-0.5">●</span>
                    <p class="text-gray-600 dark:text-gray-300 text-sm">失敗時自動重試，最多 <strong class="text-gray-900 dark:text-white">3 次</strong>（間隔 60 秒 / 120 秒 / 180 秒）</p>
                </div>
            </div>
        </div>
    </div>
</div>
