# WordPress 自作プラグイン一覧

officeueda 制作・管理の WordPress カスタムプラグインです。

| ステータス | 意味 |
|---|---|
| ✅ 安定 | 本番で使用中・動作確認済み |
| 🔵 制作完了 | コード完成・動作確認未 |
| 🚧 制作中 | 現在開発中 |
| 🔧 整備中 | 不具合あり・修正中 |
| ⚠️ 非推奨 | 旧版・新版あり |

---

## MU プラグイン（`mu-plugins/`）

WordPressの `wp-content/mu-plugins/` に配置するプラグイン。有効化不要で常時動作。

| ステータス | プラグイン名 | 概要 |
|---|---|---|
| ✅ | `ban-specific-plugins` | 特定プラグインのインストール／有効化／検索表示／ZIPアップロードを禁止 |
| ✅ | `officeueda-link-checker` | サイト内のリンク切れチェック |
| ✅ | `weekly_update_motifier` | 週1回、未更新のWPコア／プラグイン／テーマがあれば管理者宛にメール通知 |
| ✅ | `wp-approval-accounts` | 新規登録を承認制にする。承認までログイン不可。ユーザー一覧から承認/却下可能 |
| 🔵 | `ou-basic-guard` | wp-admin・wp-login.php に PHP ベースの Basic 認証。キーファイルで即時無効化可能 |

---

## 通常プラグイン

### フォーム・UX

| ステータス | プラグイン名 | 概要 |
|---|---|---|
| ✅ | `contact-form-scroll-enhancer` | ContactForm7用：確認ページで「戻る」ボタン押下時に `#contact-form` までスクロール |
| ✅ | `custom-domain-tag` | ContactForm7用：`[_site_domain]` をサイトのドメインに置き換えるタグ追加 |
| ✅ | `zipcode-address-autofill` | 郵便番号から住所を自動入力 |

### コンテンツ・投稿

| ステータス | プラグイン名 | 概要 |
|---|---|---|
| ✅ | `officeueda-news` | お知らせ（最新/一覧/単体）をサーバーサイドで描画するGutenbergブロック＋ショートコード |
| ✅ | `post-update-column` | 投稿一覧に「更新日（最終更新日時）」カラムを追加 |
| ✅ | `reviews-by-comments` | 固定ページに `[reviews_force per_page="10"]` でコメント一覧＋カスタム投稿フォームを表示 |
| ✅ | `ou-slides-rotator` | Divi用：`.slides` 配下の `.slide` を順番にフェード切替（中央重ね表示） |
| 🔧 | `ou-quiz-chat` | 診断チャットLP用 |
| 🔧 | `cook-manual-core` | 調理マニュアル管理（コア機能） |

### 構造化データ・SEO

| ステータス | プラグイン名 | 概要 |
|---|---|---|
| ⚠️ | `jsonld-structured-data` | 投稿・固定ページに schema.org BlogPosting 構造化データを出力（旧版・シンプル） |
| 🔧 | `ou-structured-data-v0.5.0` | Unified JSON-LD（@graph）出力の新版。Organization/WebSite/Breadcrumb/BlogPosting/Service/FAQ/AboutPage に対応。Settings Assistant付き |

> ⚠️ `jsonld-structured-data` は旧版。新規サイトは `ou-structured-data-v0.5.0` を使用すること。

### カスタム投稿タイプ・ディレクトリ

| ステータス | プラグイン名 | 概要 |
|---|---|---|
| ✅ | `ou-gourmet-directory` | 店舗・商品紹介ディレクトリ（ジャンル/都道府県/市区町村で絞込、Instagram埋め込み対応） |
| ✅ | `ou-property-manager` | 不動産物件管理 |

### 管理・セキュリティ

| ステータス | プラグイン名 | 概要 |
|---|---|---|
| ✅ | `change-admin-email` | 管理者メールアドレスを承認メールなしで変更 |
| ✅ | `user-nicename-editor` | 投稿で表示されるユーザー名を変更（セキュリティ対策） |
| ✅ | `ou-parameter-protect` | URLパラメータを簡易パスワードとして扱い、Gutenbergブロックからページ全体を保護 |
| ✅ | `ou-mu-installer` | 管理画面から MU プラグインを ZIP でアップロード・インストール／削除 |
| ✅ | `custom_php_shortcodes` | 実行するPHPコードをショートコードで埋め込む |

### パフォーマンス・画像

| ステータス | プラグイン名 | 概要 |
|---|---|---|
| ✅ | `disable-image-resizing` | WordPressの画像自動縮小機能をオフ |
| ✅ | `lazyload-prioritize` | 画像遅延読み込みを最適化し、通常画像の読み込み後にLazy画像を優先的にロード |
| ✅ | `htaccess-cache-control` | `.htaccess` を編集してキャッシュを無効化（テスト時のみ使用） |

### その他

| ステータス | プラグイン名 | 概要 |
|---|---|---|
| ✅ | `device-body-class` | UAをJSで判別して対応するクラス名を `<body>` に追加 |
| ✅ | `ou-root-bootstrap-pro` | サブディレクトリ設置のWordPressをルート直下で公開するために `index.php` / `.htaccess` を自動生成 |
| ✅ | `wpvividbackup-cleaner` | WPvividバックアップの古いファイルを自動削除 |
