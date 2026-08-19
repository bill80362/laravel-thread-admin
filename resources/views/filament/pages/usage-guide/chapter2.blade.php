<div class="space-y-4">
    <div class="flex items-start gap-3">
        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400 text-sm font-semibold shrink-0 mt-0.5">1</span>
        <div>
            <p class="font-medium text-gray-900 dark:text-white">設定環境變數</p>
            <p class="text-gray-600 dark:text-gray-400 mt-1">在專案根目錄的 <code class="bg-gray-200 dark:bg-gray-600 px-1 rounded text-xs">.env</code> 檔案中，設定以下兩個環境變數：</p>
            <pre class="bg-gray-900 text-gray-100 text-xs p-3 rounded overflow-x-auto mt-2"><code>THREADS_CLIENT_ID=步驟一取得的 App ID
THREADS_CLIENT_SECRET=步驟一取得的 App Secret</code></pre>
            <p class="text-gray-600 dark:text-gray-400 mt-2">設定完成後，系統會自動讀取這些憑證來進行 Threads API 呼叫。</p>
        </div>
    </div>

    <div class="flex items-start gap-3">
        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400 text-sm font-semibold shrink-0 mt-0.5">2</span>
        <div>
            <p class="font-medium text-gray-900 dark:text-white">綁定 Threads 帳號</p>
            <p class="text-gray-600 dark:text-gray-400 mt-1">進入左側選單「Threads 帳號」頁面 → 點擊「綁定 Threads 帳號」→ 系統會跳轉到 Threads 授權頁面 → 點擊「允許」→ 自動導回後台，綁定完成。</p>
        </div>
    </div>

    <div class="flex items-start gap-3">
        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400 text-sm font-semibold shrink-0 mt-0.5">3</span>
        <div>
            <p class="font-medium text-gray-900 dark:text-white">重新授權（當 token 失效時）</p>
            <p class="text-gray-600 dark:text-gray-400 mt-1">如果帳號狀態顯示「需重新授權」，在「Threads 帳號」頁面點擊「重新授權」即可更新 token，不需先解除綁定。</p>
        </div>
    </div>
</div>
