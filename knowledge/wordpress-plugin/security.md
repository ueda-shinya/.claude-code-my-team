# WordPress プラグイン セキュリティ知見

## 管理画面の基本3点

```php
// 権限チェック
if ( ! current_user_can( 'manage_options' ) ) return;

// CSRF 対策
wp_nonce_field( 'my_action' );
check_admin_referer( 'my_action' );

// 出力エスケープ
echo esc_html( $value );
echo esc_attr( $value );
echo esc_url( $url );
```

---

## 認証・比較

### ユーザー名の定数時間比較（タイミング攻撃対策）

`===` は定数時間比較ではないため、サイドチャネル攻撃の対象になり得る。
ユーザー名の照合には `hash_equals()` を使う。

```php
// NG
if ( $input_user === $stored_user && password_verify( $input_pass, $stored_hash ) ) { ... }

// OK
$user_ok = hash_equals( $stored_user, $input_user );
$pass_ok = password_verify( $input_pass, $stored_hash );
if ( $user_ok && $pass_ok ) { ... }
```

### パスワードハッシュ

WP標準の `wp_hash_password()` は phpass ベースで強度が低い。
独自認証（Basic 認証など）では PHP 標準の bcrypt を使う。

```php
// 保存
update_option( 'my_pw_hash', password_hash( $plain, PASSWORD_BCRYPT ) );

// 検証
password_verify( $input, get_option( 'my_pw_hash' ) )
```

---

## サーバー変数の扱い

### SCRIPT_NAME vs PHP_SELF

`$_SERVER['PHP_SELF']` はユーザー入力（URLの一部）を含む可能性があり、
パスインジェクションのリスクがある。パス判定には `SCRIPT_NAME` のみ使う。

```php
// NG
$script = $_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '';

// OK
$script = $_SERVER['SCRIPT_NAME'] ?? '';
```

### HTTP_HOST のリスク

`$_SERVER['HTTP_HOST']` はクライアントが送信する `Host` ヘッダーから取得されるため、
攻撃者が細工した値を送れる。ローカル環境判定などの補助的な用途には使えるが、
セキュリティ判定の主軸には据えない。`WP_ENVIRONMENT_TYPE` を優先する。

---

## base64_decode

`base64_decode()` は不正な入力に対して `false` を返す場合がある。
strict モード（第2引数 `true`）を使い、失敗を明示的に処理する。

```php
// NG
$decoded = base64_decode( substr( $auth, 6 ) );
[ $user, $pass ] = explode( ':', $decoded, 2 ) + [ '', '' ];

// OK
$decoded = base64_decode( substr( $auth, 6 ), true );
if ( $decoded === false ) {
  return [ '', '' ];
}
[ $user, $pass ] = explode( ':', $decoded, 2 ) + [ '', '' ];
```

---

## PHP-FPM 環境での Authorization ヘッダー

PHP-FPM 環境では `$_SERVER['PHP_AUTH_USER']` が取得できない場合がある。
`HTTP_AUTHORIZATION` へのフォールバックと、.htaccess への追記で対応。

```php
function get_basic_credentials(): array {
  $user = $_SERVER['PHP_AUTH_USER'] ?? '';
  $pass = $_SERVER['PHP_AUTH_PW'] ?? '';

  if ( $user === '' && $pass === '' ) {
    $auth = $_SERVER['HTTP_AUTHORIZATION']
         ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
         ?? '';

    if ( str_starts_with( $auth, 'Basic ' ) ) {
      $decoded = base64_decode( substr( $auth, 6 ), true );
      if ( $decoded === false ) return [ '', '' ];
      [ $user, $pass ] = explode( ':', $decoded, 2 ) + [ '', '' ];
    }
  }

  return [ $user, $pass ];
}
```

.htaccess への追記：
```
SetEnvIf Authorization "(.+)" HTTP_AUTHORIZATION=$1
```

---

## Basic 認証の緊急無効化設計

### キーファイル方式（推奨）

プラグイン外の固定パスにファイルを置くだけで認証をスキップする。
ディレクトリリネーム方式と異なり、プラグインの有効状態を維持できる。

```php
define( 'MY_DISABLE_KEY_PATH', WP_CONTENT_DIR . '/my-plugin-disable.key' );

function my_is_disabled(): bool {
  return file_exists( MY_DISABLE_KEY_PATH );
}
```

- ファイルの中身は問わない（ダミーテキストでOK）
- ファイルを削除すれば認証が再開される
- FTPでファイルを1つ置くだけなので、緊急時の操作が最小限

### admin-ajax.php の除外

WordPress 標準の AJAX 処理（wp-admin/admin-ajax.php）は Basic 認証の対象から除外する。
認証をかけると WP 標準機能が詰まる。

```php
if ( str_ends_with( $script, '/wp-admin/admin-ajax.php' ) ) {
  return false; // 認証不要
}
```
