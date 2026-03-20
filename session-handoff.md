# セッション引き継ぎ

## 作業中：1つのプロジェクトが継続中

---

## 【プロジェクト1】officeueda LP制作 → **完了（デプロイ待ち）**

### 現在のステータス
- コーディング：完了
- サクラによるコードレビュー：完了
- git push：完了

### デプロイ対象ファイル（シンヤさん作業）
- `lp-260319/index.php`
- `lp-260319/style.css`
- `lp-260319/contact.css`
- `lp-260319/js/main.js`
- `lp-260319/js/lazyload-prioritize.js`
- `lp-260319/images/shinyaueda.webp`
- `lp-260319/images/portfolio-iwamoto.webp`
- `lp-260319/images/svc-corporate.webp`
- `lp-260319/images/svc-recruit.webp`
- `lp-260319/images/svc-lp.webp`
- `lp-260319/images/svc-swipe.webp`
- `lp-260319/images/svc-renewal.webp`
- `lp-260319/images/svc-maintenance.webp`
- `lp-260319/images/svc-emergency.webp`

### デプロイ後の確認事項
- GA4 `cta_click` イベントが各CTAボタンで計測されるか確認
- `lazyload-prioritize.js` の動作確認（Chrome DevTools > Network でlazy画像のリクエストタイミングを確認）
- `img.src = img.src` でリフェッチが走っているか確認 → 不要なら該当行を削除

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
