# セッション引き継ぎ

## 予定

- **3/29（日）マーケティング戦略の全体像を話し合う**（シンヤさんの希望）
  - 参加：アスカ・レン・リナ（4人体制）
  - 事前準備資料：`clients/officeueda/reports/20260329_marketing-kickoff-prep.md`

## 残件

- lp-260326 を WordPress テーマに配置・動作確認（画像は `lp-260319/images/` からコピー）
- 岩本商店の担当表記を確認して lp-260326 を修正

---

## 中断中の作業

### 作業中①: メール自動化 Phase 1（クレジット追加待ち）

- スクリプト実装・サクラのセキュリティレビュー対応済み
- `~/.claude/scripts/mail-check.py` 完成
- **Anthropic APIクレジット不足で動作確認が中断中**

**再開手順：**
1. Anthropic Plans & Billing でクレジットを追加
2. `python3 ~/.claude/scripts/mail-check.py --dry-run` でドライラン確認
3. 問題なければ `--dry-run` なしで本番実行

---

### 作業中②: LINE WORKS Bot Phase 1（クレジット追加待ち）

- Flask サーバー・ngrok: 落ちている可能性あり
- ALLOWED_USER_ID: UUID `00f4ca87-5717-42de-1540-041b9e780a45` 設定済み
- Webhook受信・ユーザー認証・トークン取得：正常動作確認済み

**再開手順：**
1. Anthropic Console でクレジット追加確認
2. サーバー再起動: `python ~/.claude/line-works-bot/scripts/server.py`
3. ngrok が切れていれば: `ngrok http 5000` → Callback URL を Developer Console に再設定

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
