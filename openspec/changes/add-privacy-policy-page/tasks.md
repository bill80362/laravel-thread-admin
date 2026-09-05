## 1. 政策頁路由

- [x] 1.1 在 `routes/web.php` 新增 `Route::get('/privacy-policy', ...)`，回傳共用版型 `legal-page` 並代入「隱私政策」標題與內容
- [x] 1.2 在 `routes/web.php` 新增 `Route::get('/terms-of-service', ...)`，回傳共用版型 `legal-page` 並代入「服務條款」標題與內容
- [x] 1.3 驗證 `php artisan route:list` 中「/privacy-policy」與「/terms-of-service」為公開路由（無認證 middleware）

## 2. 共用政策版型視圖

- [x] 2.1 建立 `resources/views/legal-page.blade.php`，沿用 welcome 的深色品牌 CSS 變數與排版，以參數代入頁面標題與內容小節
- [x] 2.2 隱私政策內容以繁體中文涵蓋小節：收集的資訊、使用方式、對外分享、資料保存、你的權利、聯絡方式（聯絡含電話 0987-653382 與 OO-Pilot 品牌）
- [x] 2.3 服務條款內容以繁體中文涵蓋小節：接受條款、帳號與服務使用、使用者責任、付費與訂閱、免責聲明與責任限制、終止與暫停、準據法、聯絡方式（聯絡含電話 0987-653382 與 OO-Pilot 品牌）

## 3. 根目錄入口連結

- [x] 3.1 在 `resources/views/welcome.blade.php` 的 footer 中加入「隱私政策」與「服務條款」連結，分別指向 `/privacy-policy` 與 `/terms-of-service`

## 4. 驗證與測試

- [x] 4.1 以瀏覽器／HTTP 請求 `GET /privacy-policy` 與 `GET /terms-of-service` 確認回傳 200 且直接渲染內容（無登入跳轉）
- [x] 4.2 確認根目錄 footer 兩個連結可分別導向對應政策頁面
