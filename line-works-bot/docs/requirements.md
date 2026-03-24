# LINE WORKS Bot 統合 — 要件定義 v2

**作成日：** 2026-03-24
**更新日：** 2026-03-24（カナタ・ソウ・ミオ・ツムギ協議反映）
**担当：** アスカ（設計・統合） / シンヤさん（LINE WORKS 側作業）
**ステータス：** Phase 1 未着手（LINE WORKS Bot 作成待ち）

---

## 1. 目的

外出先のスマートフォンから LINE WORKS を使い、PC 上で動くエージェント（アスカ等）にメッセージを送って指示・応答を受け取る。最終的には VPS 上で各エージェントが独立して稼働する構成を目指す。

---

## 2. フェーズ構成

### Phase 1：PC + ngrok でローカルテスト（★ 現フェーズ）

| 項目 | 内容 |
|---|---|
| 目標 | LINE WORKS からメッセージを送り、アスカが応答する |
| インフラ | シンヤさんの PC（Windows 11）+ ngrok |
| エージェント | アスカ（メインアシスタント）のみ |
| 動作要件 | PC の電源が入っていれば外出先から使える |
| 完了基準 | LINE WORKS からテキスト送信 → アスカが3回以上安定して返答できること |

> **⚠️ 制約：** ngrok 無料版はセッション再起動のたびに URL が変わる。また約2時間でセッションが切れる場合がある。Phase 1 はテスト目的と割り切ること。

### Phase 2：VPS 移行

| 項目 | 内容 |
|---|---|
| 目標 | PC を起動せずに 24 時間対応できる |
| インフラ | VPS（さくら VPS / ConoHa VPS 推奨 ※後述） |
| エージェント | アスカ（継続）+ 他エージェント追加可能 |
| 完了基準 | VPS 上でサービスが自動起動し、再起動後も動作すること |

### Phase 3 以降：エージェント独立化

| 項目 | 内容 |
|---|---|
| 目標 | エージェントごとに呼び分けできる |
| 構成 | **1本の Webhook + 内部ルーター**（エンドポイントは分けない）|
| 呼び分け | LINE WORKS グループで `@アスカ`・`@ソウ` 等のメンション |
| デフォルト | メンションなし → アスカが受け取り、自分で判断または委譲 |

---

## 3. アーキテクチャ設計方針

### 3-1. 指揮系統（重要）

LINE WORKS 経由でも、現在の指揮系統を維持する。

```
シンヤさん（LINE WORKS）
        ↓
   Bot サーバー（Webhook受信）
        ↓
   アスカ（ゲートウェイ・チーフ）
        ↓ 必要に応じて
   ソウ / ミオ / カナタ 等
```

- **アスカが唯一のゲートウェイ**（Phase 1・2）
- Phase 3 でメンション指定した場合のみ、各エージェントに直接ルーティング（アスカへの通知は継続）

### 3-2. 非同期応答パターン（必須）

LINE WORKS の Webhook はリクエスト受信後、数秒以内にレスポンスを返す必要がある。Claude API の応答を同期で待つとタイムアウトが発生し、LINE WORKS 側が**重複送信**する。

```
1. LINE WORKS → Webhook 受信
2. Bot サーバー → 即時 200 OK を返す（+ 「受け付けました。処理中...」を送信）
3. バックグラウンドで Claude API を呼び出す
4. 応答完了後、LINE WORKS Message API で返答を送信
```

> **Phase 1 から必ず非同期で実装すること。** 後から変えると設計の作り直しになる。

### 3-3. Phase 3 のルーター設計（将来）

```
[Webhook: POST /callback]
       |
   [ディスパッチャー]  ← メンション解析 + デフォルトはアスカ
       |
  ┌────┼────┬────┐
 アスカ  ソウ  ミオ  カナタ（各ワーカープロセス）
```

- エンドポイントは1本に統一（LINE WORKS 側の Webhook 設定を変えない）
- プロセス間通信は内部 HTTP（シンプルさ優先）

---

## 4. Phase 1 機能要件

### 4-1. LINE WORKS 側（シンヤさん作業）

| No. | 作業 | 詳細 |
|---|---|---|
| 1 | Bot 作成 | LINE WORKS Developer Console で Bot を新規作成（無料） |
| 2 | 認証情報取得 | Bot ID・Channel Secret・Channel Access Token を控える |
| 3 | Webhook 設定 | サーバー起動後に Webhook URL（ngrok URL + `/callback`）を登録 |
| 4 | トーク追加 | 作成した Bot を自分のトーク一覧に追加 |

> **注意：** LINE WORKS 無料プランは Bot 機能自体は無料（最大10Bot）。トークルームでの使用は無料で可能。グループチャンネルは有料プランが必要な可能性あり（要確認）。

### 4-2. サーバー側（アスカが実装）

