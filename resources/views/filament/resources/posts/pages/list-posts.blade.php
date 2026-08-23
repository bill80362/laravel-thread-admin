<x-filament-panels::page>
    {{-- 用量提示條 --}}
    @php $usage = $this->getDailyUsageData(); @endphp
    <div class="mb-4 space-y-2">
        @if ($usage['post_max'] > 0)
            <div class="rounded-lg bg-white p-3 shadow-sm border border-gray-200">
                <div class="flex items-center justify-between text-sm">
                    <span class="font-medium">📊 今日發文用量</span>
                    <span>{{ $usage['post_sent'] }}/{{ $usage['post_max'] }}</span>
                </div>
                <div class="mt-1 h-2 w-full overflow-hidden rounded-full bg-gray-100">
                    <div class="h-full rounded-full bg-primary-500 transition-all"
                         style="width: {{ min(100, ($usage['post_sent'] / max(1, $usage['post_max'])) * 100) }}%">
                    </div>
                </div>
                <div class="mt-1 text-xs text-gray-500">
                    已發送 {{ $usage['post_sent'] }} 篇
                    @if ($usage['post_scheduled'] > 0)
                        · 排程中今日將發送 {{ $usage['post_scheduled'] }} 篇
                    @endif
                    · 剩餘 {{ max(0, $usage['post_max'] - $usage['post_sent']) }} 篇
                </div>
            </div>
        @endif

        @if ($usage['reply_max'] > 0)
            <div class="rounded-lg bg-white p-3 shadow-sm border border-gray-200">
                <div class="flex items-center justify-between text-sm">
                    <span class="font-medium">📊 今日回覆用量</span>
                    <span>{{ $usage['reply_sent'] }}/{{ $usage['reply_max'] }}</span>
                </div>
                <div class="mt-1 h-2 w-full overflow-hidden rounded-full bg-gray-100">
                    <div class="h-full rounded-full bg-success-500 transition-all"
                         style="width: {{ min(100, ($usage['reply_sent'] / max(1, $usage['reply_max'])) * 100) }}%">
                    </div>
                </div>
                <div class="mt-1 text-xs text-gray-500">
                    已回覆 {{ $usage['reply_sent'] }} 則
                    · 剩餘 {{ max(0, $usage['reply_max'] - $usage['reply_sent']) }} 則
                </div>
            </div>
        @endif
    </div>

    {{ $this->content }}

    @include('filament.resources.posts.pages.post-reply-drawer')
</x-filament-panels::page>
