## 1. 擴充 HTTP 模式說明內容

- [x] 1.1 在 `chapter5.blade.php` 的 HTTP 模式卡片下方，新增「HTTP 模式設定步驟」區塊，包含：
  - 對外連線網址說明（MCP 入口 `/mcp/threads`、`.well-known` metadata 端點、OAuth 端點）
  - 強調網址由 `APP_URL` 決定，必須是遠端可連到的公開網址
- [x] 1.2 新增 OAuth 授權流程說明區塊，包含：
  - Mermaid 流程圖（sequence diagram）
  - 純文字步驟說明（客戶端自動發現 → 動態註冊 → 跳轉授權頁 → 登入並允許 → 取得 token）
  - 明確標註「授權頁登入使用後台登入帳號」
- [x] 1.3 新增 Claude Desktop 桌面版遠端 HTTP 設定區塊，包含：
  - 設定檔路徑（macOS / Windows）
  - JSON 設定範例（`type: "http"` + `url`）
  - 首次連線 OAuth 授權說明
- [x] 1.4 新增 ChatGPT 桌面版遠端 HTTP 設定區塊，包含：
  - 操作步驟（設定 → MCP → 新增伺服器 → 選擇遠端 HTTP）
  - 填入 URL 與儲存後觸發 OAuth 的說明
- [x] 1.5 新增 token 後續管理區塊，引導至「MCP 控管」頁面，說明可檢視 token 狀態、到期時間與手動註銷
- [x] 1.6 新增注意事項區塊（對外網址必須可連、授權範圍 `mcp:use`、桌面版不受 custom_scheme 限制）

## 2. 驗證

- [x] 2.1 在瀏覽器中開啟使用說明頁面，確認 HTTP 模式內容完整顯示、排版正確
- [x] 2.2 確認流程圖可正常顯示（使用純文字 ASCII 圖，無需 Mermaid 依賴）
