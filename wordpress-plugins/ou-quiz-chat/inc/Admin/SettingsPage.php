<?php
// inc/Admin/SettingsPage.php
namespace OU\QuizChat\Admin;

if (!defined('ABSPATH')) { exit; }

use OU\QuizChat\Core\Options;

/**
 * 設定画面：質問管理／結果レンジ／メール設定／その他（プレースホルダ）
 * - 管理UIのHTMLを出力し、各タブ用のJS（admin-questions.js / admin-results.js / admin-mail.js）で動かす
 * - 本クラスはメニュー登録（register）とレンダリング（render）のみを担当
 */
class SettingsPage
{
    /** メニューのスラッグ（固定） */
    public const MENU_SLUG = 'ou-quiz-chat';

    /**
     * メニュー登録 + 当該画面でのみアセット読込
     *
     * - add_menu_page(): 管理メニューに「OU Quiz Chat」を追加
     * - admin_enqueue_scripts: 当該画面（toplevel_page_ou-quiz-chat）でのみCSS/JSをenqueue
     */
    public function register(): void
    {
        add_menu_page(
            __('OU Quiz Chat', 'ou-quiz-chat'),           // ページタイトル
            __('OU Quiz Chat', 'ou-quiz-chat'),           // メニュータイトル
            OUQ_CAP_EDIT,                                 // 必要権限（例：manage_options 相当）
            self::MENU_SLUG,                              // メニュースラッグ
            [$this, 'render'],                            // コールバック
            'dashicons-format-chat',                      // アイコン
            65                                            // 表示位置
        );

        add_action('admin_enqueue_scripts', function($hook){
            // この画面（トップレベルメニュー）以外では何もしない
            if ($hook !== 'toplevel_page_' . self::MENU_SLUG) return;

            // 共通CSS（フォーム・カード・タブ等のスタイル）
            wp_enqueue_style('ouq-admin', OUQ_URL . 'assets/css/admin.css', [], OUQ_VERSION);

            // 各タブ用JS（defer付与は Core\Assets::add_defer が担当）
            // --- 質問管理 ---
            wp_register_script('ouq-admin-questions', OUQ_URL . 'assets/js/admin-questions.js', [], OUQ_VERSION, true);
            add_filter('script_loader_tag', [\OU\QuizChat\Core\Assets::class, 'add_defer'], 10, 3);
            wp_enqueue_script('ouq-admin-questions');

            // --- 結果レンジ ---
            wp_register_script('ouq-admin-results', OUQ_URL . 'assets/js/admin-results.js', [], OUQ_VERSION, true);
            add_filter('script_loader_tag', [\OU\QuizChat\Core\Assets::class, 'add_defer'], 10, 3);
            wp_enqueue_script('ouq-admin-results');

            // --- メール設定 ---
            wp_register_script('ouq-admin-mail', OUQ_URL . 'assets/js/admin-mail.js', [], OUQ_VERSION, true);
            add_filter('script_loader_tag', [\OU\QuizChat\Core\Assets::class, 'add_defer'], 10, 3);
            wp_enqueue_script('ouq-admin-mail');

            // ※ タブ切替JSが別ファイルにある場合はここで enqueue してください。
            //   本ファイルのHTML構造は .ouq-tabs / .ouq-tab / .ouq-tabpanel の
            //   CSS・JSによる切り替えを前提にしています。
        });
    }

