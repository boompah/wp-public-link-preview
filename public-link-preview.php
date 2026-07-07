<?php
/**
 * Plugin Name:       Public Link Preview
 * Plugin URI:        https://boompah.com
 * Description:       Share a secret public link to preview any draft, pending, or scheduled post or page — no login required. The link stays live until you turn it off from the editor.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.2
 * Author:            Ryan Bollenbach
 * Author URI:        https://boompah.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       public-link-preview
 */

defined( 'ABSPATH' ) || exit;

final class Public_Link_Preview {

	const VERSION      = '1.0.0';
	const META_ENABLED = '_plp_enabled';
	const META_TOKEN   = '_plp_token';
	const QUERY_ARG    = 'plp_token';
	const NONCE_ACTION = 'plp_toggle';

	/**
	 * Post statuses that a public preview link can expose.
	 *
	 * @var string[]
	 */
	private $previewable_statuses = array( 'draft', 'pending', 'future' );

	/**
	 * @var Public_Link_Preview|null
	 */
	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		// Editor UI.
		add_action( 'post_submitbox_misc_actions', array( $this, 'render_classic_toggle' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_classic_assets' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );

		// Toggle endpoint (works for both editors).
		add_action( 'wp_ajax_plp_toggle', array( $this, 'ajax_toggle' ) );

		// Front end: let a valid token through to unpublished posts.
		add_filter( 'posts_results', array( $this, 'maybe_show_public_preview' ), 10, 2 );
	}

	/* ------------------------------------------------------------------ */
	/* Editor UI                                                           */
	/* ------------------------------------------------------------------ */

	/**
	 * Whether the toggle should be offered for this post.
	 *
	 * @param WP_Post|null $post Post object.
	 * @return bool
	 */
	private function supports_post( $post ) {
		return $post instanceof WP_Post
			&& 'attachment' !== $post->post_type
			&& is_post_type_viewable( $post->post_type )
			&& current_user_can( 'edit_post', $post->ID );
	}

	/**
	 * Data shared with both editor scripts.
	 *
	 * @param WP_Post $post Post object.
	 * @return array
	 */
	private function script_data( $post ) {
		$enabled = (bool) get_post_meta( $post->ID, self::META_ENABLED, true );

		return array(
			'postId'  => $post->ID,
			'enabled' => $enabled,
			'url'     => $enabled ? $this->preview_url( $post->ID ) : '',
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( self::NONCE_ACTION ),
			'i18n'    => array(
				'copied' => __( 'Copied!', 'public-link-preview' ),
				'error'  => __( 'Could not update the preview link.', 'public-link-preview' ),
			),
		);
	}

	/**
	 * Checkbox in the classic editor's Publish box.
	 *
	 * @param WP_Post $post Post object.
	 */
	public function render_classic_toggle( $post ) {
		if ( ! $this->supports_post( $post ) ) {
			return;
		}

		$enabled = (bool) get_post_meta( $post->ID, self::META_ENABLED, true );
		$url     = $enabled ? $this->preview_url( $post->ID ) : '';
		?>
		<div class="misc-pub-section misc-pub-public-link-preview">
			<span class="dashicons dashicons-admin-links" style="color:#8c8f94;"></span>
			<label for="plp-toggle">
				<input type="checkbox" id="plp-toggle" <?php checked( $enabled ); ?> />
				<?php esc_html_e( 'Enable public preview link', 'public-link-preview' ); ?>
			</label>
			<div id="plp-link-row" style="margin-top:6px;<?php echo esc_attr( $enabled ? '' : 'display:none;' ); ?>">
				<input type="text" id="plp-url" readonly value="<?php echo esc_attr( $url ); ?>" style="width:100%;font-size:12px;" onfocus="this.select();" />
				<button type="button" class="button button-small" id="plp-copy" style="margin-top:4px;">
					<?php esc_html_e( 'Copy link', 'public-link-preview' ); ?>
				</button>
				<p class="description" style="margin:4px 0 0;">
					<?php esc_html_e( 'Anyone with this link can view the post until you disable it.', 'public-link-preview' ); ?>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Script for the classic editor toggle.
	 *
	 * @param string $hook Current admin page.
	 */
	public function enqueue_classic_assets( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $screen && $screen->is_block_editor() ) {
			return;
		}

		$post = get_post();
		if ( ! $this->supports_post( $post ) ) {
			return;
		}

		wp_enqueue_script(
			'plp-classic',
			plugins_url( 'js/classic.js', __FILE__ ),
			array( 'jquery' ),
			self::VERSION,
			true
		);
		wp_localize_script( 'plp-classic', 'plpEditorData', $this->script_data( $post ) );
	}

	/**
	 * Script for the block editor panel.
	 */
	public function enqueue_block_editor_assets() {
		$post = get_post();
		if ( ! $this->supports_post( $post ) ) {
			return;
		}

		wp_enqueue_script(
			'plp-editor',
			plugins_url( 'js/editor.js', __FILE__ ),
			array( 'wp-plugins', 'wp-element', 'wp-components', 'wp-data', 'wp-i18n', 'wp-edit-post' ),
			self::VERSION,
			true
		);
		wp_localize_script( 'plp-editor', 'plpEditorData', $this->script_data( $post ) );
		wp_set_script_translations( 'plp-editor', 'public-link-preview' );
	}

	/* ------------------------------------------------------------------ */
	/* Toggle endpoint                                                     */
	/* ------------------------------------------------------------------ */

	/**
	 * Enable or disable the preview link for a post. The change takes
	 * effect immediately, independent of saving the post.
	 */
	public function ajax_toggle() {
		check_ajax_referer( self::NONCE_ACTION );

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to change this post.', 'public-link-preview' ) ), 403 );
		}

		$enable = isset( $_POST['enabled'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['enabled'] ) );

		if ( $enable ) {
			update_post_meta( $post_id, self::META_ENABLED, 1 );

			if ( ! get_post_meta( $post_id, self::META_TOKEN, true ) ) {
				update_post_meta( $post_id, self::META_TOKEN, wp_generate_password( 32, false ) );
			}

			wp_send_json_success(
				array(
					'enabled' => true,
					'url'     => $this->preview_url( $post_id ),
				)
			);
		}

		// Disabling deletes the token, so re-enabling issues a fresh URL
		// and any previously shared link stops working for good.
		delete_post_meta( $post_id, self::META_ENABLED );
		delete_post_meta( $post_id, self::META_TOKEN );

		wp_send_json_success(
			array(
				'enabled' => false,
				'url'     => '',
			)
		);
	}

