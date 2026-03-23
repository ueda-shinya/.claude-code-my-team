<?php
/**
 * Plugin Name: OU Basic Guard (MU)
 * Description: wp-admin / wp-login.php 用の Basic 認証設定を WordPress から管理し、.htaccess との同期・自己修復を行う MU プラグイン。
 * Author: Shinya
 * Version: 1.2.3
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 定数・共通設定
 */
const OU_BG_OPTION_ENABLED           = 'ou_basic_guard_enabled';
const OU_BG_OPTION_USERNAME          = 'ou_basic_guard_username';
const OU_BG_OPTION_PW_HASH           = 'ou_basic_guard_password_hash';
const OU_BG_OPTION_TARGETS           = 'ou_basic_guard_targets';
const OU_BG_OPTION_AUTHUSERFILE      = 'ou_basic_guard_authuserfile_path';
const OU_BG_OPTION_STATUS            = 'ou_basic_guard_status';
const OU_BG_OPTION_STATUS_MESSAGE    = 'ou_basic_guard_status_message';
const OU_BG_OPTION_LAST_APPLIED_BY   = 'ou_basic_guard_last_applied_by';
const OU_BG_OPTION_LAST_APPLIED_AT   = 'ou_basic_guard_last_applied_at';
const OU_BG_OPTION_AUTO_HEALED       = 'ou_basic_guard_auto_healed_once';
/**
 * 自己修復後、「管理画面で強調して注意喚起を出すためのフラグ」として利用。
 * （.htaccess に正常反映されるまで維持）
 */
const OU_BG_OPTION_AUTO_HEAL_POPUP   = 'ou_basic_guard_auto_heal_popup';

const OU_BG_STATUS_OK        = 'ok';
const OU_BG_STATUS_NEEDS     = 'needs_apply';
const OU_BG_STATUS_ERROR     = 'error';

const OU_BG_BLOCK_BEGIN      = '# BEGIN OU_BASIC_GUARD';
const OU_BG_BLOCK_END        = '# END OU_BASIC_GUARD';

/**
 * 絶対パス判定（Linux / Windows）
 */
function ou_bg_is_absolute_path( $path ) {
    $path = (string) $path;
    if ( $path === '' ) {
        return false;
    }

    // Unix 系
    if ( $path[0] === '/' ) {
        return true;
    }

    // Windows 系: C:\ など
    if ( preg_match( '/^[A-Za-z]:[\\\\\\/]/', $path ) ) {
        return true;
    }

    return false;
}

/**
 * 設定の取得（存在しない場合はデフォルト値）
 * ※ AuthUserFile はここでは勝手に補完しない
 */
function ou_bg_get_settings() {
    $settings = [
        'enabled'      => (bool) get_option( OU_BG_OPTION_ENABLED, false ),
        'username'     => (string) get_option( OU_BG_OPTION_USERNAME, '' ),
        'pw_hash'      => (string) get_option( OU_BG_OPTION_PW_HASH, '' ),
        'targets'      => (string) get_option( OU_BG_OPTION_TARGETS, 'both' ), // login / admin / both
        'authuserfile' => (string) get_option( OU_BG_OPTION_AUTHUSERFILE, '' ),
    ];

    return $settings;
}

/**
 * 設定の保存
 */
function ou_bg_save_settings( array $settings ) {
    update_option( OU_BG_OPTION_ENABLED,      $settings['enabled'] ? 1 : 0 );
    update_option( OU_BG_OPTION_USERNAME,     $settings['username'] );
    update_option( OU_BG_OPTION_PW_HASH,      $settings['pw_hash'] );
    update_option( OU_BG_OPTION_TARGETS,      $settings['targets'] );
    update_option( OU_BG_OPTION_AUTHUSERFILE, $settings['authuserfile'] );
}

/**
 * ルート .htaccess パス
 */
