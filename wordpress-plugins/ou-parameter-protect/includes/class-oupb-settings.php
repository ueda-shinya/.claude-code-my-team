<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 設定画面クラス
 *
 * - 「設定 ＞ パラメータ保護ページ」メニューを追加
 * - Settings API を使って OUPB_OPTIONS_KEY を管理
 * - サニタイズとバリデーションを担当
 */
class OUPB_Settings {

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * 設定メニューの登録
	 *
	 * @return void
	 */
	public function register_menu() {
		add_options_page(
			__( 'Parameter Protected Pages', 'ou-parameter-protect' ),
			__( 'パラメータ保護ページ', 'ou-parameter-protect' ),
			'manage_options',
			'ou-parameter-protect',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Settings API 設定
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'oupb_settings_group',
			OUPB_OPTIONS_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_options' ),
				'default'           => OUPB_Plugin::get_default_options(),
			)
		);

		add_settings_section(
			'oupb_main_section',
			__( '基本設定', 'ou-parameter-protect' ),
			'__return_false',
			'ou-parameter-protect'
		);

		// パラメータ名
		add_settings_field(
			'param_name',
			__( 'クエリパラメータ名', 'ou-parameter-protect' ),
			array( $this, 'field_param_name' ),
			'ou-parameter-protect',
			'oupb_main_section'
		);

		// 不正アクセス時の挙動
		add_settings_field(
			'access_behavior',
			__( '不正アクセス時の挙動', 'ou-parameter-protect' ),
			array( $this, 'field_access_behavior' ),
			'ou-parameter-protect',
			'oupb_main_section'
		);

		// ログイン中管理者／編集者のバイパス
		add_settings_field(
			'bypass_admin_editor',
			__( '管理者／編集者のバイパス', 'ou-parameter-protect' ),
			array( $this, 'field_bypass_admin_editor' ),
			'ou-parameter-protect',
			'oupb_main_section'
		);

		// SEO 設定
		add_settings_field(
			'seo_settings',
			__( 'SEO 設定', 'ou-parameter-protect' ),
			array( $this, 'field_seo_settings' ),
			'ou-parameter-protect',
			'oupb_main_section'
		);

		// キャッシュ制御
		add_settings_field(
			'disable_cache_on_protect',
			__( 'キャッシュ制御', 'ou-parameter-protect' ),
			array( $this, 'field_cache_control' ),
			'ou-parameter-protect',
			'oupb_main_section'
		);

