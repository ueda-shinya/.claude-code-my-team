# カラーミー × GMC 自動同期ツール アーキテクチャ設計書

**作成日**: 2026-03-19
**対象プロジェクト**: officeueda biz-web / gmc-sync
**初回クライアント**: 株式会社US-SAIJO

---

## 1. 技術スタック

| 区分 | 採用技術 | 理由 |
|---|---|---|
| 言語 | **Python 3.11+** | GMC/カラーミーどちらもPythonライブラリが充実。シンプルなスクリプト運用に最適 |
| 実行環境 | **Google Cloud Run Jobs** | サーバーレス・無料枠あり・Cronスケジュール対応・ログが自動集約 |
| スケジューラ | **Cloud Scheduler** | GCPに統合済み。月次・日次どちらも設定可能 |
| 認証情報管理 | **Secret Manager**（GCP） | クライアントごとのOAuthトークンを安全管理 |
| 設定管理 | **Cloud Storage（GCS）** | 設定JSON保存先。シンプル |
| ローカル開発 | **Docker Compose** | Cloud Runと同じコンテナ環境をローカル再現 |
| 主要ライブラリ | `google-auth`, `requests`, `python-dotenv` | Merchant APIは requests で直叩き |

### コスト試算（月次）

3クライアントまで実質0円で運用可能。
4クライアント目以降は Cloud Scheduler が $0.10/ジョブ/月程度。

---

## 2. システム構成図

```
【カラーミーショップ側】
  ┌─────────────────────────────┐
  │  カラーミーショップ (EC)      │
  │  ・商品データ                 │
  │  ・在庫情報                   │
  │  ・価格情報                   │
  └────────────┬────────────────┘
               │ カラーミー OAuth 2.0
               │ GET /v1/products
               ▼
【GCP 実行環境】
  ┌─────────────────────────────────────────────┐
  │                                               │
  │  Cloud Scheduler                              │
  │  （日次・週次・月次 Cron）                      │
  │        │                                      │
  │        ▼ HTTP trigger                         │
  │  Cloud Run Jobs                               │
  │  ┌───────────────────────────────────────┐   │
  │  │  gmc-sync コンテナ（Python）           │   │
  │  │                                        │   │
  │  │  1. config 読み込み（GCS）             │   │
  │  │  2. 認証情報取得（Secret Manager）     │   │
  │  │  3. カラーミーAPI → 商品データ取得     │   │
  │  │  4. フィールドマッピング変換           │   │
  │  │  5. GMC Merchant API → 商品データ送信  │   │
  │  │  6. 実行ログ記録                       │   │
  │  └───────────────────────────────────────┘   │
  │                                               │
  │  Secret Manager        GCS                    │
  │  ・カラーミー          ・clients/             │
  │    access_token          {client_id}/         │
  │  ・カラーミー              config.json        │
  │    refresh_token       ・sync-logs/           │
  │  ・GMCサービスアカウントJSON  {date}.json     │
  │                                               │
  └──────────────────────────────┬──────────────┘
                                  │ Merchant API
                                  ▼
【Google Merchant Center側】
  ┌─────────────────────────────┐
  │  Google Merchant Center      │
  │  ・products.insert           │
  │  ・products.update           │
  │  ・products.delete           │
  └─────────────────────────────┘
                  │
                  ▼
  Googleショッピングタブ（無料リスティング）
```

---

## 3. ディレクトリ構成

