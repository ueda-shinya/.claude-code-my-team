<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ブロック登録クラス
 *
 * - oupb/protected-page ブロックを登録
 * - エディタ側の JS を読み込み
 * - ブロック属性（secret / expiry）を定義
 * - フロント側では何も出力しない（レンダーコールバックで空文字を返す）
 */
class OUPB_Block {

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_block' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
	}

	/**
	 * ブロック登録
	 *
	 * @return void
	 */
	public function register_block() {
		// Gutenberg が利用できない環境では何もしない
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		$args = array(
			'api_version'     => 2,
			'editor_script'   => 'oupb-block-editor',
			'render_callback' => array( $this, 'render_protected_block' ),
			'attributes'      => array(
				'secret' => array(
					'type'    => 'string',
					'default' => '',
				),
				'expiry' => array(
					'type'    => 'string',
					'default' => '',
				),
			),
			'supports'        => array(
				// レイアウト系は特に不要だが、カスタムクラス名等はデフォルトでONのままでも害は少ない
				'html' => false,
			),
		);

		register_block_type( 'oupb/protected-page', $args );
	}

	/**
	 * エディタ用スクリプトの読み込み
	 *
	 * @return void
	 */
	public function enqueue_block_editor_assets() {
		// Gutenberg がなければ何もしない
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		$handle = 'oupb-block-editor';
		$src    = OUPB_PLUGIN_URL . 'assets/js/block.js';

		// バージョンはプラグインのバージョンに合わせる（キャッシュバスティング用）
		$ver = defined( 'OUPB_PLUGIN_VERSION' ) ? OUPB_PLUGIN_VERSION : '1.0.0';

		wp_enqueue_script(
			$handle,
			$src,
			array(
				'wp-blocks',
				'wp-element',
				'wp-components',
				'wp-i18n',
				'wp-editor',
				'wp-block-editor',
				'wp-data',
			),
			$ver,
			true
		);

		// 設定値をJSに渡す
		$options = OUPB_Plugin::get_options();

		wp_localize_script(
			$handle,
			'OUPB_BLOCK_SETTINGS',
			array(
				'paramName'   => isset( $options['param_name'] ) ? $options['param_name'] : 'id',
				// 翻訳テキストなどもここに入れておくとJS側で使いやすい
				'i18n'        => array(
					'title'          => __( 'パラメータ保護ページ', 'ou-parameter-protect' ),
					'description'    => __( 'このブロックが挿入されたページは、URLパラメータによって保護されます。', 'ou-parameter-protect' ),
					'labelSecret'    => __( 'シークレット値（必須）', 'ou-parameter-protect' ),
					'secretHelp'     => __( 'URLパラメータの値として使用される文字列です。URLを知っている人だけがページを閲覧できます。', 'ou-parameter-protect' ),
					'labelExpiry'    => __( '有効期限（任意）', 'ou-parameter-protect' ),
					'expiryHelp'     => __( 'この日時を過ぎると、一般ユーザーはこのURLからアクセスできなくなります。空欄の場合は期限なしです。', 'ou-parameter-protect' ),
					'labelPreview'   => __( '招待URLプレビュー', 'ou-parameter-protect' ),
					'copyButton'     => __( 'URLをコピー', 'ou-parameter-protect' ),
					'copySuccess'    => __( 'URLをコピーしました。', 'ou-parameter-protect' ),
					'copyFailure'    => __( 'URLのコピーに失敗しました。', 'ou-parameter-protect' ),
					'noticeMultiple' => __( 'このページには複数の保護ブロックがあります。最初の1つだけが有効になります。', 'ou-parameter-protect' ),
					'noticeUnpublished' => __( 'まだ公開されていないため、URLは変更される可能性があります。公開後にURLを確認してください。', 'ou-parameter-protect' ),
				),
			)
		);
	}

	/**
	 * ブロックのレンダーコールバック
	 *
	 * - フロント側には何も出さない（完全にロジック専用ブロック）
	 *
	 * @param array  $attributes ブロック属性.
	 * @param string $content    ブロックコンテンツ.
	 * @return string
	 */
	public function render_protected_block( $attributes, $content ) {
		// あえて何も返さない（空文字）
		return '';
	}
}
