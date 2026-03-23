<?php
/**
 * Plugin Name: OU Basic Guard
 * Description: wp-admin・wp-login.php に PHP レベルの Basic 認証をかけます。wp-content/ にキーファイルを置くだけで認証を即時無効化できます。
 * Version: 3.0.0
 * Author: Shinya Ueda
 * Requires at least: 5.9
 * Requires PHP: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

// ========== 定数 ==========
define( 'OU_BG_PLUGIN_FILE', __FILE__ );
define( 'OU_BG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'OU_BG_PLUGIN_DIRNAME', basename( dirname( __FILE__ ) ) );

// キーファイルのパス（存在するだけで認証をスキップ）
define( 'OU_BG_DISABLE_KEY_PATH', WP_CONTENT_DIR . '/ou-basic-guard-disable.key' );

// オプションキー（旧 mu-plugin と同じキーを継続使用）
const OU_BG_OPT_ENABLED  = 'ou_basic_guard_enabled';
const OU_BG_OPT_USERNAME = 'ou_basic_guard_username';
const OU_BG_OPT_PW_HASH  = 'ou_basic_guard_password_hash';
const OU_BG_OPT_TARGETS  = 'ou_basic_guard_targets';

// 旧 mu-plugin が書いた .htaccess ブロックの識別子
const OU_BG_BLOCK_BEGIN = '# BEGIN OU_BASIC_GUARD';
const OU_BG_BLOCK_END   = '# END OU_BASIC_GUARD';

// ========== 設定の取得 ==========
function ou_bg_get_settings(): array {
  return [
    'enabled'  => (bool) get_option( OU_BG_OPT_ENABLED, false ),
    'username' => (string) get_option( OU_BG_OPT_USERNAME, '' ),
    'pw_hash'  => (string) get_option( OU_BG_OPT_PW_HASH, '' ),
    'targets'  => (string) get_option( OU_BG_OPT_TARGETS, 'both' ),
  ];
}

// ========== キーファイルによる緊急無効化 ==========

/**
 * キーファイルが存在するかどうかを確認
 * ファイルの中身は問わない（ダミーテキストでOK）
 */
function ou_bg_is_disabled_by_key(): bool {
  return file_exists( OU_BG_DISABLE_KEY_PATH );
}

// ========== ローカル環境の自動スキップ ==========

/**
 * ローカル環境かどうかを判定
 * 以下のいずれかに該当する場合はローカル環境とみなす（チェック順に評価）：
 * 1. WP_ENVIRONMENT_TYPE 定数が 'local' または 'development'
 * 2. WP_LOCAL_DEV 定数が true
 * 3. HTTP_HOST が localhost / 127.0.0.1 / .local / .test / .localhost で終わる
 */
function ou_bg_is_local_env(): bool {
  // 1. WP_ENVIRONMENT_TYPE による判定
  if ( defined( 'WP_ENVIRONMENT_TYPE' ) ) {
    $env_type = WP_ENVIRONMENT_TYPE;
    if ( $env_type === 'local' || $env_type === 'development' ) {
      return true;
    }
  }

  // 2. WP_LOCAL_DEV による判定
  if ( defined( 'WP_LOCAL_DEV' ) && WP_LOCAL_DEV === true ) {
    return true;
  }

  // 3. HTTP_HOST による判定
  $host = strtolower( $_SERVER['HTTP_HOST'] ?? '' );
  // ポート番号を除去（例：localhost:8080 → localhost）
  $host = preg_replace( '/:\d+$/', '', $host );

  $local_patterns = [ 'localhost', '127.0.0.1', '.local', '.test', '.localhost' ];
  foreach ( $local_patterns as $pattern ) {
    if ( str_ends_with( $host, $pattern ) ) {
      return true;
    }
  }

  return false;
}

// ========== PHP Basic 認証 ==========

/**
 * リクエストヘッダーから資格情報を取得
 */
