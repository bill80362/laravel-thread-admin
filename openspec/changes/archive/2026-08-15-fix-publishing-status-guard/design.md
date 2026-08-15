## Context

`PublishScheduledPost` 是排程發文的 Queue Job，採用兩階段發佈流程：

```
第一階段 (creationId = null)
  status: scheduled → 建立 media container → status: publishing → dispatch 第二階段 (delay 30s)

第二階段 (creationId = "xxx")
  status: publishing → publishContainer() → status: published
```

目前的 guard clause：

```php
if ($post === null || $post->status !== PostStatus::Scheduled) {
    return;
}
```

只允許 `scheduled` 狀態通過，導致第二階段（status 已是 `publishing`）被攔截，`publishContainer()` 永遠不會執行。

## Goals / Non-Goals

**Goals:**
- 讓兩階段流程都能正確通過 guard clause，使發文流程能完成到 `published` 狀態

**Non-Goals:**
- 不更動兩階段的 dispatch 邏輯與 `delay()` 時間
- 不更動 `ThreadsClient` 的 API
- 不處理 `QUEUE_CONNECTION=sync` 下 delay 被忽略的行為（此為既有行為，非本 bug 範圍）

## Decisions

**決策：guard clause 依 `creationId` 區分兩階段的預期 status**

```php
$expectedStatus = $this->creationId === null
    ? PostStatus::Scheduled
    : PostStatus::Publishing;

if ($post === null || $post->status !== $expectedStatus) {
    return;
}
```

- 理由：`creationId` 是區分第一/第二階段的唯一判斷依據；第一階段 `creationId = null`，第二階段帶有值。以此推導各自預期的 status，最小且精確地修正 guard。
- 保留 guard 的防重複執行特性：若貼文已 `published` 或 `failed`，兩階段都不會再被重複執行。

## Risks / Trade-offs

- 低風險：僅修改 guard 條件的判斷邏輯，不影響其餘發佈流程。
- 第二階段仍可能因重試被執行多次（status 已是 `publishing` 時）——但這與現有設計一致，且 Threads 端 publishContainer 是否冪等不在本變更範圍內。
