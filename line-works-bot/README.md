# LINE WORKS Bot プロジェクト

**開始日：** 2026-03-24
**担当：** アスカ（開発・設計）/ シンヤさん（LINE WORKS 設定・最終判断）
**ステータス：** Phase 1 準備中

---

## 目的

外出先のスマートフォンから LINE WORKS 経由でアスカ（AI アシスタント）に指示を出し、応答を受け取る仕組みを作る。最終的には各エージェントが独立して稼働する構成を目指す。

---

## フェーズ進捗

| フェーズ | 内容 | ステータス |
|---|---|---|
| Phase 1 | PC + ngrok でアスカ Bot テスト | 🔄 LINE WORKS Bot 作成待ち |
| Phase 2 | Xserver VPS に移行・24時間対応 | ⬜ 未着手 |
| Phase 3 | エージェント呼び分け（@アスカ・@ソウ等） | ⬜ 未着手 |

---

## 現在のネクストアクション

**シンヤさんにやってもらうこと：**
1. LINE WORKS Developer Console で Bot 作成
   → 手順は `docs/setup-manual.md` を参照
2. 以下の情報を取得してアスカに渡す：
   - Bot ID
   - Client ID / Client Secret
   - Service Account ID
   - Private Key ファイル（`.key`）→ `~/.claude/line-works-bot/` に保存

**アスカが担当すること（Bot 情報が揃ったら）：**
- サーバーコード（`scripts/server.py`）の実装
- ngrok 起動スクリプトの作成
- テスト実施・動作確認

---

## ディレクトリ構成

```
line-works-bot/
├── README.md              ← このファイル（プロジェクト概要・進捗）
├── docs/
│   ├── requirements.md    ← 要件定義 v2（カナタ・ソウ・ミオ・ツムギ協議済み）
│   └── setup-manual.md    ← LINE WORKS Bot 設定マニュアル（初心者向け）
├── scripts/
│   └── server.py          ← Webhook サーバー（実装予定）
└── .env.example           ← 必要な環境変数の一覧（値なし）
```

---

## 必要な環境変数（`.env` に追記する項目）

`.env.example` を参照。実際の値は `.env` に記載（Git 管理外）。

---

## 協議メンバー・参照ドキュメント

| ドキュメント | 内容 |
|---|---|
| `docs/requirements.md` | 要件定義 v2（カナタ・ソウ・ミオ・ツムギ協議反映）|
| `docs/setup-manual.md` | LINE WORKS Bot 設定手順（シンヤさん用）|
| `~/.claude/agents/` | 各エージェントの定義ファイル |
| `~/.claude/CLAUDE.md` | アスカの基本定義 |