	/**
	 * Build the shareable preview URL for a post.
	 *
	 * Uses plain query args (?p= / ?page_id=) because unpublished posts
	 * have no permalink yet. `preview=true` keeps redirect_canonical from
	 * stripping the query string.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public function preview_url( $post_id ) {
		$post  = get_post( $post_id );
		$token = get_post_meta( $post_id, self::META_TOKEN, true );

		if ( ! $post || ! $token ) {
			return '';
		}

		$args = array(
			'preview'       => 'true',
			self::QUERY_ARG => $token,
		);

		// The page_id query var only resolves the built-in 'page' type;
		// everything else (including hierarchical CPTs) uses p + post_type.
		if ( 'page' === $post->post_type ) {
			$args['page_id'] = $post_id;
		} else {
			$args['p'] = $post_id;
			if ( 'post' !== $post->post_type ) {
				$args['post_type'] = $post->post_type;
			}
		}

		return add_query_arg( $args, home_url( '/' ) );
	}

	/* ------------------------------------------------------------------ */
	/* Front end                                                           */
	/* ------------------------------------------------------------------ */

	/**
	 * If the request carries a valid token for an unpublished post, treat
	 * it as published for this request only.
	 *
	 * Runs on posts_results because WP_Query's status/permission check for
	 * singular queries happens after this filter.
	 *
	 * @param WP_Post[] $posts Found posts.
	 * @param WP_Query  $query Current query.
	 * @return WP_Post[]
	 */
	public function maybe_show_public_preview( $posts, $query ) {
		// No nonce here by design: this runs for anonymous visitors following
		// a shared link. Authorization is the secret token itself, compared
		// in constant time against the stored value.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if (
			empty( $_GET[ self::QUERY_ARG ] )
			|| ! $query->is_main_query()
			|| ! $query->is_preview()
			|| ! $query->is_singular()
			|| 1 !== count( $posts )
		) {
			return $posts;
		}

		$post  = $posts[0];
		$token = sanitize_text_field( wp_unslash( $_GET[ self::QUERY_ARG ] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if (
			! in_array( $post->post_status, $this->previewable_statuses, true )
			|| ! $this->verify_token( $post->ID, $token )
		) {
			return $posts;
		}

		$post->post_status = 'publish';

		nocache_headers();
		add_filter( 'wp_robots', 'wp_robots_no_robots' );
		add_filter( 'comments_open', '__return_false' );
		add_filter( 'pings_open', '__return_false' );

		return $posts;
	}

	/**
	 * Constant-time check of the supplied token against the stored one.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $token   Token from the request.
	 * @return bool
	 */
	private function verify_token( $post_id, $token ) {
		if ( ! get_post_meta( $post_id, self::META_ENABLED, true ) ) {
			return false;
		}

		$stored = get_post_meta( $post_id, self::META_TOKEN, true );

		return is_string( $stored ) && '' !== $stored && hash_equals( $stored, $token );
	}
}

Public_Link_Preview::instance();