function ou_bg_get_htaccess_path() {
    return rtrim( ABSPATH, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . '.htaccess';
}

/**
 * wp-admin/.htaccess パス
 */
function ou_bg_get_admin_htaccess_path() {
    return rtrim( ABSPATH, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . 'wp-admin' . DIRECTORY_SEPARATOR . '.htaccess';
}

/**
 * サーバーが Apache / LiteSpeed 系かの簡易チェック
 */
function ou_bg_is_apache_like() {
    if ( empty( $_SERVER['SERVER_SOFTWARE'] ) ) {
        return false;
    }
    $server = strtolower( $_SERVER['SERVER_SOFTWARE'] );
    return ( strpos( $server, 'apache' ) !== false || strpos( $server, 'litespeed' ) !== false );
}

/**
 * HTTPS 判定
 */
function ou_bg_is_https() {
    if ( stripos( home_url(), 'https://' ) === 0 ) {
        return true;
    }
    if ( function_exists( 'is_ssl' ) && is_ssl() ) {
        return true;
    }
    if (
        ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && strtolower( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) === 'https' ) ||
        ( isset( $_SERVER['HTTP_X_FORWARDED_SSL'] ) && strtolower( $_SERVER['HTTP_X_FORWARDED_SSL'] ) === 'on' ) ||
        ( isset( $_SERVER['HTTP_FRONT_END_HTTPS'] ) && strtolower( $_SERVER['HTTP_FRONT_END_HTTPS'] ) === 'on' )
    ) {
        return true;
    }

    return false;
}

/**
 * .htpasswd 用のパスワードハッシュ生成（bcrypt）
 */
function ou_bg_generate_htpasswd_hash( $plain_password ) {
    return password_hash( $plain_password, PASSWORD_BCRYPT );
}

/**
 * .htpasswd の書き込み
 */
function ou_bg_write_htpasswd( $username, $pw_hash, $authuserfile, &$error_message ) {
    $error_message = '';

    if ( $username === '' ) {
        $error_message = 'ユーザー名が未設定です。';
        return false;
    }
    if ( $pw_hash === '' ) {
        $error_message = 'パスワードハッシュが未設定です。';
        return false;
    }

    if ( ! ou_bg_is_absolute_path( $authuserfile ) ) {
        $error_message = 'AuthUserFile のパスは絶対パス（/ または C:\ など）で指定してください。';
        return false;
    }

    $dir = dirname( $authuserfile );
    if ( ! is_dir( $dir ) ) {
        if ( ! @mkdir( $dir, 0750, true ) && ! is_dir( $dir ) ) {
            $error_message = 'AuthUserFile のディレクトリを作成できません: ' . $dir;
            return false;
        }
    }

    $content = $username . ':' . $pw_hash . "\n";

    if ( file_put_contents( $authuserfile, $content ) === false ) {
        $error_message = 'AuthUserFile（.htpasswd）を書き込めませんでした。パーミッションを確認してください。';
        return false;
    }

    // Xserver 対策：Apache から確実に読めるよう 0644 に統一
    @chmod( $authuserfile, 0644 );

    return true;
}

/**
 * Basic Guard 用の ルート .htaccess ブロック（wp-login.php）
 */
function ou_bg_build_root_htaccess_block( array $settings ) {
    $authfile = $settings['authuserfile'];
    $block    = [];

    if ( $settings['targets'] === 'login' || $settings['targets'] === 'both' ) {
        $block[] = OU_BG_BLOCK_BEGIN;
        $block[] = '<Files "wp-login.php">';
        $block[] = 'AuthType Basic';
        $block[] = 'AuthName "Protected Login"';
        $block[] = 'AuthUserFile ' . $authfile;
        $block[] = 'Require valid-user';
        $block[] = '</Files>';
        $block[] = OU_BG_BLOCK_END;
    }

    return $block ? implode( "\n", $block ) . "\n" : '';
}

/**
 * Basic Guard 用の wp-admin/.htaccess コンテンツ
 */
function ou_bg_build_admin_htaccess_content( array $settings ) {
    $authfile = $settings['authuserfile'];
    $content  = [];

    if ( $settings['targets'] === 'admin' || $settings['targets'] === 'both' ) {
        $content[] = '# .htaccess for wp-admin Basic Guard by OU Basic Guard (MU)';
        $content[] = 'AuthType Basic';
        $content[] = 'AuthName "Protected Admin Area"';
        $content[] = 'AuthUserFile ' . $authfile;
        $content[] = 'Require valid-user';
    }

    return implode( "\n", $content );
}

/**
 * .htaccess から Basic Guard ブロックを抜き出す
 */
function ou_bg_extract_htaccess_block( $htaccess_content ) {
    $pattern = '/' . preg_quote( OU_BG_BLOCK_BEGIN, '/' ) . '(.*?)' . preg_quote( OU_BG_BLOCK_END, '/' ) . '/s';

    if ( preg_match( $pattern, $htaccess_content, $m ) ) {
        return OU_BG_BLOCK_BEGIN . $m[1] . OU_BG_BLOCK_END . "\n";
    }

    return '';
}

/**
 * .htaccess から Basic Guard ブロックを削除した内容を返す
 */
function ou_bg_strip_htaccess_block( $htaccess_content ) {
    $pattern = '/' . preg_quote( OU_BG_BLOCK_BEGIN, '/' ) . '.*?' . preg_quote( OU_BG_BLOCK_END, '/' ) . '\R?/s';

    return preg_replace( $pattern, '', $htaccess_content );
}

/**
 * 設定と .htaccess の状態からステータスを判定し、option に保存
 */
function ou_bg_evaluate_status() {
    $settings = ou_bg_get_settings();

    if ( ! ou_bg_is_apache_like() ) {
        update_option( OU_BG_OPTION_STATUS, OU_BG_STATUS_ERROR );
        update_option( OU_BG_OPTION_STATUS_MESSAGE, 'サーバーが Apache / LiteSpeed 系ではない可能性があります。.htaccess による制御は利用できない場合があります。' );
        return;
    }

    $ht_path         = ou_bg_get_htaccess_path();
    $admin_ht_path   = ou_bg_get_admin_htaccess_path();
    $ht_exists       = file_exists( $ht_path );
    $admin_ht_exists = file_exists( $admin_ht_path );

    if ( ! $ht_exists && ( $settings['targets'] === 'login' || $settings['targets'] === 'both' ) ) {
        if ( $settings['enabled'] ) {
            update_option( OU_BG_OPTION_STATUS, OU_BG_STATUS_NEEDS );
            update_option( OU_BG_OPTION_STATUS_MESSAGE, '.htaccess が存在しません。Basic Guard の設定を反映するには .htaccess を作成する必要があります。' );
        } else {
            update_option( OU_BG_OPTION_STATUS, OU_BG_STATUS_OK );
            update_option( OU_BG_OPTION_STATUS_MESSAGE, 'Basic Guard は無効で、管理ブロックは存在しません。' );
        }
        return;
    }

    $ht_content = $ht_exists ? @file_get_contents( $ht_path ) : '';
    if ( $ht_exists && $ht_content === false ) {
        update_option( OU_BG_OPTION_STATUS, OU_BG_STATUS_ERROR );
        update_option( OU_BG_OPTION_STATUS_MESSAGE, 'ルート .htaccess を読み込めません。パーミッションを確認してください。' );
        return;
    }

    $admin_ht_content = $admin_ht_exists ? @file_get_contents( $admin_ht_path ) : '';
    if ( $admin_ht_exists && $admin_ht_content === false ) {
        update_option( OU_BG_OPTION_STATUS, OU_BG_STATUS_ERROR );
        update_option( OU_BG_OPTION_STATUS_MESSAGE, 'wp-admin/.htaccess を読み込めません。パーミッションを確認してください。' );
        return;
    }

    $current_block          = ou_bg_extract_htaccess_block( $ht_content );
    $has_block              = $current_block !== '';
    $expected_root_block    = ( $settings['enabled'] && $settings['authuserfile'] !== '' && ou_bg_is_absolute_path( $settings['authuserfile'] ) )
        ? ou_bg_build_root_htaccess_block( $settings )
        : '';
    $expected_admin_content = ( $settings['enabled'] && $settings['authuserfile'] !== '' && ou_bg_is_absolute_path( $settings['authuserfile'] ) )
        ? ou_bg_build_admin_htaccess_content( $settings )
        : '';

    $is_root_ht_synced = trim( $current_block ) === trim( $expected_root_block );
    $is_admin_ht_synced = ( $settings['targets'] === 'admin' || $settings['targets'] === 'both' )
        ? ( $admin_ht_exists && trim( $admin_ht_content ) === trim( $expected_admin_content ) )
        : ( ! $admin_ht_exists );

    if ( ! $settings['enabled'] ) {
        if ( $has_block || $admin_ht_exists ) {
            update_option( OU_BG_OPTION_STATUS, OU_BG_STATUS_NEEDS );
            update_option( OU_BG_OPTION_STATUS_MESSAGE, 'Basic Guard は無効ですが、管理ブロックまたは wp-admin/.htaccess が残っています。削除するには設定の反映が必要です。' );
        } else {
            update_option( OU_BG_OPTION_STATUS, OU_BG_STATUS_OK );
            update_option( OU_BG_OPTION_STATUS_MESSAGE, 'Basic Guard は無効で、管理ブロックは存在しません。' );
        }
        return;
    }

    if ( $settings['authuserfile'] === '' ) {
        update_option( OU_BG_OPTION_STATUS, OU_BG_STATUS_ERROR );
        update_option( OU_BG_OPTION_STATUS_MESSAGE, 'Basic Guard は有効ですが、AuthUserFile のパスが未設定です。設定画面で AuthUserFile を保存してください。' );
        return;
    }

    if ( ! ou_bg_is_absolute_path( $settings['authuserfile'] ) ) {
        update_option( OU_BG_OPTION_STATUS, OU_BG_STATUS_ERROR );
        update_option( OU_BG_OPTION_STATUS_MESSAGE, 'AuthUserFile のパスが絶対パスではありません。設定画面で修正してください。' );
        return;
    }

    if ( ! $is_root_ht_synced || ! $is_admin_ht_synced ) {
        $message = 'Basic Guard は有効ですが、設定と .htaccess の内容が一致していません。';
        if ( ! $is_root_ht_synced ) {
             $message .= '（ルート.htaccess が不一致）';
        }
        if ( ! $is_admin_ht_synced ) {
            $message .= '（wp-admin/.htaccess が不一致または存在しない）';
        }

        update_option( OU_BG_OPTION_STATUS, OU_BG_STATUS_NEEDS );
        update_option( OU_BG_OPTION_STATUS_MESSAGE, $message );
        return;
    }

    update_option( OU_BG_OPTION_STATUS, OU_BG_STATUS_OK );
    update_option( OU_BG_OPTION_STATUS_MESSAGE, 'Basic Guard の設定と .htaccess は同期されています。' );
}

/**
 * .htaccess に設定を反映（手動実行）
 */
function ou_bg_apply_to_htaccess( &$message ) {
    $message = '';

    if ( ! ou_bg_is_apache_like() ) {
        $message = 'サーバーが Apache / LiteSpeed 系ではない可能性があります。.htaccess による制御は利用できません。';
        return false;
    }

    $settings = ou_bg_get_settings();

    if ( $settings['enabled'] ) {
        if ( $settings['username'] === '' || $settings['pw_hash'] === '' ) {
            $message = 'Basic Guard を有効にするには、ユーザー名とパスワードを設定してください。';
            return false;
        }
        if ( $settings['authuserfile'] === '' ) {
            $message = 'AuthUserFile のパスが未設定です。設定画面で AuthUserFile を保存してください。';
            return false;
        }
        if ( ! ou_bg_is_absolute_path( $settings['authuserfile'] ) ) {
            $message = 'AuthUserFile のパスが絶対パスではありません。設定画面で修正してください。';
            return false;
        }
    }

    if ( $settings['enabled'] ) {
        $err = '';
        if ( ! ou_bg_write_htpasswd( $settings['username'], $settings['pw_hash'], $settings['authuserfile'], $err ) ) {
            $message = 'AuthUserFile の更新に失敗しました：' . $err;
            return false;
        }
    }

    $ht_path   = ou_bg_get_htaccess_path();
    $ht_exists = file_exists( $ht_path );

    $original = $ht_exists ? @file_get_contents( $ht_path ) : '';
    if ( $ht_exists && $original === false ) {
        $message = 'ルート .htaccess を読み込めませんでした。パーミッションを確認してください。';
        return false;
    }

    $backup_path = '';

    if ( $ht_exists ) {
        $backup_path = $ht_path . '.basic_guard.bak';
        if ( ! @copy( $ht_path, $backup_path ) ) {
            $message = 'ルート .htaccess のバックアップを作成できませんでした。パーミッションを確認してください。';
            return false;
        }
    }

    $content_without_block = ou_bg_strip_htaccess_block( $original );

    if ( $settings['enabled'] && ( $settings['targets'] === 'login' || $settings['targets'] === 'both' ) ) {
        $block = ou_bg_build_root_htaccess_block( $settings );
        if ( $content_without_block !== '' && substr( $content_without_block, -1 ) !== "\n" ) {
            $content_without_block .= "\n";
        }
        $new_content = $content_without_block . $block;
    } else {
        $new_content = $content_without_block;
    }

    if ( file_put_contents( $ht_path, $new_content ) === false ) {
        if ( $backup_path && file_exists( $backup_path ) ) {
            @copy( $backup_path, $ht_path );
        }
        $message = 'ルート .htaccess の書き込みに失敗しました。パーミッションを確認してください。';
        return false;
    }

    $admin_ht_path = ou_bg_get_admin_htaccess_path();
    $admin_ht_dir  = dirname( $admin_ht_path );

    if ( ! is_dir( $admin_ht_dir ) ) {
        $message = 'wp-admin ディレクトリが見つかりません。';
        return false;
    }

    if ( $settings['enabled'] && ( $settings['targets'] === 'admin' || $settings['targets'] === 'both' ) ) {
        $admin_content = ou_bg_build_admin_htaccess_content( $settings );
        if ( file_put_contents( $admin_ht_path, $admin_content ) === false ) {
            $message = 'wp-admin/.htaccess の書き込みに失敗しました。パーミッションを確認してください。ルート .htaccess の反映は完了しています。';
            return false;
        }
    } else {
        if ( file_exists( $admin_ht_path ) ) {
            if ( ! @unlink( $admin_ht_path ) ) {
                $message = 'wp-admin/.htaccess の削除に失敗しました。手動で削除してください。';
                return false;
            }
        }
    }

    ou_bg_evaluate_status();

    $current_user = wp_get_current_user();
    update_option( OU_BG_OPTION_LAST_APPLIED_BY, $current_user ? $current_user->user_login : '' );
    update_option( OU_BG_OPTION_LAST_APPLIED_AT, current_time( 'mysql' ) );

    // 自己修復フラグ・注意喚起フラグはクリア（新しい環境で正しく反映された）
    delete_option( OU_BG_OPTION_AUTO_HEALED );
    delete_option( OU_BG_OPTION_AUTO_HEAL_POPUP );

    $message = 'Basic Guard の設定を .htaccess に反映しました。';

    return true;
}

/**
 * 自己修復：フロント（トップ）アクセス時に環境変化を検知したら Basic Guard を一時無効化
 *
 * リストア後：
 *  - まずトップページ（またはブログトップ）にアクセスしてもらう前提
 *  - .htpasswd が見つからなければ、自動で Basic Guard を無効化＋.htaccess からクリア
 *  - 管理画面では赤帯で「再設定して」と強く警告
 */
function ou_bg_maybe_auto_heal_after_restore() {
    // 管理画面・Ajax・Cron などでは実行しない
    if ( is_admin() || ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) || ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) ) {
        return;
    }

    // トップページ or ブログトップだけをトリガーにする
    if ( ! ( is_front_page() || is_home() ) ) {
        return;
    }

    // 一度自己修復したあとは再実行しない
    $auto_healed = (int) get_option( OU_BG_OPTION_AUTO_HEALED, 0 );
    if ( $auto_healed ) {
        return;
    }

    $settings = ou_bg_get_settings();

    if ( ! $settings['enabled'] ) {
        return;
    }

    if ( $settings['authuserfile'] === '' || ! ou_bg_is_absolute_path( $settings['authuserfile'] ) ) {
        return;
    }

    // .htpasswd が物理的に存在しない場合のみ、環境変化とみなして自己修復
    if ( file_exists( $settings['authuserfile'] ) ) {
        return;
    }

    // Basic Guard を一時的に無効化
    $settings['enabled'] = false;
    ou_bg_save_settings( $settings );

    // ルート .htaccess からブロック削除
    $ht_path = ou_bg_get_htaccess_path();
    if ( file_exists( $ht_path ) ) {
        $ht_content = @file_get_contents( $ht_path );
        if ( $ht_content !== false ) {
            $new_content = ou_bg_strip_htaccess_block( $ht_content );
            @file_put_contents( $ht_path, $new_content );
        }
    }

    // wp-admin/.htaccess を削除
    $admin_ht_path = ou_bg_get_admin_htaccess_path();
    if ( file_exists( $admin_ht_path ) ) {
        @unlink( $admin_ht_path );
    }

    // ★自己修復が走ったときは「エラー扱い」にして赤帯で強く知らせる
    update_option( OU_BG_OPTION_STATUS, OU_BG_STATUS_ERROR );
    update_option(
        OU_BG_OPTION_STATUS_MESSAGE,
        'リストア後の環境変更を検出したため、Basic Guard を一時的に無効化しました。'
        . '現在、wp-login.php / wp-admin には Basic 認証がかかっていません。'
        . '「設定 → Basic Guard」で AuthUserFile のパスを現在の環境に合わせて設定し、「.htaccess に反映」を実行してください。'
    );

    // 自己修復済みフラグ
    update_option( OU_BG_OPTION_AUTO_HEALED, 1 );
    // 管理画面で必ず強調表示させるためのフラグ（.htaccess 反映までは残す）
    update_option( OU_BG_OPTION_AUTO_HEAL_POPUP, 1 );
}