function ou_bg_get_credentials(): array {
  $user = $_SERVER['PHP_AUTH_USER'] ?? '';
  $pass = $_SERVER['PHP_AUTH_PW'] ?? '';

  // PHP-FPM 環境では HTTP_AUTHORIZATION から取得が必要な場合がある
  if ( $user === '' && $pass === '' ) {
    $auth = $_SERVER['HTTP_AUTHORIZATION']
         ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
         ?? '';

    if ( str_starts_with( $auth, 'Basic ' ) ) {
      $decoded = base64_decode( substr( $auth, 6 ), true );
      if ( $decoded === false ) {
        return [ '', '' ];
      }
      [ $user, $pass ] = explode( ':', $decoded, 2 ) + [ '', '' ];
    }
  }

  return [ $user, $pass ];
}

/**
 * 現在のページが保護対象かどうかを判定
 * admin-ajax.php はWordPress標準のAJAX処理が詰まるため除外する
 */
function ou_bg_is_protected_page( string $targets ): bool {
  // PHP_SELF はパスインジェクションのリスクがあるため SCRIPT_NAME のみ使用
  $script = str_replace( '\\', '/', $_SERVER['SCRIPT_NAME'] ?? '' );

  // admin-ajax.php は認証対象から除外
  if ( str_ends_with( $script, '/wp-admin/admin-ajax.php' ) ) {
    return false;
  }

  $is_login = str_ends_with( $script, '/wp-login.php' );
  $is_admin = str_contains( $script, '/wp-admin/' )
           || str_ends_with( $script, '/wp-admin' );

  return match ( $targets ) {
    'login' => $is_login,
    'admin' => $is_admin,
    'both'  => $is_login || $is_admin,
    default => false,
  };
}

/**
 * Basic 認証チェック本体
 * init フック（優先度 0）で実行。認証失敗なら 401 で終了。
 *
 * 【スキップ条件（優先度順）】
 * 1. キーファイルが存在する（wp-content/ou-basic-guard-disable.key）
 * 2. ローカル環境である（WP_ENVIRONMENT_TYPE / WP_LOCAL_DEV / HTTP_HOST による判定）
 * 3. admin-ajax.php へのリクエスト
 * 4. 認証が無効または設定不完全
 * 5. 保護対象ページ以外
 */
function ou_bg_enforce_basic_auth(): void {
  // 1. キーファイルによる緊急無効化
  if ( ou_bg_is_disabled_by_key() ) {
    return;
  }

  // 2. ローカル環境の自動スキップ
  if ( ou_bg_is_local_env() ) {
    return;
  }

  $settings = ou_bg_get_settings();

  if ( ! $settings['enabled'] ) {
    return;
  }

  if ( $settings['username'] === '' || $settings['pw_hash'] === '' ) {
    return;
  }

  // 3. admin-ajax.php を含む保護対象外ページの除外
  if ( ! ou_bg_is_protected_page( $settings['targets'] ) ) {
    return;
  }

  [ $user, $pass ] = ou_bg_get_credentials();

  // hash_equals でタイミング攻撃対策（ユーザー名・パスワードともに定数時間比較）
  $user_ok = hash_equals( $settings['username'], $user );
  $pass_ok = password_verify( $pass, $settings['pw_hash'] );
  if ( $user_ok && $pass_ok ) {
    return; // 認証成功
  }

  // 認証失敗 → 401
  header( 'WWW-Authenticate: Basic realm="Protected Area"' );
  status_header( 401 );
  echo '認証が必要です。ユーザー名とパスワードを入力してください。';
  exit;
}
add_action( 'init', 'ou_bg_enforce_basic_auth', 0 );

// ========== 有効化・無効化フック ==========
register_activation_hook( OU_BG_PLUGIN_FILE, 'ou_bg_on_activate' );
function ou_bg_on_activate(): void {
  // 旧 mu-plugin の .htaccess ブロックが残っていれば検知フラグを立てる
  if ( ou_bg_has_legacy_htaccess_block() ) {
    update_option( 'ou_basic_guard_legacy_detected', 1 );
  }
}

// 無効化時は何もしない（設定は保持）
// 再有効化したときに設定が残っている方が親切なため

// ========== アンインストール ==========
// uninstall.php で処理（register_uninstall_hook は匿名関数不可のため）

