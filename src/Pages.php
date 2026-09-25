<?php
namespace RiseLandingPages;

/** Normal WordPress pages, with a small, explicitly owned metadata surface. */
final class Pages {
	const TEMPLATE = 'rise-landing-page.php';
	const META_KEYS = array( '_rise_landing_page', '_rise_landing_layout', '_rise_landing_header', '_rise_landing_footer', '_rise_landing_cta_label', '_rise_landing_booking_url', '_rise_landing_body_class' );

	public function register() {
		add_action( 'init', array( $this, 'register_meta' ) );
		add_filter( 'theme_page_templates', array( $this, 'templates' ) );
		add_filter( 'wp_insert_post_data', array( $this, 'sync_title_slug' ), 20, 4 );
		add_action( 'rest_api_init', array( $this, 'register_title_check' ) );
		add_action( 'save_post_page', array( $this, 'sync_marker' ), 20 );
	}

	/** Keep landing page URLs in step with renamed pages, including draft pages. */
	public function sync_title_slug( $data, $postarr, $unsanitized_postarr, $update ) {
		if ( 'page' !== $data['post_type'] || in_array( $data['post_status'], array( 'trash', 'auto-draft' ), true ) ) {
			return $data;
		}
		$post_id = ! empty( $postarr['ID'] ) ? (int) $postarr['ID'] : 0;
		$meta = isset( $unsanitized_postarr['meta_input'] ) && is_array( $unsanitized_postarr['meta_input'] ) ? $unsanitized_postarr['meta_input'] : array();
		$is_new_landing = ! empty( $meta['_rise_landing_page'] ) || ( isset( $meta['_wp_page_template'] ) && self::TEMPLATE === $meta['_wp_page_template'] ) || ( isset( $unsanitized_postarr['page_template'] ) && self::TEMPLATE === $unsanitized_postarr['page_template'] );
		if ( ! ( $post_id && self::is_landing( $post_id ) ) && ! $is_new_landing ) {
			return $data;
		}
		$title = trim( wp_unslash( $data['post_title'] ) );
		if ( '' === $title || ( $update && $post_id && $title === get_post_field( 'post_title', $post_id, 'raw' ) ) ) {
			return $data;
		}
		$parent = isset( $data['post_parent'] ) ? (int) $data['post_parent'] : 0;
		$resolved = self::title_and_slug( $title, $post_id, $parent );
		$data['post_title'] = wp_slash( $resolved['title'] );
		$data['post_name'] = wp_slash( $resolved['slug'] );
		return $data;
	}

