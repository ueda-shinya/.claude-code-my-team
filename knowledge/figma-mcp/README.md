# Figma MCP Server ナレッジ

> 詳細レポート出典：ミオ作成（2026-03-17）

## 概要

Figma MCP Server は AI エージェント（Claude Code / Cursor / VS Code 等）に Figma のデザインデータを提供し、デザイン→コードのワークフローを高速化するブリッジ。

---

## 接続モード

| モード | エンドポイント | 特徴 |
|---|---|---|
| リモート | `https://mcp.figma.com/mcp` | OAuth2認証。デスクトップ不要 |
| デスクトップ | `http://127.0.0.1:3845/mcp` | Dev Mode 有効化で起動。選択範囲ベース |

- VS Code で使うには **GitHub Copilot が有効**である必要がある

---

## 主要ツール一覧

| ツール | 用途 |
|---|---|
| `get_design_context` | レイヤー構造・スタイル・コンポーネント情報を取得（デフォルト: React+Tailwind） |
| `get_variable_defs` | カラー・スペーシング・タイポグラフィ変数を取得 |
| `get_screenshot` | 選択範囲の PNG 画像を取得 |
| `get_metadata` | ID・名前・位置・サイズ等の疎な XML（トークン節約向け） |
| `get_figjam` | FigJam 図を XML で取得 |
| `generate_diagram` | Mermaid 構文から FigJam 図を生成 |
| `generate_figma_design` | ブラウザ画面を Figma フレームにキャプチャ（リモート専用・レート制限免除） |
| `get_code_connect_map` | Figma ノード ID ↔ コードコンポーネントのマッピング取得 |
| `create_design_system_rules` | プロジェクト用ルールファイルを生成 |
| `whoami` | 認証ユーザーのプラン・権限確認（レート制限免除） |

---

## レート制限

| プラン | 日次上限 | 分次上限 |
|---|---|---|
| Enterprise | 600/day | 20/分 |
| Pro / Org（Full/Dev シート） | 200/day | 15/分 |
| Starter / Viewer | 6/month | 10/分 |

- `generate_figma_design` / `add_code_connect_map` / `whoami` は制限免除
- 超過時は 429 エラー

---

## 実装パターン

### パターン1: IDE 内フロントエンド実装支援（最も使いやすい）

デスクトップ MCP を使い、小さい単位で `get_design_context` → `get_variable_defs` → `get_screenshot` → コード生成を回す。

**注意点：**
- 大きいフレームを選択するとタイムアウト・失敗しやすい → **小さい単位に分割**すること
- トークンオーバーフローに注意。分割実行が基本

### パターン2: デザインシステム連携 & CI 自動化

Code Connect でノード ID ↔ コンポーネントをマッピングし、ルールファイルを生成・リポジトリ管理。Figma Webhook でファイル更新を検知して CI 自動実行。

- Code Connect は Organization / Enterprise プランが必要

### パターン3: コード→キャンバス往復レビュー

`generate_figma_design` でブラウザ画面を Figma に送信 → デザイナーがレビュー → `get_design_context` でコードに反映。

---

## 権限・セキュリティ

- 認証ユーザーが閲覧・編集権限を持つファイルのみアクセス可
- 権限不足は 403 エラー → `whoami` で権限確認
- 機密プロジェクトをLLMに送信する際はデータ保持ポリシーを確認すること

---

## 導入手順（最短 PoC）

1. Figma デスクトップで Dev Mode → MCP 有効化
2. IDE で MCP サーバー URL を設定
3. 小範囲で `get_design_context` を実行確認
4. `create_design_system_rules` でプロジェクト用ルール生成 → `rules/` に配置
5. KPI（リードタイム・レビュー指摘数・ツール成功率）を計測
