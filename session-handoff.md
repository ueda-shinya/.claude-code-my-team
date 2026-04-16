# セッション引き継ぎ

## 🔴 最優先・再起動後すぐ再開（2026-04-15）

**Claude Code レーダー Cron 登録の再試行**

- 実装・レビュー・承認すべて完了済み（Shu/Sakura/Kanata/Rina）
- 残りは `/schedule` スキルで毎日6:00 JSTのCron登録のみ
- 本セッションで2回試行したが `remote claude.ai 接続エラー` で失敗
- 再起動後、以下のコマンドで再試行：

```
/schedule 毎日 06:00 JST に /claude-code-radar を自動実行。名前: claude-code-radar-daily
```

### 完成している基盤
- Notion DB「Claude Code レーダー」作成済み（`NOTION_RADAR_DB_ID=342b7112-f5f8-81c2-b10b-c421b1f440f3`）
- `scripts/notion-radar.py`（サクラ承認済み・未コミット）
- `skills/claude-code-radar/SKILL.md`（リナ承認済み・未コミット）
- `skills/morning-briefing/SKILL.md` にステップ7統合済み（リナ承認済み・未コミット）

### 未コミット変更ファイル（Cron登録後に一括コミット）
- scripts/notion-radar.py（新規）
- skills/claude-code-radar/（新規）
- skills/morning-briefing/SKILL.md（変更）
- .env（NOTION_RADAR_DB_ID 追記・git管理外）

---

## 予定

- **4/12（日）エージェント精度向上ラウンドテーブル**（仮・knowledge-buffer.md に議題保存済み）

## 中断中: 集客プロジェクトのLP制作（サービス設計待ち）

**2026-04-13 セッションで着手、LP制作の入り口で中断。**

### 経緯
- LP改善自動化プロジェクトの話からスタート
- ラウンドテーブル実施（ナギ・レン・タク・リナ）で方向性決定
- 決定事項：Web制作の直受け、CTA「無料サイト診断」、SNS流入用サブドメインLP、価格25-30万、「3ヶ月レポート付き」差別化
- 「自社LP＝実証＋デモ＋実績」の一石三鳥スキーム（将来「LP＋AI自動改善サービス」としてメニュー化）
- 集客プロジェクトとしてSNS運用チームとLP改善チームを統合（レン統括）
- `/lp-create` 起動してヒアリング開始 → **「サービス内容がまだ確定していないのでLP制作は早い」とシンヤさん判断で中断**

### 再開時の状態
- `memory/project-business-strategy-2026.md` の「集客プロジェクト」セクションが最新方針
- 次にやること：**事業戦略チーム（ナギ・レン・タク・リナ）で先にサービス設計を固める**
- 決めるべきこと：サービス名・パッケージ内容・価格体系・「3ヶ月レポート付き」の具体的中身・無料サイト診断の範囲と内容
- サービスが確定してから LP 制作（`/lp-create`）に戻る

### 再開手順
1. 「集客プロジェクトのサービス設計の続き」と声をかける
2. アスカが事業戦略チーム（ナギ・レン・タク・リナ）でサービス設計ラウンドテーブルを開催
3. サービス確定後、`/lp-create` でLP制作に戻る

---

## 作業中: 集客プロジェクト（SNS運用＋LP改善統合）

### 完了済み
- ラウンドテーブル実施（ナギ・レン・ツムギ）→ 方針決定
- ミナト（sns-director）エージェント作成・リナ検証済み
- x-auto-wizardの知見をミナト・コトに反映・リナ検証済み
- Notion「プロジェクト管理」DB新設 + スクリプト（notion-projects.py / NOTION_PROJECTS_DB_ID）
- レン月間テーマ・KPI初期設定済み
- 初週投稿カレンダー作成・型指定済み（`clients/officeueda/sns/calendar/2026-04-W3.md`）
- twitter-mcp 導入済み（`~/.claude/mcp-servers/twitter-mcp/` / APIキー設定待ち）
- meta-mcp 導入済み（`~/.claude/mcp-servers/meta-mcp/` / APIキー設定待ち）
- 初週コンテンツ制作完了（コト7本 + ハル2本 + 画像3点）
- Notionに投稿内容アップ済み

