<div
    x-data
    x-init="requestAnimationFrame(() => { const el = $root.querySelector('[data-reply-scroll]'); if (el) el.scrollTop = el.scrollHeight; })"
    style="display: flex; flex-direction: column; height: 100%;"
>
    {{-- 標題列 --}}
    <div style="border-bottom: 1px solid #e5e7eb; padding: 1rem 1.5rem;">
        <h3 style="font-size: 1rem; font-weight: 600; color: #111827; margin: 0;">回覆</h3>
    </div>

    {{-- 貼文內容 --}}
    @if ($post)
        <div style="border-bottom: 1px solid #e5e7eb; background-color: #f9fafb; padding: 1rem 1.5rem;">
            <p style="font-size: 0.75rem; font-weight: 500; color: #6b7280; margin: 0;">
                <span style="font-weight: 600; color: #111827;">{{ $post->threadsAccount?->username }}</span>
                · 排程發文 {{ $post->scheduled_at?->format('Y-m-d H:i') }}
            </p>
            @if ($post->text)
                <p style="margin: 0.25rem 0 0 0; font-size: 0.875rem; color: #1f2937; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">{{ $post->text }}</p>
            @endif
        </div>
    @endif

    {{-- 回覆串（可捲動區域） --}}
    <div data-reply-scroll class="reply-scroll" style="flex: 1; overflow-y: auto; padding: 1rem 1.5rem;">
        @if ($replies->isEmpty())
            <div style="display: flex; height: 100%; align-items: center; justify-content: center;">
                <p style="font-size: 0.875rem; color: #9ca3af; margin: 0;">尚無回覆</p>
            </div>
        @else
            <ul style="list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 1.25rem;">
                @foreach ($replies as $reply)
                    <li style="display: flex; gap: 0.75rem;">
                        {{-- 使用者頭像圈 --}}
                        <div style="display: flex; height: 2rem; width: 2rem; flex-shrink: 0; align-items: center; justify-content: center; border-radius: 9999px; background-color: #e5e7eb; font-size: 0.75rem; font-weight: 600; color: #4b5563;">
                            {{ strtoupper(mb_substr($reply->author_username ?: '?', 0, 1)) }}
                        </div>

                        <div style="min-width: 0; flex: 1;">
                            <div style="display: flex; align-items: baseline; justify-content: space-between; gap: 0.5rem;">
                                <span style="font-size: 0.75rem; font-weight: 600; color: #111827;">{{ $reply->author_username }}</span>
                                <span style="font-size: 0.75rem; color: #9ca3af;">{{ $reply->created_at?->diffForHumans() }}</span>
                            </div>
                            <p style="margin: 0.125rem 0 0 0; white-space: pre-wrap; word-break: break-word; font-size: 0.875rem; color: #1f2937;">{{ $reply->text }}</p>

                            {{-- 發佈狀態標記（僅本機發出的回覆） --}}
                            @if ($reply->source === \App\Enums\ReplySource::Manual)
                                @if ($reply->status === \App\Enums\ReplyStatus::New || $reply->status === \App\Enums\ReplyStatus::Publishing)
                                    <p style="margin: 0.25rem 0 0 0; font-size: 0.75rem; color: #2563eb; display: flex; align-items: center; gap: 0.375rem;">
                                        <span style="display: inline-block; width: 0.5rem; height: 0.5rem; border-radius: 9999px; background-color: #2563eb; animation: pulse 1s infinite;"></span>
                                        傳送中…
                                    </p>
                                @elseif ($reply->status === \App\Enums\ReplyStatus::Replied)
                                    <p style="margin: 0.25rem 0 0 0; font-size: 0.75rem; color: #16a34a;">已回覆</p>
                                @elseif ($reply->status === \App\Enums\ReplyStatus::Failed)
                                    <p style="margin: 0.25rem 0 0 0; font-size: 0.75rem; color: #dc2626;">發佈失敗</p>
                                @endif
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    {{-- 回覆輸入框 --}}
    <div style="border-top: 1px solid #e5e7eb; padding: 1rem 1.5rem;">
        @if ($errors->has('replyText'))
            <p style="margin: 0 0 0.5rem 0; font-size: 0.75rem; color: #dc2626;">{{ $errors->first('replyText') }}</p>
        @endif
        <form wire:submit="sendReply" style="display: flex; align-items: flex-end; gap: 0.5rem;">
            <textarea
                wire:model="replyText"
                wire:loading.attr="disabled"
                wire:target="sendReply"
                x-on:keydown.shift.enter.prevent="$wire.sendReply()"
                rows="2"
                style="display: block; width: 100%; resize: none; border-radius: 0.5rem; border: 1px solid #d1d5db; background-color: #ffffff; font-size: 0.875rem; color: #1f2937; padding: 0.5rem 0.75rem;"
                placeholder="回覆貼文… (Shift+Enter 送出)"
            ></textarea>
            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="sendReply"
                style="margin-left: 0.5rem; flex-shrink: 0; border-radius: 0.5rem; background-color: #2563eb; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 600; color: #ffffff; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 0.375rem;"
            >
                <span wire:loading.remove wire:target="sendReply">送出</span>
                <span wire:loading wire:target="sendReply">
                    <span style="display: inline-flex; align-items: center; gap: 0.375rem;">
                        <span class="btn-spinner"></span>
                        送出中…
                    </span>
                </span>
            </button>
        </form>
    </div>
</div>

<style>
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.3; }
    }

    .btn-spinner {
        display: inline-block;
        width: 1rem;
        height: 1rem;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-top-color: #ffffff;
        border-radius: 50%;
        animation: btn-spin 0.6s linear infinite;
    }

    @keyframes btn-spin {
        to { transform: rotate(360deg); }
    }

    button[disabled] {
        opacity: 0.6;
        cursor: not-allowed;
    }

    textarea:disabled {
        background-color: #f3f4f6;
        cursor: not-allowed;
    }
</style>

<script>
    // 送出回覆後（Livewire morph 更新 DOM）捲動到最底部
    document.addEventListener('livewire:init', function () {
        Livewire.hook('morph.updated', function ({ el, component }) {
            const container = el.querySelector('.reply-scroll');
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        });
    });
</script>
