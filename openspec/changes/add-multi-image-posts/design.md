## Context

目前 `Post` 僅有單一 `image_path` 欄位，`ThreadsClient` 僅實作單圖發佈（`createImageContainer`），`PublishScheduledPost` Job 為兩階段流程（建立 container → 等待 → 發佈）。

Threads API 的 Carousel 需要三步驟：為每張圖建立 `is_carousel_item=true` 的 container → 建立 `media_type=CAROUSEL` 的包裝 container → 發佈。Carousel 最少 2 張、API 上限 20 張，本需求限制最多 10 張。

## Goals / Non-Goals

**Goals:**
- 支援 2-10 張圖片的 Carousel 發佈
- 後台可上傳多圖並拖曳排序
- MCP 可傳入多個圖片 URL
- 既有單圖貼文行為完全不受影響
- 既有單圖資料自動遷移至新結構

**Non-Goals:**
- 不支援影片（Carousel 可混搭影片，但本需求僅限圖片）
- 不改變純文字發文流程
- 不改變回覆（Reply）的圖片機制（回覆目前無圖片）

## Decisions

### 1. 資料模型：獨立 `post_images` 表（非 JSON 欄位）

**選擇**：新建 `post_images` 關聯表，`Post` hasMany `PostImage`。

**替代方案**：在 `posts` 加 JSON 欄位存多個路徑。
- ❌ JSON 欄位難以個別查詢、難以做排序、不利於未來擴充（如個別圖片的 alt text）
- ✅ 關聯表正規化、支援排序、Filament Repeater 原生支援 `->relationship()`

**Schema**:
```sql
CREATE TABLE post_images (
    id INTEGER PRIMARY KEY,
    post_id INTEGER NOT NULL REFERENCES posts(id) ON DELETE CASCADE,
    image_path TEXT NOT NULL,
    sort_order INTEGER NOT NULL DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### 2. 向後相容：drop `posts.image_path` 並遷移資料

**選擇**：Migration 中將既有 `image_path` 資料遷移至 `post_images` 後，直接 drop `posts.image_path` 欄位。

**原因**：
- 乾淨俐落，不留 deprecated 欄位
- 既有資料在 migration 中一次性遷移，不需考慮後續不一致問題
- 無需考慮回滾（專案尚在早期階段）

### 3. PublishScheduledPost：三階段流程

**選擇**：擴充 Job 參數，新增 `$childIds`（array|null），支援三種階段：

```
┌──────────────────────────────────────────────────────────────┐
│                  PublishScheduledPost 流程                     │
├──────────────────────────────────────────────────────────────┤
│                                                              │
│  Stage 1: creationId=null, childIds=null                     │
│  ┌──────────────────────────────────────────────────────┐   │
│  │ 圖片數 = 0 → createTextContainer                     │   │
│  │           → dispatch(creationId)                     │   │
│  │ 圖片數 = 1 → createImageContainer                    │   │
│  │           → dispatch(creationId)                     │   │
│  │ 圖片數 ≥ 2 → createCarouselItemContainer × N         │   │
│  │           → dispatch(childIds=[id1,id2,...])         │   │
│  └──────────────────────────────────────────────────────┘   │
│                           ↓                                  │
│  Stage 2: creationId=null, childIds=[...]                    │
│  ┌──────────────────────────────────────────────────────┐   │
│  │ createCarouselContainer(children=childIds)            │   │
│  │ → dispatch(creationId=carouselContainerId)            │   │
│  └──────────────────────────────────────────────────────┘   │
│                           ↓                                  │
│  Stage 3: creationId=xxx (單圖或 carousel 都一樣)            │
│  ┌──────────────────────────────────────────────────────┐   │
│  │ publishContainer(creationId) → 完成                   │   │
│  └──────────────────────────────────────────────────────┘   │
│                                                              │
└──────────────────────────────────────────────────────────────┘
```

**替代方案**：拆成兩個 Job（`PublishScheduledPost` + `PublishCarouselPost`）。
- ❌ 程式碼重複、維護兩份發佈邏輯
- ✅ 單一 Job 三階段，共用錯誤處理與重試機制

### 4. ThreadsClient：新增兩個 Carousel 方法

**選擇**：在 `ThreadsClient` 新增：
- `createCarouselItemContainer(account, imageUrl): string` — 參數含 `is_carousel_item=true`
- `createCarouselContainer(account, childrenIds, ?text): string` — 參數含 `media_type=CAROUSEL`, `children`

保留原有 `createImageContainer()` 供單圖使用。

### 5. Filament 表單：Repeater + FileUpload

**選擇**：使用 `Repeater::make('images')->relationship()` 搭配內部 `FileUpload`，啟用 `->reorderable()`。

```php
Repeater::make('images')
    ->relationship()
    ->schema([
        FileUpload::make('image_path')
            ->image()
            ->disk('public')
            ->directory('posts')
            ->acceptedFileTypes(['image/jpeg', 'image/png'])
            ->maxSize(8192),
    ])
    ->orderColumn('sort_order')
    ->reorderable()
    ->maxItems(10)
    ->addActionLabel('新增圖片')
    ->columns(1)