| 機能 | 仕様 |
|---|---|
| Webhook 受信 | `POST /callback` でメッセージを受信 |
| 即時応答 | 受信後すぐに 200 OK を返し、「受け付けました。処理中です...」を送信 |
| 署名検証 | Channel Secret による HMAC-SHA256 検証（先に raw body を取得してから検証） |
| 非同期処理 | Flask の threading でバックグラウンド処理 |
| Claude API 呼び出し | `claude-sonnet-4-6` でアスカとして応答生成 |
| 応答送信 | LINE WORKS Message API で返答を送信 |
| 送信者制限 | `ALLOWED_USER_ID` と一致しない送信者は無視 |
| ヘルスチェック | `GET /health` で Bot の生存確認 |
| セッション管理 | ユーザーごとに直近の会話履歴をインメモリ保持（Phase 2 で Redis に移行） |
| レスポンス分割 | 長文は LINE WORKS の文字数制限を超えないよう分割送信 |
| Markdown 変換 | Claude の `##` 見出し等を LINE WORKS 向けにテキスト変換 |
| コマンド処理 | `/reset`・`/status`・`/help` を実装 |
| トークン管理 | Access Token を自動リフレッシュ |

### 4-3. アスカの振る舞い

| 項目 | 仕様 |
|---|---|
| システムプロンプト | `~/.claude/CLAUDE.md` のアスカ定義を読み込む |
| 呼び出しモデル | `claude-sonnet-4-6` |
| ツール使用 | Phase 1 はなし（テキスト応答のみ） |
| キャラクター | アスカとして返答（「シンヤさん」呼び） |
| 応答スタイル | **モバイル向け短文**。要約を先に出し「詳細は PC で確認を」の2段構え |
| 確認が必要な操作 | 破壊的操作（ファイル削除・外部送信・課金）は LINE で確認メッセージを送り承認待ち |

### 4-4. コマンド体系

| コマンド | 動作 |
|---|---|
| `/reset` | 会話履歴をクリア |
| `/status` | 現在の作業状況・稼働状態を報告 |
| `/help` | 使い方一覧を表示 |

---

## 5. 非機能要件

| 項目 | 要件 |
|---|---|
| 応答時間 | Webhook 受信後 1 秒以内に 200 OK と「処理中」メッセージを返す |
| 最終応答 | Claude API 応答後 30 秒以内（API レイテンシ依存） |
| 同時接続 | Phase 1 は 1 ユーザー（シンヤさんのみ）を想定 |
| セキュリティ | 送信者 ID 制限 + HMAC-SHA256 署名検証 |
| ログ | 送受信ログをファイル出力（`~/.claude/logs/line-works-bot.log`） |
| 起動方法 | `python3 ~/.claude/scripts/line-works-bot.py` で起動 |
| Flask モード | `debug=False`（デバッグモードは常時無効） |

---

## 6. 技術スタック（Phase 1）

| レイヤー | 採用技術 |
|---|---|
| 言語 | Python 3.x |
| フレームワーク | Flask |
| 非同期処理 | threading（Celery は Phase 2 以降で検討） |
| トンネル | ngrok（固定ドメインを使用、セッション切れ対策） |
| AI | Anthropic SDK（`anthropic` パッケージ） |
| API | LINE WORKS Bot API v2 |
| 設定管理 | `python-dotenv`（`.env` ファイル読み込み） |

---

## 7. ファイル構成（Phase 1）

```
~/.claude/scripts/
└── line-works-bot.py     ← Webhook サーバー本体

~/.claude/logs/
└── line-works-bot.log    ← 送受信ログ

~/.claude/
└── .env                  ← 既存ファイルに以下を追記
    LINE_WORKS_BOT_ID=
    LINE_WORKS_CLIENT_ID=
    LINE_WORKS_CLIENT_SECRET=
    LINE_WORKS_SERVICE_ACCOUNT=
    LINE_WORKS_PRIVATE_KEY_PATH=   ← 秘密鍵ファイルのパス
    LINE_WORKS_CHANNEL_TOKEN=
    ANTHROPIC_API_KEY=
    ALLOWED_USER_ID=               ← シンヤさんの LINE WORKS ユーザーID
```

> **必須：** `.gitignore` に `.env` を追加すること。`git add -A` での誤コミットを防ぐ。

---

## 8. セキュリティ設計

| 対策 | 実装方法 |
|---|---|
| 不正リクエスト排除 | HMAC-SHA256 署名検証（raw body 先取得 → 検証の順序を守る） |
| 送信者制限 | LINE WORKS ユーザーID の固定チェック |
| 秘密鍵保護 | Private Key ファイルはパーミッション 600（所有者のみ読み取り） |
| デバッグ無効 | `debug=False` 必須（スタックトレースの外部露出防止） |
| 機密情報ルール | LINE WORKS チャット上に APIキー・パスワードを送受信しない |
| `.env` 管理 | `.gitignore` 必須 / Git 管理外で保管 |