		// アンインストール時の設定
		add_settings_field(
			'keep_options_on_uninstall',
			__( 'アンインストール時の設定', 'ou-parameter-protect' ),
			array( $this, 'field_uninstall_behavior' ),
			'ou-parameter-protect',
			'oupb_main_section'
		);
	}

	/**
	 * オプションのサニタイズ
	 *
	 * @param array $input 生の入力値.
	 * @return array
	 */
	public function sanitize_options( $input ) {
		$defaults = OUPB_Plugin::get_default_options();

		if ( ! is_array( $input ) ) {
			$input = array();
		}

		$output = $defaults;

		// パラメータ名
		if ( isset( $input['param_name'] ) ) {
			$param = sanitize_text_field( $input['param_name'] );
			// 半角英数字とアンダースコアのみ許可
			$param = preg_replace( '/[^a-zA-Z0-9_]/', '', $param );
			if ( '' === $param ) {
				$param = $defaults['param_name'];
			}
			$output['param_name'] = $param;
		}

		// 不正アクセス時の挙動
		if ( isset( $input['access_behavior'] ) ) {
			$behavior = sanitize_text_field( $input['access_behavior'] );
			$allowed  = array( 'redirect_page', 'redirect_url', 'message' );
			if ( in_array( $behavior, $allowed, true ) ) {
				$output['access_behavior'] = $behavior;
			}
		}

		// リダイレクト先ページID
		if ( isset( $input['redirect_page_id'] ) ) {
			$output['redirect_page_id'] = absint( $input['redirect_page_id'] );
		}

		// リダイレクト先URL
		if ( isset( $input['redirect_url'] ) ) {
			$url                       = esc_url_raw( $input['redirect_url'] );
			$output['redirect_url']    = $url;
		}

		// メッセージ本文
		if ( isset( $input['message_text'] ) ) {
			// シンプルなテキストとし、タグは許可しない
			$output['message_text'] = sanitize_textarea_field( $input['message_text'] );
		}

		// 管理者／編集者のバイパス
		$output['bypass_admin_editor'] = isset( $input['bypass_admin_editor'] ) ? 1 : 0;

		// パラメータ付きアクセス時 noindex
		$output['add_noindex_on_param'] = isset( $input['add_noindex_on_param'] ) ? 1 : 0;

		// 認証後にパラメータ削除
		$output['strip_param_after_auth'] = isset( $input['strip_param_after_auth'] ) ? 1 : 0;

		// 保護ページでキャッシュ抑止
		$output['disable_cache_on_protect'] = isset( $input['disable_cache_on_protect'] ) ? 1 : 0;

		// アンインストール時に設定を残す
		$output['keep_options_on_uninstall'] = isset( $input['keep_options_on_uninstall'] ) ? 1 : 0;

		return $output;
	}

	/**
	 * 設定画面 HTML
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$options = OUPB_Plugin::get_options();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'パラメータ保護ページ設定', 'ou-parameter-protect' ); ?></h1>

			<form method="post" action="options.php">
				<?php
				settings_fields( 'oupb_settings_group' );
				do_settings_sections( 'ou-parameter-protect' );
				submit_button();
				?>
			</form>

			<hr />
			<p>
				<?php esc_html_e( 'このプラグインは、URLパラメータを簡易パスワードとして扱い、特定のページを招待制で公開するためのものです。高度なセキュリティ用途には使用しないでください。', 'ou-parameter-protect' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * フィールド: パラメータ名
	 *
	 * @return void
	 */
	public function field_param_name() {
		$options   = OUPB_Plugin::get_options();
		$paramname = isset( $options['param_name'] ) ? $options['param_name'] : 'id';
		?>
		<input
			type="text"
			name="<?php echo esc_attr( OUPB_OPTIONS_KEY ); ?>[param_name]"
			value="<?php echo esc_attr( $paramname ); ?>"
			class="regular-text"
			maxlength="64"
		/>
		<p class="description">
			<?php esc_html_e( '例: ?id=xxxx の「id」の部分です。半角英数字とアンダースコアのみ利用できます。', 'ou-parameter-protect' ); ?>
		</p>
		<?php
	}

	/**
	 * フィールド: 不正アクセス時の挙動
	 *
	 * @return void
	 */
	public function field_access_behavior() {
		$options  = OUPB_Plugin::get_options();
		$behavior = isset( $options['access_behavior'] ) ? $options['access_behavior'] : 'redirect_page';

		$redirect_page_id = isset( $options['redirect_page_id'] ) ? (int) $options['redirect_page_id'] : 0;
		$redirect_url     = isset( $options['redirect_url'] ) ? $options['redirect_url'] : '';
		$message_text     = isset( $options['message_text'] ) ? $options['message_text'] : '';
		?>
		<fieldset>
			<label>
				<input
					type="radio"
					name="<?php echo esc_attr( OUPB_OPTIONS_KEY ); ?>[access_behavior]"
					value="redirect_page"
					<?php checked( $behavior, 'redirect_page' ); ?>
				/>
				<?php esc_html_e( '固定ページへリダイレクト', 'ou-parameter-protect' ); ?>
			</label>
			<br/>

			<div style="margin: 6px 0 12px 20px;">
				<?php
				// 保護ページを選択肢から除外したいところだが、
				// 設定画面では単純にページ一覧を出す。
				// 実際のループ回避は Guard 側で行う。
				wp_dropdown_pages(
					array(
						'name'              => OUPB_OPTIONS_KEY . '[redirect_page_id]',
						'show_option_none'  => __( 'フロントページ（デフォルト）', 'ou-parameter-protect' ),
						'option_none_value' => 0,
						'selected'          => $redirect_page_id,
					)
				);
				?>
				<p class="description">
					<?php esc_html_e( '不正アクセス時に遷移させるページです。未選択の場合はフロントページになります。', 'ou-parameter-protect' ); ?>
				</p>
			</div>

			<label>
				<input
					type="radio"
					name="<?php echo esc_attr( OUPB_OPTIONS_KEY ); ?>[access_behavior]"
					value="redirect_url"
					<?php checked( $behavior, 'redirect_url' ); ?>
				/>
				<?php esc_html_e( '任意URLへリダイレクト', 'ou-parameter-protect' ); ?>
			</label>
			<br/>
			<div style="margin: 6px 0 12px 20px;">
				<input
					type="url"
					name="<?php echo esc_attr( OUPB_OPTIONS_KEY ); ?>[redirect_url]"
					value="<?php echo esc_attr( $redirect_url ); ?>"
					class="regular-text"
					placeholder="<?php echo esc_attr( home_url( '/' ) ); ?>"
				/>
				<p class="description">
					<?php esc_html_e( '不正アクセス時にリダイレクトする任意のURLです。', 'ou-parameter-protect' ); ?>
				</p>
			</div>

			<label>
				<input
					type="radio"
					name="<?php echo esc_attr( OUPB_OPTIONS_KEY ); ?>[access_behavior]"
					value="message"
					<?php checked( $behavior, 'message' ); ?>
				/>
				<?php esc_html_e( 'ページ内にメッセージを表示', 'ou-parameter-protect' ); ?>
			</label>
			<br/>
			<div style="margin: 6px 0 12px 20px;">
				<textarea
					name="<?php echo esc_attr( OUPB_OPTIONS_KEY ); ?>[message_text]"
					rows="3"
					cols="50"
					class="large-text"
				><?php echo esc_textarea( $message_text ); ?></textarea>
				<p class="description">
					<?php esc_html_e( '不正アクセス時にページ内に表示するメッセージです。', 'ou-parameter-protect' ); ?>
				</p>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * フィールド: 管理者／編集者のバイパス
	 *
	 * @return void
	 */
	public function field_bypass_admin_editor() {
		$options = OUPB_Plugin::get_options();
		$checked = ! empty( $options['bypass_admin_editor'] );
		?>
		<label>
			<input
				type="checkbox"
				name="<?php echo esc_attr( OUPB_OPTIONS_KEY ); ?>[bypass_admin_editor]"
				value="1"
				<?php checked( $checked ); ?>
			/>
			<?php esc_html_e( 'ログイン中の管理者／編集者は、パラメータや有効期限に関係なく常にページを閲覧できるようにする（推奨）', 'ou-parameter-protect' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'ONにすると、管理者／編集者はパラメータ認証およびnoindex・キャッシュ制御も含めた保護ロジックをスキップします。実際の挙動確認はログアウト状態または別ブラウザで行ってください。', 'ou-parameter-protect' ); ?>
		</p>
		<?php
	}

	/**
	 * フィールド: SEO 設定
	 *
	 * @return void
	 */
	public function field_seo_settings() {
		$options            = OUPB_Plugin::get_options();
		$add_noindex        = ! empty( $options['add_noindex_on_param'] );
		$strip_param        = ! empty( $options['strip_param_after_auth'] );
		?>
		<fieldset>
			<label>
				<input
					type="checkbox"
					name="<?php echo esc_attr( OUPB_OPTIONS_KEY ); ?>[add_noindex_on_param]"
					value="1"
					<?php checked( $add_noindex ); ?>
				/>
				<?php esc_html_e( 'パラメータ付きアクセス時に noindex を付与する', 'ou-parameter-protect' ); ?>
			</label>
			<p class="description">
				<?php esc_html_e( 'Search Console などでパラメータ付きURLがインデックスされるのを抑止します。', 'ou-parameter-protect' ); ?>
			</p>

			<label>
				<input
					type="checkbox"
					name="<?php echo esc_attr( OUPB_OPTIONS_KEY ); ?>[strip_param_after_auth]"
					value="1"
					<?php checked( $strip_param ); ?>
				/>
				<?php esc_html_e( '認証後にURLからパラメータを削除する（302リダイレクト）', 'ou-parameter-protect' ); ?>
			</label>
			<p class="description">
				<?php esc_html_e( '正しいパラメータでアクセスした後、クッキーで認証状態を保持しつつ、パラメータ無しのURLへリダイレクトします。', 'ou-parameter-protect' ); ?>
			</p>
		</fieldset>
		<?php
	}

	/**
	 * フィールド: キャッシュ制御
	 *
	 * @return void
	 */
	public function field_cache_control() {
		$options    = OUPB_Plugin::get_options();
		$disable_it = ! empty( $options['disable_cache_on_protect'] );
		?>
		<label>
			<input
				type="checkbox"
				name="<?php echo esc_attr( OUPB_OPTIONS_KEY ); ?>[disable_cache_on_protect]"
				value="1"
				<?php checked( $disable_it ); ?>
			/>
			<?php esc_html_e( '保護対象ページではブラウザキャッシュを抑止する（推奨）', 'ou-parameter-protect' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'no-store / no-cache などのヘッダーを付与し、他ユーザーが同一端末を利用した際のリスクやキャッシュによる誤動作を軽減します。', 'ou-parameter-protect' ); ?>
		</p>
		<?php
	}

	/**
	 * フィールド: アンインストール時の設定
	 *
	 * @return void
	 */
	public function field_uninstall_behavior() {
		$options  = OUPB_Plugin::get_options();
		$keep     = ! empty( $options['keep_options_on_uninstall'] );
		?>
		<fieldset>
			<label>
				<input
					type="radio"
					name="<?php echo esc_attr( OUPB_OPTIONS_KEY ); ?>[keep_options_on_uninstall]"
					value="1"
					<?php checked( $keep, true ); ?>
				/>
				<?php esc_html_e( 'プラグイン削除時も設定を残す（推奨）', 'ou-parameter-protect' ); ?>
			</label>
			<br/>
			<label>
				<input
					type="radio"
					name="<?php echo esc_attr( OUPB_OPTIONS_KEY ); ?>[keep_options_on_uninstall]"
					value="0"
					<?php checked( $keep, false ); ?>
				/>
				<?php esc_html_e( 'プラグイン削除時に設定を完全に削除する', 'ou-parameter-protect' ); ?>
			</label>
			<p class="description">
				<?php esc_html_e( 'ここで削除されるのは wp_options に保存された設定のみです。各ページのブロックに保存されたシークレット値や有効期限（post_meta）は削除されません。', 'ou-parameter-protect' ); ?>
			</p>
		</fieldset>
		<?php
	}
}