### 決定事項
- アカウント統合1本（Web + AI を「DXまるごと相談」で一本化）
- メインSNS: X + Threads（同じ趣旨でトーン違いの2バージョン）
- Instagram広告は一旦停止
- 投稿実行: 初期2-3ヶ月はシンヤさん手動 → 安定後にAPI自動化検討
- シンヤさんの関与: 月1承認 + 事例素材提供 + 月1顔出し投稿
- 所在地: 広島県（※宇和島ではない）

### 次にやること
0. **【最優先・再開ポイント 2026-04-15】`/sns-post` 統合スキルを作るか判断** — コト（フック設計済み）→ humanizer（JP-1〜15・3パス）→ 品質チェックを一本化するか。現状は個別呼び出し可能だが未統合。シンヤさんの回答待ちで中断。
   - 完了済み: コトにフックフレームワーク統合 / humanizer `/humanizer` 稼働 / Notion SNS管理DB + notion-sns.py
   - 保留中: 投稿方向性は事業戦略確定後に詰める
1. **3人の意見を仕組みに反映**（レン・ミナト・コト共通指摘）— 初週データ記録フロー、判定基準設定、次週フィードバックループをミナトの運用フロー（sns-director.ja.md）に組み込む
2. **Notionの投稿文表示の改善** — シンヤさんのイメージと違ったので修正（どう変えたいかヒアリングから）
3. X / Threads APIキー取得・設定（シンヤさん作業）
4. Xアカウント開設・プロフィール設定
5. 月曜画像のFigma仕上げ（L1+L2合成 + 屋号テキスト）
6. 投稿開始（4/14〜）

### チーム体制（集客チーム）
レン（戦略統括・SNS×LP一貫性）
├ SNS運用：ミナト → コト/ハル/ルナ（制作）
└ LP改善：カイ（LP設計）→ シュウ（実装）→ サクラ（レビュー）+ リナ（論理検証）
→ シンヤさん（投稿・月次承認）

### 関連ファイル
- 投稿カレンダー: `~/.claude/clients/officeueda/sns/calendar/2026-04-W3.md`
- 投稿テキスト: `~/.claude/clients/officeueda/sns/drafts/2026-04-W3-posts.md`
- Threads長文: `~/.claude/clients/officeueda/sns/drafts/2026-04-W3-threads-long.md`
- 画像: `~/.claude/clients/officeueda/sns/images/`
- x-auto-wizard: `~/.claude/reports/x-auto-wizard.md`

---

## 残件

### Windows 専用
- chatwork-sync.py の Notion 案件リスト登録ロジックを修正（登録内容がよくない・後日対応）
- メール自動化 Phase 1：mail-check.py の動作確認（`--dry-run` → 本番実行）
- メール自動化にもスケジュール登録・変更検出を追加（Chatwork版と同様）
- Chatwork 一次返信が発動したとき LINE WORKS 通知が届くか実動作確認（2026-03-30修正済み・未確認）
- ~~hourei MCPサーバーの追加~~ → **4/11 完了**
- ~~git post-merge hookの配置~~ → **4/11 完了**

