<?php
/**
 * Plugin Name: OfficeUeda - Internal Link Checker (MU)
 * Description: サイト内部のリンク切れ（4xx/5xx/取得エラー）をWP-Cronでバッチ検査し、管理画面で一覧・CSV出力します。
 *              複数クライアントサイトへの配布を前提としています。設定はすべて管理画面から変更可能です。
 * Author: OfficeUEDA
 * Version: 2.0.3
 * License: GPLv2 or later
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) exit;

final class OfficeUeda_Internal_Link_Checker {

  const VERSION          = '2.0.3';
  const DB_VERSION       = '2.0.3';
  const TABLE_RESULTS    = 'ou_link_checker';
  const TABLE_STATE      = 'ou_link_checker_state';
  const OPTION_SETTINGS  = 'ou_lc_settings';
  const OPTION_RUN       = 'ou_lc_run';       // 実行中フラグ等（小さなスカラー値のみ）
  const CRON_HOOK        = 'ou_lc_process_queue';
  const DAILY_CRON_HOOK  = 'ou_lc_daily_kickoff';
  const ADMIN_SLUG       = 'ou-internal-link-checker';
  const BATCH_SIZE       = 25;

  // デフォルト除外ステータス（管理画面で変更可能）
  const MANAGEABLE_STATUSES      = [401, 403, 405, 406, 429, 503, 520, 521, 522, 525, 526];
  const DEFAULT_IGNORED_STATUSES = [401, 403, 405, 406, 429, 503, 520, 521, 522, 525, 526];

  // デフォルト除外パス（apply_filters で追加可）
  const DEFAULT_EXCLUDE_PATHS = [
    '#^/wp-admin(/|$)#',
    '#^/wp-login\.php#',
    '#^/feed(/|$)#',
    '#^/xmlrpc\.php#',
    '#^/wp-json(/|$)#',
    '#^/wp-sitemap#',
    '#^/wp-cron\.php#',
    '#\?p=preview#',
    '#\?preview=true#',
  ];

  /** @var string 自サイトのホスト名（コンストラクタで確定） */
  private $site_host;

  /**
   * プロセスメモリ内URLチェック結果キャッシュ（H-7対応）
   * 同一URLが複数ページから参照されても1回だけHTTPリクエストを発行する
   *
   * @var array<string, array{status: int|null, error: string|null}>
   */
  private $checked_cache = [];

  public function __construct() {
    $this->site_host = (string) wp_parse_url(home_url('/'), PHP_URL_HOST);

    add_action('init',       [$this, 'setup']);
    add_action('admin_menu', [$this, 'add_tools_page']);

    // admin-post ハンドラ
    add_action('admin_post_ou_lc_start',         [$this, 'handle_start_scan']);
    add_action('admin_post_ou_lc_stop',          [$this, 'handle_stop_scan']);
    add_action('admin_post_ou_lc_export',        [$this, 'handle_export_csv']);
    add_action('admin_post_ou_lc_save_settings', [$this, 'handle_save_settings']);

    // Cron フック
    add_action(self::CRON_HOOK,       [$this, 'process_queue']);
    add_action(self::DAILY_CRON_HOOK, [$this, 'daily_kickoff']);

    // WP-CLI
    if (defined('WP_CLI') && WP_CLI) {
      \WP_CLI::add_command('ou-lc', [$this, 'cli_command']);
    }

    register_deactivation_hook(__FILE__, [$this, 'on_deactivate']);
  }

  /* ============================================================
   * SETUP
   * ============================================================ */

  /** DB作成 / Cronスケジュール登録を init タイミングで実施 */
  public function setup() {
    $this->maybe_create_tables();
    $this->register_cron_schedule();
    $this->ensure_minute_cron();

    // 日次Cronは設定が有効なときだけ登録する
    $settings = $this->get_settings();
    if ($settings['daily_enabled'] && !wp_next_scheduled(self::DAILY_CRON_HOOK)) {
      wp_schedule_event(time() + 300, 'daily', self::DAILY_CRON_HOOK);
    } elseif (!$settings['daily_enabled']) {
      $ts = wp_next_scheduled(self::DAILY_CRON_HOOK);
      if ($ts) wp_unschedule_event($ts, self::DAILY_CRON_HOOK);
    }
  }

  /** Cron スケジュール「minute」を追加 */
  private function register_cron_schedule() {
    add_filter('cron_schedules', function ($schedules) {
      if (!isset($schedules['minute'])) {
        $schedules['minute'] = ['interval' => 60, 'display' => 'Every Minute'];
      }
      return $schedules;
    });
  }

  /** 実行中なら毎分バッチを予約 */
  private function ensure_minute_cron() {
    $run = $this->get_run();
    if (!empty($run['running']) && !wp_next_scheduled(self::CRON_HOOK)) {
      wp_schedule_event(time() + 60, 'minute', self::CRON_HOOK);
    }
  }

  public function on_deactivate() {
    $this->unschedule_all_crons();
  }

  /* ============================================================
   * DB
   * ============================================================ */

  /**
   * テーブルを作成 / スキーマ変更を適用する。
   *
   * C-3+C-4 対応:
   *   - status: SMALLINT UNSIGNED NOT NULL DEFAULT 0（取得失敗=0 のセマンティクス）
   *   - url_hash: CHAR(32) NOT NULL（MD5ハッシュ）
   *   - UNIQUE KEY を url_hash + status ベースに変更（NULLによるUPSERT不発 + 長尺URL誤UPSERTを両方解消）
   *
   * High-1 対応（v2.0.3）:
   *   マイグレーションを以下の順序で実施し、3つの失敗パターンを排除する。
   *   (A) MySQL strict mode で status=NULL が NOT NULL DEFAULT 0 へ変換できず dbDelta が失敗する問題
   *       → Step 1: dbDelta 前に NULL → 0 へ UPDATE しておく
   *   (B) (url_hash, status) が同一の重複行が存在すると ADD UNIQUE が Duplicate entry で失敗する問題
   *       → Step 5: UNIQUE 付与前に重複行を除去する
   *   (C) マイグレーション途中で失敗しても update_option で version が昇格し、
   *       次回 init で修復機会を永久に失う問題
   *       → $migration_success フラグを用意し、成功時のみ version を更新する
   *
   * 動作保証パターン:
   *   - 新規インストール（テーブルなし）: Step 1 は SHOW TABLES でスキップ、dbDelta で CREATE
   *   - v1.1.0 からの直接アップグレード: NULL→0 変換→旧INDEX削除→url_hash埋め戻し→重複除去→UNIQUE付与
   *   - v2.0.2 途中失敗環境: installed が '2.0.3' 未満なので全 Step を再走行できる（自己回復性）
   */
  private function maybe_create_tables(): void {
    global $wpdb;

    $installed = get_option('ou_lc_db_version', '0');

    // 既に最新バージョンなら dbDelta も含めて早期 return（毎 init の無駄排除）
    if (version_compare($installed, self::DB_VERSION, '>=')) {
      return;
    }

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $charset   = $wpdb->get_charset_collate();
    $t_results = $wpdb->prefix . self::TABLE_RESULTS;
    $t_state   = $wpdb->prefix . self::TABLE_STATE;

    // マイグレーション成否フラグ（false のままなら version を据え置きにして次 init で再試行）
    $migration_success = true;

    // === Step 1: 旧 NULL status を 0 に変換（dbDelta の NOT NULL 化失敗を回避） ===
    // 旧テーブルが存在する環境のみ実行（新規インストールはテーブル未作成なのでスキップ）
    if (version_compare($installed, '2.0.3', '<')) {
      $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $t_results));
      if ($exists === $t_results) {
        $wpdb->query("UPDATE `{$t_results}` SET status = 0 WHERE status IS NULL");
      }
    }

    // === Step 2: dbDelta で CREATE/ALTER（新規カラム追加・型変更を自動適用） ===
    $sql_results = "CREATE TABLE `{$t_results}` (
      `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      `url`        VARCHAR(2048)   NOT NULL DEFAULT '',
      `url_hash`   CHAR(32)        NOT NULL DEFAULT '',
      `referer`    VARCHAR(2048)   NULL,
      `status`     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
      `type`       VARCHAR(16)     NOT NULL DEFAULT 'href',
      `error`      VARCHAR(512)    NULL,
      `checked_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `hash_status` (`url_hash`, `status`),
      KEY `status_idx`     (`status`),
      KEY `checked_at_idx` (`checked_at`)
    ) {$charset};";

    // キュー / 訪問済み管理テーブル（wp_options から切り出し）
    $sql_state = "CREATE TABLE `{$t_state}` (
      `id`       BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      `kind`     ENUM('queue','visited') NOT NULL,
      `url_hash` CHAR(32)        NOT NULL,
      `url`      VARCHAR(2048)   NOT NULL DEFAULT '',
      PRIMARY KEY (`id`),
      UNIQUE KEY `kind_hash` (`kind`, `url_hash`)
    ) {$charset};";

    dbDelta($sql_results);
    dbDelta($sql_state);

    // === Step 3 〜 7: v1.x → v2.0.3 の手動マイグレーション ===
    // dbDelta では旧INDEX削除・url_hash埋め戻し・重複除去・UNIQUE補完を処理できないため手動で実施
    if (version_compare($installed, '2.0.3', '<')) {

      // Step 3: 旧 UNIQUE KEY `url` を明示的に削除（dbDelta では DROP できない）
      $idx = $wpdb->get_results("SHOW INDEX FROM `{$t_results}`", ARRAY_A);
      foreach ((array) $idx as $row) {
        if (isset($row['Key_name']) && $row['Key_name'] === 'url') {
          $wpdb->query("ALTER TABLE `{$t_results}` DROP INDEX `url`");
          break;
        }
      }

      // Step 4: 既存レコードの url_hash を埋め戻し（DEFAULT '' のままだと全件衝突する）
      $wpdb->query(
        "UPDATE `{$t_results}` SET url_hash = MD5(url) WHERE url_hash = '' OR url_hash IS NULL"
      );

      // Step 5: 新 UNIQUE 付与の前に重複行を除去（ADD UNIQUE が Duplicate entry で失敗するのを防ぐ）
      // (url_hash, status) が同一の行は id が小さい方（古い方）を削除し、新しい方を残す
      $wpdb->query("
        DELETE t1 FROM `{$t_results}` t1
        INNER JOIN `{$t_results}` t2
        WHERE t1.id < t2.id
          AND t1.url_hash = t2.url_hash
          AND t1.status = t2.status
      ");

      // Step 6: hash_status UNIQUE KEY が未付与なら補完
      $have_hash_status = $wpdb->get_results(
        "SHOW INDEX FROM `{$t_results}` WHERE Key_name='hash_status'",
        ARRAY_A
      );
      if (empty($have_hash_status)) {
        $result = $wpdb->query(
          "ALTER TABLE `{$t_results}` ADD UNIQUE KEY `hash_status` (`url_hash`, `status`)"
        );
        if ($result === false) {
          error_log(
            '[OU LinkChecker] DB migration failed: ADD UNIQUE hash_status failed. Error: '
            . $wpdb->last_error
          );
          $migration_success = false;
        }
      }

      // Step 7: 最終検証 — hash_status UNIQUE が実在することを確認
      if ($migration_success) {
        $final_check = $wpdb->get_results(
          "SHOW INDEX FROM `{$t_results}` WHERE Key_name='hash_status'",
          ARRAY_A
        );
        if (empty($final_check)) {
          error_log(
            '[OU LinkChecker] DB migration verification failed: hash_status UNIQUE KEY not present'
          );
          $migration_success = false;
        }
      }
    }

    // Step 8: マイグレーション成功時のみ DB_VERSION を更新
    // 失敗時は version を据え置きにして次 init で再試行できる（自己回復性）
    if ($migration_success) {
      update_option('ou_lc_db_version', self::DB_VERSION, false);
    }
  }

  /* ============================================================
   * 設定（wp_options）
   * ============================================================ */

  /**
   * 設定を取得。未設定はデフォルト値を返す。
   *
   * @return array{
   *   detect_statuses: int[],
   *   notify_to: string,
   *   notify_bcc: string[],
   *   daily_enabled: bool,
   *   daily_limit: int,
   *   timeout: int,
   *   max_retry: int,
   * }
   */
  private function get_settings(): array {
    $saved    = get_option(self::OPTION_SETTINGS, []);
    $admin_to = get_option('admin_email', '');

    // detect_statuses
    $detect = isset($saved['detect_statuses']) && is_array($saved['detect_statuses'])
      ? array_values(array_intersect(array_map('intval', $saved['detect_statuses']), self::MANAGEABLE_STATUSES))
      : [];

    // notify_to
    $notify_to = isset($saved['notify_to']) && is_string($saved['notify_to'])
      ? sanitize_email(trim($saved['notify_to']))
      : $admin_to;
    if (!$notify_to) $notify_to = $admin_to;

    // notify_bcc（カンマ区切り → 配列）
    $bcc_raw = isset($saved['notify_bcc']) && is_string($saved['notify_bcc']) ? $saved['notify_bcc'] : '';
    $notify_bcc = array_values(array_filter(
      array_map(fn($e) => sanitize_email(trim($e)), explode(',', $bcc_raw)),
      fn($e) => (bool) $e
    ));

    return [
      'detect_statuses' => $detect,
      'notify_to'       => $notify_to,
      'notify_bcc'      => $notify_bcc,
      'daily_enabled'   => !empty($saved['daily_enabled']),
      'daily_limit'     => isset($saved['daily_limit']) ? max(0, intval($saved['daily_limit'])) : 2000,
      'timeout'         => isset($saved['timeout'])     ? max(5, min(60, intval($saved['timeout'])))  : 10,
      // B-3: 0 または 1 のみ有効（既存保存値が 2〜5 の場合も 1 に clamp して返す）
      'max_retry'       => isset($saved['max_retry'])   ? min(1, max(0, intval($saved['max_retry']))) : 1,
    ];
  }

  private function save_settings(array $raw): void {
    // detect_statuses
    $detect = isset($raw['detect_statuses']) && is_array($raw['detect_statuses'])
      ? array_values(array_intersect(array_map('intval', $raw['detect_statuses']), self::MANAGEABLE_STATUSES))
      : [];

    // notify_to
    $notify_to = sanitize_email(trim($raw['notify_to'] ?? ''));

    // notify_bcc（カンマ区切り文字列として保存）
    $bcc_str = isset($raw['notify_bcc']) && is_string($raw['notify_bcc']) ? $raw['notify_bcc'] : '';

    update_option(self::OPTION_SETTINGS, [
      'detect_statuses' => $detect,
      'notify_to'       => $notify_to,
      'notify_bcc'      => $bcc_str,
      'daily_enabled'   => !empty($raw['daily_enabled']),
      'daily_limit'     => max(0, intval($raw['daily_limit'] ?? 2000)),
      'timeout'         => max(5, min(60, intval($raw['timeout'] ?? 10))),
      // B-3: 0 または 1 のみ有効
      'max_retry'       => min(1, max(0, intval($raw['max_retry'] ?? 1))),
    ], false);
  }

  /**
   * 現在の設定から「除外するステータスコード」を返す。
   * apply_filters で外部拡張可能。
   *
   * @return int[]
   */
  private function get_ignored_statuses(): array {
    $detect  = $this->get_settings()['detect_statuses'];
    $ignored = array_values(array_diff(self::DEFAULT_IGNORED_STATUSES, $detect));
    return apply_filters('ou_lc_ignored_statuses', array_unique(array_map('intval', $ignored)));
  }

  /* ============================================================
   * 実行状態（wp_options の小さなスカラー値）
   * ============================================================ */

  /**
   * 実行状態を取得。
   * queue / visited はDBで管理するため、ここにはスカラー値のみ格納する。
   *
   * @return array{running: bool, started: string|null, limit: int, report_sent: bool, report_retry: int}
   */
  private function get_run(): array {
    $default = [
      'running'      => false,
      'started'      => null,
      'limit'        => 0,
      'report_sent'  => false,
      'report_retry' => 0,
    ];
    return wp_parse_args(get_option(self::OPTION_RUN, []), $default);
  }

  private function save_run(array $run): void {
    update_option(self::OPTION_RUN, $run, false);
  }

  /* ============================================================
   * キュー / 訪問済み（DB管理）
   * ============================================================ */

  /** キューに URL を追加（重複は IGNORE） */
  private function enqueue(string $url): void {
    global $wpdb;
    $table = $wpdb->prefix . self::TABLE_STATE;
    $wpdb->query($wpdb->prepare(
      "INSERT IGNORE INTO `{$table}` (kind, url_hash, url) VALUES ('queue', %s, %s)",
      md5($url), $url
    ));
  }

  /** 訪問済みとしてマーク */
  private function mark_visited(string $url): void {
    global $wpdb;
    $table = $wpdb->prefix . self::TABLE_STATE;
    $wpdb->query($wpdb->prepare(
      "INSERT IGNORE INTO `{$table}` (kind, url_hash, url) VALUES ('visited', %s, %s)",
      md5($url), $url
    ));
  }

  /** 訪問済みかどうか確認 */
  private function is_visited(string $url): bool {
    global $wpdb;
    $table = $wpdb->prefix . self::TABLE_STATE;
    return (bool) $wpdb->get_var($wpdb->prepare(
      "SELECT 1 FROM `{$table}` WHERE kind = 'visited' AND url_hash = %s LIMIT 1",
      md5($url)
    ));
  }

  /** キューから先頭1件を取り出す（取り出したら削除）。なければ null。 */
  private function dequeue(): ?string {
    global $wpdb;
    $table = $wpdb->prefix . self::TABLE_STATE;

    // 先頭のキューエントリを取得
    $row = $wpdb->get_row(
      "SELECT id, url FROM `{$table}` WHERE kind = 'queue' ORDER BY id ASC LIMIT 1",
      ARRAY_A
    );
    if (!$row) return null;

    $wpdb->delete($table, ['id' => intval($row['id'])], ['%d']);
    return (string) $row['url'];
  }

  /** キューの残件数 */
  private function queue_count(): int {
    global $wpdb;
    $table = $wpdb->prefix . self::TABLE_STATE;
    return (int) $wpdb->get_var("SELECT COUNT(*) FROM `{$table}` WHERE kind = 'queue'");
  }

  /** 訪問済みの件数 */
  private function visited_count(): int {
    global $wpdb;
    $table = $wpdb->prefix . self::TABLE_STATE;
    return (int) $wpdb->get_var("SELECT COUNT(*) FROM `{$table}` WHERE kind = 'visited'");
  }

  /**
   * キュー / 訪問済みを全削除（スキャン初期化時）。
   * H-8: TRUNCATE（DDL）から DELETE（DML）に変更。
   * TRUNCATE はトランザクション非対応かつ一部MySQLフォークで挙動が異なるため。
   */
  private function clear_state_table(): void {
    global $wpdb;
    $table = $wpdb->prefix . self::TABLE_STATE;
    $wpdb->query("DELETE FROM `{$table}` WHERE kind IN ('queue','visited')");
  }

  /* ============================================================
   * スキャン初期化ヘルパー
   * ============================================================ */

  /**
   * スキャン開始状態を初期化する。
   *
   * @param string $start_url  走査開始URL（正規化済みであること）
   * @param int    $limit      URL上限（0=無制限）
   */
  private function init_scan(string $start_url, int $limit): void {
    // テーブルをリセット
    $this->clear_state_table();

    // プロセスキャッシュもクリア（H-7）
    $this->checked_cache = [];

    // 最初のURLをキューに積む
    $this->enqueue($start_url);

    // 実行状態を保存
    $this->save_run([
      'running'      => true,
      'started'      => current_time('mysql'),
      'limit'        => $limit,
      'report_sent'  => false,
      'report_retry' => 0,
    ]);

    // 90日以上前の古いレコードを削除
    $this->purge_old_records();

    // 毎分バッチを予約
    if (!wp_next_scheduled(self::CRON_HOOK)) {
      wp_schedule_event(time() + 60, 'minute', self::CRON_HOOK);
    }
  }

  /* ============================================================
   * 管理画面
   * ============================================================ */

  public function add_tools_page(): void {
    add_management_page(
      '内部リンクチェッカー',
      '内部リンクチェッカー',
      'manage_options',
      self::ADMIN_SLUG,
      [$this, 'render_tools_page']
    );
  }

  public function render_tools_page(): void {
    if (!current_user_can('manage_options')) return;

    $run      = $this->get_run();
    $settings = $this->get_settings();
    $nonce    = wp_create_nonce('ou_lc_actions');

    // 直近エラー100件（除外ステータスを除く）
    $errors = $this->get_recent_errors(100);
    ?>
    <div class="wrap">
      <h1>内部リンクチェッカー v<?php echo esc_html(self::VERSION); ?></h1>
      <p>サイト内部のページ・画像・CSS・JSの参照先を検査し、エラー（4xx/5xx/取得失敗）のみを記録・表示します。<br>
         複数サイトへの配布を想定しています。設定はすべて「通知・スキャン設定」から変更してください。</p>

      <?php if (isset($_GET['updated'])): ?>
        <div class="notice notice-success is-dismissible"><p>設定を保存しました。</p></div>
      <?php endif; ?>

      <!-- 状態表示 -->
      <h2>実行状態</h2>
      <table class="widefat" style="max-width:760px;">
        <tbody>
          <tr><th style="width:180px;">状態</th>
              <td><?php echo $run['running'] ? '<strong style="color:#d63638;">実行中</strong>' : '停止中'; ?></td></tr>
          <tr><th>開始時刻</th>
              <td><?php echo esc_html($run['started'] ?: '-'); ?></td></tr>
          <tr><th>キュー残件数</th>
              <td><?php echo number_format_i18n($this->queue_count()); ?></td></tr>
          <tr><th>処理済み件数</th>
              <td><?php echo number_format_i18n($this->visited_count()); ?></td></tr>
          <tr><th>URL上限</th>
              <td><?php echo $run['limit'] ? esc_html(number_format_i18n($run['limit'])) : '制限なし'; ?></td></tr>
        </tbody>
      </table>

      <!-- 操作ボタン -->
      <div style="margin-top:16px;display:flex;gap:8px;flex-wrap:wrap;align-items:flex-start;">

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
          <input type="hidden" name="action"    value="ou_lc_start">
          <input type="hidden" name="_wpnonce"  value="<?php echo esc_attr($nonce); ?>">
          <fieldset style="border:1px solid #ccd0d4;padding:12px;background:#fff;">
            <legend>スキャン開始</legend>
            <label>開始URL（省略時: サイトトップ）<br>
              <input type="url" name="start_url" value="<?php echo esc_attr(home_url('/')); ?>" size="55">
            </label><br><br>
            <label>URL上限（0=無制限・大規模サイトは2000程度を推奨）<br>
              <input type="number" name="limit" value="<?php echo esc_attr($settings['daily_limit']); ?>" min="0" step="1" style="width:120px;">
            </label><br><br>
            <?php submit_button('スキャン開始', 'primary', '', false); ?>
          </fieldset>
        </form>

        <div style="display:flex;flex-direction:column;gap:8px;">
          <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action"   value="ou_lc_stop">
            <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
            <?php submit_button(
              '停止 / キュー破棄',
              'secondary', '', false,
              ['onclick' => "return confirm('停止してキューを破棄します。よろしいですか？');"]
            ); ?>
          </form>

          <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action"   value="ou_lc_export">
            <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
            <?php submit_button('CSVエクスポート（エラーのみ）', 'secondary', '', false); ?>
          </form>
        </div>
      </div>

      <!-- 検出対象ステータス設定 -->
      <h2 style="margin-top:32px;">検出対象ステータス</h2>
      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action"   value="ou_lc_save_settings">
        <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
        <input type="hidden" name="tab"      value="statuses">
        <fieldset style="max-width:760px;padding:12px;border:1px solid #ccd0d4;background:#fff;">
          <legend>デフォルトは除外。検出したいコードにチェックしてください。</legend>
          <?php
          $detect = $settings['detect_statuses'];
          foreach (self::MANAGEABLE_STATUSES as $code) {
            $checked = in_array($code, $detect, true) ? 'checked' : '';
            printf(
              '<label style="display:inline-block;min-width:300px;margin:6px 12px 6px 0;">
                <input type="checkbox" name="detect_statuses[]" value="%1$d" %3$s> %2$s
              </label>',
              $code,
              esc_html($this->status_label($code)),
              $checked
            );
          }
          ?>
          <div style="margin-top:8px;"><?php submit_button('設定を保存', 'secondary', '', false); ?></div>
          <p class="description">現在の除外ステータス: <?php
            $ign = $this->get_ignored_statuses();
            echo $ign ? esc_html(implode(', ', $ign)) : 'なし（全ステータスを検出）';
          ?></p>
        </fieldset>
      </form>

      <!-- 通知・スキャン設定 -->
      <h2 style="margin-top:24px;">通知・スキャン設定</h2>
      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action"   value="ou_lc_save_settings">
        <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
        <input type="hidden" name="tab"      value="notify">
        <table class="form-table" style="max-width:760px;">
          <tr>
            <th><label for="notify_to">通知先メール（To）</label></th>
            <td>
              <input type="email" id="notify_to" name="notify_to"
                     value="<?php echo esc_attr($settings['notify_to']); ?>" class="regular-text">
              <p class="description">未設定時はWordPress管理者メールアドレスを使用します。</p>
            </td>
          </tr>
          <tr>
            <th><label for="notify_bcc">BCC（カンマ区切りで複数可）</label></th>
            <td>
              <?php
              // 設定値（sanitize_email後の配列）をカンマ区切り文字列に戻して表示
              $bcc_raw = get_option(self::OPTION_SETTINGS, []);
              $bcc_display = isset($bcc_raw['notify_bcc']) && is_string($bcc_raw['notify_bcc'])
                ? $bcc_raw['notify_bcc'] : '';
              ?>
              <input type="text" id="notify_bcc" name="notify_bcc"
                     value="<?php echo esc_attr($bcc_display); ?>" class="large-text">
              <p class="description">例: admin@example.com, dev@example.com</p>
            </td>
          </tr>
          <tr>
            <th>日次自動スキャン</th>
            <td>
              <label>
                <input type="checkbox" name="daily_enabled" value="1"
                  <?php checked($settings['daily_enabled']); ?>>
                有効にする（デフォルト: <strong>無効</strong>）
              </label>
              <p class="description">有効にすると毎日1回自動でスキャンを開始し、エラーがあればメール通知します。</p>
            </td>
          </tr>
          <tr>
            <th><label for="daily_limit">日次スキャンのURL上限</label></th>
            <td>
              <input type="number" id="daily_limit" name="daily_limit"
                     value="<?php echo esc_attr($settings['daily_limit']); ?>"
                     min="0" step="100" style="width:120px;">
              <span class="description"> URL（0=無制限、デフォルト: 2000）</span>
            </td>
          </tr>
          <tr>
            <th><label for="timeout">タイムアウト（秒）</label></th>
            <td>
              <input type="number" id="timeout" name="timeout"
                     value="<?php echo esc_attr($settings['timeout']); ?>"
                     min="5" max="60" step="1" style="width:80px;">
              <span class="description"> 秒（デフォルト: 10、範囲: 5〜60）</span>
            </td>
          </tr>
          <tr>
            <th><label for="max_retry">リトライ回数</label></th>
            <td>
              <input type="number" id="max_retry" name="max_retry"
                     value="<?php echo esc_attr($settings['max_retry']); ?>"
                     min="0" max="1" step="1" style="width:80px;">
              <span class="description"> 回（デフォルト: 1、範囲: 0〜1）</span>
              <p class="description">※ 現バージョンでは 0 または 1 のみ有効（Cron タイムアウト回避のため）</p>
            </td>
          </tr>
        </table>
        <?php submit_button('通知・スキャン設定を保存'); ?>
      </form>

      <!-- エラー一覧 -->
      <h2 style="margin-top:24px;">直近のエラー（最大100件・除外ステータス反映）</h2>
      <table class="widefat">
        <thead>
          <tr>
            <th style="width:120px;">日時</th>
            <th>リンク先URL</th>
            <th style="width:70px;">ステータス</th>
            <th>参照元 (Referer)</th>
            <th style="width:60px;">タイプ</th>
            <th>エラー</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$errors): ?>
            <tr><td colspan="6">記録されたエラーはありません（除外設定を確認してください）。</td></tr>
          <?php else: foreach ($errors as $row): ?>
            <tr>
              <td><?php echo esc_html(wp_date('Y-m-d H:i', strtotime($row['checked_at']))); ?></td>
              <td><code style="word-break:break-all;"><?php echo esc_html($row['url']); ?></code></td>
              <td><?php
                // C-3+C-4: status=0 は取得失敗を表す（旧来 NULL だったものと同義）
                $st = (int)$row['status'];
                echo $st === 0 ? 'ERR' : esc_html((string)$st);
              ?></td>
              <td><code style="word-break:break-all;"><?php echo esc_html((string)($row['referer'] ?? '')); ?></code></td>
              <td><?php echo esc_html($row['type']); ?></td>
              <td><?php echo esc_html(mb_strimwidth((string)($row['error'] ?? ''), 0, 200, '...')); ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
    <?php
  }

  /* ============================================================
   * admin-post ハンドラ
   * ============================================================ */

  public function handle_start_scan(): void {
    if (!current_user_can('manage_options')) wp_die('Forbidden', 403);
    check_admin_referer('ou_lc_actions');

    // 開始URLの検証（SSRF対策: 自サイト内URLに強制）
    $raw_url   = isset($_POST['start_url']) ? wp_unslash($_POST['start_url']) : '';
    $start_url = $this->safe_start_url($raw_url);

    $limit = isset($_POST['limit']) ? max(0, intval($_POST['limit'])) : 0;

    $this->init_scan($start_url, $limit);

    wp_safe_redirect(admin_url('tools.php?page=' . self::ADMIN_SLUG));
    exit;
  }

  public function handle_stop_scan(): void {
    if (!current_user_can('manage_options')) wp_die('Forbidden', 403);
    check_admin_referer('ou_lc_actions');

    $this->stop_scan();

    wp_safe_redirect(admin_url('tools.php?page=' . self::ADMIN_SLUG));
    exit;
  }

  public function handle_export_csv(): void {
    if (!current_user_can('manage_options')) wp_die('Forbidden', 403);
    check_admin_referer('ou_lc_actions');

    $rows = $this->get_all_errors();

    nocache_headers();
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename=ou_link_errors_' . wp_date('Ymd_His') . '.csv');

    $out = fopen('php://output', 'w');
    // UTF-8 BOM（Excelでの文字化け対策）
    fprintf($out, "\xEF\xBB\xBF");
    fputcsv($out, ['checked_at', 'url', 'status', 'referer', 'type', 'error']);

    foreach ($rows as $r) {
      // C-3+C-4: status=0 は取得失敗を表す（旧来 NULL だったものと同義）
      $st = (int)($r['status'] ?? 0);
      fputcsv($out, [
        $this->csv_safe((string)($r['checked_at'] ?? '')),
        $this->csv_safe((string)($r['url'] ?? '')),
        $st === 0 ? 'ERR' : $st,
        $this->csv_safe((string)($r['referer'] ?? '')),
        $this->csv_safe((string)($r['type'] ?? '')),
        $this->csv_safe((string)($r['error'] ?? '')),
      ]);
    }
    fclose($out);
    exit;
  }

  public function handle_save_settings(): void {
    if (!current_user_can('manage_options')) wp_die('Forbidden', 403);
    check_admin_referer('ou_lc_actions');

    $tab = isset($_POST['tab']) && is_scalar($_POST['tab']) ? (string)$_POST['tab'] : '';

    // 保存対象を tab で分岐（タブをまたいだ設定の上書き防止）
    $current_saved = get_option(self::OPTION_SETTINGS, []);

    if ($tab === 'statuses') {
      $detect = isset($_POST['detect_statuses']) && is_array($_POST['detect_statuses'])
        ? (array)$_POST['detect_statuses'] : [];
      $current_saved['detect_statuses'] = array_values(array_intersect(
        array_map('intval', $detect),
        self::MANAGEABLE_STATUSES
      ));
      update_option(self::OPTION_SETTINGS, $current_saved, false);

    } elseif ($tab === 'notify') {
      $notify_to  = sanitize_email(trim(wp_unslash($_POST['notify_to'] ?? '')));
      $notify_bcc = sanitize_textarea_field(wp_unslash($_POST['notify_bcc'] ?? ''));
      $current_saved['notify_to']     = $notify_to;
      $current_saved['notify_bcc']    = $notify_bcc;
      $current_saved['daily_enabled'] = !empty($_POST['daily_enabled']);
      $current_saved['daily_limit']   = max(0, intval($_POST['daily_limit'] ?? 2000));
      $current_saved['timeout']       = max(5, min(60, intval($_POST['timeout'] ?? 10)));
      // B-3: max_retry は 0 または 1 のみ有効（Cron タイムアウト回避のため min(1,...) に clamp）
      $current_saved['max_retry']     = min(1, max(0, intval($_POST['max_retry'] ?? 1)));
      update_option(self::OPTION_SETTINGS, $current_saved, false);

      // 日次Cronを設定に応じて登録/解除
      if ($current_saved['daily_enabled']) {
        if (!wp_next_scheduled(self::DAILY_CRON_HOOK)) {
          wp_schedule_event(time() + 300, 'daily', self::DAILY_CRON_HOOK);
        }
      } else {
        $ts = wp_next_scheduled(self::DAILY_CRON_HOOK);
        if ($ts) wp_unschedule_event($ts, self::DAILY_CRON_HOOK);
      }
    }

    wp_safe_redirect(admin_url('tools.php?page=' . self::ADMIN_SLUG . '&updated=1'));
    exit;
  }

  /* ============================================================
   * Cron
   * ============================================================ */

  /** 日次キックオフ: 設定が有効かつ停止中のときのみ開始 */
  public function daily_kickoff(): void {
    $settings = $this->get_settings();
    if (!$settings['daily_enabled']) return;

    $run = $this->get_run();
    if (!empty($run['running'])) return; // 実行中なら何もしない

    $start_url = $this->normalize_url(home_url('/'));
    $this->init_scan($start_url, $settings['daily_limit']);
  }

  /**
   * 毎分バッチ: キューを BATCH_SIZE 件処理。
   *
   * C-1 対応（方針B）:
   *   - set_time_limit(0) でPHP実行時間上限を解除
   *   - ウォッチドッグ: deadline = 現在時刻 + 50秒。超過したら while を break し
   *     キューは state テーブルに残したまま次Cronに引き継ぐ（60秒制限内に必ず収まる）
   *   - sleep / usleep によるブロックは一切使用しない（非同期リトライ方式）
   *   - 自DDoS防止の usleep(200000) は維持
   *
   * B-2: $cli_mode=true のとき deadline を PHP_INT_MAX に設定してウォッチドッグを無効化する。
   *   WP-CLI は長時間連続実行が正当ユースケースのため、50秒で break すると
   *   無駄な再進入ループになる。
   */
  public function process_queue(bool $cli_mode = false): void {
    $run = $this->get_run();
    if (empty($run['running'])) return;

    // Cron が強制終了されないよう実行時間制限を解除
    @set_time_limit(0);

    // ウォッチドッグ: WP-Cron は 50秒以内、WP-CLI は無制限
    $deadline  = $cli_mode ? PHP_INT_MAX : (microtime(true) + 50);
    $settings  = $this->get_settings();
    $processed = 0;

    while ($processed < self::BATCH_SIZE) {
      // ウォッチドッグ超過チェック: 残りキューは次のCronバッチへ引き継ぐ
      if (microtime(true) >= $deadline) {
        break;
      }

      $url = $this->dequeue();
      if ($url === null) break; // キューが空

      if ($this->is_visited($url)) continue;
      $this->mark_visited($url);

      // 自DDoS防止: 内部URLへのリクエスト間に200msのスリープ（sleep/usleep の同期ブロックはこれのみ）
      if ($processed > 0) usleep(200000);

      // ページ取得＆HTML解析
      $page = $this->fetch_page($url, $settings);
      if (!empty($page['error'])) {
        $this->record_error($url, 0, 'href', null, $page['error']);
      } else {
        $status = $page['status'];
        if ($status !== null && $status >= 400) {
          $this->record_error($url, $status, 'href', null, null);
        }
        // M-5: content_type が配列で返る場合に対応
        $ctype = $page['content_type'] ?? '';
        if (is_array($ctype)) $ctype = (string) reset($ctype);
        $ctype = (string) $ctype;
        if (stripos($ctype, 'text/html') !== false && !empty($page['body'])) {
          $this->scan_html($url, $page['body'], $run, $settings);
        }
      }

      $processed++;

      // URL上限チェック
      $limit = intval($run['limit']);
      if ($limit > 0 && $this->visited_count() >= $limit) {
        $run['running'] = false;
        $this->save_run($run);
        $this->unschedule_minute_cron();
        $this->maybe_send_report($run);
        return;
      }
    }

    // キューが空になったら完了
    if ($this->queue_count() === 0) {
      $run['running'] = false;
      $this->save_run($run);
      $this->unschedule_minute_cron();
      $this->maybe_send_report($run);
    }
    // ウォッチドッグ超過の場合はキューを残したまま終了。次のCronで続行する。
  }

  /* ============================================================
   * HTML解析
   * ============================================================ */

  /**
   * ページHTMLを解析してリンクを検査し、内部URLをキューに積む。
   *
   * H-1 対応:
   *   mb_convert_encoding + HTML-ENTITIES で文字コードを正規化してから DOMDocument に渡す。
   *   <meta charset> が優先されて文字化けするケースを防ぐ。
   *   mb_convert_encoding + HTML-ENTITIES は PHP 8.2+ で非推奨警告が出るため @ で抑制。
   *   将来は mb_encode_numericentity 等への移行を検討（TODO）。
   *
   * @param string $base_url ページのURL（相対URL解決の基点）
   * @param string $html     ページ本文
   * @param array  $run      実行状態（limit参照用）
   * @param array  $settings 設定
   */
  private function scan_html(string $base_url, string $html, array $run, array $settings): void {
    $prev = libxml_use_internal_errors(true);
    $dom  = new DOMDocument();

    // B-1: PHP バージョン別の文字コード正規化（DOMDocument 文字化け対策）
    if (PHP_VERSION_ID >= 80200) {
      // PHP 8.2+: mb_convert_encoding(HTML-ENTITIES) は非推奨（PHP 9 で削除予定）
      // XML 宣言プレフィックスで UTF-8 を明示することで <meta charset> 優先問題を回避
      $loaded = $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOERROR | LIBXML_NOWARNING);
    } else {
      // PHP 7.4 / 8.0 / 8.1: mb_convert_encoding が使える
      $html_utf8 = @mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8, SJIS-win, EUC-JP, JIS, ASCII');
      $loaded    = $dom->loadHTML($html_utf8, LIBXML_NOERROR | LIBXML_NOWARNING);
    }

    libxml_clear_errors();
    libxml_use_internal_errors($prev);

    if (!$loaded) return;

    $xpath = new DOMXPath($dom);

    // <a href>
    foreach ($xpath->query('//a[@href]') as $el) {
      $this->handle_found_url(trim($el->getAttribute('href')), $base_url, 'href', $run, $settings);
    }
    // <img src>
    foreach ($xpath->query('//img[@src]') as $el) {
      $this->handle_found_url(trim($el->getAttribute('src')), $base_url, 'img', $run, $settings);
    }
    // <script src>
    foreach ($xpath->query('//script[@src]') as $el) {
      $this->handle_found_url(trim($el->getAttribute('src')), $base_url, 'script', $run, $settings);
    }
    // <link rel="stylesheet" href>
    foreach ($xpath->query('//link[@rel="stylesheet"][@href]') as $el) {
      $this->handle_found_url(trim($el->getAttribute('href')), $base_url, 'css', $run, $settings);
    }
  }

  /**
   * 検出したURLを検査し、エラーなら記録する。
   * href タイプの内部URLはキューに追加する。
   *
   * H-7 対応: プロセスメモリ内キャッシュで同一URLの重複リクエストを防ぐ。
   */
  private function handle_found_url(string $raw, string $base_url, string $type, array $run, array $settings): void {
    if ($raw === '' || $raw === '#') return;
    if (preg_match('#^(mailto:|tel:|javascript:|data:)#i', $raw)) return;

    $abs = $this->to_absolute_url($raw, $base_url);
    if (!$abs) return;

    $abs = $this->normalize_url($abs);
    if (!$this->is_internal($abs)) return;
    if ($this->is_excluded($abs)) return;

    // H-7: 同一URLはキャッシュ済み結果を再利用（HTTPリクエストを1回に削減）
    if (isset($this->checked_cache[$abs])) {
      $result = $this->checked_cache[$abs];
    } else {
      $result = $this->check_url_status($abs, $settings);
      $this->checked_cache[$abs] = $result;
    }

    if (!empty($result['error'])) {
      $this->record_error($abs, 0, $type, $base_url, $result['error']);
    } elseif (isset($result['status']) && $result['status'] >= 400) {
      $this->record_error($abs, $result['status'], $type, $base_url, null);
    }

    // href かつ非バイナリ → クロール対象としてキューに追加
    if ($type === 'href' && !$this->is_binary_url($abs) && !$this->is_visited($abs)) {
      $limit = intval($run['limit']);
      if ($limit === 0 || $this->visited_count() < $limit) {
        $this->enqueue($abs);
      }
    }
  }

  /* ============================================================
   * HTTP通信
   * ============================================================ */

  /**
   * ページ全体を取得する（クロール用）。
   *
   * @return array{status: int|null, error: string|null, body: string|null, content_type: string|null}
   */
  private function fetch_page(string $url, array $settings): array {
    $args = $this->build_http_args($settings, false);
    $res  = wp_remote_get($url, $args);

    if (is_wp_error($res)) {
      // タイムアウト / 接続エラーはリトライ対象
      return $this->retry_on_error($url, $settings, $res->get_error_message());
    }

    $code = (int) wp_remote_retrieve_response_code($res);
    // 5xx はリトライ対象
    if ($code >= 500) {
      return $this->retry_on_error($url, $settings, null, $code);
    }

    // M-5: content-type ヘッダが配列で返る場合に対応
    $ctype = wp_remote_retrieve_header($res, 'content-type');
    if (is_array($ctype)) $ctype = (string) reset($ctype);
    $ctype = (string) $ctype;

    return [
      'status'       => $code,
      'error'        => null,
      'body'         => wp_remote_retrieve_body($res),
      'content_type' => $ctype,
    ];
  }

  /**
   * URLのステータスのみを確認する（リンクチェック用）。
   * HEAD → GET フォールバック付き。
   *
   * @return array{status: int|null, error: string|null}
   */
  private function check_url_status(string $url, array $settings): array {
    $args = $this->build_http_args($settings, true); // HEAD
    $res  = wp_remote_request($url, $args);

    if (is_wp_error($res)) {
      return $this->retry_status($url, $settings, $res->get_error_message());
    }

    $code = (int) wp_remote_retrieve_response_code($res);

    // HEAD を機械的に弾く環境向けフォールバック（0/403/405/429/501）
    if (in_array($code, [0, 403, 405, 429, 501], true)) {
      $get_args = $this->build_http_args($settings, false); // GET
      $res2     = wp_remote_get($url, $get_args);
      if (is_wp_error($res2)) {
        return $this->retry_status($url, $settings, $res2->get_error_message());
      }
      $code = (int) wp_remote_retrieve_response_code($res2);
    }

    // 5xx / 429 はリトライ対象
    if ($code >= 500 || $code === 429) {
      return $this->retry_status($url, $settings, null, $code);
    }

    return ['status' => $code, 'error' => null];
  }

  /**
   * ノーウェイト即時リトライ（ページ取得用）。
   *
   * C-1 対応: sleep を全廃。失敗時に即時1回だけ再試行する。
   * sleep によるブロックは Cron タイムアウトの主因のため排除。
   *
   * @return array{status: int|null, error: string|null, body: string|null, content_type: string|null}
   */
  private function retry_on_error(string $url, array $settings, ?string $err_msg, ?int $status = null): array {
    $max = intval($settings['max_retry']);

    // A-2: max_retry=0 のとき再試行なしで即返却（$tries=0 だとループを経ずに null/null で返る不具合を回避）
    if ($max === 0) {
      return $err_msg !== null
        ? ['status' => null, 'error' => $err_msg, 'body' => null, 'content_type' => null]
        : ['status' => $status, 'error' => null, 'body' => null, 'content_type' => null];
    }

    // sleep を排除: 最大1回の即時再試行のみ（間隔なし）
    $tries = min($max, 1);

    for ($i = 0; $i < $tries; $i++) {
      $args = $this->build_http_args($settings, false);
      $res  = wp_remote_get($url, $args);

      if (!is_wp_error($res)) {
        $code = (int) wp_remote_retrieve_response_code($res);
        if ($code < 500 && $code !== 429) {
          // M-5: content-type ヘッダが配列で返る場合に対応
          $ctype = wp_remote_retrieve_header($res, 'content-type');
          if (is_array($ctype)) $ctype = (string) reset($ctype);
          $ctype = (string) $ctype;
          return [
            'status'       => $code,
            'error'        => null,
            'body'         => wp_remote_retrieve_body($res),
            'content_type' => $ctype,
          ];
        }
        $status  = $code;
        $err_msg = null;
      } else {
        $err_msg = $res->get_error_message();
        $status  = null;
      }
    }

    if ($err_msg !== null) {
      return ['status' => null, 'error' => $err_msg, 'body' => null, 'content_type' => null];
    }
    return ['status' => $status, 'error' => null, 'body' => null, 'content_type' => null];
  }

  /**
   * ノーウェイト即時リトライ（ステータス確認用）。
   *
   * C-1 対応: sleep を全廃。失敗時に即時1回だけ再試行する。
   *
   * @return array{status: int|null, error: string|null}
   */
  private function retry_status(string $url, array $settings, ?string $err_msg, ?int $status = null): array {
    $max = intval($settings['max_retry']);

    // A-2: max_retry=0 のとき再試行なしで即返却（$tries=0 だとループを経ずに null/null で返る不具合を回避）
    if ($max === 0) {
      return $err_msg !== null
        ? ['status' => null, 'error' => $err_msg]
        : ['status' => $status, 'error' => null];
    }

    // sleep を排除: 最大1回の即時再試行のみ（間隔なし）
    $tries = min($max, 1);

    for ($i = 0; $i < $tries; $i++) {
      $args = $this->build_http_args($settings, false);
      $res  = wp_remote_get($url, $args);

      if (!is_wp_error($res)) {
        $code = (int) wp_remote_retrieve_response_code($res);
        if ($code < 500 && $code !== 429) {
          return ['status' => $code, 'error' => null];
        }
        $status  = $code;
        $err_msg = null;
      } else {
        $err_msg = $res->get_error_message();
        $status  = null;
      }
    }

    if ($err_msg !== null) {
      return ['status' => null, 'error' => $err_msg];
    }
    return ['status' => $status, 'error' => null];
  }

  /**
   * wp_remote_* に渡すリクエスト引数を構築する。
   * apply_filters('ou_lc_http_args', ...) でヘッダ等を差し替え可能。
   *
   * M-1 対応: 最初の sprintf による UA 代入を削除（デッドコード）。
   * ブラウザ風 UA のみ残す。
   *
   * @param array  $settings 設定
   * @param bool   $is_head  true=HEAD / false=GET
   * @return array
   */
  private function build_http_args(array $settings, bool $is_head): array {
    // WAF対策: ブラウザ風 UA（旧 sprintf UA はデッドコードにつき削除）
    $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) '
        . 'Chrome/124.0.0.0 Safari/537.36 '
        . '(compatible; OU-LinkChecker/' . self::VERSION . '; +' . home_url('/') . ')';

    $args = [
      'timeout'     => intval($settings['timeout']),
      'redirection' => 5,
      'sslverify'   => true,
      'user-agent'  => $ua,
      'headers'     => [
        'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language' => 'ja,en-US;q=0.8,en;q=0.6',
      ],
    ];
    if ($is_head) {
      $args['method'] = 'HEAD';
    }

    return apply_filters('ou_lc_http_args', $args, $is_head);
  }

  /* ============================================================
   * URLユーティリティ
   * ============================================================ */

  /**
   * 相対URLを絶対URLに変換する。
   *
   * @return string|null 変換失敗時は null
   */
  private function to_absolute_url(string $url, string $base): ?string {
    // 既に絶対URL
    if (preg_match('#^https?://#i', $url)) return $url;

    // プロトコル相対URL（//example.com/...）
    if (strpos($url, '//') === 0) {
      $scheme = is_ssl() ? 'https' : 'http';
      return $scheme . ':' . $url;
    }

    $parts = wp_parse_url($base);
    if (!$parts || empty($parts['scheme']) || empty($parts['host'])) return null;

    $scheme = $parts['scheme'];
    $host   = $parts['host'];
    $port   = isset($parts['port']) ? ':' . $parts['port'] : '';

    // ルート相対URL（/path/...）
    if (strpos($url, '/') === 0) {
      return "{$scheme}://{$host}{$port}{$url}";
    }

    // 相対URL（../foo, ./bar, baz 等）
    return $this->resolve_relative_url($parts, $port, $url);
  }

  /** RFC 3986 準拠の相対URL解決 */
  private function resolve_relative_url(array $base_parts, string $port, string $rel): string {
    $scheme = $base_parts['scheme'];
    $host   = $base_parts['host'];
    $path   = isset($base_parts['path']) ? $base_parts['path'] : '/';

    // base がファイルの場合はディレクトリ部分を取る
    if (substr($path, -1) !== '/') {
      $path = dirname($path) . '/';
    }

    $merged = $path . $rel;
    $segs   = explode('/', $merged);
    $stack  = [];
    foreach ($segs as $seg) {
      if ($seg === '' || $seg === '.') continue;
      if ($seg === '..') {
        array_pop($stack);
      } else {
        $stack[] = $seg;
      }
    }
    $new_path = '/' . implode('/', $stack);
    return "{$scheme}://{$host}{$port}{$new_path}";
  }

  /**
   * URLを正規化する（フラグメント除去・クエリキーソート・スキーム/ホスト小文字化・末尾スラッシュ統一）。
   * 誤検知の主因となる不統一URLを同一視するために必須。
   */
  private function normalize_url(string $url): string {
    $p = wp_parse_url($url);
    if (!$p || empty($p['scheme']) || empty($p['host'])) return $url;

    $scheme = strtolower($p['scheme']);
    $host   = strtolower($p['host']);
    $port   = isset($p['port']) ? ':' . $p['port'] : '';
    $path   = isset($p['path']) ? $p['path'] : '/';

    // 末尾スラッシュを除去して統一（パスが "/" のみの場合はそのまま）
    if (strlen($path) > 1 && substr($path, -1) === '/') {
      $path = rtrim($path, '/');
    }

    // クエリパラメータのキー名ソート（?b=2&a=1 → ?a=1&b=2）
    $query = '';
    if (!empty($p['query'])) {
      parse_str($p['query'], $params);
      ksort($params);
      $query = '?' . http_build_query($params);
    }

    // フラグメントは除去（fragment は再構築しない）
    return "{$scheme}://{$host}{$port}{$path}{$query}";
  }

  /** 自サイトの内部URLかどうか判定 */
  private function is_internal(string $url): bool {
    $host = wp_parse_url($url, PHP_URL_HOST);
    return $host && (strcasecmp($host, $this->site_host) === 0);
  }

  /** 除外パターンに一致するかどうか判定 */
  private function is_excluded(string $url): bool {
    $path = (string) wp_parse_url($url, PHP_URL_PATH);
    // クエリ文字列も含めて判定するため、クエリ部分も取得
    $query = (string) wp_parse_url($url, PHP_URL_QUERY);
    $target = $path . ($query ? '?' . $query : '');

    $patterns = apply_filters('ou_lc_exclude_patterns', self::DEFAULT_EXCLUDE_PATHS);
    foreach ($patterns as $re) {
      if (!is_string($re) || !$re) continue;
      if (@preg_match($re, $target)) return true;
    }
    return false;
  }

  /**
   * URLがバイナリファイル（画像・動画・フォント等）かどうか判定する。
   * バイナリはクロール対象外（ステータス確認は行うが本文パースしない）。
   */
  private function is_binary_url(string $url): bool {
    $path = (string) wp_parse_url($url, PHP_URL_PATH);
    if (!$path) return false;
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    return in_array($ext, [
      'jpg','jpeg','png','gif','webp','svg','ico',
      'pdf','zip','tar','gz',
      'woff','woff2','ttf','eot','otf',
      'mp4','webm','ogv','mov','avi',
      'mp3','ogg','wav','flac',
    ], true);
  }

  /**
   * 開始URLのSSRF対策バリデーション。
   * 自サイトのホスト以外を指定されたら home_url('/') に差し替える。
   *
   * H-2 対応: リバースプロキシ配下でもプライベートIPへのクロールを防ぐ。
   * home_url() がプライベートIPを返す環境でも安全に動作する。
   */
  private function safe_start_url(string $raw): string {
    $url = esc_url_raw(wp_unslash($raw));
    if (!$url) return $this->normalize_url(home_url('/'));

    if (!$this->is_internal($url)) {
      // 自サイト外のURLが指定された場合はサイトトップに差し替え
      return $this->normalize_url(home_url('/'));
    }

    // プライベートIP拒否（リバースプロキシ配下でのLANクロール防止）
    $host = wp_parse_url($url, PHP_URL_HOST);
    if ($host !== null) {
      $ip = filter_var($host, FILTER_VALIDATE_IP);
      if ($ip !== false && filter_var(
        $ip,
        FILTER_VALIDATE_IP,
        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
      ) === false) {
        return $this->normalize_url(home_url('/'));
      }
    }

    return $this->normalize_url($url);
  }

  /* ============================================================
   * DB操作（結果）
   * ============================================================ */

  /**
   * エラーをDBに記録する（UPSERT: 同一url_hash+statusは上書き更新）。
   *
   * C-3+C-4 対応:
   *   - $status: 取得失敗時は 0（旧来 null だったものを NOT NULL DEFAULT 0 に統一）
   *   - url_hash: MD5($url) を UNIQUE KEY に使用（長尺URL誤UPSERT + NULL UPSERT不発を解消）
   *
   * @param string   $url     対象URL
   * @param int      $status  HTTPステータスコード（取得失敗時は 0）
   * @param string   $type    リンク種別（href/img/script/css）
   * @param string|null $referer 参照元URL
   * @param string|null $error   エラーメッセージ
   */
  private function record_error(string $url, int $status, string $type, ?string $referer, ?string $error): void {
    // 成功(2xx/3xx)は記録しない（status=0 は取得失敗なので記録対象）
    if ($status !== 0 && $status < 400) return;

    // 除外ステータスは記録しない（status=0 は除外対象外）
    if ($status !== 0 && in_array($status, $this->get_ignored_statuses(), true)) return;

    // 文字列長を切り詰め（VARCHAR上限対策）
    $url     = mb_substr($url, 0, 2048);
    $referer = $referer !== null ? mb_substr($referer, 0, 2048) : null;
    $error   = $error   !== null ? mb_substr($error,   0, 512)  : null;

    global $wpdb;
    $table = $wpdb->prefix . self::TABLE_RESULTS;

    // UPSERT: 同じ url_hash + status の組み合わせは上書き更新
    $wpdb->query($wpdb->prepare(
      "INSERT INTO `{$table}` (url, url_hash, referer, status, type, error, checked_at)
       VALUES (%s, %s, %s, %d, %s, %s, %s)
       ON DUPLICATE KEY UPDATE
         url        = VALUES(url),
         referer    = VALUES(referer),
         type       = VALUES(type),
         error      = VALUES(error),
         checked_at = VALUES(checked_at)",
      $url,
      md5($url),
      $referer,
      $status,
      $type,
      $error,
      current_time('mysql')
    ));
  }

  /**
   * 直近エラーを取得する。除外ステータスを除く。
   *
   * C-3+C-4 対応: status IS NULL → status = 0（取得失敗の表現を統一）
   *
   * @param int $limit 取得件数
   * @return array[]
   */
  private function get_recent_errors(int $limit = 100): array {
    global $wpdb;
    $table   = $wpdb->prefix . self::TABLE_RESULTS;
    $ignored = $this->get_ignored_statuses();

    if ($ignored) {
      // $ignored は intval 済みの配列なので安全にプレースホルダ構築
      $ph  = implode(',', array_fill(0, count($ignored), '%d'));
      $sql = $wpdb->prepare(
        "SELECT * FROM `{$table}`
         WHERE (status >= 400 OR status = 0)
           AND status NOT IN ({$ph})
         ORDER BY checked_at DESC
         LIMIT %d",
        ...[...$ignored, $limit]
      );
    } else {
      $sql = $wpdb->prepare(
        "SELECT * FROM `{$table}`
         WHERE (status >= 400 OR status = 0)
         ORDER BY checked_at DESC
         LIMIT %d",
        $limit
      );
    }

    return (array) $wpdb->get_results($sql, ARRAY_A);
  }

  /**
   * CSVエクスポート用: 全エラーを取得。除外ステータスを除く。
   *
   * C-3+C-4 対応: status IS NULL → status = 0
   *
   * @return array[]
   */
  private function get_all_errors(): array {
    global $wpdb;
    $table   = $wpdb->prefix . self::TABLE_RESULTS;
    $ignored = $this->get_ignored_statuses();

    if ($ignored) {
      $ph  = implode(',', array_fill(0, count($ignored), '%d'));
      $sql = $wpdb->prepare(
        "SELECT checked_at, url, status, referer, type, error
         FROM `{$table}`
         WHERE (status >= 400 OR status = 0)
           AND status NOT IN ({$ph})
         ORDER BY checked_at DESC",
        ...$ignored
      );
    } else {
      $sql = "SELECT checked_at, url, status, referer, type, error
              FROM `{$table}`
              WHERE (status >= 400 OR status = 0)
              ORDER BY checked_at DESC";
    }

    return (array) $wpdb->get_results($sql, ARRAY_A);
  }

  /** スキャン開始時に90日以上前のレコードを削除 */
  private function purge_old_records(): void {
    global $wpdb;
    $table = $wpdb->prefix . self::TABLE_RESULTS;
    $wpdb->query($wpdb->prepare(
      "DELETE FROM `{$table}` WHERE checked_at < %s",
      wp_date('Y-m-d H:i:s', strtotime('-90 days'))
    ));
  }

  /* ============================================================
   * メール通知
   * ============================================================ */

  /**
   * スキャン完了後にエラーがあればレポートメールを送信する。
   * 送信失敗時は最大3回リトライ（次Cronで再試行）。
   *
   * C-3+C-4 対応: status IS NULL → status = 0 に統一。
   * COALESCE(CAST(status AS CHAR), 'ERR') → CASE WHEN status = 0 THEN 'ERR' ELSE ... に変更。
   */
  private function maybe_send_report(array $run): void {
    if (!empty($run['report_sent'])) return;
    if (intval($run['report_retry']) >= 3) {
      // 3回失敗で諦める
      error_log('[OU LinkChecker] メール送信を3回試みましたが失敗したため、通知を中断します。');
      return;
    }

    $settings = $this->get_settings();
    $started  = $run['started'] ?? null;
    if (!$started) return;

    global $wpdb;
    $table   = $wpdb->prefix . self::TABLE_RESULTS;
    $ignored = $this->get_ignored_statuses();

    // 件数カウント（C-3+C-4: status = 0 が取得失敗）
    if ($ignored) {
      $ph    = implode(',', array_fill(0, count($ignored), '%d'));
      $count = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM `{$table}`
         WHERE (status >= 400 OR status = 0)
           AND status NOT IN ({$ph})
           AND checked_at >= %s",
        ...[...$ignored, $started]
      ));
    } else {
      $count = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM `{$table}`
         WHERE (status >= 400 OR status = 0)
           AND checked_at >= %s",
        $started
      ));
    }

    if ($count <= 0) {
      $run['report_sent'] = true;
      $this->save_run($run);
      return;
    }

    // 直近50件を取得
    if ($ignored) {
      $ph   = implode(',', array_fill(0, count($ignored), '%d'));
      $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT checked_at, url, status, referer, type, error
         FROM `{$table}`
         WHERE (status >= 400 OR status = 0)
           AND status NOT IN ({$ph})
           AND checked_at >= %s
         ORDER BY checked_at DESC
         LIMIT 50",
        ...[...$ignored, $started]
      ), ARRAY_A);
    } else {
      $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT checked_at, url, status, referer, type, error
         FROM `{$table}`
         WHERE (status >= 400 OR status = 0)
           AND checked_at >= %s
         ORDER BY checked_at DESC
         LIMIT 50",
        $started
      ), ARRAY_A);
    }

    // 内訳取得（C-3+C-4: status=0 を 'ERR' と表示）
    if ($ignored) {
      $ph        = implode(',', array_fill(0, count($ignored), '%d'));
      $by_status = $wpdb->get_results($wpdb->prepare(
        "SELECT CASE WHEN status = 0 THEN 'ERR' ELSE CAST(status AS CHAR) END AS s, COUNT(*) AS c
         FROM `{$table}`
         WHERE (status >= 400 OR status = 0)
           AND status NOT IN ({$ph})
           AND checked_at >= %s
         GROUP BY s ORDER BY s ASC",
        ...[...$ignored, $started]
      ), ARRAY_A);
    } else {
      $by_status = $wpdb->get_results($wpdb->prepare(
        "SELECT CASE WHEN status = 0 THEN 'ERR' ELSE CAST(status AS CHAR) END AS s, COUNT(*) AS c
         FROM `{$table}`
         WHERE (status >= 400 OR status = 0)
           AND checked_at >= %s
         GROUP BY s ORDER BY s ASC",
        $started
      ), ARRAY_A);
    }

    // メール本文構築（URL/referer/errorから改行・制御文字を除去してインジェクション対策）
    $site_name = get_bloginfo('name');
    $site_url  = home_url('/');
    $subject   = sprintf('[内部リンクチェッカー] リンク切れ検出 (%s)', $site_name);

    $lines   = [];
    $lines[] = sprintf('サイト: %s (%s)', $site_name, $site_url);
    $lines[] = sprintf('走査開始: %s', $this->sanitize_for_mail((string)$started));
    $lines[] = sprintf('検出件数: %d 件', $count);

    if ($by_status) {
      $lines[] = '内訳:';
      foreach ($by_status as $r) {
        $lines[] = sprintf('  - %s: %d', $this->sanitize_for_mail((string)$r['s']), (int)$r['c']);
      }
    }

    $lines[] = '';
    $lines[] = '▼直近のエラー例（最大50件）';
    foreach ((array)$rows as $r) {
      $st = (int)($r['status'] ?? 0);
      $lines[] = sprintf(
        '- [%s] %s | status:%s | type:%s',
        $this->sanitize_for_mail(wp_date('Y-m-d H:i', strtotime((string)$r['checked_at']))),
        $this->sanitize_for_mail((string)($r['url'] ?? '')),
        $this->sanitize_for_mail($st === 0 ? 'ERR' : (string)$st),
        $this->sanitize_for_mail((string)($r['type'] ?? ''))
      );
      if (!empty($r['referer'])) {
        $lines[] = '    referer: ' . $this->sanitize_for_mail((string)$r['referer']);
      }
      if (!empty($r['error'])) {
        $lines[] = '    error  : ' . $this->sanitize_for_mail((string)$r['error']);
      }
    }

    $lines[] = '';
    $lines[] = '詳細・CSVエクスポートは 管理画面 > ツール > 内部リンクチェッカー をご利用ください。';

    // 送信先
    $to      = $settings['notify_to'] ?: get_option('admin_email', '');
    $headers = ['Content-Type: text/plain; charset=UTF-8'];
    foreach ($settings['notify_bcc'] as $bcc) {
      if ($bcc) $headers[] = 'Bcc: ' . sanitize_email($bcc);
    }

    $ok = wp_mail($to, $subject, implode("\n", $lines), $headers);

    if ($ok) {
      $run['report_sent'] = true;
      $this->save_run($run);
    } else {
      $run['report_retry'] = intval($run['report_retry']) + 1;
      $this->save_run($run);
      error_log(sprintf('[OU LinkChecker] メール送信失敗（試行 %d/3）To: %s', $run['report_retry'], $to));
    }
  }

  /* ============================================================
   * Cronユーティリティ
   * ============================================================ */

  private function unschedule_minute_cron(): void {
    $ts = wp_next_scheduled(self::CRON_HOOK);
    while ($ts) {
      wp_unschedule_event($ts, self::CRON_HOOK);
      $ts = wp_next_scheduled(self::CRON_HOOK);
    }
  }

  private function unschedule_all_crons(): void {
    $this->unschedule_minute_cron();
    $ts = wp_next_scheduled(self::DAILY_CRON_HOOK);
    if ($ts) wp_unschedule_event($ts, self::DAILY_CRON_HOOK);
  }

  private function stop_scan(): void {
    $this->unschedule_minute_cron();
    $run = $this->get_run();
    $run['running'] = false;
    $this->save_run($run);
    $this->clear_state_table(); // キューも削除
  }

  /* ============================================================
   * 共通ヘルパー
   * ============================================================ */

  /**
   * CSVインジェクション対策。
   * 数式として解釈されうる先頭文字（= + - @ タブ CR LF）の前に ' を付ける。
   *
   * C-2 対応: LF (\n) を正規表現パターンに追加。
   * Excel / LibreOffice は LF 始まりも数式評価トリガにする。
   */
  private function csv_safe(string $v): string {
    if ($v === '') return $v;
    if (preg_match('/^[=+\-@\t\r\n]/', $v)) {
      return "'" . $v;
    }
    return $v;
  }

  /**
   * メール本文インジェクション対策。
   * 改行・制御文字（CRLF含む）を除去する。
   */
  private function sanitize_for_mail(string $v): string {
    // 改行・制御文字を除去
    return preg_replace('/[\x00-\x1F\x7F]/', '', $v);
  }

  /** ステータスコードのラベルを返す */
  private function status_label(int $code): string {
    $labels = [
      401 => '401 Unauthorized（認証必須）',
      403 => '403 Forbidden（アクセス拒否/WAF等）',
      405 => '405 Method Not Allowed（メソッド拒否）',
      406 => '406 Not Acceptable（コンテンツ検査等）',
      429 => '429 Too Many Requests（レート制限）',
      503 => '503 Service Unavailable（一時停止/高負荷）',
      520 => '520 CF: Unknown Error',
      521 => '521 CF: Web Server Down',
      522 => '522 CF: Connection Timed Out',
      525 => '525 CF: SSL Handshake Failed',
      526 => '526 CF: Invalid SSL Certificate',
    ];
    return $labels[$code] ?? (string)$code;
  }

  /* ============================================================
   * WP-CLI
   * ============================================================ */

  /**
   * WP-CLIコマンド: wp ou-lc --start=<URL> --limit=<N>
   *
   * High-2 対応（v2.0.3）:
   *   init_scan() 内で wp_schedule_event() が発火し、CLI 実行中に WP-Cron も裏で
   *   process_queue(false) を呼び出す競合が発生していた。
   *   init_scan() 直後に unschedule_minute_cron() を呼ぶことで CLI 単独実行にする。
   *
   *   CLI 終了後は通常の日次スキャン等が止まる状態になるが、次回 init_scan() 呼び出し時に
   *   wp_schedule_event() が再登録されるため自動で復旧する（再起動不要）。
   *
   * @param array $args       位置引数（未使用）
   * @param array $assoc_args オプション引数
   */
  public function cli_command(array $args, array $assoc_args): void {
    $raw_start = isset($assoc_args['start']) && is_string($assoc_args['start'])
      ? $assoc_args['start'] : home_url('/');
    $limit = isset($assoc_args['limit']) ? max(0, intval($assoc_args['limit'])) : 0;

    // SSRF対策: 自サイト内URLに強制
    $start_url = $this->safe_start_url($raw_start);

    \WP_CLI::log('Scanning start: ' . $start_url . ' | limit: ' . ($limit ?: 'none'));

    $this->init_scan($start_url, $limit);

    // CLI 実行中は裏の WP-Cron を停止（二重 dequeue・running フラグ競合を防止）
    // 次回 init_scan() で wp_schedule_event() が再登録されるため自動復旧する
    $this->unschedule_minute_cron();

    $run = $this->get_run();

    while ($run['running']) {
      // B-2: CLI ではウォッチドッグを無効化して長時間連続実行を許可
      $this->process_queue(true);
      $run = $this->get_run();
      \WP_CLI::log(sprintf(
        'Queue: %d | Visited: %d',
        $this->queue_count(),
        $this->visited_count()
      ));
      usleep(200000);
    }

    \WP_CLI::success('Scan finished. Visited: ' . $this->visited_count());
  }
}

new OfficeUeda_Internal_Link_Checker();
