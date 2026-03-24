# トラブル: WordPress BASIC認証プラグイン ログイン不可

## メタ情報
- 開始日: 2026-03-24
- 解決日: 2026-03-24
- ステータス: 解決済み
- 関連システム: WordPress on エックスサーバー（Apache + CGI/mod_fcgid）

## 関連ドキュメント（開始時に検索）
- 検索対象: `resolved/`, `~/.claude/reports/`
- 該当: なし

## 症状
WordPress に BASIC 認証をかけるプラグインの動作確認中、ログインできない。
- BASIC 認証ダイアログは**表示される**
- ユーザー名・パスワードを入力すると**弾かれる**（認証失敗）

## 現在の絞り込み状態
- 根本原因特定済み: CGI/mod_fcgid 環境で Authorization ヘッダーが PHP に転送されていなかった

## 作業ログ
<!-- 新しいものが上 -->

### [2026-03-24 19:00] 解決確認
- 操作: .htaccess に RewriteRule を追加して Authorization ヘッダーを PHP に転送する設定を実施
- 結果: BASIC 認証ログイン成功。問題解決
- 分類: 設定変更
- タグ: WordPress, BASIC認証, エックスサーバー, .htaccess, 解決

### [2026-03-24 18:30] 切り分け情報による仮説絞り込み
- 操作: シンヤさんから追加情報を受領し、仮説を一括レビュー
- 結果: 11仮説中 5仮説を棄却。最有力は「CGI/mod_fcgid で Authorization ヘッダー未到達」
- 分類: 調査
- タグ: WordPress, BASIC認証, エックスサーバー, 切り分け

### [2026-03-24 18:00] 初期仮説の洗い出し
- 操作: 過去ログ検索 + 一般的な原因候補の整理
- 結果: 過去に類似事例なし。仮説リストを作成
- 分類: 調査
- タグ: WordPress, BASIC認証, プラグイン, 初期調査

## 試行済みアクション一覧
| # | 日時 | 分類 | タグ | 結果 |
|---|------|------|------|------|
| 1 | 2026-03-24 18:00 | 調査 | WordPress, BASIC認証, 初期調査 | 過去事例なし。仮説リスト作成 |
| 2 | 2026-03-24 18:30 | 調査 | WordPress, BASIC認証, エックスサーバー, 切り分け | 5仮説棄却。最有力候補特定 |
| 3 | 2026-03-24 19:00 | 設定変更 | WordPress, BASIC認証, .htaccess, 解決 | .htaccess 追記で解決 |

## 仮説リスト

### サーバー・環境系
- [x] 仮説: CGI/mod_fcgid 環境で Authorization ヘッダーが PHP に渡されていない → **確定**（解決策に記載）
- [x] 仮説: Nginx で Authorization ヘッダーが proxy_pass 先に転送されていない → 棄却（理由: サーバーはエックスサーバー = Apache）
- [x] 仮説: .htaccess の RewriteRule で Authorization ヘッダーが消されている → 棄却（理由: 消されていたのではなく、そもそも転送されていなかった）
- [x] 仮説: CDN・WAF・リバースプロキシが Authorization ヘッダーを除去している → 棄却（理由: .htaccess の修正で解決したため無関係）
- [x] 仮説: SSL/TLS 未対応で BASIC 認証のクレデンシャルが送信されない → 棄却（理由: ダイアログ表示済み）

### プラグイン・WordPress 設定系
- [x] 仮説: プラグインの設定で認証ユーザー/パスワードが正しく保存されていない → 棄却（理由: ヘッダー転送設定で解決）
- [x] 仮説: エックスサーバーのサーバーパネルから別途 BASIC 認証を設定済みで二重認証 → 棄却（理由: 同上）
- [x] 仮説: プラグインが REST API や wp-login.php と干渉している → 棄却（理由: 同上）
- [x] 仮説: 他のセキュリティ系プラグインとの競合 → 棄却（理由: 同上）
- [x] 仮説: WordPress のサイトURL/ホームURL設定の不一致 → 棄却（理由: ダイアログ正常表示）

### 認証情報・ブラウザ系
- [x] 仮説: ブラウザがキャッシュした古い認証情報を送っている → 棄却（理由: ヘッダー転送設定で解決）
- [x] 仮説: パスワードに特殊文字が含まれエンコード問題 → 棄却（理由: 同上）

## 解決策

### 根本原因
エックスサーバー（Apache + mod_fcgid）環境では、Apache が `Authorization` ヘッダーをセキュリティ上の理由で CGI/FastCGI プロセス（PHP）に転送しない。そのため、BASIC 認証プラグインが `$_SERVER['HTTP_AUTHORIZATION']` や `$_SERVER['PHP_AUTH_USER']` を取得できず、常に認証失敗となっていた。

### 対処
WordPress の `.htaccess` の `# BEGIN WordPress` より前に以下を追加:

```apache
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
</IfModule>
```

これにより、`Authorization` ヘッダーの値が環境変数 `HTTP_AUTHORIZATION` として PHP に渡されるようになり、プラグインが認証情報を正常に受け取れるようになった。

### 適用範囲
この問題はエックスサーバーに限らず、Apache + CGI/FastCGI 構成の共用ホスティング全般で発生する。同様の環境で BASIC 認証プラグインを使う場合は、同じ対処が必要。
