<?php

class Gdex_Pickup {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 *
	 * @since    1.0.0
	 *
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		$this->define_hooks();
	}

	/**
	 * Define hooks
	 */
	protected function define_hooks() {
		add_action( 'admin_menu', [ $this, 'add_pickups_submenu' ], 20 );

		add_action( 'wp_ajax_gdex_cancel_pickup', [ $this, 'ajax_cancel_pickup' ] );
	}

	/**
	 * Add menu
	 */
	public function add_pickups_submenu() {
		add_submenu_page(
			'gdex',
			__( 'Pickups', 'gdex' ),
			__( 'Pickups', 'gdex' ),
			'manage_woocommerce',
			'gdex_pickups',
			[ $this, 'show_pickups_page' ]
		);
	}

	/**
	 *
	 * Show pickups page
	 */
	public function show_pickups_page() {
		include_once dirname( __FILE__ ) . '/partials/gdex-admin-page-pickups.php';
	}

	/**
	 * Cancel pickup
	 */
	public function ajax_cancel_pickup() {
		check_ajax_referer( 'gdex-cancel-pickup', 'nonce' );

		$want_json = empty( $_REQUEST['redirect'] );

		$error = null;

		try {
			gdex_api_cancel_pickup( wc_clean( $_REQUEST['pickup_id'] ) );

			if ( $want_json ) {
				wp_send_json_success( null, 200 );
				wp_die();
			}

			gdex_add_admin_notice( sprintf( __( 'Pickup #%s successfully cancelled.', 'gdex' ),
				wc_clean( $_REQUEST['pickup_id'] ) ), 'success' );
		} catch ( Exception $exception ) {
			if ( $want_json ) {
				wp_send_json_error( [ 'message' => $exception->getMessage() ], $exception->getCode() );
				wp_die();
			}

			gdex_add_admin_notice( sprintf( __( 'Cannot cancel pickup #%s: %s', 'gdex' ), wc_clean( $_REQUEST['pickup_id'] ),
				$exception->getMessage() ), 'error' );
		}

		return wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=gdex_pickups' ) );
	}

}