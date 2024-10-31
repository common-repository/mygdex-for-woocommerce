<?php

class Gdex_Settings {

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
		add_filter( 'woocommerce_shipping_methods', [ $this, 'add_shipping_method' ] );

		add_action( 'wp_ajax_gdex-get-place', [ $this, 'ajax_get_place' ] );

		add_action( 'admin_print_footer_scripts', [ $this, 'add_get_place_nonce' ] );
	}

	/**
	 * Add woocommerce shipping method
	 *
	 * @param $methods
	 *
	 * @return mixed
	 */
	public function add_shipping_method( $methods ) {
		require_once GDEX_PLUGIN_DIR_PATH . 'integrations/woocommerce/includes/shipping/gdex-shipping/class-gdex-shipping.php';

		$methods['gdex'] = Gdex_Shipping::class;

		return $methods;
	}

	public function ajax_get_place() {
		check_ajax_referer( 'gdex-get-place', 'gdex-nonce' );

		try {
			$place = gdex_get_place( wc_clean( $_REQUEST['postal_code'] ) );

			wp_send_json_success( [
				'locations' => $place,
			] );
		} catch ( Exception $exception ) {
			wp_send_json_error( [
				'message' => $exception->getMessage(),
			] );
		}

		wp_die();
	}

	public function add_get_place_nonce() {
		global $current_screen;

		if ( ! $current_screen || $current_screen->id !== 'woocommerce_page_wc-settings' ) {
			return;
		}

		$current_tab = isset( $_REQUEST['tab'] ) ? wc_clean( $_REQUEST['tab'] ) : 'general';
		if ( $current_tab !== 'shipping' ) {
			return;
		}

		$current_section = isset( $_REQUEST['section'] ) ? wc_clean( $_REQUEST['section'] ) : '';
		if ( $current_section !== 'gdex' ) {
			return;
		}

		?>
        <script>
          var gdex_setting = {
            get_place: {
              nonce: "<?= wp_create_nonce( 'gdex-get-place' ) ?>"
            }
          }
        </script>
		<?php
	}

}