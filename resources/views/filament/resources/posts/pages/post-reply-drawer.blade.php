{{-- 回覆抽屜：由 ListPosts 頁面屬性控制開啟/關閉 --}}
@if ($replyDrawerOpen && $replyDrawerPostId)
    {{-- 遮罩 --}}
    <div
        x-show="$wire.replyDrawerOpen"
        x-transition.opacity
        x-cloak
        style="position: fixed; inset: 0; z-index: 40; background-color: rgba(17, 24, 39, 0.4);"
        @click="$wire.closeReplyDrawer()"
    ></div>

    {{-- 抽屜本體 --}}
    <div
        x-show="$wire.replyDrawerOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        x-cloak
        style="position: fixed; top: 0; bottom: 0; right: 0; z-index: 50; width: 100%; max-width: 28rem; background-color: #ffffff; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);"
    >
        {{-- 關閉按鈕 --}}
        <button
            type="button"
            style="position: absolute; right: 1rem; top: 1rem; z-index: 10; padding: 0.25rem; border-radius: 0.375rem; color: #9ca3af;"
            @click="$wire.closeReplyDrawer()"
        >
            <svg style="height: 1.25rem; width: 1.25rem;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        {{-- Livewire 元件（載入對應貼文的回覆） --}}
        <livewire:post-reply-drawer :post-id="$replyDrawerPostId" :key="'drawer-'.$replyDrawerPostId" />
    </div>
@endif
