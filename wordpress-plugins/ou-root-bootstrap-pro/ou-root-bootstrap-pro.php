<?php

/**
 * Plugin Name: OU Root Bootstrap Pro (Auto Detect)
 * Description: サブディレクトリ設置のWordPressをルート直下で公開するために index.php / .htaccess を自動生成。サブディレクトリは自動判定＋手動上書き対応。
 * Version: 2.0.0
 * Author: Office Ueda
 * License: GPL-2.0+
 */

if (!defined('ABSPATH')) exit;

class OU_Root_Bootstrap_Pro
{
    const SLUG = 'ou-root-bootstrap-pro';
    const OPTION_LOG = 'ou_root_bootstrap_log';

    public static function init()
    {
        $self = new self();
        add_action('admin_menu', [$self, 'admin_menu']);
        add_action('admin_post_ou_rb_run', [$self, 'handle_run']);
        add_action('admin_post_ou_rb_check', [$self, 'handle_check']);
    }

    /** サブディレクトリ自動判定 */
    private function detect_wp_subdir(): string
    {
        // 1) ABSPATHから判定
        $fromAbs = basename(rtrim(ABSPATH, DIRECTORY_SEPARATOR));

        // 2) site_url のパスから判定
        $path = trim((string)parse_url(site_url(), PHP_URL_PATH), '/');
        $fromSite = $path !== '' ? explode('/', $path)[0] : '';

        // 3) site_url優先
        $guess = $fromSite !== '' ? $fromSite : $fromAbs;

        // 4) 手動上書き（定数）
        if (defined('OU_RB_SUBDIR') && OU_RB_SUBDIR !== '') {
            $guess = OU_RB_SUBDIR;
        }

        // 5) フィルタで上書き
        $guess = apply_filters('ou_rb/subdir', $guess);

        return trim($guess, '/');
    }

    /** 生成対象パス */
    private function target_paths()
    {
        $root = rtrim(dirname(ABSPATH), DIRECTORY_SEPARATOR);

        return [
            'root_dir'      => $root,
            'index_path'    => $root . '/index.php',
            'htaccess_path' => $root . '/.htaccess',
            'wp_subdir'     => $this->detect_wp_subdir(),
        ];
    }

    /** index.php 内容 */
    private function c_index_content($subdir)
    {
        return <<<PHP
<?php
define('WP_USE_THEMES', true);
require __DIR__ . '/{$subdir}/wp-blog-header.php';
PHP;
    }

    /** .htaccess 内容 */
    private function c_htaccess_content()
    {
        return <<<HTA
# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
# END WordPress
HTA;
    }

    /** 書込 */
    private function write_file($file, $content)
    {
        $bytes = @file_put_contents($file, $content);
        if ($bytes === false) return false;
        @chmod($file, 0644);
        return true;
    }

    /** 診断 */
    public function handle_check()
    {
        if (!current_user_can('manage_options')) wp_die('forbidden', 403);
        check_admin_referer('ou_rb_check');
        $p = $this->target_paths();

        $report = [];
        $report[] = 'サブディレクトリ検出: ' . $p['wp_subdir'];
        $report[] = 'ルート: ' . $p['root_dir'];
        $report[] = 'index.php: ' . $p['index_path'];
        $report[] = '.htaccess: ' . $p['htaccess_path'];

        update_option(self::OPTION_LOG, implode("\n", $report), false);
        wp_safe_redirect(admin_url('tools.php?page=' . self::SLUG . '&checked=1'));
        exit;
    }

    /** 実行 */
    public function handle_run()
    {
        if (!current_user_can('manage_options')) wp_die('forbidden', 403);
        check_admin_referer('ou_rb_run');
        $p = $this->target_paths();

        $indexContent   = $this->c_index_content($p['wp_subdir']);
        $htaccessContent = $this->c_htaccess_content();

        $ok1 = $this->write_file($p['index_path'], $indexContent);
        $ok2 = $this->write_file($p['htaccess_path'], $htaccessContent);

        $log = [];
        if ($ok1 && $ok2) {
            $log[] = 'index.php と .htaccess を作成しました';
        } else {
            $log[] = '書き込み失敗（パーミッションやopen_basedir制限を確認）';
        }

        update_option(self::OPTION_LOG, implode("\n", $log), false);
        wp_safe_redirect(admin_url('tools.php?page=' . self::SLUG . '&ran=1'));
        exit;
    }

    /** 管理画面UI */
    public function admin_menu()
    {
        add_management_page(
            'ルートブートストラップPro',
            'ルートブートストラップPro',
            'manage_options',
            self::SLUG,
            [$this, 'render']
        );
    }

    public function render()
    {
        if (!current_user_can('manage_options')) wp_die('forbidden', 403);
        $log = get_option(self::OPTION_LOG, '');
?>
        <div class="wrap">
            <h1>ルートブートストラップ Pro</h1>
            <p>サブディレクトリを自動検出し、ルート直下に index.php / .htaccess を生成します。</p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('ou_rb_check'); ?>
                <input type="hidden" name="action" value="ou_rb_check">
                <button class="button">診断</button>
            </form>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('ou_rb_run'); ?>
                <input type="hidden" name="action" value="ou_rb_run">
                <button class="button button-primary">ファイル作成</button>
            </form>
            <?php if ($log): ?>
                <h2>ログ</h2>
                <pre><?php echo esc_html($log); ?></pre>
            <?php endif; ?>
        </div>
<?php
    }
}
OU_Root_Bootstrap_Pro::init();
