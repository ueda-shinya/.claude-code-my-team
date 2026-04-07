# セッション引き継ぎ

## 予定

- **4/12（日）マーケティング戦略の全体像を話し合う**（シンヤさんの希望）
  - 参加：アスカ・レン・リナ・ナギ（5人体制）
  - 事前準備資料：`clients/officeueda/reports/20260329_marketing-kickoff-prep.md`
- **4/12（日）エージェント精度向上ラウンドテーブル**（仮・knowledge-buffer.md に議題保存済み）

## 残件

### Windows 専用
- chatwork-sync.py の Notion 案件リスト登録ロジックを修正（登録内容がよくない・後日対応）
- メール自動化 Phase 1：mail-check.py の動作確認（`--dry-run` → 本番実行）
- メール自動化にもスケジュール登録・変更検出を追加（Chatwork版と同様）
- Chatwork 一次返信が発動したとき LINE WORKS 通知が届くか実動作確認（2026-03-30修正済み・未確認）

### PC不問
- エージェント精度向上ラウンドテーブル（日程未定・knowledge-buffer.mdに議題保存済み）
- **LPヒアリングシートのレビュー**（`skills/lp-create/hearing-sheet.md`）レン・コト・Kaiで内容検証・必要に応じて修正
- 広告の直帰率改善をレンに相談（instagram/cpc: 90.2%、google/cpc: 93.3%）
- オフィスウエダの今後の事業展開について話し合う（4/12 ラウンドテーブルで実施）

---

## 設計・実装決定ログ

形式：`[YYYY-MM-DD] <決定内容>（対象ファイル or 機能）`

**削除ポリシー：** アスカが sync 時に各エントリを確認し、「実装がgitにコミット済み」または「シンヤさんが完了と明示した」エントリを削除する。「作業なし」判定は残件セクションのみで行い、このログが残っていても「作業なし」にできる。

[2026-04-05] メールにスケジュール登録・変更検出を追加するタイミングで、カレンダー処理を `calendar_utils.py` として共通モジュールに切り出す（chatwork-sync.py・mail-check.py 両方から import して使う構成）

[2026-04-07] /lp-create スキル ステップ⑤（デザイン設計）の仕組み化 → **完了**

- `skills/lp-create/design-spec-template.md` 新規作成（Kaiが埋める9セクション構成）
- `agents/lp-designer.ja.md` にStep 3.5＋デザイン設計知識ベース6カテゴリを追加
- `skill.ja.md` ステップ⑤・⑥・承認ゲート②を整備（リナ確認済み）
- 英語版（`skill.md` / `lp-designer.md`）同期済み


---

## 中断中の作業

### 作業中①: メール自動化 Phase 1

- スクリプト実装・サクラのセキュリティレビュー対応済み
- `~/.claude/scripts/mail-check.py` 完成
- クレジット追加済み（2026-03-28）→ 動作確認未実施

**再開手順：**
1. `"X:\Python310\python.exe" ~/.claude/scripts/mail-check.py --dry-run` でドライラン確認
2. 問題なければ `--dry-run` なしで本番実行

---

### 運用中: Chatwork → Notion/Calendar/LINE WORKS 連携（2026-03-28）

- スクリプト: `~/.claude/scripts/chatwork-sync.py`
- APScheduler で4時間ごと自動チェック（server.py に統合済み）
- Notionプロパティ修正済み・優先度高の判定基準定義済み
- **運用しながら通知精度を見直し中**（不要な通知があれば随時プロンプト調整）
- APScheduler稼働中・1時間ごとに自動チェック
- **要確認: 次回一次返信が発動したとき、LINE WORKS通知が届くか確認**（2026-03-30修正済み・実動作未確認）

---

### 完了済み: LINE WORKS Bot Phase 1（2026-03-28）

- Flask + ngrok サーバー稼働中
- Python: `X:\Python310\python.exe`（これ以外は動作不可）
- 起動スクリプト：`~/.claude/line-works-bot/start-server.bat`（PC起動時自動起動）
- 起動時に旧ngrokを自動kill済み（プロセス溜まり問題解決）

**機能一覧：**
- `/ga4`・自然語「GA4レポートお願い」→ キャッシュ優先・当日初回のみ取得（90秒）
  - 「最新版」「再取得」で強制リフレッシュ。キャッシュ: `~/.claude/tmp/ga4-cache.txt`
- `/tasks` → Notion「残件タスク」DBから未完了タスクを取得
- `/clients` → クライアント一覧
- `/memo <テキスト>` → knowledge-buffer.md に保存
- `/notion <タイトル>` → Notion議事録DBに追加
- 自然語「今日の予定は？」「明日の予定は？」→ Google Calendar リアルタイム取得
- 自然語「明日14時にMTG追加して」→ Google Calendar にイベント追加（1時間）

**サーバー手動起動：**
```
"X:\Python310\python.exe" "C:\Users\ueda-\.claude\line-works-bot\scripts\server.py"
```

**次フェーズ：** Phase 2（Xserver VPS移行・24時間対応）は後回し

---

### 完了済み: Notion CRM（全Phase完了）

- DB作成済み：アスカ室 → 顧客リスト・案件リスト・議事録（各DB IDは .env に記載）
- CLIスクリプト完成：`~/.claude/scripts/notion-crm.py` / `notion-projects.py`
- 既存顧客58件のインポート済み（2026-03-26）
- リレーション・バックリンク設定済み（顧客↔案件↔議事録）
- 運用中

### 完了済み: Notion 見積・請求台帳（全Phase完了）

- DB作成済み（NOTION_LEDGER_DB_ID は .env に記載）
- 過去データ238件インポート済み（2026-03-26）
- CLIスクリプト（notion-ledger.py）完成・運用中（2026-03-26）

### 完了済み: GA4 → Notion 自動書き込み（2026-03-26）

- アスカ室に「GA4 日次レポート」DB 作成（NOTION_GA4_DB_ID は .env に記載）
- ga4-report.py 末尾に Notion 書き込み追加。毎朝のブリーフィングで自動積み上げ
- ハイブリッド運用（.md 保存 + Notion）。タイミングを見て Notion のみに移行予定

### 完了済み: officeueda LP lp-260326 新規作成（2026-03-26）

- ファイル：`clients/officeueda/biz-web/lp-260326/`（index.php・style.css・contact.css）
- CTA 7箇所・LINE 全面統合・data-cta-label 付与済み
- サクラレビュー済み（重要度高：なし）
- **WordPress 配置・動作確認はシンヤさん作業**
