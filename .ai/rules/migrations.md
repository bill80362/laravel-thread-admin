---
paths:
  - 'database/migrations/**'
---

# Migrations

## Migration 不使用資料庫外鍵約束
所有 migration 不得使用 ->constrained()、foreignId()->constrained()、foreign() 等外鍵約束。關聯僅在 Model 層透過 Eloquent 實現。不使用 cascadeOnDelete/OnUpdate，資料清理在 Model booted() deleting 事件中手動處理。
