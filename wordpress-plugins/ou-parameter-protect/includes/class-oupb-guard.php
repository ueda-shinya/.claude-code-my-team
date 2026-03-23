<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * フロント側のガード処理クラス
 *
 * - template_redirect でパラメータチェック／認証／リダイレクトを実施
 * - the_content フィルタで「メッセージ表示モード」のコンテンツ置き換え
 * - send_headers でキャッシュ抑止ヘッダー付与
 * - wp_head で noindex / canonical を出力（一般ユーザー向け）
 */
class OUPB_Guard {

	/**
	 * 現在のリクエストが保護対象ページかどうか
	 *
	 * @var bool
	 */
	protected $is_protected_page = false;

	/**
	 * 現在の投稿オブジェクト
	 *
	 * @var WP_Post|null
	 */
	protected $current_post = null;

	/**
	 * メッセージ表示モード時に true
	 *
	 * @var bool
	 */
	protected $deny_with_message = false;

	/**
	 * メッセージ表示モード時のメッセージ本文
	 *
	 * @var string
	 */
	protected $deny_message = '';

	/**
	 * 現在のパラメータ付きアクセスかどうか
	 *
	 * @var bool
	 */
	protected $has_param_access = false;

	/**
	 * 使用するパラメータ名
	 *
	 * @var string
	 */
	protected $param_name = 'id';

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		// 認証・リダイレクトロジック
		add_action( 'template_redirect', array( $this, 'handle_protection' ), 0 );

		// キャッシュ制御ヘッダー
		add_action( 'send_headers', array( $this, 'send_cache_headers' ) );

		// noindex / canonical 出力
		add_action( 'wp_head', array( $this, 'output_meta_tags' ), 0 );

