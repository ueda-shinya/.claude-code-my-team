<?php
/**
 * Plugin Name: OU Basic Guard
 * Description: wp-admin・wp-login.php に PHP レベルの Basic 認証をかけます。プラグインディレクトリを FTP でリネームするだけで認証を即時無効化できます。
 * Version: 2.0.0
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
      $decoded = base64_decode( substr( $auth, 6 ) );
      [ $user, $pass ] = explode( ':', $decoded, 2 ) + [ '', '' ];
    }
  }

  return [ $user, $pass ];
}

/**
 * 現在のページが保護対象かどうかを判定
 */
function ou_bg_is_protected_page( string $targets ): bool {
  $script = str_replace( '\\', '/', $_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '' );

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
 * 【緊急無効化の仕組み】
 * このプラグインは PHP レベルで認証を行うため、プラグインが読み込まれなければ
 * 認証チェック自体が実行されない。
 * → FTP でプラグインディレクトリをリネームするだけで認証を即時無効化できる。
 */
function ou_bg_enforce_basic_auth(): void {
  $settings = ou_bg_get_settings();

  if ( ! $settings['enabled'] ) {
    return;
  }

  if ( $settings['username'] === '' || $settings['pw_hash'] === '' ) {
    return;
  }

  if ( ! ou_bg_is_protected_page( $settings['targets'] ) ) {
    return;
  }

  [ $user, $pass ] = ou_bg_get_credentials();

  if ( $user === $settings['username'] && password_verify( $pass, $settings['pw_hash'] ) ) {
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
      $pattern = '/' . preg_quote( OU_BG_BLOCK_BEGIN, '/' ) . '.*?' . preg_quote( OU_BG_BLOCK_END, '/' ) . '\R?/s';
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

  $has_pw   = $settings['pw_hash'] !== '';
  $is_https = is_ssl();

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
            <?php if ( $settings['enabled'] && $has_pw && $settings['username'] !== '' ) : ?>
              ✅ <strong>有効</strong>（<?php echo esc_html( $target_label ); ?> を保護中）
            <?php elseif ( $settings['enabled'] ) : ?>
              ⚠️ <strong>設定が不完全</strong>（ユーザー名またはパスワードが未設定のため認証は動作しません）
            <?php else : ?>
              ⬜ <strong>無効</strong>
            <?php endif; ?>
          </td>
        </tr>
        <tr>
          <th>緊急無効化の方法</th>
          <td>
            <span style="font-family:monospace;background:#f0f0f1;padding:2px 6px;">
              <?php echo esc_html( OU_BG_PLUGIN_DIRNAME ); ?>
            </span>
            ディレクトリを FTP でリネームする<br>
            <span class="description">例：<code>_disabled-<?php echo esc_html( OU_BG_PLUGIN_DIRNAME ); ?></code></span>
          </td>
        </tr>
      </tbody>
    </table>

    <p class="description" style="margin-bottom:1.5em;">
      このプラグインは <strong>PHP レベル</strong>で認証を処理します。
      .htaccess に依存しないため、プラグインが読み込まれなければ認証チェックは実行されません。
      ID・パスワードを忘れた場合は、FTP でディレクトリをリネームするだけで即時ロック解除できます。
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
