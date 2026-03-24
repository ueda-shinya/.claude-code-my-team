# セッション引き継ぎ

## 状態
LINE WORKS Bot（アスカ Bot）Phase 1 テスト中断中

## 中断理由
Anthropic API のクレジット残高不足（400エラー）

## 現在の状態
- Flask サーバー: ポート 5000 で稼働中
- ngrok: セッション上限のため自動起動失敗。前のセッション URL が生きているか不明
- ALLOWED_USER_ID: UUID `00f4ca87-5717-42de-1540-041b9e780a45` で設定済み
- Webhook 受信・ユーザー認証・LINE WORKS トークン取得：すべて正常動作確認済み
- server.py の expires_in TypeError バグ：修正済み

## 再開時にやること
1. Anthropic Console でクレジット追加を確認
2. サーバーが落ちていれば再起動: `python ~/.claude/line-works-bot/scripts/server.py`
3. ngrok が切れていれば: `ngrok http 5000` → Callback URL を LINE WORKS Developer Console に再設定
4. LINE WORKS からメッセージを送って動作確認

## 次のフェーズ
- Phase 1 完了確認後 → 署名検証の有効化 → ngrok 固定ドメイン設定 → Xserver VPS 移行（Phase 2）
