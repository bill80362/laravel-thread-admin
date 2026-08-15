# 設計：移除排程時間下限限制

## 變更範圍

單一檔案 `app/Filament/Resources/Posts/Schemas/PostForm.php`，`DateTimePicker::make('scheduled_at')` 欄位。

## 變更前

```php
DateTimePicker::make('scheduled_at')
    ->label('排程時間')
    ->required()
    ->minDate(now())
    ->native(false),
```

## 變更後

```php
DateTimePicker::make('scheduled_at')
    ->label('排程時間')
    ->required()
    ->default(now())
    ->native(false),
```

## 行為變化

| 情境 | 變更前 | 變更後 |
|------|--------|--------|
| 建立頁面初始值 | 空白 | 預設帶入當前時間 |
| 選擇過去時間 | ❌ 驗證失敗 | ✅ 允許 |
| 選擇未來時間 | ✅ 允許 | ✅ 允許 |
| 編輯頁面 | 顯示既有 `scheduled_at` 值 | 同左（`default` 僅在無值時生效） |

## 排程系統相容性

`RunThreadsScheduler::dispatchDuePosts()` 拾取條件為 `scheduled_at <= now()`，過去時間的貼文會被立即拾取發送，無需額外調整。
