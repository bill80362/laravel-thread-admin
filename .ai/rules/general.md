---
paths:
  - '**'
---

# General

## 禁止 migrate:fresh 自動執行
禁止直接執行 `php artisan migrate:fresh` — 會清空所有資料。若需重新 migration，告知使用者由使用者手動操作。可執行 `php artisan migrate` 套用新增 migration，但需先確認不會影響現有資料。