    /**
     * 設定画面の描画（単一ブロックに統合）
     *
     * - 画面共通のタブナビゲーション
     * - 各タブ：質問管理／結果レンジ／メール設定／その他
     * - Nonceはここで一括生成し、各タブに hidden として埋め込み
     */
    public function render(): void
    {
        if (!current_user_can(OUQ_CAP_EDIT)) {
            wp_die(esc_html__('You do not have sufficient permissions.', 'ou-quiz-chat'));
        }

        // Nonce を一括生成（各タブJSが参照）
        $q_nonce = wp_create_nonce('ouq_admin_questions'); // 質問管理 保存用
        $r_nonce = wp_create_nonce('ouq_admin_results');   // 結果レンジ 保存用
        $m_nonce = wp_create_nonce('ouq_admin_mail');      // メール設定 保存用

        // 将来の拡張用に取得（現時点では未使用）
        $opts = new Options();

        ?>
        <div class="wrap ouq-admin">
          <h1>OU Quiz Chat</h1>

          <!-- タブナビゲーション -->
          <nav class="ouq-tabs" aria-label="<?php echo esc_attr__('設定タブ', 'ou-quiz-chat'); ?>">
            <button class="ouq-tab is-active" data-tab="questions">
              <?php echo esc_html__('質問管理', 'ou-quiz-chat'); ?>
            </button>
            <button class="ouq-tab" data-tab="results">
              <?php echo esc_html__('結果レンジ', 'ou-quiz-chat'); ?>
            </button>
            <button class="ouq-tab" data-tab="mail">
              <?php echo esc_html__('メール設定', 'ou-quiz-chat'); ?>
            </button>
            <button class="ouq-tab" data-tab="others">
              <?php echo esc_html__('その他設定（準備中）', 'ou-quiz-chat'); ?>
            </button>
          </nav>

          <!-- =========================
               質問管理タブ
               ========================= -->
          <section id="ouq-tab-questions" class="ouq-tabpanel is-active" role="tabpanel" aria-labelledby="質問管理">
            <div class="ouq-toolbar">
              <button type="button" class="button button-primary js-add-q">＋ <?php echo esc_html__('質問を追加', 'ou-quiz-chat'); ?></button>
              <button type="button" class="button js-import">JSON<?php echo esc_html__('インポート', 'ou-quiz-chat'); ?></button>
              <button type="button" class="button js-export">JSON<?php echo esc_html__('エクスポート', 'ou-quiz-chat'); ?></button>
              <span class="ouq-spacer"></span>
              <button type="button" class="button button-primary js-save"><?php echo esc_html__('保存', 'ou-quiz-chat'); ?></button>
            </div>

            <div class="ouq-hint">
              <p>
                ・<?php echo esc_html__('分岐なし：single（単一選択）/ multi（複数選択）に対応', 'ou-quiz-chat'); ?><br>
                ・<?php echo esc_html__('スコアは整数。multiは合算／必要なら score_cap で上限設定', 'ou-quiz-chat'); ?>
              </p>
            </div>

            <div class="ouq-questions js-questions" aria-live="polite">
              <!-- JSで質問カードを描画 -->
            </div>

            <!-- 質問管理用 Nonce -->
            <input type="hidden" class="js-nonce" value="<?php echo esc_attr($q_nonce); ?>">
          </section>

          <!-- =========================
               結果レンジタブ
               ========================= -->
          <section id="ouq-tab-results" class="ouq-tabpanel" role="tabpanel" aria-labelledby="結果レンジ">
            <div class="ouq-toolbar">
              <button type="button" class="button button-primary js-add-band">＋ <?php echo esc_html__('帯を追加', 'ou-quiz-chat'); ?></button>
              <button type="button" class="button js-import-results">JSON<?php echo esc_html__('インポート', 'ou-quiz-chat'); ?></button>
              <button type="button" class="button js-export-results">JSON<?php echo esc_html__('エクスポート', 'ou-quiz-chat'); ?></button>
              <span class="ouq-spacer"></span>
              <button type="button" class="button button-primary js-save-results"><?php echo esc_html__('保存', 'ou-quiz-chat'); ?></button>
            </div>

            <div class="ouq-hint">
              <p>
                ・<?php echo esc_html__('スコアの最小値（min）〜最大値（max）でレンジを定義', 'ou-quiz-chat'); ?><br>
                ・<?php echo esc_html__('レンジが重ならないように設定してください', 'ou-quiz-chat'); ?>
              </p>
            </div>

            <div class="ouq-questions js-bands" aria-live="polite">
              <!-- JSでレンジカードを描画 -->
            </div>

            <!-- 結果レンジ用 Nonce -->
            <input type="hidden" id="ouq-results-nonce" value="<?php echo esc_attr($r_nonce); ?>">
          </section>

          <!-- =========================
               メール設定タブ
               ========================= -->
          <section id="ouq-tab-mail" class="ouq-tabpanel" role="tabpanel" aria-labelledby="メール設定">
            <div class="ouq-toolbar">
              <span class="ouq-spacer"></span>
              <button type="button" class="button button-primary js-mail-save">
                <?php echo esc_html__('保存', 'ou-quiz-chat'); ?>
              </button>
            </div>

            <div class="ouq-hint">
              <p>
                ・<?php echo esc_html__('送信先/送信元の設定と、チャット内に表示する送受信メッセージ、送信後の予約リンクを管理します。', 'ou-quiz-chat'); ?><br>
                ・<?php echo esc_html__('結果レンジ側にCTAがある場合は、送信後の予約リンクより優先して表示されます。', 'ou-quiz-chat'); ?>
              </p>
            </div>

            <div class="ouq-questions" style="gap:16px;">
              <!-- 送信先（管理者） -->
              <div class="ouq-qcard">
                <div class="ouq-qcard__head">
                  <strong class="ouq-qcard__title"><?php echo esc_html__('送信先（管理者）', 'ou-quiz-chat'); ?></strong>
                </div>
                <div class="ouq-qcard__body">
                  <div class="ouq-row">
                    <span class="ouq-label"><?php echo esc_html__('メールアドレス', 'ou-quiz-chat'); ?></span>
                    <input
                        type="text"
                        class="regular-text js-mail-admins"
                        placeholder="<?php echo esc_attr__('カンマ区切りで複数指定可（例: a@ex.com, b@ex.com）', 'ou-quiz-chat'); ?>"
                    >
                  </div>
                </div>
              </div>

              <!-- 送信元 -->
              <div class="ouq-qcard">
                <div class="ouq-qcard__head">
                  <strong class="ouq-qcard__title"><?php echo esc_html__('送信元', 'ou-quiz-chat'); ?></strong>
                </div>
                <div class="ouq-qcard__body">
                  <div class="ouq-cols">
                    <label style="flex:1">
                      <span class="ouq-label"><?php echo esc_html__('送信元名', 'ou-quiz-chat'); ?></span>
                      <input type="text" class="regular-text js-mail-from-name">
                    </label>
                    <label style="flex:1">
                      <span class="ouq-label"><?php echo esc_html__('送信元メール', 'ou-quiz-chat'); ?></span>
                      <input type="email" class="regular-text js-mail-from-email">
                    </label>
                  </div>
                  <label style="display:flex; gap:8px; align-items:center; margin-top:8px;">
                    <input type="checkbox" class="js-mail-user-copy">
                    <span><?php echo esc_html__('ユーザーに控えメールを送る', 'ou-quiz-chat'); ?></span>
                  </label>
                </div>
              </div>

              <!-- チャット文言 -->
              <div class="ouq-qcard">
                <div class="ouq-qcard__head">
                  <strong class="ouq-qcard__title"><?php echo esc_html__('チャット文言', 'ou-quiz-chat'); ?></strong>
                </div>
                <div class="ouq-qcard__body">
                  <label class="ouq-row" style="align-items:flex-start">
                    <span class="ouq-label"><?php echo esc_html__('入力前アナウンス', 'ou-quiz-chat'); ?></span>
                    <textarea
                      rows="2"
                      class="large-text js-mail-pre-text"
                      placeholder="<?php echo esc_attr__('例：診断結果をメールでお送りします。', 'ou-quiz-chat'); ?>"
                    ></textarea>
                  </label>
                  <div class="ouq-cols">
                    <label style="flex:1">
                      <span class="ouq-label"><?php echo esc_html__('送信成功メッセージ', 'ou-quiz-chat'); ?></span>
                      <textarea
                        rows="2"
                        class="large-text js-mail-success"
                        placeholder="<?php echo esc_attr__('例：診断結果を送信しました。{email} 宛のメールをご確認ください。', 'ou-quiz-chat'); ?>"
                      ></textarea>
                    </label>
                    <label style="flex:1">
                      <span class="ouq-label"><?php echo esc_html__('送信失敗メッセージ', 'ou-quiz-chat'); ?></span>
                      <textarea
                        rows="2"
                        class="large-text js-mail-error"
                        placeholder="<?php echo esc_attr__('例：送信に失敗しました。時間をおいて再度お試しください。', 'ou-quiz-chat'); ?>"
                      ></textarea>
                    </label>
                  </div>
                </div>
              </div>

              <!-- 送信後の予約リンク -->
              <div class="ouq-qcard">
                <div class="ouq-qcard__head">
                  <strong class="ouq-qcard__title"><?php echo esc_html__('送信後の予約リンク', 'ou-quiz-chat'); ?></strong>
                </div>
                <div class="ouq-qcard__body">
                  <label style="display:flex; gap:8px; align-items:center;">
                    <input type="checkbox" class="js-mail-resv-enabled">
                    <span><?php echo esc_html__('予約リンクを表示する（結果レンジ側にCTAがある場合はそちらを優先）', 'ou-quiz-chat'); ?></span>
                  </label>
                  <div class="ouq-cols" style="margin-top:8px;">
                    <label style="flex:2">
                      <span class="ouq-label">URL</span>
                      <input type="url" class="regular-text js-mail-resv-url" placeholder="https://...">
                    </label>
                    <label style="flex:1">
                      <span class="ouq-label"><?php echo esc_html__('ボタンラベル', 'ou-quiz-chat'); ?></span>
                      <input
                        type="text"
                        class="regular-text js-mail-resv-label"
                        placeholder="<?php echo esc_attr__('無料相談を予約する', 'ou-quiz-chat'); ?>"
                      >
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <!-- メール設定用 Nonce -->
            <input type="hidden" id="ouq-mail-nonce" value="<?php echo esc_attr($m_nonce); ?>">
          </section>

          <!-- =========================
               その他設定（プレースホルダ）
               ========================= -->
          <section id="ouq-tab-others" class="ouq-tabpanel" role="tabpanel" aria-labelledby="その他">
            <p><?php echo esc_html__('このタブは今後の実装で有効になります。', 'ou-quiz-chat'); ?></p>
          </section>
        </div>
        <?php
    }
}
