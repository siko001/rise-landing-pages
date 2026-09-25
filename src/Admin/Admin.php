<?php
namespace RiseLandingPages\Admin;

use RiseLandingPages\Pages;
use RiseLandingPages\Settings\Settings;

/** Reuses the native Pages list, including search, pagination, and trash. */
final class Admin {
	public function register() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_post_rise_landing_create', array( $this, 'create' ) );
		add_action( 'admin_post_rise_landing_duplicate', array( $this, 'duplicate' ) );
		add_action( 'pre_get_posts', array( $this, 'filter_list' ) );
		add_filter( 'page_row_actions', array( $this, 'row_actions' ), 10, 2 );
		add_filter( 'views_edit-page', array( $this, 'views' ) );
		add_filter( 'parent_file', array( $this, 'parent_menu' ) );
		add_filter( 'submenu_file', array( $this, 'submenu' ) );
		add_action( 'restrict_manage_posts', array( $this, 'retain_filter' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
	}

	public static function list_url() {
		return admin_url( 'edit.php?post_type=page&rise_landing=1' );
	}

	public static function create_url() {
		// Return a raw URL: callers escape for HTML or JSON at the output boundary.
		return add_query_arg( '_wpnonce', wp_create_nonce( 'rise_landing_create' ), admin_url( 'admin-post.php?action=rise_landing_create' ) );
	}

	public function menu() {
		$hook = add_menu_page( __( 'Rise Landing Pages', 'rise-landing-pages' ), __( 'Rise Landing Pages', 'rise-landing-pages' ), 'edit_pages', 'rise-landing-pages', '__return_null', 'dashicons-welcome-widgets-menus', 21 );
		add_action( 'load-' . $hook, static function () { wp_safe_redirect( self::list_url() ); exit; } );
		add_submenu_page( 'rise-landing-pages', __( 'All Landing Pages', 'rise-landing-pages' ), __( 'All Landing Pages', 'rise-landing-pages' ), 'edit_pages', 'rise-landing-pages', '__return_null' );
		add_submenu_page( 'rise-landing-pages', __( 'Add Landing Page', 'rise-landing-pages' ), __( 'Add Landing Page', 'rise-landing-pages' ), 'edit_pages', self::create_url() );
		add_submenu_page( 'rise-landing-pages', __( 'Landing Page Settings', 'rise-landing-pages' ), __( 'Settings', 'rise-landing-pages' ), Settings::CAPABILITY, 'rise-landing-settings', array( Settings::class, 'render_page' ) );
	}

	private function check_create_permission() {
		$type = get_post_type_object( 'page' );
		if ( ! current_user_can( $type->cap->create_posts ) ) {
			wp_die( esc_html__( 'You cannot create pages.', 'rise-landing-pages' ), '', array( 'response' => 403 ) );
		}
	}

	public function create() {
		$this->check_create_permission();
		check_admin_referer( 'rise_landing_create' );
		$this->redirect_to_editor( Pages::create() );
	}

	public function duplicate() {
		$this->check_create_permission();
		$id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
		check_admin_referer( 'rise_landing_duplicate_' . $id );
		if ( ! current_user_can( 'edit_post', $id ) || ! current_user_can( 'read_post', $id ) ) {
			wp_die( esc_html__( 'You cannot duplicate this page.', 'rise-landing-pages' ), '', array( 'response' => 403 ) );
		}
		$this->redirect_to_editor( Pages::duplicate( $id ) );
	}

	private function redirect_to_editor( $result ) {
		if ( is_wp_error( $result ) ) {
			wp_die( esc_html( $result->get_error_message() ) );
		}
		wp_safe_redirect( get_edit_post_link( $result, 'raw' ) );
		exit;
	}

	public static function is_list() {
		global $pagenow;
		return 'edit.php' === $pagenow && isset( $_GET['post_type'], $_GET['rise_landing'] ) && 'page' === $_GET['post_type'] && '1' === $_GET['rise_landing'];
	}

	public function filter_list( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() || ! self::is_list() || ! current_user_can( 'edit_pages' ) ) {
			return;
		}
		$existing = $query->get( 'meta_query' );
		$rise = array( 'key' => '_rise_landing_page', 'value' => '1' );
		$query->set( 'meta_query', $existing ? array( 'relation' => 'AND', $existing, $rise ) : array( $rise ) );
	}

	public function row_actions( $actions, $post ) {
		if ( Pages::is_landing( $post->ID ) && 'trash' !== $post->post_status && current_user_can( 'edit_post', $post->ID ) && current_user_can( 'edit_pages' ) ) {
			$url = wp_nonce_url( admin_url( 'admin-post.php?action=rise_landing_duplicate&post=' . $post->ID ), 'rise_landing_duplicate_' . $post->ID );
			$actions['rise_duplicate'] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Duplicate landing page', 'rise-landing-pages' ) . '</a>';
		}
		return $actions;
	}

	/** Build counts from the filtered collection so normal page totals are not misleading. */
	public function views( $views ) {
		if ( ! self::is_list() ) {
			return $views;
		}
		$views = array();
		$statuses = get_post_stati( array( 'show_in_admin_status_list' => true ), 'objects' );
		$current = isset( $_GET['post_status'] ) && is_scalar( $_GET['post_status'] ) ? sanitize_key( $_GET['post_status'] ) : '';
		foreach ( array_merge( array( 'all' => null ), $statuses ) as $status => $object ) {
			$args = array( 'post_type' => 'page', 'post_status' => 'all' === $status ? array_keys( get_post_stati( array( 'show_in_admin_all_list' => true ) ) ) : $status, 'meta_key' => '_rise_landing_page', 'meta_value' => '1', 'posts_per_page' => 1, 'fields' => 'ids' );
			if ( ! current_user_can( 'edit_others_pages' ) ) {
				$args['author'] = get_current_user_id();
			}
			$query = new \WP_Query( $args );
			$count = (int) $query->found_posts;
			if ( ! $count && 'all' !== $status ) {
				continue;
			}
			$url = 'all' === $status ? self::list_url() : add_query_arg( 'post_status', $status, self::list_url() );
			$selected = $current === $status || ( '' === $current && 'all' === $status );
			$label = 'all' === $status ? __( 'All', 'rise-landing-pages' ) : $object->label;
			$views[ $status ] = '<a href="' . esc_url( $url ) . '"' . ( $selected ? ' class="current" aria-current="page"' : '' ) . '>' . esc_html( $label ) . ' <span class="count">(' . number_format_i18n( $count ) . ')</span></a>';
		}
		return $views;
	}

	public function retain_filter() {
		if ( self::is_list() ) {
			echo '<input type="hidden" name="rise_landing" value="1">';
		}
	}

	public function parent_menu( $parent ) {
		global $post;
		return self::is_list() || ( $post && Pages::is_landing( $post->ID ) ) ? 'rise-landing-pages' : $parent;
	}

	public function submenu( $submenu ) {
		return self::is_list() ? 'rise-landing-pages' : $submenu;
	}

	public function assets() {
		if ( self::is_list() ) {
			wp_enqueue_script( 'rise-landing-list', RISE_LP_URL . 'assets/admin-list.js', array(), RISE_LP_VERSION, true );
			wp_add_inline_script( 'rise-landing-list', 'window.riseLandingList = ' . wp_json_encode( array( 'createUrl' => self::create_url(), 'title' => __( 'Rise Landing Pages', 'rise-landing-pages' ), 'addLabel' => __( 'Add Landing Page', 'rise-landing-pages' ) ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . ';', 'before' );
		}
	}
}