// ========== 旧 .htaccess クリーンアップ ==========
function ou_bg_has_legacy_htaccess_block(): bool {
  $root_ht  = ABSPATH . '.htaccess';
  $admin_ht = ABSPATH . 'wp-admin/.htaccess';

  if ( file_exists( $root_ht ) ) {
    $content = @file_get_contents( $root_ht );
    if ( $content && str_contains( $content, OU_BG_BLOCK_BEGIN ) ) {
      return true;
    }
  }

  if ( file_exists( $admin_ht ) ) {
    $content = @file_get_contents( $admin_ht );
    if ( $content && str_contains( $content, 'Basic Guard' ) ) {
      return true;
    }
  }

  return false;
}

function ou_bg_remove_legacy_htaccess_blocks(): array {
  $results = [];

  // ルート .htaccess から Basic Guard ブロックを削除
  $root_ht = ABSPATH . '.htaccess';
  if ( file_exists( $root_ht ) ) {
    $content = @file_get_contents( $root_ht );
    if ( $content !== false ) {
      $pattern = '/' . preg_quote( OU_BG_BLOCK_BEGIN, '/' ) . '.*?' . preg_quote( OU_BG_BLOCK_END, '/' ) . '\R*/s';
      $new     = preg_replace( $pattern, '', $content );
      if ( $new !== $content ) {
        $results['root'] = ( file_put_contents( $root_ht, $new ) !== false ) ? 'success' : 'error';
      }
    }
  }

  // wp-admin/.htaccess を削除（Basic Guard が書いたもの）
  $admin_ht = ABSPATH . 'wp-admin/.htaccess';
  if ( file_exists( $admin_ht ) ) {
    $content = @file_get_contents( $admin_ht );
    if ( $content && str_contains( $content, 'Basic Guard' ) ) {
      $results['admin'] = @unlink( $admin_ht ) ? 'success' : 'error';
    }
  }

  if ( ! empty( $results ) ) {
    delete_option( 'ou_basic_guard_legacy_detected' );
  }

  return $results;
}

// ========== 管理画面 ==========
add_action( 'admin_menu', function () {
  add_options_page(
    'Basic Guard 設定',
    'Basic Guard',
    'manage_options',
    'ou-basic-guard',
    'ou_bg_render_settings_page'
  );
} );

add_action( 'admin_notices', function () {
  if ( ! current_user_can( 'manage_options' ) ) {
    return;
  }
  if ( get_option( 'ou_basic_guard_legacy_detected' ) ) {
    $url = admin_url( 'options-general.php?page=ou-basic-guard' );
    echo '<div class="notice notice-warning"><p>'
      . '<strong>OU Basic Guard：</strong>旧バージョン（MU プラグイン）の .htaccess ブロックが残っています。'
      . '<a href="' . esc_url( $url ) . '">設定 → Basic Guard</a> から削除できます。'
      . '</p></div>';
  }
} );

