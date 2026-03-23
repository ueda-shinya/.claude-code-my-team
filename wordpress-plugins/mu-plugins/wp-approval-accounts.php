<?php
/**
 * Plugin Name: WP Approval Accounts (MU)
 * Description: 新規登録を「承認制」にするMUプラグイン。承認までログイン不可。ユーザー一覧から承認/却下可能。
 * Author: Office Ueda / ChatGPT
 * Version: 1.0.0
 */

declare(strict_types=1);

// ==== 基本設定（必要に応じて変更） ==========================================
const WPAA_PENDING_ROLE_KEY   = 'pending_approval';
const WPAA_PENDING_ROLE_LABEL = '承認待ち';
const WPAA_STATUS_META        = 'wpaa_status';          // 'pending' | 'approved' | 'rejected'
const WPAA_ORIGINAL_ROLES     = 'wpaa_original_roles';  // array<string>
const WPAA_MAIL_FROM_NAME     = 'サイト管理者';
const WPAA_MAIL_SUBJECT_TAG   = '[アカウント承認制] ';
// ============================================================================

/**
 * MUなので activate hook は使えない。ロード時にロールを確実に用意。
 */
add_action('init', function () {
    if (!get_role(WPAA_PENDING_ROLE_KEY)) {
        add_role(WPAA_PENDING_ROLE_KEY, WPAA_PENDING_ROLE_LABEL, []); // 権限ゼロ
    }
}, 5);

/**
 * 新規登録時：承認待ちへ切替、元ロールを保存、通知メール送信
 */
add_action('user_register', function (int $user_id) {
    $user = get_user_by('id', $user_id);
    if (!$user instanceof WP_User) {
        return;
    }

    // 元ロールを保存（通常は default_role=subscriber などが1つ入っている想定）
    $original_roles = (array)$user->roles;
    update_user_meta($user_id, WPAA_ORIGINAL_ROLES, $original_roles);

    // 承認待ちへロール差し替え
    $user->set_role(WPAA_PENDING_ROLE_KEY);
    update_user_meta($user_id, WPAA_STATUS_META, 'pending');

    // --- 通知メール ---
    $site      = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
    $login_url = wp_login_url();

    // 管理者へ通知
    $to_admin = get_option('admin_email');
    if ($to_admin) {
        wp_mail(
            $to_admin,
            WPAA_MAIL_SUBJECT_TAG . '新規登録（承認待ち）',
            "サイト: {$site}\nユーザー: {$user->user_login}\nメール: {$user->user_email}\n状態: 承認待ち\n\n承認/却下は「ユーザー > 一覧」から行ってください。",
            ['Content-Type: text/plain; charset=UTF-8']
        );
    }

    // 申請ユーザーへ受付メール（ログインはまだ不可）
    wp_mail(
        $user->user_email,
        WPAA_MAIL_SUBJECT_TAG . '登録申請を受け付けました',
        "{$user->user_login} 様\n\nアカウント登録申請を受け付けました。現在、管理者による承認待ちです。\n承認後、ログイン可能になります。\nログインURL: {$login_url}\n\n※このメールに心当たりが無い場合は破棄してください。",
        ['Content-Type: text/plain; charset=UTF-8']
    );
}, 10);

/**
 * デフォルトの「新規ユーザーへようこそメール」を停止（承認前に“使える”と誤解させないため）
 */
add_filter('wp_send_new_user_notification_to_user', function (bool $send, WP_User $user): bool {
    // 承認待ちならユーザー宛を止める（管理者宛はそのまま）
    $status = get_user_meta($user->ID, WPAA_STATUS_META, true);
    if ($status === 'pending') {
        return false;
    }
    return $send;
}, 10, 2);

/**
 * 認証フロー：承認待ち/却下はログインさせない
 */
add_filter('authenticate', function ($user_or_error) {
    if ($user_or_error instanceof WP_User) {
        $uid    = $user_or_error->ID;
        $status = get_user_meta($uid, WPAA_STATUS_META, true);

        if ($status === 'pending') {
            return new WP_Error('wpaa_pending', 'あなたのアカウントは現在、承認待ちです。承認完了までログインできません。');
        }
        if ($status === 'rejected') {
            return new WP_Error('wpaa_rejected', 'あなたのアカウントは却下されています。詳細は管理者にお問い合わせください。');
        }
    }
    return $user_or_error;
}, 30);

/**
 * パスワードリセットも承認まで禁止
 */
add_filter('allow_password_reset', function ($allow, int $user_id) {
    $status = get_user_meta($user_id, WPAA_STATUS_META, true);
    if ($status === 'pending' || $status === 'rejected') {
        return new WP_Error('wpaa_block_reset', '承認前（または却下済み）のアカウントはパスワードをリセットできません。');
    }
    return $allow;
}, 10, 2);

