## Context

見 `proposal.md` — Why。當前 `isTokenInvalid()` 用 `str_contains($message, 'token')` 判斷是否 token 失效，但 Guzzle 的 5xx 錯誤訊息包含完整 URL（含 `access_token` 參數），導致誤判。`ThreadsClient::request()` 的 `GuzzleException` catch 區塊也未記錄 response body 或傳遞 HTTP status。

## Goals / Non-Goals

**Goals:**
- 讓 `isTokenInvalid()` 只在真正的 token 失效訊號（HTTP 401 或特定錯誤碼）時回傳 true
- 對 5xx 錯誤正確記錄 response body
- 正確傳遞 HTTP status code 給 `ThreadsApiException`

**Non-Goals:**
- 不改變 API 呼叫行為
- 不修改其他錯誤分類方法（`isRateLimited`、`isRetryable`）
- 不更動 SCOPES 或其他權限邏輯

## Decisions

### 1. 強化 `isTokenInvalid()` 判斷邏輯

移除過於寬鬆的 `str_contains(strtolower($message), 'token')` 判斷，改用更精確的訊號：

```php
public function isTokenInvalid(): bool
{
    return $this->httpStatus === 401
        || $this->errorCode === 190
        || str_contains(strtolower($this->message), 'oauth');
}
```

- **理由**：錯誤訊息含 `access_token=` URL 參數時會誤判。HTTP 401 與 error code 190 已是官方明確的 token 失效訊號，`'oauth'` 字樣則涵蓋 OAuthException 類型錯誤。
- **替代方案**：保留 `'token'` 但排除 URL pattern — 過於脆弱，不易維護，捨棄。

### 2. `request()` 的 `GuzzleException` 分支記錄 5xx response body

`ClientException`（4xx）已有 `toApiException()` 解析 body，但 `ServerException`（5xx）繼承自 `GuzzleException` 卻未被處理。改為在 `GuzzleException` catch 中嘗試讀取並記錄 response body，並傳遞 status code：

```php
} catch (GuzzleException $e) {
    $httpStatus = null;
    $body = null;

    if ($e instanceof RequestException && $e->getResponse() !== null) {
        $response = $e->getResponse();
        $httpStatus = $response->getStatusCode();
        $body = (string) $response->getBody();
        $this->logResponse($response);
    }

    Log::error('Threads API request failed', [
        'method' => $method,
        'url' => $url,
        'error' => $e->getMessage(),
        'status' => $httpStatus,
        'response' => $body,
    ]);

    throw new ThreadsApiException($e->getMessage(), null, $httpStatus);
}
```

- **理由**：`RequestException`（含 `ServerException`、`BadResponseException`）能透過 `getResponse()` 取得實際回應，據此記錄 status 與 body，並傳遞給 `ThreadsApiException`。
- **替代方案**：為 5xx 單獨新增 catch — 但 Guzzle 的 5xx 全部是 `RequestException` 子類，合併處理更精簡。

## Risks / Trade-offs

- 去掉 `'token'` 字樣判斷可能漏掉某些非標準 token 錯誤訊息（非 401/190/OAuth 字樣）→ 但官方標準錯誤大多符合 401 / 190 / OAuthException，可接受。
- `$e->getResponse()` 可能為 null（網路層錯誤）→ 已用 `instanceof` + null 檢查保護。
