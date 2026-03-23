<?php
/**
 * Plugin Name: OU MU Plugin Installer
 * Description: 管理画面から MU プラグイン（mu-plugins）を ZIP でアップロード・インストール／削除するための管理者向けツール。
 * Version: 1.0.2
 * Author: Office Ueda
 */

if (!defined('ABSPATH')) {
    exit; // 直接アクセス禁止
}

if (!class_exists('OU_MU_Installer')) {

    class OU_MU_Installer
    {
        /**
         * 一時展開情報を保存するオプション名
         */
        const OPTION_PENDING   = 'ou_mu_installer_pending';

        /**
         * 通知メッセージを保存するトランジェント名
         */
        const TRANSIENT_NOTICE = 'ou_mu_installer_notice';

        /**
         * コンストラクタ：フック登録
         */
        public function __construct()
        {
            // 管理画面メニュー
            add_action('admin_menu', [$this, 'add_menu']);

            // admin-post ハンドラ（管理者のみ）
            add_action('admin_post_ou_mu_upload', [$this, 'handle_upload']);
            add_action('admin_post_ou_mu_install', [$this, 'handle_install']);
            add_action('admin_post_ou_mu_delete', [$this, 'handle_delete']);

            // 通知表示
            add_action('admin_notices', [$this, 'render_notices']);
        }

        /**
         * メニュー追加（ツール > MUインストーラー）
         */
        public function add_menu()
        {
            if (!current_user_can('manage_options')) {
                return;
            }

            add_submenu_page(
                'tools.php',
                'MUインストーラー',
                'MUインストーラー',
                'manage_options',
                'ou-mu-installer',
                [$this, 'render_page']
            );
        }

        /**
         * 管理画面：メインページ描画
         */
        public function render_page()
        {
            if (!current_user_can('manage_options')) {
                wp_die('このページにアクセスする権限がありません。');
            }

            $zip_available = class_exists('ZipArchive');
            $mu_dir        = WP_CONTENT_DIR . '/mu-plugins';

            // 保留中のインストール情報
            $pending = get_option(self::OPTION_PENDING);
            if (!is_array($pending)) {
                $pending = null;
            }

            ?>
            <div class="wrap">
                <h1>MUインストーラー</h1>

                <?php if (!$zip_available) : ?>
                    <div class="notice notice-error">
                        <p>このサーバーでは <code>ZipArchive</code> クラスが利用できないため、ZIP からのインストールは実行できません。</p>
                    </div>
                <?php endif; ?>

                <h2>1. MUプラグインを ZIP でアップロード</h2>
                <p>ZIP 内の構成は、<code>wp-content/mu-plugins</code> 配下にそのまま展開される前提で作成してください。</p>

                <?php if ($zip_available) : ?>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
                        <?php wp_nonce_field('ou_mu_upload'); ?>
                        <input type="hidden" name="action" value="ou_mu_upload" />

                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row"><label for="ou_mu_zip">ZIP ファイル</label></th>
                                <td>
                                    <input type="file"
                                           id="ou_mu_zip"
                                           name="mu_plugins[]"
                                           multiple="multiple"
                                           accept=".zip,application/zip" />
                                    <p class="description">
                                        複数の ZIP を同時にアップロードできます。<br>
                                        <strong>※ ZIP 以外（.php など）はアップロードしても処理対象になりません。</strong>
                                    </p>
                                </td>
                            </tr>
                        </table>

                        <?php submit_button('アップロードして確認画面へ'); ?>
                    </form>
                <?php endif; ?>

                <hr />

                <h2>2. インストール確認（保留中）</h2>
                <?php
                // pending 情報と tmp ディレクトリ存在チェック
                if ($pending && !empty($pending['tmp_dir']) && !empty($pending['files']) && is_dir($pending['tmp_dir'])) :

                    $tmp_dir = rtrim($pending['tmp_dir'], '/\\') . DIRECTORY_SEPARATOR;
                    $files   = (array) $pending['files'];

                    $mu_base = rtrim($mu_dir, '/\\') . DIRECTORY_SEPARATOR;

                    $conflicts = [];
                    $new_files = [];

                    foreach ($files as $rel_path) {
                        $rel_path = ltrim(str_replace(['\\'], '/', $rel_path), '/');
                        $src      = $tmp_dir . $rel_path;
                        $dst      = $mu_base . $rel_path;

                        if (!is_file($src)) {
                            // 念のため存在確認
                            continue;
                        }

                        if (file_exists($dst)) {
                            $conflicts[$rel_path] = [
                                'src' => $src,
                                'dst' => $dst,
                            ];
                        } else {
                            $new_files[$rel_path] = [
                                'src' => $src,
                                'dst' => $dst,
                            ];
                        }
                    }

                    // プラグインメタ情報取得用に plugin.php 読み込み
                    if (!function_exists('get_file_data')) {
                        require_once ABSPATH . 'wp-admin/includes/plugin.php';
                    }
                    ?>
                    <p>以下のファイルが一時ディレクトリに展開されています。インストール内容を確認してください。</p>

                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <?php wp_nonce_field('ou_mu_install_confirm'); ?>
                        <input type="hidden" name="action" value="ou_mu_install" />

                        <?php if ($new_files) : ?>
                            <h3>新規インストールされるファイル</h3>
                            <table class="widefat striped">
                                <thead>
                                    <tr>
                                        <th>ファイルパス（mu-plugins 基準）</th>
                                        <th>新規ファイルの情報</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($new_files as $rel_path => $info) : ?>
                                        <?php
                                        $meta_new = $this->get_plugin_meta($info['src']);
                                        ?>
                                        <tr>
                                            <td>
                                                <code><?php echo esc_html($rel_path); ?></code>
                                                <input type="hidden" name="files[]" value="<?php echo esc_attr($rel_path); ?>" />
                                                <input type="hidden" name="action_mode[<?php echo esc_attr($rel_path); ?>]" value="install" />
                                            </td>
                                            <td>
                                                <?php $this->render_meta_block($meta_new, '新規'); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else : ?>
                            <p>新規追加のみのファイルはありません。</p>
                        <?php endif; ?>

                        <?php if ($conflicts) : ?>
                            <h3>同名ファイルが存在するもの（処理を選択してください）</h3>
                            <table class="widefat striped">
                                <thead>
                                    <tr>
                                        <th>ファイルパス（mu-plugins 基準）</th>
                                        <th>既存ファイルの情報</th>
                                        <th>新規ファイルの情報</th>
                                        <th>処理</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($conflicts as $rel_path => $info) : ?>
                                        <?php
                                        $meta_old   = $this->get_plugin_meta($info['dst']);
                                        $meta_new   = $this->get_plugin_meta($info['src']);
                                        $field_name = 'action_mode[' . $rel_path . ']';
                                        ?>
                                        <tr>
                                            <td>
                                                <code><?php echo esc_html($rel_path); ?></code>
                                                <input type="hidden" name="files[]" value="<?php echo esc_attr($rel_path); ?>" />
                                            </td>
                                            <td>
                                                <?php $this->render_meta_block($meta_old, '既存'); ?>
                                            </td>
                                            <td>
                                                <?php $this->render_meta_block($meta_new, '新規'); ?>
                                            </td>
                                            <td>
                                                <fieldset>
                                                    <label>
                                                        <input type="radio"
                                                               name="<?php echo esc_attr($field_name); ?>"
                                                               value="overwrite"
                                                               checked="checked" />
                                                        上書き（既存を .bak_* にリネームしてから上書き）
                                                    </label><br/>
                                                    <label>
                                                        <input type="radio"
                                                               name="<?php echo esc_attr($field_name); ?>"
                                                               value="skip" />
                                                        スキップ（このファイルは変更しない）
                                                    </label><br/>
                                                    <label>
                                                        <input type="radio"
                                                               name="<?php echo esc_attr($field_name); ?>"
                                                               value="backup" />
                                                        バックアップして上書き（<code>mu-plugins/_backup/</code> に退避）
                                                    </label>
                                                </fieldset>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else : ?>
                            <p>同名ファイルとの衝突はありません。</p>
                        <?php endif; ?>

                        <?php if ($new_files || $conflicts) : ?>
                            <p class="submit">
                                <input type="submit" class="button-primary" value="上記の内容でインストールを実行" />
                            </p>
                        <?php else : ?>
                            <p>インストール対象のファイルがありません。</p>
                        <?php endif; ?>
                    </form>

                <?php else : ?>
                    <p>現在、保留中のインストールはありません。ZIP をアップロードすると、ここに確認画面が表示されます。</p>
                <?php endif; ?>

                <hr />

                <h2>3. 現在の MU プラグイン一覧（削除）</h2>
                <p><code>wp-content/mu-plugins</code> 直下の PHP ファイルを MU プラグインとして一覧表示します。</p>

                <?php
                require_once ABSPATH . 'wp-admin/includes/plugin.php';

                $mu_plugins = get_mu_plugins(); // キー: ファイル名（mu-plugins 基準）

                if ($mu_plugins) :
                    $mu_base = rtrim($mu_dir, '/\\') . DIRECTORY_SEPARATOR;
                    ?>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th>ファイル</th>
                                <th>プラグイン名</th>
                                <th>バージョン</th>
                                <th>説明</th>
                                <th>更新日時</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mu_plugins as $file => $data) : ?>
                                <?php
                                $full_path = $mu_base . $file;
                                $mtime     = file_exists($full_path) ? filemtime($full_path) : false;
                                ?>
                                <tr>
                                    <td><code><?php echo esc_html($file); ?></code></td>
                                    <td><?php echo esc_html($data['Name']); ?></td>
                                    <td><?php echo esc_html($data['Version']); ?></td>
                                    <td><?php echo esc_html($data['Description']); ?></td>
                                    <td>
                                        <?php
                                        if ($mtime) {
                                            echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $mtime));
                                        } else {
                                            echo '&mdash;';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <form method="post"
                                              action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
                                              onsubmit="return confirm('本当にこの MU プラグインを削除しますか？');">
                                            <?php wp_nonce_field('ou_mu_delete'); ?>
                                            <input type="hidden" name="action" value="ou_mu_delete" />
                                            <input type="hidden" name="file" value="<?php echo esc_attr($file); ?>" />
                                            <input type="submit" class="button button-secondary" value="削除" />
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <p>現在、有効な MU プラグインは検出されませんでした。</p>
                <?php endif; ?>

            </div>
            <?php
        }

        /**
         * プラグインメタ情報取得
         *
         * @param string $file_path
         * @return array{name:string,version:string,description:string,mtime:string}
         */
        private function get_plugin_meta($file_path)
        {
            $name        = '(ヘッダーなし)';
            $version     = '(不明)';
            $description = '';
            $mtime_label = '&mdash;';

            if (file_exists($file_path) && is_readable($file_path)) {
                if (!function_exists('get_file_data')) {
                    require_once ABSPATH . 'wp-admin/includes/plugin.php';
                }

                $headers = [
                    'Name'        => 'Plugin Name',
                    'Version'     => 'Version',
                    'Description' => 'Description',
                ];
                $data = get_file_data($file_path, $headers, 'plugin');

                if (!empty($data['Name'])) {
                    $name = $data['Name'];
                }
                if (!empty($data['Version'])) {
                    $version = $data['Version'];
                }
                if (!empty($data['Description'])) {
                    $description = $data['Description'];
                }

                $mtime = filemtime($file_path);
                if ($mtime) {
                    $mtime_label = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $mtime);
                }
            }

            return [
                'name'        => $name,
                'version'     => $version,
                'description' => $description,
                'mtime'       => $mtime_label,
            ];
        }

        /**
         * メタ情報表示用 HTML（既存／新規）
         *
         * @param array  $meta
         * @param string $label_prefix
         */
        private function render_meta_block($meta, $label_prefix)
        {
            ?>
            <strong><?php echo esc_html($label_prefix); ?>：</strong><br />
            <?php if (!empty($meta['name'])) : ?>
                <span>名前：<?php echo esc_html($meta['name']); ?></span><br />
            <?php endif; ?>
            <span>バージョン：<?php echo esc_html($meta['version']); ?></span><br />
            <?php if (!empty($meta['description'])) : ?>
                <span>説明：<?php echo esc_html($meta['description']); ?></span><br />
            <?php endif; ?>
            <span>更新日時：<?php echo esc_html($meta['mtime']); ?></span>
            <?php
        }

        /**
         * ZIP アップロード処理
         */
        public function handle_upload()
        {
            if (!current_user_can('manage_options')) {
                wp_die('権限がありません。');
            }

            check_admin_referer('ou_mu_upload');

            if (!class_exists('ZipArchive')) {
                $this->set_notice('error', ['ZipArchive が利用できないため、ZIP の展開ができませんでした。']);
                wp_safe_redirect(admin_url('tools.php?page=ou-mu-installer'));
                exit;
            }

            // 既存の保留中データがあれば掃除
            $this->cleanup_pending();

            $files = isset($_FILES['mu_plugins']) ? $_FILES['mu_plugins'] : null;
            if (!$files || empty($files['name'])) {
                $this->set_notice('error', ['ファイルが選択されていません。']);
                wp_safe_redirect(admin_url('tools.php?page=ou-mu-installer'));
                exit;
            }

            $upload_dir = wp_upload_dir();
            if (!empty($upload_dir['error'])) {
                $this->set_notice('error', ['アップロードディレクトリを取得できませんでした。: ' . $upload_dir['error']]);
                wp_safe_redirect(admin_url('tools.php?page=ou-mu-installer'));
                exit;
            }

            $base_tmp = trailingslashit($upload_dir['basedir']) . 'ou-mu-installer/tmp/';
            if (!wp_mkdir_p($base_tmp)) {
                $this->set_notice('error', ['一時ディレクトリを作成できませんでした。']);
                wp_safe_redirect(admin_url('tools.php?page=ou-mu-installer'));
                exit;
            }

            $token   = gmdate('YmdHis') . '-' . wp_generate_password(8, false, false);
            $tmp_dir = trailingslashit($base_tmp . $token);

            if (!wp_mkdir_p($tmp_dir)) {
                $this->set_notice('error', ['一時ディレクトリを作成できませんでした。（tmp_dir）']);
                wp_safe_redirect(admin_url('tools.php?page=ou-mu-installer'));
                exit;
            }

            $all_full_paths = [];
            $zips_processed = 0;

            // 複数ファイルを処理
            $names = (array) $files['name'];

            foreach ($names as $index => $name) {
                if ($name === '' || $name === null) {
                    continue;
                }

                $tmp_name = $files['tmp_name'][$index];
                $error    = $files['error'][$index];

                if ($error !== UPLOAD_ERR_OK || !$tmp_name) {
                    $this->set_notice('error', ['ファイルのアップロードに失敗しました: ' . esc_html($name)]);
                    continue;
                }

                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if ($ext !== 'zip') {
                    // ZIP 以外は単純にスキップ（この時点では展開しない）
                    $this->set_notice('error', ['ZIP 形式のみアップロード可能です（無視したファイル）：' . esc_html($name)]);
                    continue;
                }

                // 一時ファイルに保存
                $zip_filename  = 'upload-' . $index . '-' . basename($name);
                $zip_tmp_path  = $tmp_dir . $zip_filename;

                if (!@move_uploaded_file($tmp_name, $zip_tmp_path)) {
                    $this->set_notice('error', ['一時ファイルの保存に失敗しました: ' . esc_html($name)]);
                    continue;
                }

                // ZIP 展開（この ZIP に含まれるファイルのみの絶対パス配列が返る）
                $extracted = $this->extract_zip($zip_tmp_path, $tmp_dir);
                // ZIP 自体は不要なので削除
                @unlink($zip_tmp_path);

                if ($extracted === false) {
                    $this->set_notice('error', ['ZIP の展開に失敗しました: ' . esc_html($name)]);
                    continue;
                }

                if ($extracted) {
                    $zips_processed++;
                    $all_full_paths = array_merge($all_full_paths, $extracted);
                }
            }

            if ($zips_processed === 0) {
                // 一つも有効な ZIP がなかった
                $this->recursive_rmdir($tmp_dir);
                $this->set_notice('error', ['有効な ZIP ファイルがアップロードされませんでした。ZIP 形式のみ対応しています。']);
                wp_safe_redirect(admin_url('tools.php?page=ou-mu-installer'));
                exit;
            }

            if (empty($all_full_paths)) {
                $this->recursive_rmdir($tmp_dir);
                $this->set_notice('error', ['ZIP 内にコピー対象となるファイルがありませんでした。']);
                wp_safe_redirect(admin_url('tools.php?page=ou-mu-installer'));
                exit;
            }

            // 相対パスに変換（tmp_dir 基準）
            $rel_files        = [];
            $tmp_dir_norm     = rtrim($tmp_dir, '/\\') . DIRECTORY_SEPARATOR;
            $tmp_base_for_rel = str_replace(['\\'], '/', $tmp_dir_norm); // ★ ベース側も / に統一

            foreach ($all_full_paths as $path) {
                if (!is_file($path)) {
                    continue;
                }

                // フルパス側も / に統一
                $normalized = str_replace(['\\'], '/', $path);

                // ベース部分を削って相対パスに
                $rel = str_replace($tmp_base_for_rel, '', $normalized);
                $rel = ltrim($rel, '/');

                if ($rel !== '') {
                    $rel_files[] = $rel;
                }
            }

            $rel_files = array_values(array_unique($rel_files));

            if (empty($rel_files)) {
                $this->recursive_rmdir($tmp_dir);
                $this->set_notice('error', ['ZIP 内に有効なファイルがありませんでした。']);
                wp_safe_redirect(admin_url('tools.php?page=ou-mu-installer'));
                exit;
            }

            // 保留中情報として保存（tmp_dir は OS 依存でOK：後続ではそのまま使う）
            $pending = [
                'tmp_dir'   => $tmp_dir_norm,
                'files'     => $rel_files,
                'timestamp' => time(),
            ];
            update_option(self::OPTION_PENDING, $pending, false);

            $this->set_notice('success', ['ZIP の展開が完了しました。インストール内容を確認してください。']);
            wp_safe_redirect(admin_url('tools.php?page=ou-mu-installer'));
            exit;
        }

        /**
         * ZIP 展開
         *
         * @param string $zip_path   ZIP ファイルの絶対パス
         * @param string $target_dir 展開先ディレクトリ（末尾にスラッシュがなくても良い）
         * @return array|false 展開されたファイルの絶対パス配列（この ZIP 分のみ）／失敗時 false
         */
        private function extract_zip($zip_path, $target_dir)
        {
            $zip = new ZipArchive();
            if ($zip->open($zip_path) !== true) {
                return false;
            }

            $file_names = [];

            // ZIP 内のエントリを走査してファイルのみ取得
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                if (!$stat || empty($stat['name'])) {
                    continue;
                }
                $name = $stat['name'];

                // ディレクトリ（末尾スラッシュ）ならスキップ
                if (substr($name, -1) === '/') {
                    continue;
                }

                $file_names[] = $name;
            }

            if (empty($file_names)) {
                $zip->close();
                // そもそもファイルが入っていない ZIP
                return [];
            }

            // 実際に展開
            $target_dir_norm = rtrim($target_dir, '/\\') . DIRECTORY_SEPARATOR;

            if (!$zip->extractTo($target_dir_norm)) {
                $zip->close();
                return false;
            }

            $zip->close();

            // この ZIP に含まれていたファイルの絶対パスだけを構築して返す
            $paths = [];

            foreach ($file_names as $name) {
                $normalized = str_replace(['\\'], '/', $name);
                $normalized = ltrim($normalized, '/');

                // ZIP 内の相対パスをそのまま target_dir 配下にマッピング
                $full_path = $target_dir_norm . str_replace('/', DIRECTORY_SEPARATOR, $normalized);

                if (is_file($full_path)) {
                    $paths[] = $full_path;
                }
            }

            return $paths;
        }

        /**
         * インストール実行処理（確認画面での選択後）
         */
        public function handle_install()
        {
            if (!current_user_can('manage_options')) {
                wp_die('権限がありません。');
            }

            check_admin_referer('ou_mu_install_confirm');

            $pending = get_option(self::OPTION_PENDING);
            if (!is_array($pending) || empty($pending['tmp_dir']) || empty($pending['files']) || !is_dir($pending['tmp_dir'])) {
                $this->set_notice('error', ['保留中のインストール情報が見つからないか、一時ディレクトリが存在しません。最初からやり直してください。']);
                $this->cleanup_pending();
                wp_safe_redirect(admin_url('tools.php?page=ou-mu-installer'));
                exit;
            }

            $tmp_dir = rtrim($pending['tmp_dir'], '/\\') . DIRECTORY_SEPARATOR;
            $files   = (array) $pending['files'];

            $mu_dir  = WP_CONTENT_DIR . '/mu-plugins';
            $mu_base = rtrim($mu_dir, '/\\') . DIRECTORY_SEPARATOR;

            if (!is_dir($mu_dir) && !wp_mkdir_p($mu_dir)) {
                $this->set_notice('error', ['mu-plugins ディレクトリを作成できませんでした。']);
                $this->cleanup_pending();
                wp_safe_redirect(admin_url('tools.php?page=ou-mu-installer'));
                exit;
            }

            $actions = isset($_POST['action_mode']) && is_array($_POST['action_mode'])
                ? wp_unslash($_POST['action_mode'])
                : [];

            $files_input = isset($_POST['files']) ? (array) wp_unslash($_POST['files']) : [];

            $count_new       = 0;
            $count_overwrite = 0;
            $count_backup    = 0;
            $count_skip      = 0;
            $errors          = [];

            foreach ($files_input as $rel) {
                $rel = ltrim(str_replace(['\\'], '/', $rel), '/');

                if (!in_array($rel, $files, true)) {
                    // 不正な入力は無視
                    continue;
                }

                $mode = isset($actions[$rel]) ? $actions[$rel] : 'install';

                $src = $tmp_dir . $rel;
                $dst = $mu_base . $rel;

                if (!file_exists($src) || !is_readable($src)) {
                    $errors[] = 'ソースファイルが存在しません: ' . esc_html($rel);
                    continue;
                }

                // コピー先ディレクトリを作成
                $dst_dir = dirname($dst);
                if (!is_dir($dst_dir) && !wp_mkdir_p($dst_dir)) {
                    $errors[] = 'コピー先ディレクトリを作成できませんでした: ' . esc_html($dst_dir);
                    continue;
                }

                $exists = file_exists($dst);

                if (!$exists && $mode !== 'install') {
                    // 本来衝突していないはずだが、保守的に install 扱いにする
                    $mode = 'install';
                }

                // スキップ
                if ($mode === 'skip' && $exists) {
                    $count_skip++;
                    continue;
                }

                // 既存ファイルがあり、上書き系の場合
                if ($exists && ($mode === 'overwrite' || $mode === 'backup')) {

                    // overwrite の場合は同一ディレクトリ内で .bak_YYYYMMDDHHMMSS を付けてリネーム
                    if ($mode === 'overwrite') {
                        $backup_name = $dst . '.bak_' . gmdate('YmdHis');
                        if (!@rename($dst, $backup_name)) {
                            $errors[] = '既存ファイルのバックアップに失敗しました（上書き）: ' . esc_html($rel);
                            continue;
                        }
                    }

                    // backup の場合は mu-plugins/_backup/ 以下に退避
                    if ($mode === 'backup') {
                        $backup_dir = $mu_base . '_backup';
                        if (!is_dir($backup_dir) && !wp_mkdir_p($backup_dir)) {
                            $errors[] = 'バックアップディレクトリを作成できませんでした: ' . esc_html($rel);
                            continue;
                        }
                        $backup_name = $backup_dir . '/' . basename($rel) . '.' . gmdate('YmdHis') . '.bak';
                        if (!@copy($dst, $backup_name)) {
                            $errors[] = '既存ファイルのバックアップに失敗しました: ' . esc_html($rel);
                            continue;
                        }
                    }

                    // 新ファイルをコピー
                    if (!@copy($src, $dst)) {
                        $errors[] = 'ファイルのコピーに失敗しました: ' . esc_html($rel);
                        continue;
                    }

                    if ($mode === 'overwrite') {
                        $count_overwrite++;
                    } else {
                        $count_backup++;
                    }

                    continue;
                }

                // 新規インストール（exists = false）
                if (!$exists && $mode === 'install') {
                    if (!@copy($src, $dst)) {
                        $errors[] = '新規ファイルのコピーに失敗しました: ' . esc_html($rel);
                        continue;
                    }
                    $count_new++;
                    continue;
                }

                // その他（想定外モードなど）は保守的にスキップ扱い
                $count_skip++;
            }

            // 一時ディレクトリと保留中データを掃除
            $this->cleanup_pending();

            $messages = [];

            if ($count_new) {
                $messages[] = '新規インストール: ' . intval($count_new) . ' 件';
            }
            if ($count_overwrite) {
                $messages[] = '上書き（.bak_* にリネーム）: ' . intval($count_overwrite) . ' 件';
            }
            if ($count_backup) {
                $messages[] = 'バックアップして上書き（_backup ディレクトリに退避）: ' . intval($count_backup) . ' 件';
            }
            if ($count_skip) {
                $messages[] = 'スキップ: ' . intval($count_skip) . ' 件';
            }

            if ($errors) {
                $messages = array_merge($messages, $errors);
                $this->set_notice('error', $messages);
            } else {
                if (!$messages) {
                    $messages[] = '変更は行われませんでした。';
                }
                $this->set_notice('success', $messages);
            }

            wp_safe_redirect(admin_url('tools.php?page=ou-mu-installer'));
            exit;
        }

        /**
         * MU プラグイン削除処理
         */
        public function handle_delete()
        {
            if (!current_user_can('manage_options')) {
                wp_die('権限がありません。');
            }

            check_admin_referer('ou_mu_delete');

            $file = isset($_POST['file']) ? wp_unslash($_POST['file']) : '';
            $file = ltrim(str_replace(['\\'], '/', $file), '/');

            if ($file === '') {
                $this->set_notice('error', ['削除対象ファイルが指定されていません。']);
                wp_safe_redirect(admin_url('tools.php?page=ou-mu-installer'));
                exit;
            }

            $mu_dir  = WP_CONTENT_DIR . '/mu-plugins';
            $mu_base = rtrim($mu_dir, '/\\') . DIRECTORY_SEPARATOR;

            $target = $mu_base . $file;

            if (!file_exists($target)) {
                $this->set_notice('error', ['対象ファイルが存在しません: ' . esc_html($file)]);
                wp_safe_redirect(admin_url('tools.php?page=ou-mu-installer'));
                exit;
            }

            // パストラバーサル対策：realpath で mu-plugins 配下か確認
            $real_mu_base = realpath($mu_base);
            $real_target  = realpath($target);

            if (!$real_mu_base || !$real_target || strpos($real_target, $real_mu_base) !== 0) {
                $this->set_notice('error', ['不正なパスが指定されました。']);
                wp_safe_redirect(admin_url('tools.php?page=ou-mu-installer'));
                exit;
            }

            if (!@unlink($target)) {
                $this->set_notice('error', ['ファイルの削除に失敗しました: ' . esc_html($file)]);
            } else {
                $this->set_notice('success', ['MU プラグインを削除しました: ' . esc_html($file)]);
            }

            wp_safe_redirect(admin_url('tools.php?page=ou-mu-installer'));
            exit;
        }

        /**
         * 保留中インストール情報の掃除（tmp ディレクトリ & option 削除）
         */
        private function cleanup_pending()
        {
            $pending = get_option(self::OPTION_PENDING);
            if (is_array($pending) && !empty($pending['tmp_dir']) && is_dir($pending['tmp_dir'])) {
                $this->recursive_rmdir($pending['tmp_dir']);
            }
            delete_option(self::OPTION_PENDING);
        }

        /**
         * 再帰的ディレクトリ削除
         *
         * @param string $dir
         */
        private function recursive_rmdir($dir)
        {
            $dir = rtrim($dir, '/\\');
            if (!is_dir($dir)) {
                return;
            }

            $items = scandir($dir);
            if ($items === false) {
                return;
            }

            foreach ($items as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }
                $path = $dir . DIRECTORY_SEPARATOR . $item;
                if (is_dir($path)) {
                    $this->recursive_rmdir($path);
                } else {
                    @unlink($path);
                }
            }
            @rmdir($dir);
        }

        /**
         * 通知をセット（transient 経由）
         *
         * @param string $type    success|error
         * @param array  $messages
         */
        private function set_notice($type, array $messages)
        {
            $data = [
                'type'     => $type === 'error' ? 'error' : 'success',
                'messages' => array_values($messages),
            ];
            set_transient(self::TRANSIENT_NOTICE, $data, 60);
        }

        /**
         * admin_notices に通知を表示
         */
        public function render_notices()
        {
            if (!is_admin()) {
                return;
            }

            global $pagenow;
            if ($pagenow !== 'tools.php') {
                return;
            }
            if (!isset($_GET['page']) || $_GET['page'] !== 'ou-mu-installer') {
                return;
            }

            $data = get_transient(self::TRANSIENT_NOTICE);
            if (!$data || empty($data['messages']) || empty($data['type'])) {
                return;
            }
            delete_transient(self::TRANSIENT_NOTICE);

            $class = $data['type'] === 'error' ? 'notice notice-error' : 'notice notice-success';

            echo '<div class="' . esc_attr($class) . '"><ul>';
            foreach ($data['messages'] as $msg) {
                echo '<li>' . wp_kses_post($msg) . '</li>';
            }
            echo '</ul></div>';
        }
    }
}

// プラグイン起動
new OU_MU_Installer();