---

## 9. PC 運用上の注意（Phase 1）

| 項目 | 対策 |
|---|---|
| Windows スリープ | 電源設定で「スリープなし」に変更する |
| ngrok セッション切れ | ngrok の固定ドメイン（無料で1つ使用可能）を利用する |
| Webhook URL 変更 | 再起動時は Developer Console で URL を更新する |
| ファイアウォール | ngrok を Windows Defender の例外に追加 |
| プロセス停止検知 | ログ監視またはヘルスチェック `/health` を定期確認 |

---

## 10. VPS 選定方針（Phase 2）

**採用：Xserver VPS（シンヤさんの意向）**

| 項目 | Xserver VPS | Render |
|---|---|---|
| 日本リージョン | ✅ 東京 | ❌（シンガポール） |
| 固定 IP | ✅ | ❌ |
| SSH | ✅ | ❌ |
| Python / Flask | ✅ 動作確認済み | ✅ |
| 料金 | 月額 830円〜（2GB / 36ヶ月） | 無料〜 |
| Phase 3 対応 | ✅ | ❌ |

> **Xserver VPS に決定。**
> SSH・固定IP・Python 環境・日本リージョンすべて揃っており、Phase 3 の複数エージェント運用にも対応可能。
> Render はスリープ問題・固定IP なし・SSH 不可のため不採用。

---

## 11. 運用ルール（未決→要合意）

| ルール | 現状 | 方針案 |
|---|---|---|
| PC と LINE WORKS の指示競合 | 未定義 | LINE WORKS を優先（外出中のため） |
| セッション引き継ぎ | `session-handoff.md` で管理 | LINE→PC は「LINE で話した内容」を handoff に追記する運用 |
| 長時間タスクの通知 | 完了時のみ | デフォルトは完了時のみ通知。「途中報告して」と言われたら随時通知 |
| 深夜の指示（Phase 2 以降） | 未定義 | 即時実行を原則とするが、合意が必要な操作は翌朝確認 |
| メッセージ記録 | 未定義 | LINE WORKS のやり取りはログファイルに保存（ナレッジ化は任意） |

---

## 12. 起動・テスト手順（Phase 1）

```bash
# 1. ngrok アカウント作成 + 固定ドメイン取得
# https://dashboard.ngrok.com/

# 2. パッケージインストール（初回のみ）
pip install flask anthropic requests python-dotenv

# 3. .env に認証情報を追記

# 4. サーバー起動
python3 ~/.claude/scripts/line-works-bot.py
# → コンソールに ngrok URL が表示される

# 5. LINE WORKS Developer Console で Webhook URL を設定
# 表示された https://your-domain.ngrok-free.app/callback を貼り付ける

# 6. LINE WORKS アプリでボットにメッセージを送って動作確認
# 「受け付けました。処理中です...」が返ってきたら成功

# 7. ヘルスチェック確認
curl https://your-domain.ngrok-free.app/health
```

---

## 13. 将来拡張メモ（Phase 2 以降）

- [ ] Redis によるセッション永続化（VPS 再起動でも会話が消えない）
- [ ] systemd / pm2 でプロセス自動起動・再起動
- [ ] nginx リバースプロキシで複数エンドポイント振り分け（Phase 3 対応）
- [ ] GA4 日次レポートを LINE WORKS に朝 8 時配信
- [ ] ファイル添付・画像受信対応
- [ ] 音声メッセージ → テキスト変換（Whisper 等）
- [ ] トークンベースのセッション管理（件数ベースから移行）
- [ ] system prompt ホットリロード（再起動なしで定義変更）

---

## 14. 未決事項・シンヤさんへの確認事項

| 項目 | ステータス | 優先度 |
|---|---|---|
| LINE WORKS Bot 作成 | **シンヤさん側作業待ち** | 高 |
| ngrok 固定ドメイン取得 | ngrok アカウント登録が必要 | 高 |
| グループチャンネルの利用有無 | 無料プランでは使えない可能性あり（要確認） | 中 |
| VPS の選定（Phase 2） | PC テスト完了後に検討 | 低 |
| 深夜指示の扱い（Phase 2 以降） | 運用合意が必要 | 低 |

---

## 15. 協議参加メンバーと主要貢献

| エージェント | 主な貢献 |
|---|---|
| カナタ | アーキテクチャ設計・非同期パターン・セッション管理・コマンド体系 |
| ソウ | インフラリスク・ngrok 制限・VPS 選定・セキュリティ |
| ミオ | LINE WORKS API 仕様調査（アスカが代替実施） |
| ツムギ | 指揮系統設計・UX・運用ルール・移行基準 |
| アスカ | 統合・全体設計 |
