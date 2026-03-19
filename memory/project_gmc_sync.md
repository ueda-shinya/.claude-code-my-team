---
name: カラーミー×GMC自動同期ツール プロジェクト
description: officeueda が導入代行サービスとして提供するカラーミー×GMC連携ツールの開発プロジェクト情報
type: project
---

## プロジェクト概要

カラーミーショップの商品情報を Google Merchant Center（GMC）に自動同期するツール。
officeueda が導入設定代行・継続フォローサービスとして提供する。

## ビジネスモデル

- ツール自体は無料・カラーミーアプリストア公開なし
- officeueda の収益源：導入セットアップ ＋ 月次フォロー契約
- 初回クライアント：株式会社 US-SAIJO（2026年4月1日ECオープン）

## 確定した技術方針

- 言語：Python 3.11+
- 実行環境：Google Cloud Run Jobs
- スケジューラ：Cloud Scheduler
- 認証情報：Secret Manager（GCP）
- 設定管理：Cloud Storage（GCS）
- **GMC API：Merchant API（v1beta）← Content API は 2026年8月廃止**
- GCP プロジェクト：新規作成

## 重要な制約・決定事項

- GMC Content API は 2026年8月18日廃止 → 必ず Merchant API を使うこと
- カラーミーアプリストアへの公開は行わない（officeueda 経由で導入が前提）
- 3クライアントまで GCP 無料枠で運用可能

## プロジェクトファイル

- `clients/officeueda/biz-web/gmc-sync/README.md`
- `clients/officeueda/biz-web/gmc-sync/ARCHITECTURE.md`

## 現在の進捗（2026-03-19時点）

設計完了・実装フェーズ待ち。次は Phase 1（カラーミーAPI接続）から着手。
