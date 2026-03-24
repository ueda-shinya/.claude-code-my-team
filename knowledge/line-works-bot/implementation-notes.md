# LINE WORKS Bot 実装メモ

## ユーザーID の形式

LINE WORKS API v2 の Webhook で送られてくる `userId` は **UUID 形式**。
管理画面に表示されるログインID（`shinya@example.com` 形式）とは**別物**。

- 正しい形式例：`00f4ca87-5717-42de-1540-041b9e780a45`
- 取得方法：ALLOWED_USER_ID を仮の値にしてサーバー起動 → メッセージ送信 → ログで確認
  ```
  [WARNING] 未許可ユーザーからのメッセージを無視: 00f4ca87-...
  ```

## expires_in の型

LINE WORKS トークン API（`/oauth2/v2.0/token`）の `expires_in` は**文字列**で返ってくる。
`int()` キャストしないと `TypeError: unsupported operand type(s) for +: 'float' and 'str'` が出る。

```python
# NG
_token_cache['expires_at'] = now + data.get('expires_in', 3600)

# OK
_token_cache['expires_at'] = now + int(data.get('expires_in', 3600))
```

## ngrok 無料プランの制限

- 同時セッション上限：**3本**（ERR_NGROK_108）
- 解消方法：https://dashboard.ngrok.com/agents で古いセッションを Terminate
- サーバーを再起動するたびに ngrok URL が変わる → LINE WORKS Developer Console の Callback URL も更新が必要
- 固定ドメイン（有料）を使うと URL が変わらなくて楽

## 署名検証

LINE WORKS API v2 の署名検証は `X-WORKS-Signature` ヘッダーで届く。
検証には `CLIENT_SECRET` ではなく **Bot Secret**（Developer Console > Bot > Secret）を使う。
現在（Phase 1 テスト中）はスキップ中。有効化前に Bot Secret の正しい値を確認すること。

## Phase 1 動作確認済み項目（2026-03-24）

- Webhook 受信 ✅
- ALLOWED_USER_ID による送信者制限 ✅
- LINE WORKS アクセストークン取得（JWT 認証） ✅
- Claude API 呼び出し → クレジット不足でブロック中（クレジット追加後に確認予定）
