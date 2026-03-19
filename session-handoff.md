# セッション引き継ぎ

## 作業中：2つのプロジェクトが並走中

---

## 【プロジェクト1】officeueda LP制作（コーディング完了・WordPress実装待ち）

### 完成済みファイル一式
- コーディング済みテンプレート：`clients/officeueda/biz-web/lp-260319/index.php`
- スタイル：`clients/officeueda/biz-web/lp-260319/style.css`
- JS：`clients/officeueda/biz-web/lp-260319/js/main.js`
- 画像：`clients/officeueda/biz-web/lp-260319/images/`（13ファイル）

### 本日の変更点
- 補助金関連コンテンツを全削除（セクション11・FAQ Q5・JSON-LD・課題提起の項目）

### シンヤさんの次のアクション（WordPress側）
- `lp-260319/` フォルダをテーマディレクトリに配置
- WordPress でページ新規作成 → テンプレート「LP 2026-03-19」を選択 → スラッグ `lp`
- CF7 フォーム作成 → `YOUR_FORM_ID` を差し替え
- Widgets for Google Reviews 設定 → `YOUR_WIDGET_ID` を差し替え
- GoogleマイビジネスURL → `YOUR_GOOGLE_BUSINESS_URL` を差し替え
- 電話番号 → `YOUR_PHONE_NUMBER`（JSON-LD）を差し替え
- 岩本商店サムネイル → 実スクリーンショットに差し替え（現在は AI 生成仮画像）

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