```
gmc-sync/
├── README.md
├── ARCHITECTURE.md              # 本ファイル
│
├── src/
│   ├── main.py                  # エントリポイント（引数: --client-id）
│   ├── config.py                # 設定読み込み（GCS or ローカル.env）
│   ├── auth/
│   │   ├── colorime_auth.py     # カラーミー OAuth 2.0 クライアント
│   │   └── gmc_auth.py          # GMC サービスアカウント認証
│   ├── api/
│   │   ├── colorime_client.py   # カラーミー API クライアント
│   │   └── gmc_client.py        # GMC Merchant API クライアント
│   ├── mapping/
│   │   ├── product_mapper.py    # カラーミー商品 → GMC products 変換（メイン）
│   │   ├── category_mapper.py   # カテゴリ変換
│   │   └── price_formatter.py   # 価格・通貨フォーマット変換
│   ├── sync/
│   │   ├── sync_engine.py       # 差分検出・同期制御
│   │   └── id_registry.py       # 商品ID対応管理
│   └── utils/
│       ├── logger.py            # 構造化ログ出力（Cloud Logging対応）
│       └── gcs_client.py        # GCS 読み書きユーティリティ
│
├── clients/
│   └── us-saijo/
│       ├── config.json          # クライアント固有設定
│       └── id-registry.json     # 同期済み商品ID対応表
│
├── scripts/
│   ├── setup_client.py          # 新規クライアント初期セットアップ
│   ├── refresh_token.py         # カラーミー refresh_token 更新
│   ├── dry_run.py               # 本番送信なしで変換結果確認
│   └── monthly_report.py        # 月次レポート出力
│
├── tests/
│   ├── test_product_mapper.py
│   ├── test_sync_engine.py
│   └── fixtures/
│       ├── colorime_product.json
│       └── gmc_product.json
│
├── Dockerfile
├── docker-compose.yml
├── requirements.txt
├── .env.example
└── deploy/
    ├── cloud-run-job.yaml
    └── cloud-scheduler.yaml
```

---

## 4. フィールドマッピング設計

### 基本マッピング表

| カラーミーフィールド | GMC Merchant API フィールド | 変換ロジック |
|---|---|---|
| `product.id` | `offerId` | `colorime_{shop_id}_{product_id}` 形式 |
| `product.name` | `title` | 最大150文字（超過はtruncate） |
| `product.description` | `description` | HTMLタグ除去・最大5,000文字 |
| `product.image_url` | `imageLink` | HTTPS URLのみ有効 |
| `product.images[1..10]` | `additionalImageLinks` | 最大10件 |
| `product.url` | `link` | 商品詳細URL |
| `product.price` | `price.value` | float変換・小数点以下2桁 |
| （固定値: "JPY"） | `price.currency` | 常に "JPY" |
| `product.stock` | `availability` | 下記ロジック参照 |
| `product.category` | `googleProductCategory` | カテゴリマッピングテーブル参照 |
| `product.model_number` | `mpn` | 存在する場合のみ |
| `product.maker` | `brand` | なければ shop名をフォールバック |
| （固定値） | `condition` | `"new"` 固定 |
| （固定値） | `channel` | `"online"` 固定 |
| （固定値） | `targetCountry` | `"JP"` 固定 |
| （固定値） | `contentLanguage` | `"ja"` 固定 |

### 在庫ステータス変換ロジック

```python
def map_availability(product: dict) -> str:
    stock = product.get("stock", 0)
    stock_management = product.get("stock_management", False)

    if not stock_management:
        return "in_stock"
    elif stock > 0:
        return "in_stock"
    elif stock == 0:
        if product.get("backorder_allowed", False):
            return "preorder"
        return "out_of_stock"
    else:
        return "out_of_stock"
```

### バリエーション処理方針

GMC の `itemGroupId` を使って親子関係を表現。

```
カラーミー: product_id=100（ピットシャツ）
  └── variant[0]: color=赤, size=M → offerId: colorime_XXXX_100_v0
  └── variant[1]: color=赤, size=L → offerId: colorime_XXXX_100_v1
  └── variant[2]: color=青, size=M → offerId: colorime_XXXX_100_v2

全variantに itemGroupId: colorime_XXXX_100 を付与
```

### カテゴリマッピング（US-SAIJO 初期設定）

```json
"category_map": {
  "ユニフォーム": "Apparel & Accessories > Clothing",
  "作業服": "Apparel & Accessories > Clothing > Outerwear",
  "学生服": "Apparel & Accessories > Clothing > Uniforms",
  "グッズ": "Arts & Entertainment > Party & Celebration > Gift Giving > Gifts",
  "__default__": "Apparel & Accessories"
}
```

---

## 5. クライアント管理

### config.json 構造

```json
{
  "client_id": "us-saijo",
  "client_name": "株式会社US-SAIJO",
  "colorime": {
    "shop_id": "wsp.us-saijo.com",
    "api_base": "https://api.shop-pro.jp/v1",
    "token_secret_name": "gmc-sync-us-saijo-colorime-token"
  },
  "gmc": {
    "merchant_id": "123456789",
    "sa_secret_name": "gmc-sync-us-saijo-gmc-sa-key"
  },
  "sync": {
    "schedule": "0 3 * * *",
    "batch_size": 100,
    "enable_delete": true,
    "dry_run": false
  },
  "category_map": { ... },
  "shop_defaults": {
    "brand_fallback": "US-SAIJO",
    "condition": "new",
    "target_country": "JP",
    "content_language": "ja"
  }
}
```

