# LINE WORKS Bot 実装の落とし穴と教訓

マルチBot構成（同一サーバーで複数Botを運用）で発生した問題と、正しい実装パターンをまとめる。

---

## 問題1: 署名検証で片方のBotしか検証しない

**症状:** ミオBotのwebhookで403エラー、ミオが応答しない

**原因:** `verify_signature()` がアスカのシークレットのみチェックしていた

**正しい実装:** 全Botのシークレットをループして検証し、どのBotか返す（ラベルを返す）

```python
def verify_signature(body, signature):
    secrets = [(BOT_SECRET, 'asuka'), (MIO_BOT_SECRET, 'mio')]
    for secret, label in secrets:
        if not secret:
            continue
        computed = base64.b64encode(
            hmac.new(secret.encode(), body, hashlib.sha256).digest()
        ).decode()
        if hmac.compare_digest(computed, signature):
            return label
    return None
```

---

## 問題2: send_message が常にアスカの BOT_ID を使う

**症状:** ミオ宛てメッセージもアスカBotから送信される

**原因:** `send_message()` がハードコードの `BOT_ID` を使っていた

**正しい実装:** `bot_id` パラメータを追加し、呼び出し元から渡す

---

## 問題3: グループチャットで両Botが同じメッセージに返答

**症状:** 「ミオ、いる?」に対してアスカ・ミオ両方が返答

**原因:** 両BotのwebhookURLが同じエンドポイントのため、同じメッセージを2回受信する（Bot別にwebhookが飛ぶ）

**正しい実装:** ルーティング後に「自分宛てでないメッセージは無視」チェックを入れる

```python
if channel_id:
    if agent == 'mio' and not is_mio_bot:
        return
    if agent == 'asuka' and is_mio_bot:
        return
```

---

## 問題4: ミオDMのデフォルトルーティングがグループでも発動

**症状:** グループチャットの宛先なしメッセージにもミオが応答してしまう

**原因:** `is_mio_bot and agent == 'default'` の条件にグループ除外がなかった

**正しい実装:** `and not channel_id` を追加して、DMのみデフォルトルーティングが発動するようにする

---

## 問題5: Bot-to-Bot連鎖のハンドオフパターンが狭すぎる

**症状:** アスカが「ミオに即時調査を依頼します」と書いてもミオが呼ばれない

**原因:** `detect_handoff()` のパターンが `ミオ[、,：:　\s]` のみで「ミオに」を検出しなかった

**正しい実装:** 「ミオに〇〇を（動詞）」パターンも追加。かつシステムプロンプトで形式を2パターン明示する

---

## 問題6: .env のシークレット末尾スペース

**症状:** 署名検証が常に失敗（403）

**原因:** `.env` の BOT_SECRET に末尾スペースが入っていた

**対策:** `.env` 編集後は以下で値を確認する

```bash
python3 -c "import os; print(repr(os.environ.get('LINE_WORKS_MIO_BOT_SECRET')))"
```

---

## 問題7: LINE Works は Bot メッセージを他 Bot の webhook に配信しない

**症状:** Bot-to-Bot会話をwebhook経由で実装しようとしても動かない

**原因:** LINE Worksのプラットフォーム仕様で、Botが送ったメッセージは他BotのWebhookには届かない

**正しい実装:** サーバー内で直接関数呼び出し（`_run_bot_chain()`）で実現する。webhook経由ではなく、同一プロセス内のメソッド呼び出しでBot間連携を行う。

---

## 問題8: 外部スクリプトの BOT_ID 参照先が環境変数名変更に追従しなかった

**症状:** chatwork-sync.py の LINE WORKS 通知が404エラーで失敗

**原因:** LINE WORKS Bot の設定整理で環境変数を `LINE_WORKS_BOT_ID` から `LINE_WORKS_ASUKA_BOT_ID` に改名した。
しかし chatwork-sync.py は旧名 `LINE_WORKS_BOT_ID` を参照したまま → 空文字になりURLが壊れた。

**修正方針:** 目的別に変数を新設する。`LINE_WORKS_CHATWORK_BOT_ID` のように用途を変数名に含める。
用途が同じでも変数名は共有しない（片方を改名するともう片方が壊れる）。

**教訓:** 環境変数を改名したとき、参照している全スクリプトを必ず横断検索すること。
```bash
grep -r "LINE_WORKS_BOT_ID" ~/.claude/scripts/
```

---

## Bot追加時のチェックリスト

新しいBotを追加するとき、以下を漏れなく確認すること。

1. `.env` に `BOT_ID` と `BOT_SECRET` を追加（末尾スペースなしを `repr()` で確認）
2. `verify_signature()` の secrets リストに新Botのシークレットとラベルを追加
3. `send_message()` の呼び出し箇所で `bot_id=received_bot_id` を渡しているか確認
4. グループルーティングに「自分宛てでないメッセージは無視」チェックを追加
5. DMのデフォルトルーティングに `and not channel_id` を入れる
6. システムプロンプトのBot間引き継ぎセクションにハンドオフ形式を明記する（**「【Bot名】を冒頭に付ける」指示は不要**。LINE WORKSではアイコン・名前が表示されるため）
7. `detect_handoff()` のパターンに新Bot名のバリエーションを追加

---

## 関連ファイル

- 実装メモ: `knowledge/line-works-bot/implementation-notes.md`
- Bot追加スキル: `skills/lineworks-add-bot/skill.md`
- サーバー本体: `line-works-bot/scripts/server.py`