### PC不問
- ~~GA4 MCPサーバー 認証セットアップ~~ → **4/11 完了**（analytics-mcp + search-analytics 両方接続確認済み）
- ~~GA4 MCPサーバー リネーム~~ → **4/11 完了**（vesivanov→officeueda / mebelcenter→ussaijo + サクラレビュー済み）
- エージェント精度向上ラウンドテーブル（日程未定・knowledge-buffer.mdに議題保存済み）
- 広告の直帰率改善をレンに相談（instagram/cpc: 95.2%、google/cpc: 83.3%）※2026-04-11 GA4更新
- ~~オフィスウエダの今後の事業展開について話し合う~~ → **4/11-12 ラウンドテーブル実施済み**
- ~~事業計画アクションプラン実行中（P1）~~ → **4/12 事業戦略を全面刷新**（下記参照）
- **4/12 事業戦略ラウンドテーブル結果** → `memory/project-business-strategy-2026.md` / `memory/project-service-design.md` / `memory/project-branding.md`
- ITまるごとサポートの詳細設計（Q1〜Q6ヒアリング完了・次回はサービス仕様書作成から再開）
  - Q1 現保守作業: WP本体/プラグイン/テーマ更新・バックアップ
  - Q2 対応スキル: WP全般・サーバー/DNS・Google Workspace/M365・PC/NW・GBP・Excel・AI活用（kintone/Notionは除外）
  - Q3 対応しない: 基幹システム直接（メーカー橋渡しは可）・大規模NW（複数拠点VPN等）・24/365（営業時間内で最速）・休日深夜（約束不可だが場合により可）・重大セキュリティ。個人PC/家庭NWは対応可
  - Q4 対応方法: 訪問（東広島/広島/呉、県外は案件次第で交通費別途）・リモート・電話・チャット。現物預かりは単価低いので消極的
  - Q5 工数目安（初期設定・運用で調整）: 2.2万=2-3h/月、3.3万=4-5h/月、5.5万=8-10h/月。20社時の総工数上限は運用で見極め
  - Q6 既存保守: 1社（月5,000〜20,000円、訪問毎課金＋追加作業）。ECサイト立ち上げ完了→運用フェーズ移行予定＝新サービスへの移行候補
  - 次アクション: プラン別サービス仕様書作成（対応範囲・料金・SLA・契約条件）
- 自社サイト改修（新サービス体系への対応・未着手）
- 広島の交流会を探してエントリー（未着手）
- 名刺・自己紹介の最終確定（仮OKの状態・`memory/project-branding.md`参照）
- LP改善自動化プロジェクト開始（別セッション・`memory/project-business-strategy-2026.md`参照）
- notion-tasks.py のDBスキーマエラー（--addで「種別・開始日・担当」プロパティが見つからない）→ Win側で作成したDBとスクリプトの整合性を確認する
- Notion CRM改善: 顧客レコードに「納品サイトURL」「業種」フィールドを追加し、既存顧客分を埋める（営業アプローチ時の事前準備・A/B/C仕分けに活用）

---

## 中断中の作業：gsc-ga4-analyzer（/feature-flow 初号機）

**2026-04-13 セッションで着手、ステップ5途中で区切り。**

### 案件
Notion案件管理DB「GSC・GA4計測診断＆改善提案ツール」（P2-今週中 / Windows）

### 進捗
- **ステップ1 ヒアリング**：完了
- **ステップ2 要件定義**：リナ局所検証＋修正＋シンヤさん合意済み（Notion 1. 要件定義 に2ブロック）
- **ステップ3 設計**：シュウ起案→リナ局所検証 rev.1→rev.7 まで7ラウンド／Notion 2. 設計 に複数ブロック
- **ステップ4 KYT**：コア5名＋ケン で実施、48件洗い出し→設計バグ4件発見→rev.6/rev.7で反映／Notion 2.5 に記録
- **ステップ5 リナ統合検証**：**条件付き承認**。残り4点補正で実装着手OK
  - M1：要件「営業品質＝主観評価」と KYT「rubric事前固定」の矛盾 → 要件完了条件を「rubric全項目パス＋シンヤ最終承認」に更新
  - M2：KYT「プロンプトファイル化」が設計未反映 → 設計rev.8で `prompts/` ディレクトリ追加
  - 設計rev.8で：サニタイズ関数配置箇所指定／将来の複数クライアント解除コメント／3分ゲート実装時対処
  - KYT対処マトリクス1枚（15件を 設計反映済み/実装ガードレール/監視 に分類）
  - OAuth審査回避禁止をREADME運用セクションに明記
