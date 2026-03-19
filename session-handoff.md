# セッション引き継ぎ

## 作業中：2つのプロジェクトが並走中

---

## 【プロジェクト1】officeueda LP制作（継続中・一時停止）

### 完成済み素材
- テキスト仕様書：`clients/officeueda/biz-web/reports/20260316_lp-text-spec.md`
- FV背景画像：`clients/officeueda/biz-web/images/fv-bg.webp`
- 選ばれる理由アイコン×3：`icon-local.webp` / `icon-easy.webp` / `icon-result.webp`
- サービスカードアイコン×7：`svc-corporate.webp` / `svc-recruit.webp` / `svc-lp.webp` / `svc-swipe.webp` / `svc-renewal.webp` / `svc-maintenance.webp` / `svc-emergency.webp`
- HTMLドラフト：`clients/officeueda/biz-web/lp-260319/index.php`（および `reports/20260319_lp-html-draft.html`）

### 次のステップ
- Swellでの実装作業（テキスト仕様書に沿って）
- または追加素材があれば生成

### 参考
- 代表顔写真：`clients/officeueda/biz-web/reports/shinyaueda.png`
- サイト改善提案：`clients/officeueda/biz-web/reports/20260316_site-improvement.md`

---

## 【プロジェクト2】カラーミー × GMC 自動同期ツール（新規・設計完了）

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

### カラーミー OAuth 認証
- officeueda が US-SAIJO さんのカラーミー管理画面に編集権限アカウントを持っている
- まず officeueda 側で代理 OAuth 取得を試みる
- 権限不足であれば US-SAIJO さんに一度だけブラウザ操作をお願いする

### GMC 管理者権限
- サービスアカウント登録には管理者権限が必要
- US-SAIJO さんに officeueda の Google アカウントを GMC 管理者として招待してもらう方針

### プロジェクトファイル
- README：`clients/officeueda/biz-web/gmc-sync/README.md`
- アーキテクチャ設計書：`clients/officeueda/biz-web/gmc-sync/ARCHITECTURE.md`

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
