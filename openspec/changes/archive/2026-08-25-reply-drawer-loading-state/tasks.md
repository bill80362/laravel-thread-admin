## 1. 修改 Blade 視圖 — 按鈕與 textarea 的 loading 狀態

- [x] 1.1 在送出按鈕加入 `wire:loading.attr="disabled"` 和 `wire:target="sendReply"`，防止請求期間重複點擊
- [x] 1.2 在送出按鈕內部加入 `wire:loading` 顯示 spinner +「送出中…」，`wire:loading.remove` 顯示「送出」
- [x] 1.3 在 textarea 加入 `wire:loading.attr="disabled"` 和 `wire:target="sendReply"`，請求期間禁用編輯
- [x] 1.4 加入 CSS spinner 動畫樣式（旋轉 border 動畫）
- [x] 1.5 驗證：在瀏覽器中實際測試，模擬慢請求確認 loading 狀態正確顯示與恢復

## 2. 確認與清理

- [x] 2.1 執行 `vendor/bin/pint --format agent` 確保程式碼格式正確
- [x] 2.2 確認雙重提交防護：在請求期間無法再次點擊送出按鈕
- [x] 2.3 確認錯誤情況：請求失敗後按鈕與 textarea 恢復為可操作狀態，textarea 保留使用者輸入內容
