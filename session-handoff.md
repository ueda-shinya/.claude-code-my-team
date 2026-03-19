# セッション引き継ぎ

## 作業中：1つのプロジェクトが継続中

---

## 【プロジェクト1】officeueda LP制作（コーディング完了・デプロイ待ち）

### 現在のステータス
- コーディング修正：完了（2026-03-19）
- サクラによるコードレビュー：完了
- サービス画像刷新：完了（2026-03-19）
- git push：完了（commit: feat: officeueda LP サービス画像をシンプルバナーに刷新）

### 完了した修正
1. FVキャッチ・サブコピー差し替え ✅
2. セクション順序変更 ✅
3. 選ばれる理由コピー差し替え ✅
4. FV人物写真：円形→角丸矩形・WebP変換 ✅
5. 課題提起アイコン：SVGに変更 ✅
6. LINE URL設定（https://lin.ee/v7FmZuu）✅
7. 岩本商店担当表記修正（「コーディング」）✅
8. shinyaueda.webp変換・差し替え ✅
9. portfolio-iwamoto.webp差し替え ✅
10. svc-*.webp 全7枚をブランドブルーバナーに刷新 ✅

### 次のアクション
- WordPressにファイルをデプロイする（シンヤさん作業）
  - `lp-260319/index.php`
  - `lp-260319/style.css`
  - `lp-260319/contact.css`
  - `lp-260319/images/shinyaueda.webp`
  - `lp-260319/images/portfolio-iwamoto.webp`
  - `lp-260319/images/svc-corporate.webp`
  - `lp-260319/images/svc-recruit.webp`
  - `lp-260319/images/svc-lp.webp`
  - `lp-260319/images/svc-swipe.webp`
  - `lp-260319/images/svc-renewal.webp`
  - `lp-260319/images/svc-maintenance.webp`
  - `lp-260319/images/svc-emergency.webp`

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