/**
 * 管理画面用：ステータス評価
 */
add_action( 'admin_init', function() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ou_bg_evaluate_status();
});

/**
 * フロント側で自己修復を試みる（トップページアクセス時）
 */
add_action( 'template_redirect', 'ou_bg_maybe_auto_heal_after_restore' );

/**
 * admin_notices でステータス帯表示 ＋ 自己修復後の特別アラート
 */
add_action( 'admin_notices', function() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // 1) 自己修復後専用の、強制アラート（フラグが消えるまで毎回表示）
    $auto_heal_flag = (int) get_option( OU_BG_OPTION_AUTO_HEAL_POPUP, 0 );
    if ( $auto_heal_flag ) {
        echo '<div class="notice notice-error"><p>'
            . 'Basic Guard がリストア後の環境変更を検出したため、<strong>自動的に無効化</strong>されました。'
            . '<br>このままだと wp-login.php / wp-admin には Basic 認証がかかっていません。'
            . '<br>「設定 → Basic Guard」で AuthUserFile のパスを現在の環境に合わせて設定し、「.htaccess に反映」を実行してください。'
            . '</p></div>';
    }

    // 2) 通常のステータス表示
    $status  = get_option( OU_BG_OPTION_STATUS, '' );
    $message = get_option( OU_BG_OPTION_STATUS_MESSAGE, '' );

    if ( $status === OU_BG_STATUS_OK ) {
        $class = 'notice notice-success';
        $text  = 'Basic Guard：設定と .htaccess は同期されています。';
    } elseif ( $status === OU_BG_STATUS_NEEDS ) {
        $class = 'notice notice-warning';
        $text  = 'Basic Guard：設定と .htaccess の内容が一致していません。「設定 → Basic Guard」で内容を確認し、「.htaccess に反映」を実行してください。';
    } elseif ( $status === OU_BG_STATUS_ERROR ) {
        $class = 'notice notice-error';
        $text  = 'Basic Guard：エラーが発生しています。設定 → Basic Guard で詳細を確認してください。';
    } else {
        return;
    }

    if ( $message ) {
        $text .= '<br>' . esc_html( $message );
    }

    if ( ! ou_bg_is_https() ) {
        $text .= '<br><strong>このサイトは HTTP でアクセスされています。Basic 認証の安全な利用には HTTPS 環境を強く推奨します。</strong>';
    }

    echo '<div class="' . esc_attr( $class ) . '"><p>' . wp_kses_post( $text ) . '</p></div>';
});