- **ステップ6 実装**：未着手
- **ステップ7 レビュー**：未着手
- **ステップ8 動作確認＋ふりかえり**：未着手

### 再開手順
1. Notion案件ページを `python ~/.claude/scripts/notion-tasks.py --show "GSC・GA4計測診断"` で全文確認
2. 上記M1・M2＋4点補正を rev.8 として実施（アスカが直接またはシュウに依頼）
3. リナに差分再検証を依頼
4. 要件「rubric」を作成（シンヤさん＋レン、実装着手前の必須作業）
5. ステップ6（シュウに実装依頼）へ進む

### 決定事項（リセット不可）
- **MVP = 手動CLI実行型**、Markdown出力、officeueda 3対象（コーポ/biz-ai LP/biz-web LP）のドッグフーディング
- **API直叩き**（MCP非依存、GA4 Data API / Search Console API、OAuth 2.0 テストユーザー方式）
- **Claude APIに送るのは集計値のみ**（ホワイトリスト＋正規表現二重防御、GSC query は物理ブロック）
- **officeuedaはデータを預からない**（tmp/ 配下で完結、git管理対象外）
- **法務3地雷は別トラック**（ケン監修済み、契約書・覚書・免責文のひな形整備は弁護士スポット相談で進行）
- **実装対象ディレクトリ**：`scripts/gsc-ga4-analyzer/`（未作成）

### 法務並行タスク
- 弁護士スポット相談（1〜2時間、5〜10万円）：業務委託契約＋個情法覚書（DPA相当）＋免責条項の監修
- ケン作成のドキュメント12点リストは `memory/`（未作成）または sync 時に別途記録予定

### 注意
- 本セッションでリナが7ラウンドも局所検証→統合検証まで回しているため、次回は**差分のみ検証依頼**で良い
- 残り4点補正はドキュメント中心なのでアスカ直接反映可能（`prompts/` ディレクトリ追加は実質1行）

---

## 🔥 稲田さん案件：商談準備（アクティブ）

**スライド送付済み → 稲田さんから「話を聞きたい」と返信あり → 商談準備フェーズ**

詳細は `clients/inada-ryota/README.md` の「商談準備」セクションに一元化。
別セッションで作業する場合はそちらを参照すること。

### 別セッションでの再開手順
1. `clients/inada-ryota/README.md` の「商談準備」セクションを読む
2. スライド修正11件の反映方法を判断（Genspark再生成 or 手動修正）
3. `proposals/phase2-plans-detail.md` を把握（商談で使うプラン詳細）
4. 商談日程が確定したら準備（シミュレーション等）に進む

---

## 作業中: Office Ueda 標準サービス資料化

**2026-04-14 セッションで着手、資料化＋スライド化テストまで完了。**

### 完了
- Phase 2 継続運用プラン（梅竹松）の詳細設計 → `clients/inada-ryota/proposals/phase2-plans-detail.md`
- Office Ueda 標準サービス「Web運用パートナーサービス」資料化 → `clients/officeueda/services/web-partner-service.md`（Phase 1 STEP 1-3 + Phase 2 梅竹松 + オプション7種 + FAQ + 運用ルール）
- スライド化ツール導入（Marp CLI / pptx-from-layouts-skill）
- 比較テスト実施 → `tmp/slide-comparison/` に両ツール成果物あり

### 次にやること
- 両ツールの成果物を PowerPoint で開いて見栄え比較 → 商談用にどちらを採用するか判断
- Section 12（実績・プロフィール等）の埋め込みは Notion タスクへ登録済み（P4）
- Office Ueda ブランド用 Marp カスタムテーマ CSS 作成は Notion タスク登録済み（P3）

### 関連ファイル
- サービス資料: `~/.claude/clients/officeueda/services/web-partner-service.md`
- Phase 2 詳細: `~/.claude/clients/inada-ryota/proposals/phase2-plans-detail.md`
- 比較テスト: `~/.claude/tmp/slide-comparison/`

