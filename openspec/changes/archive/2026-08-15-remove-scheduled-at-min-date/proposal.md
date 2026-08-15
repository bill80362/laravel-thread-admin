# 移除排程時間下限限制

## Why

`PostForm` 的 `scheduled_at` 欄位設有 `->minDate(now())`，限制排程時間必須在表單載入時刻之後。這導致兩個問題：

1. **無法設定已過去的排程時間**：使用者可能想補發一筆貼文，設定過去時間後應由排程系統立即拾取發送，但 `minDate(now())` 阻擋了這個操作。
2. **`now()` 凍結在頁面載入時**：若使用者開啟頁面後停留一段時間才送出，`minDate` 仍是頁面載入時的時間，而非送出時的時間，造成不一致的驗證行為。

排程系統（`RunThreadsScheduler::dispatchDuePosts()`）的拾取條件是 `scheduled_at <= now()`，過去時間的貼文會被立即發送，因此移除下限限制不會造成功能異常。

## What

- 移除 `PostForm` 中 `scheduled_at` 欄位的 `->minDate(now())` 限制
- 新增 `->default(now())` 讓建立頁面預設帶入當前時間
- 建立/編輯頁面規則一致，共用同一份 `PostForm`

## Impact

- `app/Filament/Resources/Posts/Schemas/PostForm.php` — 移除 `minDate`、新增 `default`
- `tests/Feature/PostResourceTest.php` — 新增過去時間可建立的測試案例