/**
 * ユーザー一覧に「承認」/「却下」アクションを追加（承認待ちのみ）
 */
add_filter('user_row_actions', function (array $actions, WP_User $user): array {
    $status = get_user_meta($user->ID, WPAA_STATUS_META, true);
    if ($status !== 'pending') {
        return $actions;
    }
    if (!current_user_can('promote_users')) {
        return $actions;
    }

    $approve_url = wp_nonce_url(
        admin_url('admin-post.php?action=wpaa_approve&user_id=' . $user->ID),
        'wpaa_approve_' . $user->ID
    );
    $reject_url = wp_nonce_url(
        admin_url('admin-post.php?action=wpaa_reject&user_id=' . $user->ID),
        'wpaa_reject_' . $user->ID
    );

    $actions['wpaa_approve'] = '<a href="' . esc_url($approve_url) . '">承認</a>';
    $actions['wpaa_reject']  = '<a href="' . esc_url($reject_url) . '">却下</a>';

    return $actions;
}, 10, 2);

/**
 * 承認ハンドラ
 */
add_action('admin_post_wpaa_approve', function () {
    if (!current_user_can('promote_users')) {
        wp_die('権限がありません。');
    }
    $uid = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    if ($uid <= 0 || !wp_verify_nonce($_GET['_wpnonce'] ?? '', 'wpaa_approve_' . $uid)) {
        wp_die('不正なリクエストです。');
    }
    $user = get_user_by('id', $uid);
    if (!$user) {
        wp_die('ユーザーが見つかりません。');
    }
    $status = get_user_meta($uid, WPAA_STATUS_META, true);
    if ($status !== 'pending') {
        wp_redirect(admin_url('users.php?wpaa=already'));
        exit;
    }

    // 元ロール復元（無ければ default_role）
    $original = get_user_meta($uid, WPAA_ORIGINAL_ROLES, true);
    $target_role = '';
    if (is_array($original) && !empty($original)) {
        $target_role = (string)array_values($original)[0];
    }
    if (!$target_role || !get_role($target_role)) {
        $target_role = get_option('default_role', 'subscriber');
    }

    $user = new WP_User($uid);
    $user->set_role($target_role);
    update_user_meta($uid, WPAA_STATUS_META, 'approved');

    // 承認メール
    $login_url = wp_login_url();
    wp_mail(
        $user->user_email,
        WPAA_MAIL_SUBJECT_TAG . 'アカウントが承認されました',
        "{$user->user_login} 様\n\nアカウントが承認されました。以下よりログインいただけます。\n{$login_url}\n\nご利用ありがとうございます。",
        ['Content-Type: text/plain; charset=UTF-8']
    );

    wp_redirect(admin_url('users.php?wpaa=approved'));
    exit;
});

/**
 * 却下ハンドラ（削除はせず、ログイン不可のままにする）
 * ※運用で“即削除”したい場合は wp_delete_user($uid) に切替可能。
 */
add_action('admin_post_wpaa_reject', function () {
    if (!current_user_can('promote_users')) {
        wp_die('権限がありません。');
    }
    $uid = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    if ($uid <= 0 || !wp_verify_nonce($_GET['_wpnonce'] ?? '', 'wpaa_reject_' . $uid)) {
        wp_die('不正なリクエストです。');
    }
    $user = get_user_by('id', $uid);
    if (!$user) {
        wp_die('ユーザーが見つかりません。');
    }

    // 却下状態へ
    $wp_user = new WP_User($uid);
    $wp_user->set_role(WPAA_PENDING_ROLE_KEY); // 権限ゼロで維持
    update_user_meta($uid, WPAA_STATUS_META, 'rejected');

    // 却下メール（任意）
    wp_mail(
        $user->user_email,
        WPAA_MAIL_SUBJECT_TAG . 'アカウント申請は却下されました',
        "{$user->user_login} 様\n\n恐れ入りますが、今回のアカウント登録申請は承認に至りませんでした。\nご不明点は管理者までお問い合わせください。",
        ['Content-Type: text/plain; charset=UTF-8']
    );

    wp_redirect(admin_url('users.php?wpaa=rejected'));
    exit;
});

/**
 * 管理画面のフラッシュメッセージ
 */
add_action('admin_notices', function () {
    if (!isset($_GET['wpaa'])) return;
    $msg = match ($_GET['wpaa']) {
        'approved' => 'ユーザーを承認しました。',
        'rejected' => 'ユーザーを却下しました。',
        'already'  => 'このユーザーはすでに承認待ちではありません。',
        default    => '',
    };
    if ($msg) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($msg) . '</p></div>';
    }
});
