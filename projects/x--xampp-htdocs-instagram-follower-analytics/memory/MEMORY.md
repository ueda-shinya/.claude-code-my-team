# Instagram Follower Analytics - Project Memory

## プロジェクト概要
- **種別**: Chrome拡張機能（Manifest V3）
- **目的**: 自分のInstagramフォロワーを解析して営業活用
- **場所**: `x:/xampp/htdocs/instagram-follower-analytics/`

## ファイル構成
```
manifest.json     - MV3マニフェスト
injected.js       - ページコンテキストのfetch/XHRインターセプター
content.js        - injected.jsをページに挿入 + メッセージ中継
background.js     - データ保存・業種/規模分類・バッジ更新
popup.html        - UI（テーブル・フィルター・CSV出力）
popup.js          - ポップアップロジック
popup.css         - スタイル
```

## 技術的ポイント
- Instagram API: `/api/v1/friendships/{userId}/followers/` と `/api/v1/users/{userId}/info/`
- ページコンテキストとコンテンツスクリプトは `window.postMessage` で通信
- データは `chrome.storage.local` に保存（`{ followers: { [username]: UserObject } }`）
- 業種分類: bio のキーワードマッチング（11業種）
- 規模分類: フォロワー数で4段階（インフルエンサー/マイクロ/中規模/個人）

## ユーザー要望
- 対象: 自分のアカウントのフォロワー
- 収集方法: Chrome拡張（フォロワーページスクロールで自動収集）
- 活用: フォロワーリスト作成・CSV出力、プロフィール分析（業種・規模）

## 全自動取得対応（v1.2）
- injected.js: getUserId(web_profile_info API) + fetchAllFollowers(ページネーション, 1.5秒間隔) + performLogout(POST /api/v1/accounts/logout/)
- IG_APP_ID: '936619743392459', csrftoken を cookie から取得
- 取得フロー: pendingFetch をストレージに保存 → 新タブ開く → content.js onReady で拾う → injected.js で API 実行
- autoFetchState: tabId, username, autoLogout, autoVisit, fetched
- fetchStatus: 'idle' | 'opening_tab' | 'resolving_user' | 'fetching' | 'logging_out' | 'done' | 'error'
- 完了後 autoLogout=true → startLogout → logoutComplete → autoVisit=true なら自動で visitState 開始
- popup: fetch-bar (緑系ダーク), step-label, autoChain チェックボックス

## ログアウト対応（v1.1）
- content.js: OGメタタグ + JSON-LD からプロフィールデータを取得（ログアウト時も有効）
- background.js: 自動巡回キュー (visitState) で tabs API を使いプロフィールページを順次訪問
- popup: 自動巡回バー（▶開始/⏸一時停止/■停止、プログレスバー）+ インポートモーダル
- CAPTCHA 検出時は visitBlocked メッセージで自動停止

## ワークフロー
1. ログイン中: フォロワーページをスクロール → ユーザー名収集
2. ログアウト後: ▶ 自動巡回開始 → プロフィールを自動訪問しデータ取得
3. CSV出力

## インストール手順
Chrome → 拡張機能 → デベロッパーモード ON → 「パッケージ化されていない拡張機能を読み込む」→ フォルダ選択
