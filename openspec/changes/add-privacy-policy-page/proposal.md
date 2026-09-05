## Why

申請 Meta App Review（Threads API 應用程式審核）時，必須提供一個「不需登入即可存取」且「不會跳出審核者驗證」的公開隱私政策網址。目前專案僅有根目錄介紹頁 `welcome`，沒有可對外提供的隱私政策頁面，導致較難完成審核流程。

## What Changes

- 新增公開路由 `GET /privacy-policy` 與 `GET /terms-of-service`，分別回傳隱私政策與服務條款頁面（無需登入、無後台保護）。
- 新增一個共用政策版型視圖 `resources/views/legal-page.blade.php`，沿用根目錄介紹頁的深色品牌風格（OO-Pilot），以參數代入標題與內容，隱私政策與服務條款共用同一版型。
- 在根目錄介紹頁 `welcome.blade.php` 的 footer 加入「隱私政策」與「服務條款」連結，指向對應路由。
- 頁面內容以繁體中文書寫，涵蓋各政策常見項目：
  - **隱私政策**：收集的資訊、使用資訊的方式、對外分享、資料保存、使用者權利、聯絡方式。
  - **服務條款**：接受條款、帳號與服務使用、使用者責任、付費與訂閱、免責聲明與責任限制、終止、準據法、聯絡方式。

不需要新增資料表、Model、Service 或後台資源，純粹是對外靜態頁面。

## Capabilities

### New Capabilities

- `privacy-policy`: 提供公開、不需登入的隱私政策頁面（`/privacy-policy`），內容涵蓋資訊收集、使用、分享、保存、權利與聯絡方式，並由根目錄介紹頁 footer 提供入口連結。
- `terms-of-service`: 提供公開、不需登入的服務條款頁面（`/terms-of-service`），內容涵蓋接受條款、服務使用、使用者責任、付費、免責聲明與責任限制、終止、準據法與聯絡方式，並由根目錄介紹頁 footer 提供入口連結。

### Modified Capabilities

<!-- 無既有規格需修改 -->

## Impact

- **新增檔案**：
  - `resources/views/legal-page.blade.php`（隱私政策與服務條款共用的政策版型視圖）
- **修改檔案**：
  - `routes/web.php`（新增 `GET /privacy-policy` 與 `GET /terms-of-service` 路由）
  - `resources/views/welcome.blade.php`（footer 加入「隱私政策」與「服務條款」連結）
