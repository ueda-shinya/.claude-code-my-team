# メール自動化ワークフロー — 要件定義

## 概要

受信メールをチェックし、内容に応じて返信下書き作成・タスク追加・スケジュール登録・ゴミ箱移動を自動で行うワークフロー。

---

## トリガー

| タイミング | 方法 |
|---|---|
| 朝のブリーフィング時 | `morning-briefing` スキルの最終ステップとして実行 |
| 手動実行時 | 「メールチェックして」等の指示 → `/mail-check` スキルとして実行 |

---

## 接続情報

| 項目 | 値 |
|---|---|
| メールサービス | Xserver |
| IMAP ホスト | `imap.xserver.ne.jp` |
| IMAP ポート | `993`（SSL） |
| SMTP ホスト | `smtp.xserver.ne.jp` |
| SMTP ポート | `465`（SSL）または `587`（STARTTLS） |
| 認証情報管理 | `~/.claude/.env` に追記 |

### `.env` 追記内容

```env
# メール自動化（Xserver）
MAIL_HOST=imap.xserver.ne.jp
MAIL_PORT=993
MAIL_SMTP_HOST=smtp.xserver.ne.jp
MAIL_SMTP_PORT=465
MAIL_USER=          # メールアドレス
MAIL_PASSWORD=      # メールパスワード
MAIL_TRASH_FOLDER=Trash
MAIL_DRAFT_FOLDER=Drafts
```

---

## 処理フロー

```
① 受信トレイ（INBOX）の未読メールを取得（最大20件）
        │
② Claude で各メールを分類
        │
        ├─ [返信必要]      → ③ 返信文生成 → IMAP Drafts に保存 → LINE WORKS にタスク追加
        │
        ├─ [日程確定]      → ④ Googleカレンダーに登録 → ブリーフィング報告に含める
        │
        ├─ [不要DM]        → ⑤ Trash フォルダに移動（既読にする）
        │
        └─ [判断困難]      → ⑥ 未読・未移動のまま放置
```

---

## 分類ロジック（Claude による判定）

| 分類 | 判定基準 |
|---|---|
| `reply_needed` | 質問・問い合わせ・提案・依頼が含まれる。返信が期待されている |
| `schedule_confirm` | 打ち合わせ・ミーティングの日時・場所が確定している |
| `spam_dm` | 配信停止リンクあり・メルマガ・宣伝・通知メール |
| `hold` | 上記いずれにも当てはまらない（FYI・領収書・受付完了等） |

---

## 各処理の詳細

### 返信下書き（reply_needed）

- Claude がメール本文を読んで返信文を生成
- 署名は `~/.claude/.env` の `MAIL_SIGNATURE` から取得（なければ省略）
- IMAP APPEND コマンドで `Drafts` フォルダに保存
- LINE WORKS にタスク追加：
  - タイトル：「【要返信】{件名}」
  - メモ：差出人・受信日時・返信文の要約

### スケジュール登録（schedule_confirm）

- Claude がメール本文から以下を抽出：
  - 件名・日時（開始・終了）・場所・参加者
- Google Calendar API で primary カレンダーに登録
- 既存の予定と重複する場合は警告のみ（上書きしない）

### ゴミ箱移動（spam_dm）

- IMAP MOVE（または COPY + EXPUNGE）で `Trash` フォルダへ移動
- 既読にする

### 判断困難（hold）

- 何もしない（未読・未移動のまま）

---

## 外部連携

| サービス | 用途 | 認証 |
|---|---|---|
| Xserver IMAP | メール取得・下書き保存・ゴミ箱移動 | `.env`（ID/PW） |
| Claude API | メール分類・返信文生成 | `.env`（`ANTHROPIC_API_KEY`） |
| LINE WORKS API | タスク追加 | `.env`（既存の LINE WORKS 認証情報） |
| Google Calendar API | スケジュール登録 | `mcp-google-calendar-token.json`（既存） |

---

## 出力・報告

- モーニングブリーフィングに組み込む場合：
  ```
  ## メールチェック
  - 返信必要：X件（下書き保存済み・LINE WORKSにタスク追加）
  - スケジュール登録：X件
  - 不要DM削除：X件
  - 保留：X件
  ```
- 手動実行時：上記 + 各件名の一覧を表示

---

## ファイル構成

```
~/.claude/
├── scripts/
│   └── mail-check.py       # メインスクリプト
├── skills/
│   └── mail-check/
│       ├── requirements.md  # 本ファイル
│       └── SKILL.md         # スキル定義（実装後に作成）
└── .env                     # 認証情報追記
```

---

## バージョン履歴

| バージョン | 内容 |
|---|---|
| v0.1 | 要件定義作成（2026-03-24） |