/**
 * 設定メニューに Basic Guard を追加
 */
add_action( 'admin_menu', function() {
    add_options_page(
        'Basic Guard 設定',
        'Basic Guard',
        'manage_options',
        'ou-basic-guard',
        'ou_bg_render_settings_page'
    );
});

/**
 * 設定ページの描画
 */
function ou_bg_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $settings        = ou_bg_get_settings();
    $status          = get_option( OU_BG_OPTION_STATUS, '' );
    $status_message  = get_option( OU_BG_OPTION_STATUS_MESSAGE, '' );
    $last_by         = get_option( OU_BG_OPTION_LAST_APPLIED_BY, '' );
    $last_at         = get_option( OU_BG_OPTION_LAST_APPLIED_AT, '' );
    $ht_path         = ou_bg_get_htaccess_path();
    $ht_block        = '';

    if ( file_exists( $ht_path ) ) {
        $ht_content = @file_get_contents( $ht_path );
        if ( $ht_content !== false ) {
            $ht_block = ou_bg_extract_htaccess_block( $ht_content );
        }
    }

    $notice = '';

    // 推奨 AuthUserFile パス（表示専用）＝ この WP ルート直下
    $recommended_auth = rtrim( ABSPATH, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . '.htpasswd_basic_guard';

    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ou_bg_action'] ) ) {
        check_admin_referer( 'ou_bg_settings' );

        $action = sanitize_text_field( wp_unslash( $_POST['ou_bg_action'] ) );

        if ( $action === 'save_settings' ) {
            $enabled  = isset( $_POST['enabled'] );
            $username = isset( $_POST['username'] ) ? sanitize_text_field( wp_unslash( $_POST['username'] ) ) : '';
            $targets  = isset( $_POST['targets'] ) ? sanitize_text_field( wp_unslash( $_POST['targets'] ) ) : 'both';
            $auth     = isset( $_POST['authuserfile'] ) ? trim( (string) wp_unslash( $_POST['authuserfile'] ) ) : '';

            $new_settings = $settings;

            if ( $enabled ) {
                if ( $auth === '' ) {
                    $notice .= '<div class="notice notice-error"><p>Basic Guard を有効にするには、AuthUserFile の絶対パスを設定してください。</p></div>';
                } elseif ( ! ou_bg_is_absolute_path( $auth ) ) {
                    $notice .= '<div class="notice notice-error"><p>AuthUserFile のパスは絶対パス（/ から始まるフルパス）で指定してください。</p></div>';
                } else {
                    $new_settings['authuserfile'] = $auth;
                }
            } else {
                if ( $auth !== '' ) {
                    if ( ou_bg_is_absolute_path( $auth ) ) {
                        $new_settings['authuserfile'] = $auth;
                    } else {
                        $notice .= '<div class="notice notice-error"><p>AuthUserFile のパスは絶対パス（/ から始まるフルパス）で指定してください。</p></div>';
                    }
                }
            }

            if ( strpos( $notice, 'notice-error' ) === false ) {
                $new_settings['enabled']  = $enabled;
                $new_settings['username'] = $username;
                $new_settings['targets']  = in_array( $targets, [ 'login', 'admin', 'both' ], true ) ? $targets : 'both';

                if ( ! empty( $_POST['password'] ) ) {
                    $plain_pw                = (string) wp_unslash( $_POST['password'] );
                    $new_settings['pw_hash'] = ou_bg_generate_htpasswd_hash( $plain_pw );
                }

                ou_bg_save_settings( $new_settings );
                $settings = $new_settings;

                ou_bg_evaluate_status();
                $status         = get_option( OU_BG_OPTION_STATUS, '' );
                $status_message = get_option( OU_BG_OPTION_STATUS_MESSAGE, '' );

                $notice .= '<div class="notice notice-success"><p>Basic Guard の設定を保存しました。</p></div>';
            }
        } elseif ( $action === 'apply_htaccess' ) {
            if ( ! ou_bg_is_https() && ! isset( $_POST['risk_acknowledged'] ) ) {
                $notice .= '<div class="notice notice-error"><p>HTTP 環境での反映を実行するには、危険性（平文での認証情報送信）を理解した上でチェックボックスをオンにする必要があります。</p></div>';
            } else {
                $msg = '';
                if ( ou_bg_apply_to_htaccess( $msg ) ) {
                    $notice .= '<div class="notice notice-success"><p>' . esc_html( $msg ) . '</p></div>';
                } else {
                    $notice .= '<div class="notice notice-error"><p>' . esc_html( $msg ) . '</p></div>';
                }
            }

            $status         = get_option( OU_BG_OPTION_STATUS, '' );
            $status_message = get_option( OU_BG_OPTION_STATUS_MESSAGE, '' );

            if ( file_exists( $ht_path ) ) {
                $ht_content = @file_get_contents( $ht_path );
                if ( $ht_content !== false ) {
                    $ht_block = ou_bg_extract_htaccess_block( $ht_content );
                }
            }
        }
    }

    $settings = ou_bg_get_settings();
    $is_https = ou_bg_is_https();

    ?>
    <div class="wrap">
        <h1>Basic Guard 設定</h1>

        <?php if ( $notice ) echo $notice; ?>

        <h2>現在の状態</h2>
        <p>
            <strong>ステータス：</strong>
            <?php
            if ( $status === OU_BG_STATUS_OK ) {
                echo '✅ 正常（設定と .htaccess は同期されています）';
            } elseif ( $status === OU_BG_STATUS_NEEDS ) {
                echo '⚠️ 要反映（設定と .htaccess の内容が一致していません）';
            } elseif ( $status === OU_BG_STATUS_ERROR ) {
                echo '❌ エラー（.htaccess または環境に問題があります）';
            } else {
                echo '（未評価）';
            }
            ?>
        </p>
        <?php if ( $status_message ) : ?>
            <p><?php echo esc_html( $status_message ); ?></p>
        <?php endif; ?>

        <?php if ( $last_at ) : ?>
            <p><strong>最終反映：</strong>
                <?php echo esc_html( $last_at ); ?>
                <?php if ( $last_by ) : ?>
                    （<?php echo esc_html( $last_by ); ?>）
                <?php endif; ?>
            </p>
        <?php endif; ?>

        <?php if ( ! $is_https ) : ?>
            <div class="notice notice-error">
                <p><strong>警告：</strong>このサイトは HTTP でアクセスされています。Basic 認証の安全な利用には HTTPS 環境を強く推奨します。HTTP 環境で反映すると、認証情報が暗号化されずに送信されません。</p>
            </div>
        <?php endif; ?>

        <hr>

        <h2>Basic Guard 設定</h2>
        <form method="post">
            <?php wp_nonce_field( 'ou_bg_settings' ); ?>
            <input type="hidden" name="ou_bg_action" value="save_settings">

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">Basic Guard 有効化</th>
                    <td>
                        <label>
                            <input type="checkbox" name="enabled" <?php checked( $settings['enabled'] ); ?>>
                            有効にする（wp-login.php / wp-admin に Basic 認証をかける）
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">ユーザー名</th>
                    <td>
                        <input type="text" name="username" value="<?php echo esc_attr( $settings['username'] ); ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">パスワード</th>
                    <td>
                        <input type="password" name="password" value="" class="regular-text">
                        <p class="description">※ 新しいパスワードを設定する場合のみ入力してください。空欄の場合は既存のパスワードを維持します（平文は保存されません）。</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">保護対象</th>
                    <td>
                        <label>
                            <input type="radio" name="targets" value="login" <?php checked( $settings['targets'], 'login' ); ?>>
                            wp-login.php のみ
                        </label><br>
                        <label>
                            <input type="radio" name="targets" value="admin" <?php checked( $settings['targets'], 'admin' ); ?>>
                            /wp-admin/ のみ
                        </label><br>
                        <label>
                            <input type="radio" name="targets" value="both" <?php checked( $settings['targets'], 'both' ); ?>>
                            両方
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">AuthUserFile パス</th>
                    <td>
                        <input
                            type="text"
                            name="authuserfile"
                            id="ou-bg-authuserfile"
                            value="<?php echo esc_attr( $settings['authuserfile'] ); ?>"
                            class="regular-text"
                            placeholder="<?php echo esc_attr( $recommended_auth ); ?>"
                        >
                        <p class="description">
                            ※ .htpasswd を配置する絶対パス（例：<code><?php echo esc_html( $recommended_auth ); ?></code>）。<br>
                            通常はこの WordPress のルートディレクトリ直下に <code>.htpasswd_basic_guard</code> を置くことを推奨します。
                        </p>
                        <p>
                            <button type="button"
                                class="button"
                                id="ou-bg-set-recommended"
                                data-recommended="<?php echo esc_attr( $recommended_auth ); ?>">
                                このサイトの推奨パスを自動入力
                            </button>
                        </p>
                    </td>
                </tr>
            </table>

            <?php submit_button( '設定を保存', 'primary' ); ?>
        </form>

        <hr>

        <h2>.htaccess への反映</h2>
        <p>現在の設定内容をもとに、ルートの .htaccess と wp-admin/.htaccess の Basic Guard ブロックを更新します。</p>
        <form method="post" style="margin-bottom: 2em;">
            <?php wp_nonce_field( 'ou_bg_settings' ); ?>
            <input type="hidden" name="ou_bg_action" value="apply_htaccess">

            <?php $button_class = $is_https ? 'button-primary' : 'button-secondary'; ?>

            <?php if ( ! $is_https ) : ?>
                <p>
                    <label>
                        <input type="checkbox" id="risk_acknowledged" name="risk_acknowledged">
                        危険性（HTTPによる平文での認証情報送信）を理解したうえで実行する
                    </label>
                </p>
            <?php endif; ?>

            <?php submit_button( '.htaccess に反映', $button_class, 'submit_apply_htaccess', true ); ?>
        </form>

        <h2>ルート .htaccess の Basic Guard ブロック</h2>
        <?php if ( $ht_block ) : ?>
            <p>現在ルートの .htaccess に記録されている Basic Guard ブロックの内容です（wp-login.php の保護部分）。</p>
            <textarea readonly rows="12" style="width:100%; font-family: monospace;"><?php echo esc_textarea( $ht_block ); ?></textarea>
        <?php else : ?>
            <p>ルートの .htaccess に Basic Guard ブロックは存在しません。</p>
        <?php endif; ?>

    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // HTTP 環境でのリスク許諾チェック
        const riskCheckbox = document.getElementById('risk_acknowledged');
        const submitButton = document.getElementById('submit_apply_htaccess');
        if (riskCheckbox && submitButton) {
            submitButton.disabled = true;
            riskCheckbox.addEventListener('change', function() {
                submitButton.disabled = !this.checked;
            });
        }

        // 推奨 AuthUserFile パス自動入力ボタン
        const setBtn = document.getElementById('ou-bg-set-recommended');
        const authInput = document.getElementById('ou-bg-authuserfile');
        if (setBtn && authInput) {
            setBtn.addEventListener('click', function() {
                const recommended = this.getAttribute('data-recommended');
                if (recommended) {
                    authInput.value = recommended;
                }
            });
        }
    });
    </script>
    <?php
}
