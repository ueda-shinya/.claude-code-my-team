<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * メインプラグインクラス
 *
 * - 設定値の管理（デフォルト・取得・更新）
 * - 有効化／アンインストール処理
 * - 各機能クラス（設定画面／ガード／ブロック）の初期化
 */
class OUPB_Plugin {

	/**
	 * シングルトンインスタンス
	 *
	 * @var OUPB_Plugin|null
	 */
	protected static $instance = null;

	/**
	 * プラグインの現在の設定値（キャッシュ用）
	 *
	 * @var array
	 */
	protected static $options = array();

	/**
	 * 初期化エントリポイント
	 *
	 * plugins_loaded で呼び出される
	 */
	public static function init() {
		if ( null !== self::$instance ) {
			return;
		}

		self::$instance = new self();

		// オプションを読み込み
		self::$options = self::load_options();

		// 設定画面
		if ( class_exists( 'OUPB_Settings' ) ) {
			new OUPB_Settings();
		}

		// ブロック登録
		if ( class_exists( 'OUPB_Block' ) ) {
			new OUPB_Block();
		}

		// フロント側のガード
		if ( class_exists( 'OUPB_Guard' ) ) {
			new OUPB_Guard();
		}
	}

	/**
	 * プラグイン有効化時の処理
	 *
	 * - デフォルト設定を登録（既存設定がなければ）
	 *
	 * @return void
	 */
	public static function activate() {
		$current = get_option( OUPB_OPTIONS_KEY, null );

		if ( null === $current ) {
			// 設定が存在しない場合のみ、デフォルトを登録
			add_option( OUPB_OPTIONS_KEY, self::get_default_options() );
		}
	}

	/**
	 * プラグイン削除時の処理
	 *
	 * - 「設定を残す／削除」をオプションに従って実行
	 * - ここで削除するのは wp_options に保存されているオプションのみ
	 * - post_meta（ブロックのシークレット値や有効期限）は削除しない
	 *
	 * @return void
	 */
	public static function uninstall() {
		// セキュリティ：管理画面からのアンインストールを想定
		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			return;
		}

		$options = get_option( OUPB_OPTIONS_KEY, array() );

		$keep = isset( $options['keep_options_on_uninstall'] )
			? (int) $options['keep_options_on_uninstall']
			: 1;

		if ( $keep ) {
			// 設定を残す
			return;
		}

		// 設定を削除
		delete_option( OUPB_OPTIONS_KEY );

		// 要件上、post_meta は削除対象外
	}

	/**
	 * デフォルト設定値を返す
	 *
	 * @return array
	 */
	public static function get_default_options() {
		return array(
			// クエリパラメータ名（例： ?id=xxxx の id 部分）
			'param_name'                => 'id',

			// 不正アクセス時の挙動
			// redirect_page: 固定ページへリダイレクト
			// redirect_url : 任意URLへリダイレクト
			// message      : メッセージ表示
			'access_behavior'          => 'redirect_page',

			// リダイレクト先固定ページID（0 または未設定の場合はフロントページ）
			'redirect_page_id'         => 0,

			// リダイレクト先URL（access_behavior === 'redirect_url' のとき使用）
			'redirect_url'             => '',

			// メッセージ表示モード時のメッセージ本文
			'message_text'             => 'このページは招待制です。管理者にお問い合わせください。',

			// ログイン中の管理者／編集者をバイパスするか
			// 1: バイパスON（パラメータ・期限・noindex等すべてスキップ）
			// 0: バイパスOFF（一般ユーザーと同様にチェック）
			'bypass_admin_editor'      => 1,

			// パラメータ付きアクセス時に noindex を付与するか
			'add_noindex_on_param'     => 1,

			// 認証後にURLからパラメータを削除するか
			'strip_param_after_auth'   => 1,

			// 保護ページでブラウザキャッシュを抑止するか
			'disable_cache_on_protect' => 1,

			// プラグイン削除時に設定を残すか（1: 残す / 0: 削除）
			'keep_options_on_uninstall'=> 1,
		);
	}

	/**
	 * オプションを読み込み、デフォルトで補完した配列を返す
	 *
	 * @return array
	 */
	protected static function load_options() {
		$defaults = self::get_default_options();
		$saved    = get_option( OUPB_OPTIONS_KEY, array() );

		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		// デフォルトで補完
		$options = array_merge( $defaults, $saved );

		return $options;
	}

	/**
	 * 現在のオプション配列を返す
	 *
	 * @return array
	 */
	public static function get_options() {
		// まだロードされていない場合も考慮
		if ( empty( self::$options ) ) {
			self::$options = self::load_options();
		}

		return self::$options;
	}

	/**
	 * 特定キーのオプション値を取得
	 *
	 * @param string     $key     オプションキー
	 * @param mixed|null $default デフォルト値
	 *
	 * @return mixed|null
	 */
	public static function get_option( $key, $default = null ) {
		$options = self::get_options();

		if ( array_key_exists( $key, $options ) ) {
			return $options[ $key ];
		}

		return $default;
	}

	/**
	 * オプションを更新し、キャッシュも更新
	 *
	 * @param array $new_options 保存するオプション配列
	 *
	 * @return void
	 */
	public static function update_options( array $new_options ) {
		$defaults = self::get_default_options();

		// デフォルトにマージして不要なキーを排除
		$merged = array_merge( $defaults, $new_options );
		$clean  = array_intersect_key( $merged, $defaults );

		update_option( OUPB_OPTIONS_KEY, $clean );

		self::$options = $clean;
	}

	/**
	 * 管理者／編集者バイパス対象かどうか
	 *
	 * - 要件に基づき、バイパスONのときは「パラメータ・有効期限・noindex・キャッシュ抑止含む保護ロジックをすべてスキップ」
	 *
	 * @return bool
	 */
	public static function is_bypass_user() {
		// 設定がOFFなら常に false
		$bypass = (int) self::get_option( 'bypass_admin_editor', 1 );
		if ( ! $bypass ) {
			return false;
		}

		if ( ! is_user_logged_in() ) {
			return false;
		}

		$user = wp_get_current_user();
		if ( ! $user || empty( $user->roles ) ) {
			return false;
		}

		$roles = (array) $user->roles;

		// administrator or editor のいずれかを含んでいればバイパス対象
		if ( array_intersect( array( 'administrator', 'editor' ), $roles ) ) {
			return true;
		}

		return false;
	}
}