	/** Suggest the same title and URL that the save hook will use. */
	public static function title_and_slug( $title, $post_id = 0, $parent = 0 ) {
		global $wpdb;
		$title = trim( sanitize_text_field( $title ) );
		$candidate = $title;
		$suffix = 2;
		while ( $candidate && $wpdb->get_var( $wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'page' AND post_status NOT IN ('trash', 'auto-draft') AND ID <> %d AND LOWER(TRIM(post_title)) = LOWER(%s) LIMIT 1",
			$post_id,
			$candidate
		) ) ) {
			$candidate = sprintf( '%s (%d)', $title, $suffix++ );
		}
		$slug = sanitize_title( $candidate );
		return array(
			'title' => $candidate,
			'slug' => $slug ? wp_unique_post_slug( $slug, $post_id, 'publish', 'page', $parent ) : '',
			'duplicate' => $candidate !== $title,
		);
	}

	public function register_title_check() {
		register_rest_route( 'rise-landing/v1', '/title-check/(?P<id>\d+)', array(
			'methods' => 'POST',
			'permission_callback' => static function ( $request ) {
				$id = (int) $request['id'];
				return self::is_landing( $id ) && current_user_can( 'edit_post', $id );
			},
			'callback' => static function ( $request ) {
				$id = (int) $request['id'];
				$post = get_post( $id );
				return rest_ensure_response( self::title_and_slug( $request['title'], $id, (int) $post->post_parent ) );
			},
			'args' => array( 'title' => array( 'required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ) ),
		) );
	}

	public static function is_landing( $post_id ) {
		return 'page' === get_post_type( $post_id ) && ( (bool) get_post_meta( $post_id, '_rise_landing_page', true ) || self::TEMPLATE === get_page_template_slug( $post_id ) );
	}

	/** Preserve the old combined website layout until its settings are edited. */
	public static function chrome_choice( $post_id, $part ) {
		if ( self::is_editor_chrome_preview( $post_id ) ) {
			$query_key = 'header' === $part ? 'rise_lp_editor_header' : 'rise_lp_editor_footer';
			$preview_choice = isset( $_GET[ $query_key ] ) && is_string( $_GET[ $query_key ] ) ? wp_unslash( $_GET[ $query_key ] ) : '';
			$allowed = 'header' === $part ? array( 'minimal', 'site', 'hidden' ) : array( 'landing', 'site', 'hidden' );
			if ( in_array( $preview_choice, $allowed, true ) ) {
				return $preview_choice;
			}
		}
		if ( 'site' === get_post_meta( $post_id, '_rise_landing_layout', true ) ) {
			return 'site';
		}
		$key = 'header' === $part ? '_rise_landing_header' : '_rise_landing_footer';
		$value = get_post_meta( $post_id, $key, true );
		return in_array( $value, array( 'site', 'hidden' ), true ) ? $value : ( 'header' === $part ? 'minimal' : 'landing' );
	}

	/** Read-only, editor-only frontend request for an accurate chrome preview. */
	public static function is_editor_chrome_preview( $post_id ) {
		return isset( $_GET['rise_lp_editor_preview'] ) && '1' === $_GET['rise_lp_editor_preview'] && current_user_can( 'edit_post', $post_id );
	}

	public function templates( $templates ) {
		$templates[ self::TEMPLATE ] = __( 'Rise Landing Page', 'rise-landing-pages' );
		return $templates;
	}

	public function sync_marker( $post_id ) {
		if ( ! wp_is_post_revision( $post_id ) && self::TEMPLATE === get_page_template_slug( $post_id ) ) {
			update_post_meta( $post_id, '_rise_landing_page', true );
		}
	}

	public function register_meta() {
		foreach ( self::META_KEYS as $key ) {
			$args = array(
				'single' => true,
				'type' => '_rise_landing_page' === $key ? 'boolean' : 'string',
				'show_in_rest' => true,
				'auth_callback' => static function ( $allowed, $meta_key, $post_id ) { return current_user_can( 'edit_post', $post_id ); },
				'sanitize_callback' => static function ( $value ) use ( $key ) { return self::sanitize_meta( $key, $value ); },
			);
			if ( '_rise_landing_layout' === $key ) {
				$args['default'] = 'standalone';
				$args['show_in_rest'] = array( 'schema' => array( 'enum' => array( 'standalone', 'site' ) ) );
			} elseif ( '_rise_landing_header' === $key ) {
				$args['default'] = 'minimal';
				$args['show_in_rest'] = array( 'schema' => array( 'enum' => array( 'minimal', 'site', 'hidden' ) ) );
			} elseif ( '_rise_landing_footer' === $key ) {
				$args['default'] = 'landing';
				$args['show_in_rest'] = array( 'schema' => array( 'enum' => array( 'landing', 'site', 'hidden' ) ) );
			}
			register_post_meta( 'page', $key, $args );
		}
	}

	public static function sanitize_meta( $key, $value ) {
		if ( '_rise_landing_page' === $key ) {
			return rest_sanitize_boolean( $value );
		}
		if ( ! is_scalar( $value ) ) {
			return '';
		}
		if ( '_rise_landing_layout' === $key ) {
			return 'site' === $value ? 'site' : 'standalone';
		}
		if ( '_rise_landing_header' === $key ) {
			return in_array( $value, array( 'site', 'hidden' ), true ) ? $value : 'minimal';
		}
		if ( '_rise_landing_footer' === $key ) {
			return in_array( $value, array( 'site', 'hidden' ), true ) ? $value : 'landing';
		}
		if ( '_rise_landing_booking_url' === $key ) {
			return esc_url_raw( $value, array( 'http', 'https', 'mailto', 'tel' ) );
		}
		if ( '_rise_landing_body_class' === $key ) {
			return implode( ' ', array_filter( array_map( 'sanitize_html_class', preg_split( '/\s+/', trim( $value ) ) ) ) );
		}
		return sanitize_text_field( $value );
	}

	/** Create a ready-to-edit draft. Callers must enforce capability and nonce. */
	public static function create() {
		$settings = Settings\Settings::get();
		return wp_insert_post(
			array(
				'post_type' => 'page',
				'post_status' => 'draft',
				'post_title' => __( 'New landing page', 'rise-landing-pages' ),
				'post_content' => wp_slash( Blocks\Registry::default_content() ),
				'post_author' => get_current_user_id(),
				'meta_input' => array( '_rise_landing_page' => true, '_wp_page_template' => self::TEMPLATE, '_rise_landing_header' => $settings['default_header'], '_rise_landing_footer' => $settings['default_footer'] ),
			),
			true
		);
	}

	/** Copy content and owned settings only; intentionally exclude third-party metadata. */
	public static function duplicate( $post_id ) {
		$source = get_post( $post_id );
		if ( ! $source || ! self::is_landing( $post_id ) ) {
			return new \WP_Error( 'rise_invalid_page', __( 'Choose a Rise landing page to duplicate.', 'rise-landing-pages' ) );
		}
		$meta = array( '_wp_page_template' => self::TEMPLATE, '_rise_landing_page' => true );
		foreach ( self::META_KEYS as $key ) {
			if ( metadata_exists( 'post', $post_id, $key ) ) {
				$meta[ $key ] = self::sanitize_meta( $key, get_post_meta( $post_id, $key, true ) );
			}
		}
		// wp_unique_post_slug skips drafts; use publish for collision checking only.
		$slug = wp_unique_post_slug( sanitize_title( 'copy-of-' . $source->post_name ), 0, 'publish', 'page', 0 );
		return wp_insert_post(
			array(
				'post_type' => 'page', 'post_status' => 'draft', 'post_parent' => 0,
				'post_title' => wp_slash( sprintf( __( 'Copy of %s', 'rise-landing-pages' ), $source->post_title ) ),
				'post_name' => $slug,
				'post_content' => wp_slash( $source->post_content ),
				'post_excerpt' => wp_slash( $source->post_excerpt ),
				'post_author' => get_current_user_id(),
				'meta_input' => wp_slash( $meta ),
			), true
		);
	}
}
