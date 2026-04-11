# セッション引き継ぎ

## 予定

- **4/12（日）エージェント精度向上ラウンドテーブル**（仮・knowledge-buffer.md に議題保存済み）

## 残件

### Windows 専用
- chatwork-sync.py の Notion 案件リスト登録ロジックを修正（登録内容がよくない・後日対応）
- メール自動化 Phase 1：mail-check.py の動作確認（`--dry-run` → 本番実行）
- メール自動化にもスケジュール登録・変更検出を追加（Chatwork版と同様）
- Chatwork 一次返信が発動したとき LINE WORKS 通知が届くか実動作確認（2026-03-30修正済み・未確認）
- **hourei MCPサーバーの追加**：`claude mcp add hourei --scope user -- npx -y hourei-mcp-server`（e-Gov法令API。ケンの法務判断で条文参照に使用。Mac側は追加済み）
- **git post-merge hookの配置**：Mac側で `cross-platform-check.py` が `post-merge` hookで自動発火する仕組みを作成済み。Win側の `.git/hooks/post-merge` にも同じファイルを配置する必要あり（Mac側の `.git/hooks/post-merge` を参考に）

### PC不問
- **GA4 MCPサーバー 認証セットアップ**（2026-04-11追加）→ 下記「中断中の作業」に詳細あり
- エージェント精度向上ラウンドテーブル（日程未定・knowledge-buffer.mdに議題保存済み）
- 広告の直帰率改善をレンに相談（instagram/cpc: 95.2%、google/cpc: 83.3%）※2026-04-11 GA4更新
- ~~オフィスウエダの今後の事業展開について話し合う~~ → **4/11 ラウンドテーブル実施済み** → `clients/officeueda/reports/20260411_business-plan.md`
- 事業計画アクションプラン実行中（P1）: 今週中にA群への電話、今月中に10件アプローチ完了が目標
- notion-tasks.py のDBスキーマエラー（--addで「種別・開始日・担当」プロパティが見つからない）→ Win側で作成したDBとスクリプトの整合性を確認する
- Notion CRM改善: 顧客レコードに「納品サイトURL」「業種」フィールドを追加し、既存顧客分を埋める（営業アプローチ時の事前準備・A/B/C仕分けに活用）

---

## 設計・実装決定ログ

形式：`[YYYY-MM-DD] <決定内容>（対象ファイル or 機能）`

**削除ポリシー：** アスカが sync 時に各エントリを確認し、「実装がgitにコミット済み」または「シンヤさんが完了と明示した」エントリを削除する。「作業なし」判定は残件セクションのみで行い、このログが残っていても「作業なし」にできる。

[2026-04-05] メールにスケジュール登録・変更検出を追加するタイミングで、カレンダー処理を `calendar_utils.py` として共通モジュールに切り出す（chatwork-sync.py・mail-check.py 両方から import して使う構成）
[2026-04-09] skill.md（英語版）を skill.ja.md に同期する（カナタにフォアグラウンドで依頼すること。バックグラウンドだと権限プロンプトに応答できず失敗する）
[2026-04-10] has_schedule除外条件に「スケジュール確定の報告はfalseにしない」の但し書きを追加する（chatwork-sync.py の build_analyze_prompt 内）



---

## 中断中の作業

### 作業中: GA4 MCPサーバー 認証セットアップ（2026-04-11）

GA4データ分析・マーケ支援のためのMCPサーバー2つを導入済み。認証セットアップが残っている。

**導入済みMCP：**
- `analytics-mcp`（Google公式）— GA4データ取得・レポート・広告分析
- `search-analytics`（GSC統合版）— SEOキーワード分析・GA4+GSC相関

**settings.json 登録済み、コードレビュー済み。認証のみ未完了。**

**セットアップ手順：**

#### Step 1: Google Cloud Console でサービスアカウント作成
1. https://console.cloud.google.com/ にアクセス
2. 「IAMと管理」→「サービスアカウント」→「サービスアカウントを作成」
3. 名前: `ga4-mcp`（任意）
4. 役割:「閲覧者」（Viewer）を付与
5. 「キー」タブ → 「鍵を追加」→「新しい鍵を作成」→ JSON を選択 → ダウンロード

#### Step 2: GA4プロパティへのアクセス権付与
1. GA4管理画面 → 「プロパティのアクセス管理」
2. サービスアカウントのメールアドレス（`ga4-mcp@xxx.iam.gserviceaccount.com`）を「閲覧者」で追加
3. search-analytics でGSCも使う場合: Google Search Console → 設定 → ユーザーと権限 → 同じメールを追加

#### Step 3: JSONキーの配置
```bash
# ダウンロードしたJSONを配置
cp ~/Downloads/xxxx.json ~/.claude/credentials/ga4-service-account.json
```

#### Step 4: search-analytics の .env 編集
```bash
# ファイル: ~/.claude/mcp-servers/mcp-search-analytics/.env
# 以下を実際の値に書き換え:
ANALYTICS_CREDENTIALS_PATH=/Users/uedashinya/.claude/credentials/ga4-service-account.json
GSC_SITE_URL=sc-domain:実際のドメイン
GA4_PROPERTY_ID=実際のプロパティID
# 2サイト目がなければ MEBELCENTER_* は仮値でOK（起動時チェックで弾かれるので要対応）
```

#### Step 5: Claude Code 再起動
再起動後、MCPツールが使えるようになる。

**確認コマンド（再起動後）：**
- analytics-mcp: `get_account_summaries` が呼べればOK
- search-analytics: GSCデータが取得できればOK

**背景:** マーケ支援の自動化基盤として導入。分析結果 → Ren（マーケティング）が施策提案 → 広告運用・SEO対策をエージェントで回すのがゴール。詳細は `knowledge/template-sales/daily-improvement-strategy.md` 参照。

**Sakura レビュー残件（Medium 2点、次回改善でOK）：**
- `_initialize_services` の raise メッセージを汎用化
- `load_dotenv()` にファイルパスを明示指定
対象: `~/.claude/mcp-servers/mcp-search-analytics/unified_analytics_server.py`

---

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