function ou_bg_render_settings_page(): void {
  if ( ! current_user_can( 'manage_options' ) ) {
    return;
  }

  $settings      = ou_bg_get_settings();
  $notice        = '';
  $legacy_exists = ou_bg_has_legacy_htaccess_block();

  // ---- POST 処理 ----
  if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ou_bg_action'] ) ) {
    check_admin_referer( 'ou_bg_settings' );
    $action = sanitize_text_field( wp_unslash( $_POST['ou_bg_action'] ) );

    if ( $action === 'save_settings' ) {
      $enabled  = ! empty( $_POST['enabled'] );
      $username = sanitize_text_field( wp_unslash( $_POST['username'] ?? '' ) );
      $targets  = sanitize_text_field( wp_unslash( $_POST['targets'] ?? 'both' ) );

      if ( ! in_array( $targets, [ 'login', 'admin', 'both' ], true ) ) {
        $targets = 'both';
      }

      $has_pw = get_option( OU_BG_OPT_PW_HASH ) !== '';

      if ( ! empty( $_POST['password'] ) ) {
        $plain  = (string) wp_unslash( $_POST['password'] );
        $has_pw = true;
        // WP の wp_hash_password() は phpass ベースで強度が低いため、PHP 標準の bcrypt を意図的に使用
        update_option( OU_BG_OPT_PW_HASH, password_hash( $plain, PASSWORD_BCRYPT ) );
      }

      if ( $enabled && ( $username === '' || ! $has_pw ) ) {
        $notice  .= '<div class="notice notice-error inline"><p>有効にするにはユーザー名とパスワードを設定してください。</p></div>';
        $enabled  = false;
      }

      update_option( OU_BG_OPT_ENABLED,  $enabled ? 1 : 0 );
      update_option( OU_BG_OPT_USERNAME, $username );
      update_option( OU_BG_OPT_TARGETS,  $targets );

      $notice   .= '<div class="notice notice-success inline"><p>設定を保存しました。</p></div>';
      $settings  = ou_bg_get_settings();

    } elseif ( $action === 'cleanup_htaccess' ) {
      $results = ou_bg_remove_legacy_htaccess_blocks();
      $legacy_exists = false;

      if ( empty( $results ) ) {
        $notice .= '<div class="notice notice-info inline"><p>削除対象の .htaccess ブロックは見つかりませんでした。</p></div>';
      } else {
        $lines = [];
        if ( ( $results['root'] ?? '' ) === 'success' ) {
          $lines[] = 'ルート .htaccess の Basic Guard ブロックを削除しました。';
        } elseif ( ( $results['root'] ?? '' ) === 'error' ) {
          $lines[] = 'ルート .htaccess の書き込みに失敗しました（パーミッション確認）。';
        }
        if ( ( $results['admin'] ?? '' ) === 'success' ) {
          $lines[] = 'wp-admin/.htaccess を削除しました。';
        } elseif ( ( $results['admin'] ?? '' ) === 'error' ) {
          $lines[] = 'wp-admin/.htaccess の削除に失敗しました（パーミッション確認）。';
        }
        $notice .= '<div class="notice notice-success inline"><p>' . implode( '<br>', array_map( 'esc_html', $lines ) ) . '</p></div>';
      }
    }
  }

  $has_pw        = $settings['pw_hash'] !== '';
  $is_https      = is_ssl();
  $key_exists    = ou_bg_is_disabled_by_key();
  $is_local      = ou_bg_is_local_env();
  $key_file_path = OU_BG_DISABLE_KEY_PATH;

  $target_label = match ( $settings['targets'] ) {
    'login' => 'ログインページ（wp-login.php）のみ',
    'admin' => '管理画面（/wp-admin/）のみ',
    default => 'ログインページ + 管理画面（推奨）',
  };

  ?>
  <div class="wrap">
    <h1>Basic Guard 設定</h1>
    <?php if ( $notice ) echo $notice; ?>

    <?php if ( ! $is_https ) : ?>
      <div class="notice notice-warning inline">
        <p><strong>注意：</strong>このサイトは HTTP 環境です。Basic 認証は HTTPS 環境でのご利用を強く推奨します。</p>
      </div>
    <?php endif; ?>

    <h2>現在の状態</h2>
    <table class="widefat striped" style="max-width:680px; margin-bottom:1.5em;">
      <tbody>
        <tr>
          <th style="width:200px;">認証状態</th>
          <td>
            <?php if ( $key_exists ) : ?>
              ⏸️ <strong>スキップ中（キーファイルあり）</strong>
            <?php elseif ( $is_local ) : ?>
              ⏸️ <strong>スキップ中（ローカル環境）</strong>
            <?php elseif ( $settings['enabled'] && $has_pw && $settings['username'] !== '' ) : ?>
              ✅ <strong>有効</strong>（<?php echo esc_html( $target_label ); ?> を保護中）
            <?php elseif ( $settings['enabled'] ) : ?>
              ⚠️ <strong>設定が不完全</strong>（ユーザー名またはパスワードが未設定のため認証は動作しません）
            <?php else : ?>
              ⬜ <strong>無効</strong>
            <?php endif; ?>
          </td>
        </tr>
        <tr>
          <th>キーファイルの状態</th>
          <td>
            <?php if ( $key_exists ) : ?>
              ⏸️ <strong>存在する → 認証スキップ中</strong>
            <?php else : ?>
              ✅ <strong>存在しない → 通常動作</strong>
            <?php endif; ?>
            <p class="description" style="margin-top:4px;">
              キーファイルのパス：<br>
              <code><?php echo esc_html( $key_file_path ); ?></code>
            </p>
          </td>
        </tr>
        <tr>
          <th>ローカル環境判定</th>
          <td>
            <?php if ( $is_local ) : ?>
              ✅ <strong>ローカル環境と判定 → 認証スキップ中</strong>
            <?php else : ?>
              ⬜ 本番環境（スキップなし）
            <?php endif; ?>
          </td>
        </tr>
        <tr>
          <th>緊急無効化の方法</th>
          <td>
            以下のパスに <strong>任意の内容のファイル</strong>を置くだけで認証をスキップできます。<br>
            プラグインは有効のまま認証だけを無効化できます。<br>
            <code><?php echo esc_html( $key_file_path ); ?></code><br>
            <span class="description">例：<code>touch <?php echo esc_html( $key_file_path ); ?></code></span>
          </td>
        </tr>
      </tbody>
    </table>

    <p class="description" style="margin-bottom:1.5em;">
      このプラグインは <strong>PHP レベル</strong>で認証を処理します。
      .htaccess に依存しないため、キーファイルを置くだけで認証を即時スキップできます。
      また <code>wp-admin/admin-ajax.php</code> は WordPress 標準の AJAX 処理のため認証対象から自動除外されます。
    </p>

    <hr>

    <h2>設定</h2>
    <form method="post">
      <?php wp_nonce_field( 'ou_bg_settings' ); ?>
      <input type="hidden" name="ou_bg_action" value="save_settings">

      <table class="form-table" role="presentation">
        <tr>
          <th scope="row">Basic 認証を有効化</th>
          <td>
            <label>
              <input type="checkbox" name="enabled" <?php checked( $settings['enabled'] ); ?>>
              有効にする
            </label>
          </td>
        </tr>
        <tr>
          <th scope="row">保護対象</th>
          <td>
            <label><input type="radio" name="targets" value="login" <?php checked( $settings['targets'], 'login' ); ?>> wp-login.php のみ</label><br>
            <label><input type="radio" name="targets" value="admin" <?php checked( $settings['targets'], 'admin' ); ?>> /wp-admin/ のみ</label><br>
            <label><input type="radio" name="targets" value="both"  <?php checked( $settings['targets'], 'both' ); ?>> 両方（推奨）</label>
          </td>
        </tr>
        <tr>
          <th scope="row">ユーザー名</th>
          <td>
            <input type="text" name="username" value="<?php echo esc_attr( $settings['username'] ); ?>" class="regular-text" autocomplete="off">
          </td>
        </tr>
        <tr>
          <th scope="row">パスワード</th>
          <td>
            <input type="password" name="password" value="" class="regular-text" autocomplete="new-password">
            <p class="description">
              <?php if ( $has_pw ) : ?>
                ✅ 設定済み。変更する場合のみ入力してください（平文は保存されません）。
              <?php else : ?>
                ⚠️ 未設定。認証を有効にするには設定が必要です。
              <?php endif; ?>
            </p>
          </td>
        </tr>
      </table>

      <?php submit_button( '設定を保存', 'primary' ); ?>
    </form>

    <?php if ( $legacy_exists ) : ?>
      <hr>
      <h2>⚠️ 旧バージョンの .htaccess クリーンアップ</h2>
      <p>
        旧バージョン（MU プラグイン）が書き込んだ <code>.htaccess</code> の Basic Guard ブロックが残っています。<br>
        このプラグインは PHP ベースの認証を使用するため、<strong>.htaccess のブロックは不要</strong>です。<br>
        このまま残しておくと Apache レベルで別途認証が要求される場合があります。削除を推奨します。
      </p>
      <form method="post">
        <?php wp_nonce_field( 'ou_bg_settings' ); ?>
        <input type="hidden" name="ou_bg_action" value="cleanup_htaccess">
        <?php submit_button( '旧 .htaccess ブロックを削除する', 'secondary' ); ?>
      </form>
    <?php endif; ?>

    <hr>
    <h2>サーバー互換性について</h2>
    <p>
      PHP-FPM 環境では <code>Authorization</code> ヘッダーが PHP に届かない場合があります。<br>
      その場合は、ルートの <code>.htaccess</code> に以下を追加してください：
    </p>
    <textarea readonly rows="3" style="width:100%;max-width:600px;font-family:monospace;background:#f0f0f1;">SetEnvIf Authorization "(.+)" HTTP_AUTHORIZATION=$1</textarea>

  </div>
  <?php
}
