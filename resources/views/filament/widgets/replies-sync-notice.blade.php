<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-start gap-3">
            <x-filament::icon
                icon="heroicon-o-information-circle"
                class="h-5 w-5 text-primary-500 shrink-0 mt-0.5"
            />

            <div class="space-y-1">
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    回覆資料每 {{ $syncInterval }} 分鐘自動同步一次，新留言可能不會立即顯示。請稍候片刻後重新整理頁面。
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    回覆發佈採用兩階段機制，建立後約 {{ $publishDelaySeconds }} 秒才會顯示在 Threads 上。
                </p>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
