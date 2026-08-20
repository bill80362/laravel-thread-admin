## 1. 設定檔與環境變數

- [x] 1.1 建立 `config/branding.php`，定義 `admin.name` 與 `user.name` 兩個節點，各自讀取 `ADMIN_BRAND` / `USER_BRAND` 環境變數，預設值皆為 `SocialMediaAdmin`
- [x] 1.2 在 `.env` 新增 `ADMIN_BRAND=管理員後台` 與 `USER_BRAND=社群媒體整合`
- [x] 1.3 在 `.env.example` 新增 `ADMIN_BRAND` 與 `USER_BRAND`（含說明註解）

## 2. 面板套用品牌名稱

- [x] 2.1 在 `AdminPanelProvider` 的 panel 鏈式呼叫加入 `->brandName(config('branding.admin.name'))`
- [x] 2.2 在 `UserPanelProvider` 的 panel 鏈式呼叫加入 `->brandName(config('branding.user.name'))`

## 3. 驗證

- [x] 3.1 執行 `php artisan config:clear` 確保新 config 生效
- [x] 3.2 手動瀏覽 admin 面板登入頁與側邊欄，確認顯示「管理員後台」
- [x] 3.3 手動瀏覽 user 面板登入頁與側邊欄，確認顯示「社群媒體整合」
- [x] 3.4 執行 `vendor/bin/pint --dirty --format agent` 格式化修改的 PHP 檔案
