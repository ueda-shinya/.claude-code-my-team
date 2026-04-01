# /lineworks-add-bot スキル

LINE WORKS に新しいBotを追加するときに使う手順スキル。
シュウ（backend-engineer）への委譲指示を含む。

## 前提

- 既存サーバー: `~/.claude/line-works-bot/scripts/server.py`
- 既存Bot: アスカ（ASUKA）・ミオ（MIO）
- 教訓ドキュメント: `knowledge/line-works-bot/implementation-gotchas.md`

## 実行手順

### ステップ 1: LINE WORKS Developer Console でBot作成

ユーザーに以下を依頼する：

1. https://dev.worksmobile.com/ にアクセス
2. Bot を新規作成し、以下を取得：
   - Bot ID（数値）
   - Bot Secret（文字列）
3. Callback URL に既存サーバーの ngrok URL + `/callback` を設定

### ステップ 2: .env に追記

`~/.claude/.env` に以下を追記（アスカが直接実施してよい）：

```
LINE_WORKS_{BOT名}_BOT_ID={Bot ID}
LINE_WORKS_{BOT名}_BOT_SECRET={Bot Secret}
```

末尾スペースがないかを `repr()` で確認すること：

```bash
python3 -c "import os; print(repr(os.environ.get('LINE_WORKS_{BOT名}_BOT_SECRET')))"
```

### ステップ 3: server.py の修正（シュウに委譲）

シュウに以下を依頼する：

1. **環境変数読み込み追加**
   - `LINE_WORKS_{BOT名}_BOT_ID` と `LINE_WORKS_{BOT名}_BOT_SECRET` を読み込む変数を追加

2. **`verify_signature()` の secrets リストに新Botを追加**
   - `(NEW_BOT_SECRET, '{bot名}')` のタプルを追加

3. **`process_message()` にルーティングロジック追加**
   - DM: `is_{bot名}_bot` フラグで判定
   - グループ: 「自分宛てでないメッセージは無視」チェック
   - DMデフォルトルーティングに `and not channel_id` 条件を確認

4. **`send_message()` 呼び出しで `bot_id=received_bot_id` を渡す**
   - 新Botのレスポンス送信箇所すべてで確認

5. **システムプロンプト（新Botの役割定義）を追加**
   - 既存Bot（アスカ・ミオ）のプロンプト構造を参考に作成
   - **「【Bot名】を冒頭に付ける」指示は不要**（LINE WORKSではアイコン・名前が表示されるため）

6. **Bot間引き継ぎのハンドオフパターン更新**
   - `detect_handoff()` に新Bot名のパターンを追加
   - システムプロンプトにハンドオフ形式を2パターン以上明記

### ステップ 4: 動作確認

以下の順序でテストする：

1. サーバー再起動
2. 新BotへのDM送信テスト（応答が返るか）
3. グループチャットでの振り分けテスト（名前呼びかけで正しいBotが応答するか）
4. 二重返答が起きないかチェック（グループで片方だけ応答するか）
5. Bot間ハンドオフテスト（引き継ぎ指示で正しく連鎖するか）

## よくある落とし穴

詳細は `knowledge/line-works-bot/implementation-gotchas.md` を参照。

- 署名検証に新Botを追加し忘れ → 403エラー
- send_message の bot_id がハードコード → 別Botから送信される
- グループで二重返答 → 「自分宛てでない」チェック漏れ
- DMデフォルトがグループでも発動 → `channel_id` チェック漏れ
- .env の末尾スペース → 署名検証が常に失敗
- Bot間連携をwebhook経由で実装 → LINE Works仕様で動かない（サーバー内関数呼び出しで実装する）