		// メッセージ表示モード時のコンテンツ差し替え
		add_filter( 'the_content', array( $this, 'filter_the_content' ), 999 );
	}

	/**
	 * 保護ロジックのメイン
	 *
	 * @return void
	 */
	public function handle_protection() {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		if ( ! is_singular() ) {
			return;
		}

		global $post;

		if ( ! $post instanceof WP_Post ) {
			return;
		}

		$this->current_post = $post;

		// ページに保護ブロックが含まれているか（1ページ1ブロック想定）
		if ( ! function_exists( 'has_block' ) || ! has_block( 'oupb/protected-page', $post ) ) {
			return;
		}

		$this->is_protected_page = true;

		// 管理者／編集者でバイパスONなら、以降の処理をスキップ
		if ( OUPB_Plugin::is_bypass_user() ) {
			return;
		}

		$options = OUPB_Plugin::get_options();

		// 使用するパラメータ名
		$this->param_name       = isset( $options['param_name'] ) ? $options['param_name'] : 'id';
		$this->has_param_access = isset( $_GET[ $this->param_name ] ) && '' !== (string) $_GET[ $this->param_name ];

		// ブロックからシークレット値と有効期限を取得
		$block_attrs = $this->get_first_protect_block_attributes( $post );
		$secret      = isset( $block_attrs['secret'] ) ? (string) $block_attrs['secret'] : '';
		$expiry_raw  = isset( $block_attrs['expiry'] ) ? (string) $block_attrs['expiry'] : '';

		if ( '' === $secret ) {
			// 本来は設定必須だが、万が一空の場合は保護をスキップ（閲覧許可）
			return;
		}

		// 有効期限の判定
		$now        = current_time( 'timestamp' );
		$expiry_ts  = null;
		$is_expired = false;

		if ( '' !== $expiry_raw ) {
			$expiry_ts = strtotime( $expiry_raw );
			if ( $expiry_ts && $now > $expiry_ts ) {
				$is_expired = true;
			}
		}

		// 一般ユーザー向け 認証判定
		$post_id      = (int) $post->ID;
		$cookie_name  = $this->get_cookie_name( $post_id );
		$cookie_value = isset( $_COOKIE[ $cookie_name ] ) ? (string) $_COOKIE[ $cookie_name ] : '';

		$expected_token   = $this->build_token( $post_id, $secret );
		$has_valid_cookie = ( $cookie_value && hash_equals( $expected_token, $cookie_value ) );

		$authorized = false;

		if ( ! $is_expired && $has_valid_cookie ) {
			// 有効期限内かつクッキー有効なら認証済み
			$authorized = true;
		} elseif ( ! $is_expired && $this->has_param_access ) {
			// パラメータでのアクセス
			$param_value = (string) wp_unslash( $_GET[ $this->param_name ] );
			if ( hash_equals( $secret, $param_value ) ) {
				$authorized = true;
				// 認証済みクッキーをセット（セッションクッキー）
				$this->set_auth_cookie( $cookie_name, $expected_token );
			}
		}

		// 認証成功かつURLからパラメータを削除する設定の場合
		if ( $authorized ) {
			if ( ! $is_expired && ! headers_sent() ) {
				$strip = ! empty( $options['strip_param_after_auth'] );
				if ( $strip && $this->has_param_access ) {
					$clean_url = $this->get_clean_url_without_param();
					if ( $clean_url ) {
						wp_safe_redirect( $clean_url, 302 );
						exit;
					}
				}
			}
			// 通常のレンダリングを許可
			return;
		}

		// ここまできたら「一般ユーザーに対しては閲覧不可」
		// → 不正アクセス時の挙動（リダイレクト or メッセージ表示）に従う

		$behavior = isset( $options['access_behavior'] ) ? $options['access_behavior'] : 'redirect_page';

		if ( 'message' === $behavior ) {
			// メッセージ表示モード
			$this->deny_with_message = true;
			$this->deny_message      = isset( $options['message_text'] ) ? (string) $options['message_text'] : '';
			// the_content フィルタでメッセージに差し替える
			return;
		}

		// リダイレクトモード
		$target_url = '';

		if ( 'redirect_url' === $behavior ) {
			$target_url = isset( $options['redirect_url'] ) ? (string) $options['redirect_url'] : '';
		} else {
			// redirect_page またはデフォルト
			$page_id = isset( $options['redirect_page_id'] ) ? (int) $options['redirect_page_id'] : 0;
			if ( $page_id > 0 ) {
				$permalink = get_permalink( $page_id );
				if ( $permalink ) {
					$target_url = $permalink;
				}
			}
			if ( '' === $target_url ) {
				$target_url = home_url( '/' );
			}
		}

		// リダイレクトループ回避（リダイレクト先 = 現在URL の場合はフロントページへ）
		$current_url = $this->get_current_url();
		if ( $target_url && $current_url ) {
			// クエリなどを無視して比較したい場合は parse_url を使うが、
			// ここでは単純に完全一致を回避条件とする。
			if ( $target_url === $current_url ) {
				$target_url = home_url( '/' );
			}
		}

		if ( $target_url && ! headers_sent() ) {
			// redirect_url（任意URL）の場合は、外部ドメインも許可したいので wp_redirect を使用
			if ( 'redirect_url' === $behavior ) {
				wp_redirect( $target_url, 302 );
			} else {
				// 固定ページへのリダイレクトは、同一サイト内のはずなので wp_safe_redirect のまま
				wp_safe_redirect( $target_url, 302 );
			}
			exit;
		}
	}

	/**
	 * the_content フィルタ
	 *
	 * - メッセージ表示モード時のみ、コンテンツをメッセージに差し替える
	 *
	 * @param string $content 元のコンテンツ.
	 * @return string
	 */
	public function filter_the_content( $content ) {
		if ( ! $this->is_protected_page ) {
			return $content;
		}

		// 管理者／編集者バイパスONの場合は何もしない
		if ( OUPB_Plugin::is_bypass_user() ) {
			return $content;
		}

		if ( ! $this->deny_with_message ) {
			return $content;
		}

		$message = $this->deny_message;
		if ( '' === trim( $message ) ) {
			$message = __( 'このページは招待制です。管理者にお問い合わせください。', 'ou-parameter-protect' );
		}

		// シンプルにプレーンテキスト → pタグ＆br変換
		$message = esc_html( $message );
		$message = nl2br( $message );

		return '<div class="oupb-protected-message">' . $message . '</div>';
	}

	/**
	 * キャッシュ制御ヘッダーの付与
	 *
	 * @return void
	 */
	public function send_cache_headers() {
		if ( ! $this->is_protected_page ) {
			return;
		}

		// 管理者／編集者バイパスONの場合はヘッダーもスキップ
		if ( OUPB_Plugin::is_bypass_user() ) {
			return;
		}

		$options         = OUPB_Plugin::get_options();
		$disable_cache   = ! empty( $options['disable_cache_on_protect'] );
		if ( ! $disable_cache ) {
			return;
		}

		if ( headers_sent() ) {
			return;
		}

		// ブラウザキャッシュを抑止
		// WP標準の nocache_headers() でもよいが、要件に合わせて明示的に送る
		header( 'Cache-Control: private, no-store, max-age=0, must-revalidate' );
		header( 'Pragma: no-cache' );
	}

	/**
	 * noindex / canonical の出力
	 *
	 * @return void
	 */
	public function output_meta_tags() {
		if ( ! $this->is_protected_page ) {
			return;
		}

		// 管理者／編集者バイパスONの場合は、一般ユーザー向け制御はスキップ
		if ( OUPB_Plugin::is_bypass_user() ) {
			return;
		}

		$options = OUPB_Plugin::get_options();

		$add_noindex = ! empty( $options['add_noindex_on_param'] );

		// パラメータ付きアクセス時のみ noindex / canonical を出力
		if ( ! $add_noindex || ! $this->has_param_access ) {
			return;
		}

		$canonical = $this->get_clean_url_without_param();
		if ( ! $canonical ) {
			return;
		}

		// ここでは直接タグを出力する。
		// Yoast等のSEOプラグインとの連携用フックは、実装フェーズで別途検討。
		echo '<meta name="robots" content="noindex" />' . "\n";
		echo '<link rel="canonical" href="' . esc_url( $canonical ) . '" />' . "\n";
	}

	/**
	 * 最初に見つかった保護ブロックの attributes を返す
	 *
	 * @param WP_Post $post 投稿オブジェクト.
	 * @return array
	 */
	protected function get_first_protect_block_attributes( WP_Post $post ) {
		$content = $post->post_content;
		if ( '' === trim( $content ) ) {
			return array();
		}

		$blocks = parse_blocks( $content );
		if ( ! is_array( $blocks ) ) {
			return array();
		}

		return $this->find_protect_block_attrs_recursive( $blocks );
	}

	/**
	 * ブロック配列を再帰的に探索して保護ブロックを探す
	 *
	 * @param array $blocks ブロック配列.
	 * @return array
	 */
	protected function find_protect_block_attrs_recursive( array $blocks ) {
		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}

			if ( isset( $block['blockName'] ) && 'oupb/protected-page' === $block['blockName'] ) {
				return isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();
			}

			if ( isset( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) && ! empty( $block['innerBlocks'] ) ) {
				$found = $this->find_protect_block_attrs_recursive( $block['innerBlocks'] );
				if ( ! empty( $found ) ) {
					return $found;
				}
			}
		}

		return array();
	}

	/**
	 * 認証クッキー名を生成
	 *
	 * @param int $post_id 投稿ID.
	 * @return string
	 */
	protected function get_cookie_name( $post_id ) {
		$post_id = (int) $post_id;
		return 'oupb_auth_' . $post_id;
	}

	/**
	 * 認証トークンを生成
	 *
	 * @param int    $post_id 投稿ID.
	 * @param string $secret  シークレット値.
	 * @return string
	 */
	protected function build_token( $post_id, $secret ) {
		$post_id = (int) $post_id;
		$data    = $post_id . '|' . $secret;

		// AUTH_SALT が定義されていないケースも考慮して fallback
		$salt = defined( 'AUTH_SALT' ) ? AUTH_SALT : ( defined( 'LOGGED_IN_SALT' ) ? LOGGED_IN_SALT : wp_salt( 'auth' ) );

		return hash_hmac( 'sha256', $data, $salt );
	}

	/**
	 * 認証クッキーをセット（セッションクッキー）
	 *
	 * @param string $name  クッキー名.
	 * @param string $value クッキー値.
	 * @return void
	 */
	protected function set_auth_cookie( $name, $value ) {
		if ( headers_sent() ) {
			return;
		}

		// セッションクッキー：有効期限 0
		setcookie(
			$name,
			$value,
			0,
			COOKIEPATH ? COOKIEPATH : '/',
			COOKIE_DOMAIN,
			is_ssl(),
			true
		);
	}

	/**
	 * 現在のURLを取得（スキーム＋ホスト＋パス＋クエリ）
	 *
	 * @return string
	 */
	protected function get_current_url() {
		$scheme = is_ssl() ? 'https' : 'http';
		$host   = isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : '';
		$uri    = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';

		if ( '' === $host ) {
			return '';
		}

		return $scheme . '://' . $host . $uri;
	}

	/**
	 * 現在URLから本プラグインのパラメータだけを取り除いたURLを返す
	 *
	 * - canonical / 認証後リダイレクトで利用
	 *
	 * @return string
	 */
	protected function get_clean_url_without_param() {
		$current = $this->get_current_url();
		if ( '' === $current ) {
			return '';
		}

		$parts = wp_parse_url( $current );
		if ( ! $parts || ! is_array( $parts ) ) {
			return $current;
		}

		$scheme = isset( $parts['scheme'] ) ? $parts['scheme'] : ( is_ssl() ? 'https' : 'http' );
		$host   = isset( $parts['host'] ) ? $parts['host'] : '';
		$path   = isset( $parts['path'] ) ? $parts['path'] : '';
		$query  = array();

		if ( isset( $parts['query'] ) && '' !== $parts['query'] ) {
			parse_str( $parts['query'], $query );
			if ( isset( $query[ $this->param_name ] ) ) {
				unset( $query[ $this->param_name ] );
			}
		}

		$base = $scheme . '://' . $host . $path;

		if ( ! empty( $query ) ) {
			$base .= '?' . http_build_query( $query );
		}

		return $base;
	}
}
