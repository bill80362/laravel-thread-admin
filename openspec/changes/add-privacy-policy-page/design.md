## Context

應用程式目前僅有根目錄介紹頁 `welcome.blade.php`，透過 `routes/web.php` 中 `Route::get('/', ...)` 的 closure 回傳視圖，無需登入即可存取。Filament 後台則需登入，不能作為對外審核用的隱私政策頁面。見 proposal.md - Why。

## Goals / Non-Goals

**Goals:**
- 提供公開、不需登入、不會跳出審核驗證的隱私政策與服務條款頁面
- 沿用根目錄介紹頁的深色品牌風格（OO-Pilot），保持外觀一致
- 在根目錄 footer 提供兩個入口連結

**Non-Goals:**
- 不新增資料表、Model、Service、後台 Resource
- 不接 CMS 或資料庫動態內容（純靜態視圖即可）

## Decisions

### 1. 使用獨立公開路由 `/privacy-policy` 與 `/terms-of-service` 而非放進 Filament 後台

- **理由**：Meta App Review 要求隱私政策網址「不需登入、不跳出審核者驗證」。Filament 後台需登入，會讓審核員卡在登入頁，故政策頁必須是公開路由。
- **替代方案**：放在 Filament 自訂 Page → 需登入，不適用。

### 2. 路由以 closure 直接 `view()` 回傳，比對既有的根目錄作法

- **理由**：根目錄 `Route::get('/', fn () => view('welcome'))` 已是既有慣例（closure handler + 直接回傳 view）。政策頁是純靜態頁面，無需控制器，沿用同一 style 最一致。
- **替代方案**：建立 `LegalPageController` → 對無業務邏輯的靜態頁面過度設計。

### 3. 使用單一共用版型 `legal-page.blade.php`，以參數區分隱私政策與服務條款

- **理由**：使用者選擇「共用版型但獨立內容」。隱私政策與服務條款外觀一致、僅內容不同，共用一個 `legal-page` 版型可避免重複版型 HTML，且只需維護一份樣式。版型接收「頁面標題」與「內容小節」資料，由路由分別代入。
- **替代方案**：各自建立 `privacy-policy.blade.php` 與 `terms-of-service.blade.php` 完全獨立視圖 → 內容與樣式重複，維護成本高。

### 4. 內容以繁體中文書寫，涵蓋各政策的最小需求小節

- **理由**：符合專案「溝通語言使用繁體中文」慣例；服務條款採最小內容，涵蓋可接受的條款、服務使用、責任、付費、免責、終止與聯絡等核心小節。
- **替代方案**：額外加入 Cookie / GDPR / 詳細法律用語 → 對「開發用審核頁」過度，先保有核心小節即可。

## Risks / Trade-offs

- **文案為通用範本而非律師審閱** → 對 Meta 審核足夠，但正式上線前建議由法務／業務確認用語。此為內容風險，不影響實作。
- **政策頁為純靜態內容** → 若日後需維護，需改動 Blade。目前需求單一，可接受。
- **共用版型由資料代入標題與內容** → 內容只能透過 Blade 結構化呈現，缺少動態管理；目前為靜態頻道，可接受。

## Migration Plan

1. 新增路由與共用版型後，直接以 `GET /privacy-policy` 與 `GET /terms-of-service` 存取驗證即可。
2. 無資料庫、無副作用，不需停機；反之可直接還原路由與版型檔案。