```

**替代方案**：`FileUpload::make('images')->multiple()->reorderable()`。
- ❌ 資料存為 JSON 陣列，與關聯表模式不一致
- ✅ Repeater 直接對應 `post_images` 關聯，CRUD 自動處理

### 6. Filament 列表：Grid 卡片佈局

**選擇**：將 `ListPosts` 從 Table 改為 Grid 卡片佈局，每張卡片顯示首張圖片縮圖。

- 使用 Filament 的 Grid 佈局（非 Table）
- 每張卡片顯示：首張圖片縮圖（`sort_order = 0`）、帳號 `@username`、狀態 badge、內容預覽（截斷 50 字）、排程時間
- 多圖時在縮圖上疊加 `+N` badge（如 `+2`）
- 無圖片時顯示「無圖片」placeholder
- 卡片上直接放 EditAction / DeleteAction
- 保留搜尋與篩選功能

### 7. MCP：`image_url` → `image_urls` 陣列

**選擇**：將 `CreatePostTool` 的 `image_url`（string）改為 `image_urls`（array of strings），驗證最多 10 個。

**原因**：這是 **BREAKING** 變更（參數名稱與型別改變），但 MCP 尚在早期階段，且變更語意更清晰。

### 8. PostService::create()：統一處理單圖與多圖

**選擇**：`create()` 方法接受 `image_paths` 陣列（新參數），同時保留 `image_path` 和 `image_url` 的向後相容處理：

```php
// 向後相容：單一 image_path / image_url → 轉為陣列
if (!empty($data['image_path'])) {
    $imagePaths = [$data['image_path']];
} elseif (!empty($data['image_url'])) {
    $imagePaths = [$data['image_url']];
} elseif (!empty($data['image_urls'])) {
    $imagePaths = $data['image_urls'];
} elseif (!empty($data['image_paths'])) {
    $imagePaths = $data['image_paths'];
}
```

## Risks / Trade-offs

| 風險 | 緩解措施 |
|------|---------|
| Carousel 發佈時間較長（每張圖需建立 container） | 各 container 建立可並行？目前 API 無批次端點，只能序列呼叫。透過 Job 非同步處理不影響使用者 |
| 部分圖片 container 建立失敗 | 沿用現有重試機制（最多 3 次），全部失敗則標記 Post 為 Failed |
| MCP `image_url` → `image_urls` 為 breaking change | MCP 尚無外部使用者，變更成本低；在 schema description 中清楚說明 |
| Repeater 上傳多圖的 UX 較重 | 每張圖一個 Repeater item，可接受；後續可優化為拖曳上傳 |

## Migration Plan

1. 建立 `post_images` migration（新增表）
2. 在 migration 中將 `posts.image_path IS NOT NULL` 的資料遷移至 `post_images`（`sort_order = 0`）
3. Drop `posts.image_path` 欄位
4. 回滾：重建 `image_path` 欄位並從 `post_images` 還原資料

## Open Questions

- 無