---

## 導入済みツール（2026-04-14）

### Marp CLI v4.3.1（グローバル）
- `npm i -g @marp-team/marp-cli` で導入済み
- 使い方: `marp input.md --pptx -o output.pptx`（html/pdf も可）
- 既存 Edge を自動検出するので追加 Chromium 不要
- サクラ監査条件: `--server` モード使用禁止・npm run version等禁止

### pptx-from-layouts skill
- 配置先: `~/.claude/skills/pptx-from-layouts/`（51ファイル）
- テンプレ: `~/template/inner-chapter.pptx`（think-cell系・要 VirusTotal 手動スキャン推奨）
- **Python 3.12 必須**（typing.Self 依存）。python-pptx / pydantic は 3.12 側に導入済み
- 既知バグ: profile.py / validate.py が `_archive` 依存で動作不可（generate.py は独立動作OK） → Notion 登録済み（P3）
- サクラ監査条件: thumbnail 機能禁止（LibreOffice/ImageMagick 非導入）

### 変換スクリプト（既存）
- `~/.claude/scripts/md-to-html.py` — Markdown → HTML（ブラウザ印刷PDF用）
- `~/.claude/scripts/md-to-docx.py` — Markdown → Word

---

## 設計・実装決定ログ

形式：`[YYYY-MM-DD] <決定内容>（対象ファイル or 機能）`

**削除ポリシー：** アスカが sync 時に各エントリを確認し、「実装がgitにコミット済み」または「シンヤさんが完了と明示した」エントリを削除する。「作業なし」判定は残件セクションのみで行い、このログが残っていても「作業なし」にできる。

[2026-04-05] メールにスケジュール登録・変更検出を追加するタイミングで、カレンダー処理を `calendar_utils.py` として共通モジュールに切り出す（chatwork-sync.py・mail-check.py 両方から import して使う構成）
[2026-04-09] skill.md（英語版）を skill.ja.md に同期する（カナタにフォアグラウンドで依頼すること。バックグラウンドだと権限プロンプトに応答できず失敗する）
[2026-04-10] has_schedule除外条件に「スケジュール確定の報告はfalseにしない」の但し書きを追加する（chatwork-sync.py の build_analyze_prompt 内）

[2026-04-15] **settings.json パースエラー問題（Mac発生・真因未特定）**
- 症状: Claude Code 起動時に `settings file failed to parse: Expected array, but received undefined. Permission rules and other settings from this file are not in effect.`（Mac側）
- Mac側対応: `permissions.ask: []` 追加 ＋ `hooks` セクション新形式化の2点を同時修正で解決
- **真因は未特定**（二分法検証未実施・リナ指摘）
- Win側状況（2026-04-15チェック済み）:
  - hooks は既に新形式で問題なし
  - `permissions.ask` は欠落していたが**正常起動できていた** → `ask` 欠落単独はパースエラーの十分条件ではない
  - 無害な予防措置として `ask: []` を追加・同期（バックアップ: `settings.json.bak.20260415-000000`）
- **次アクション**: Mac側で二分法検証（片方だけ戻して再現）で真因を特定する必要あり（Notion案件登録済み）
- 関連: 同日 settings.json から平文 Gemini API キーも削除（Win側は元から .env 運用なので不要）



---

## 中断中の作業

### 完了済み: GA4 MCPサーバー 認証セットアップ（2026-04-11）

- analytics-mcp + search-analytics 両方接続確認済み
- サービスアカウント: `ga4-mcp@claude-mcp-integration-490103.iam.gserviceaccount.com`
- プロパティ: officeueda.com (320411221) / WSP us-saijo (530385907)
- **Sakura レビュー残件（Medium 2点、次回改善でOK）：** `_initialize_services` の raise 汎用化 / `load_dotenv()` パス明示指定（対象: `unified_analytics_server.py`）
- **リネーム残件:** サイト名 vesivanov→officeueda / mebelcenter→ussaijo（動作に支障なし）

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
