# セッション引き継ぎ

## 作業中：2つのプロジェクトが並走中

---

## 【プロジェクト1】officeueda LP制作（要件定義完了・コピー確定・コーディング修正待ち）

### 現在のステータス
- 要件定義書：確定済み `clients/officeueda/biz-web/lp-260319-requirements.md`
- チームレビュー：完了（2026-03-19）
- 判断：作り直し不要・現状修正で進める

### 確定済みコピー
- FVキャッチ：「作ったのに成果が出ない。その先から、一緒にやります。東広島・広島のホームページ制作。」
- FVサブ：「「問い合わせが来ない」「何を直せばいいかわからない」——原因の整理から改善・運用まで、専門用語なしで伴走します。」
- 選ばれる理由：3項目確定（見出し・本文・アイコン方向性）

### ツバサへの修正指示（次のアクション）
以下をindex.phpとstyle.cssに反映する：
1. FVキャッチ・サブコピーの差し替え
2. セクション順序変更：選ばれる理由→プロフィール→中間CTA→サービス→実績→口コミ→制作フロー→FAQ→フォーム
3. 選ばれる理由3項目のコピー差し替え
4. FV人物写真：円形→角丸矩形・スマホ260px以上
5. 課題提起アイコン「…」→SVGアイコンに変更

### シンヤさんの確認待ち事項
- LINE URL（YOUR_LINE_URLのまま）
- 岩本商店の担当表記（正しい表記に修正）
- 料金FAQ追加するかどうか
- 代表プロフィール写真（角丸矩形用）

### 既存ファイル
- `clients/officeueda/biz-web/lp-260319/index.php`
- `clients/officeueda/biz-web/lp-260319/style.css`
- `clients/officeueda/biz-web/lp-260319/js/main.js`
- `clients/officeueda/biz-web/lp-260319/images/`

---

## 【プロジェクト2】カラーミー × GMC 自動同期ツール（設計完了・実装待ち）

### 概要
カラーミーショップの商品情報を Google Merchant Center（GMC）に自動同期するツール。
officeueda が導入設定代行・継続フォローサービスとして提供するビジネス。

### ビジネスモデル（確定）
- ツール自体は無料・カラーミーアプリストアへの公開なし（officeueda 経由で導入）
- officeueda の収益源：導入セットアップ代行 ＋ 月次フォロー契約
- 初回クライアント：株式会社 US-SAIJO（2026年4月1日ECオープン）

### 確定した技術方針
| 項目 | 決定内容 |
|---|---|
| 言語 | Python 3.11+ |
| 実行環境 | Google Cloud Run Jobs（サーバーレス・無料枠あり） |
| スケジューラ | Cloud Scheduler（日次・週次・月次） |
| 認証情報管理 | Secret Manager（GCP） |
| 設定管理 | Cloud Storage（GCS） |
| GMC API | **Merchant API（v1beta）** ← Content API は 2026年8月廃止のため使用不可 |
| GCP プロジェクト | 新規作成（officeueda 用） |

### 次のステップ（実装フェーズ）
**Phase 1 から着手**
1. Phase 1：カラーミーAPI接続・商品取得・ページネーション確認
2. Phase 2：GMC Merchant API接続・サービスアカウント認証・1件送信テスト
3. Phase 3：フィールドマッピング実装（バリエーション含む）
4. Phase 4：差分同期エンジン・id-registry 実装
5. Phase 5：Cloud Run Jobs + Cloud Scheduler デプロイ
6. Phase 6：US-SAIJO 実環境での dry_run → 本番同期
7. Phase 7：エラー通知・月次レポート機能

### 重要メモ
- GMC Content API は 2026年8月18日廃止 → 必ず Merchant API（v1beta）を使うこと
- 3クライアントまで GCP 無料枠で運用可能
- バリエーション商品は `itemGroupId` で GMC の親子関係を表現
