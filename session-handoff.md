# セッション引き継ぎ

## 残件（アスカからの提案）

1. **コトへの依頼テンプレートをCLAUDE.mdに組み込む**（コピーライティングガイドの9項目テンプレート）
2. **コトとカイにコピーライティング基礎をagent-studyで学習させる**（`knowledge/copywriting/copywriting-basics-judgment-guide.md`）
3. **Notion CRM再開前にスコープ記述書を1枚まとめる**（WBSガイドのテンプレート活用）

---

## 完了済み（このセッションで実施）
- `agents/sales-consultant.md`（タク）を新規作成 → **再起動で有効化**
- `agents/marketing-planner.md`（レン）にマーケ知識ベースを追加
- `skills/agent-study/SKILL.md` を新規作成
- `knowledge/marketing/Marketing_Textbook_for_Beginners.md` に教科書を移動
- `agents/sales-consultant.md` の agent-study マッピングへの追記は未実施（次回対応可）

---

## 作業中③: Notion顧客リスト管理の実装

### 状況
- 設計フェーズで一時停止中

### 提案済みフィールド構成
- 会社名 / 屋号（タイトル）
- 担当者名、電話番号、メールアドレス
- ステータス（見込み・商談中・成約・失注・既存）
- 事業種別（Web制作・AI・その他）
- 担当、最終連絡日、メモ、流入元

### 再開時の確認事項
1. フィールド構成の承認（追加・変更があれば）
2. Notionアカウント・ワークスペース確認
3. APIトークン（`secret_...`）の取得状況
4. トークンが揃い次第、`~/.claude/scripts/notion-crm.py` を作成する

---

## 作業中①: メール自動化 Phase 1（日程確定 → Googleカレンダー登録）

### 状況
- スクリプト実装・サクラのセキュリティレビュー対応済み
- `~/.claude/scripts/mail-check.py` 完成（サニタイズ・タイムアウト・UID方式・バリデーション対応済み）
- **Anthropic APIクレジット不足で動作確認が中断中**

### 再開手順
1. Anthropic Plans & Billing でクレジットを追加
2. `python3 ~/.claude/scripts/mail-check.py --dry-run` でドライラン確認（日程確定の分類テスト）
3. 分類結果に問題なければ `--dry-run` なしで本番実行

### 次フェーズ（Phase 1 完了後）
- Phase 2: 返信下書き作成 + LINE WORKS タスク追加
  - LINE WORKS タスクAPIはOAuthスコープに `todo` を追加して試す必要あり
- Phase 3: ゴミ箱移動・不要DM削除

### 関連ファイル
- `~/.claude/scripts/mail-check.py`
- `~/.claude/skills/mail-check/requirements.md`

---

## 作業中②: LINE WORKS Bot Phase 1（クレジット追加待ち）

### 状況
- Flask サーバー・ngrok: 前セッションから落ちている可能性あり
- ALLOWED_USER_ID: UUID `00f4ca87-5717-42de-1540-041b9e780a45` 設定済み
- Webhook受信・ユーザー認証・トークン取得：正常動作確認済み

### 再開手順
1. Anthropic Console でクレジット追加を確認
2. サーバー再起動: `python ~/.claude/line-works-bot/scripts/server.py`
3. ngrok が切れていれば: `ngrok http 5000` → Callback URL を Developer Console に再設定
4. LINE WORKS からメッセージを送って動作確認

### 次フェーズ
- Phase 1 完了後 → 署名検証の有効化 → ngrok 固定ドメイン → Xserver VPS 移行