### 認証情報（Secret Manager）

| シークレット名 | 内容 |
|---|---|
| `gmc-sync-{client_id}-colorime-token` | カラーミー access_token + refresh_token |
| `gmc-sync-{client_id}-gmc-sa-key` | GMCサービスアカウントのJSONキー |

### 新規クライアント追加手順

```
1. カラーミーOAuth認証 → scripts/setup_client.py --client-id {id}
2. GMCサービスアカウントJSONキーを Secret Manager に登録
3. config.json を作成して GCS にアップロード
4. Cloud Scheduler にジョブ追加
5. scripts/dry_run.py --client-id {id} で確認
6. 初回フル同期を実行
```

---

## 6. 差分同期エンジン

```
① カラーミーから全商品取得（ページネーション対応）
② GCS の id-registry.json を読み込み（前回同期情報）
③ 差分判定
   ・未登録 → INSERT
   ・updated_at が新しい → UPDATE
   ・変更なし → SKIP
   ・カラーミー側に存在しない → DELETE（enable_delete=true の場合）
④ GMC API に送信（バッチ処理・100件/回）
⑤ id-registry.json を更新して GCS に保存
⑥ 実行サマリーをログ出力
```

---

## 7. GMC Merchant API エンドポイント

> ⚠️ Content API は 2026年8月18日廃止。Merchant API を使用する。

```
POST   https://merchantapi.googleapis.com/products/v1beta/accounts/{merchantId}/products
GET    https://merchantapi.googleapis.com/products/v1beta/accounts/{merchantId}/products
DELETE https://merchantapi.googleapis.com/products/v1beta/accounts/{merchantId}/products/{productId}
```

認証はサービスアカウントを推奨（OAuthより安定・refresh不要）。

---

## 8. リスクと対策

| リスク | 対策 |
|---|---|
| カラーミー access_token 期限切れ | refresh_token で毎回更新 → Secret Manager 書き戻し |
| GMC 商品ポリシー違反による非承認 | 基本バリデーション（画像HTTPS・タイトル必須・価格>0）を事前実施 |
| GMC 30日更新義務の失敗 | Cloud Monitoring でジョブ失敗時にメール通知 |
| カラーミー API レート制限 | ページネーション間に 0.5秒スリープ |
| Merchant API ベータ版仕様変更 | `v1beta` でピン止め。半年に1回の定期確認 |
| id-registry.json 破損 | 書き込み前にバックアップ作成。破損時はフル再同期で復旧 |

---

## 9. 実装フェーズ計画

| フェーズ | 内容 |
|---|---|
| Phase 1 | カラーミーAPI接続・商品取得・ページネーション確認 |
| Phase 2 | GMC Merchant API接続・サービスアカウント認証・1件送信テスト |
| Phase 3 | フィールドマッピング実装（バリエーション含む） |
| Phase 4 | 差分同期エンジン・id-registry 実装 |
| Phase 5 | Cloud Run Jobs + Cloud Scheduler デプロイ |
| Phase 6 | US-SAIJO 実環境での dry_run → 本番同期 |
| Phase 7 | エラー通知・月次レポート機能 |

---

## 10. 月次フォロー運用（officeueda向け）

1. Cloud Logging でジョブ成功率を確認
2. GMC コンソールで非承認商品数を確認
3. カテゴリマッピング未対応カテゴリの確認（ログ警告件数）
4. 商品件数の増減確認
5. クライアントへの月次レポートを出力（`scripts/monthly_report.py`）

---

## 未確認事項（要シンヤさん確認）

1. **Merchant API のベータ版使用**について → 2026年8月の Content API 廃止後に正式版へ移行する想定でよいか
2. **GCP プロジェクト** → 既存の officeueda 用プロジェクトがあるか、新規作成が必要か
3. **カラーミーOAuth認証フロー** → US-SAIJOさんがブラウザ操作するか、officeueda が代理でトークン取得するか
