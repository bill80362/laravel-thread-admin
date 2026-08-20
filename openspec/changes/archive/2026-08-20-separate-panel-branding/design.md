## Context

專案目前有兩個 Filament 面板：`AdminPanelProvider`（id `admin`）與 `UserPanelProvider`（id `user`）。兩者的品牌標題目前皆未覆寫，統一回退到 `config('app.name')`（即 `APP_NAME`）。`APP_NAME` 同時被 cache/session/database 的 prefix 設定（透過 `Str::slug()`）使用，因此不能改為中文。需求是讓兩個面板各自有獨立、且支援中文的品牌名稱。詳細動機見 proposal.md - Why，行為契約見 specs/panel-branding。

## Goals / Non-Goals

**Goals:**
- admin 與 user 面板各自顯示獨立品牌名稱。
- 品牌名稱集中於單一 config 來源管理（方案 B 的精神）。
- 品牌支援中文；`APP_NAME` 維持英文內部識別名。

**Non-Goals:**
- 不支援每個面板上傳品牌圖片（`brandLogo()`）。僅處理文字標題（`brandName()`）。
- 不修改 cache / session / database 的 prefix 邏輯。

## Decisions

**D1：新增獨立 `config/branding.php`，而非 `config/filament.php`**

- 原因：專案尚未發佈 `config/filament.php`。若直接新增此檔，Laravel 會用它**覆蓋** Filament 套件自身的 config（themes、auth 等預設值），可能導致面板壞掉。建立自訂 `config/branding.php` 是 Laravel 慣用的自訂 config 方式，不會覆蓋任何套件設定，同時保有「集中管理」的好處。
- 替代方案：在兩個 PanelProvider 直接讀 `env('ADMIN_BRAND')` / `env('USER_BRAND')`（方案 A，更簡單但發散）；或發佈官方 `config/filament.php` 再添加節點（引入大量非必要內容）。均不採用。

**D2：config/branding.php 結構**

```php
return [
    'admin' => [
        'name' => env('ADMIN_BRAND', 'SocialMediaAdmin'),
    ],
    'user' => [
        'name' => env('USER_BRAND', 'SocialMediaAdmin'),
    ],
];
```

**D3：Panel 透過 `->brandName(config('branding.admin.name'))` 覆寫**

- `AdminPanelProvider` 傳入 `admin` 節點；`UserPanelProvider` 傳入 `user` 節點。
- Filament 的 `getBrandName()` 已定義為「設定的 brandName，否則 `config('app.name')`」，所以只要設定即會覆寫，不再回退到 `APP_NAME`。

**D4：預設值不可為空**

- fallback 預設沿用 `SocialMediaAdmin`，避免 `.env` 未設定時顯示空白。

## Risks / Trade-offs

- [環境變數未設定時顯示 fallback 名稱] → config fallback 設為既有 `SocialMediaAdmin`。
- [config 快取] → 修改 config 後需 `php artisan config:clear`；這是一般 Laravel 部署注意事項，非此變更特有。
- [使用者已存在被 cache 的 admin password] → 無關，此變更不觸碰認證邏輯。
