<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://geeksworking.com
 * @since      1.0.0
 *
 * @package    Gdex
 * @subpackage Gdex/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Gdex
 * @subpackage Gdex/admin
 * @author     Geeks One <one@geeksworking.com>
 */
class Gdex_Admin {

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
		add_action( 'plugins_loaded', [ $this, 'check_woocommerce_activated' ] );

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_styles' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

		add_action( 'wp_dashboard_setup', [ $this, 'add_dashboard_overview_widget' ] );

		add_action( 'admin_menu', [ $this, 'add_menu' ] );
		add_action( 'admin_menu', [ $this, 'print_admin_notices' ] );

		add_action( 'admin_notices', [ $this, 'add_product_weight_notice' ] );

		add_filter( 'http_request_timeout', [ $this, 'increase_http_request_timeout' ], 10, 2 );

		add_action( 'http_api_debug', [ $this, 'log_api_response' ], 10, 5 );
	}

	/**
	 * Check if Woocommerce installed
	 */
	public function check_woocommerce_activated() {
		if ( defined( 'WC_VERSION' ) ) {
			return;
		}

		add_action( 'admin_notices', [ $this, 'notice_woocommerce_required' ] );
	}

	/**
	 * Admin error notifying user that Woocommerce is required
	 */
	public function notice_woocommerce_required() {
		?>
        <div class="notice notice-error">
            <p><?= __( 'GDEX requires WooCommerce to be installed and activated!', $this->plugin_name ) ?></p>
        </div>
		<?php
	}

	/**
	 *
	 */
	public function show_product_weight_notice() {
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Gdex_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Gdex_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( 'bootstrap-grid', 'https://unpkg.com/bootstrap@4.3.1/dist/css/bootstrap-grid.min.css', [], '4.3' );
		wp_enqueue_style( 'fontawesome', 'https://use.fontawesome.com/releases/v5.8.0/css/all.css', [], '5.8' );
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/gdex-admin.css', [], $this->version );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Gdex_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Gdex_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( 'axios', 'https://unpkg.com/axios@0.18/dist/axios.min.js', [], '0.18', true );

		wp_enqueue_script( 'vue', 'https://unpkg.com/vue@2.6/dist/vue.min.js', [], '2.6', true );
		wp_enqueue_script( 'vuevalidate', 'https://unpkg.com/vuelidate@0.7/dist/vuelidate.min.js', [ 'vue' ], '0.7', true );
		wp_enqueue_script( 'vuevalidate-validators', 'https://unpkg.com/vuelidate@0.7/dist/validators.min.js', [ 'vue', 'vuevalidate' ], '0.7', true );

		wp_enqueue_script(
			$this->plugin_name,
			plugin_dir_url( __FILE__ ) . 'js/gdex-admin.js',
			[ 'jquery', 'lodash', 'axios', 'vue', 'vuevalidate', 'vuevalidate-validators' ],
			$this->version,
			true
		);
	}

	/**
	 * Add gdexs dashboard overview widget
	 */
	public function add_dashboard_overview_widget() {
		if ( ! gdex_has_api_user_access_token() ) {
			return;
		}

		wp_add_dashboard_widget( 'gdex-overview-widget', 'GDEX', function () {
			?>
            <a class="button button-primary gdex-top-up-buttion" href="https://my.gdexpress.com/dashboard/ewallet"
               target="_blank">Top Up</a>
            <h2>
                RM<?= number_format( gdex_api_get_check_ewallet_balance(), 2 ) ?><br>
                <span class="description">Wallet Balance</span>
            </h2>
            <br>
			<?php
		} );
	}

	/**
	 * Add menu
	 */
	public function add_menu() {
		add_menu_page(
			__( 'GDEX', 'gdex' ),
			__( 'GDEX', 'gdex' ),
			'manage_woocommerce',
			'gdex',
			null,
			'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4KPHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIiB3aWR0aD0iNDM0LjIxcHQiIGhlaWdodD0iMTY0LjA2cHQiIHZpZXdCb3g9IjAgMCA0MzQuMjEgMTY0LjA2IiB2ZXJzaW9uPSIxLjEiPgo8ZGVmcz4KPGNsaXBQYXRoIGlkPSJjbGlwMSI+CiAgPHBhdGggZD0iTSAwIDAgTCAxNjUgMCBMIDE2NSAxNjQuMDU4NTk0IEwgMCAxNjQuMDU4NTk0IFogTSAwIDAgIi8+CjwvY2xpcFBhdGg+CjxjbGlwUGF0aCBpZD0iY2xpcDIiPgogIDxwYXRoIGQ9Ik0gNDE1IDg4IEwgNDM0LjIxMDkzOCA4OCBMIDQzNC4yMTA5MzggMTA4IEwgNDE1IDEwOCBaIE0gNDE1IDg4ICIvPgo8L2NsaXBQYXRoPgo8L2RlZnM+CjxnIGlkPSJzdXJmYWNlMSI+CjxwYXRoIHN0eWxlPSJmaWxsOiNmZmYiIGQ9Ik0gMTczLjEwOTM3NSAzNS4zMTY0MDYgTCAxNzMuMTA5Mzc1IDEyNi40OTYwOTQgTCAxODUuODc4OTA2IDEyNi40OTYwOTQgQyAxOTguNDI1NzgxIDEyNi40OTYwOTQgMjA1LjA3ODEyNSAxMjUuMDM1MTU2IDIxMC43MzA0NjkgMTIyLjE3MTg3NSBDIDIxNi4zOTQ1MzEgMTE5LjI4MTI1IDIyMS4wMTk1MzEgMTE0LjQyMTg3NSAyMjQuNTk3NjU2IDEwNy42MDE1NjMgQyAyMjguMjA3MDMxIDEwMC44MDg1OTQgMjMwLjAwMzkwNiA5Mi4zODY3MTkgMjMwLjAwMzkwNiA4Mi40MjU3ODEgQyAyMzAuMDAzOTA2IDY3LjA4MjAzMSAyMjUuNzIyNjU2IDU1LjE5MTQwNiAyMTcuMTYwMTU2IDQ2Ljc0MjE4OCBDIDIwOS40Mzc1IDM5LjEyMTA5NCAxOTkuNTA3ODEzIDM1LjMxNjQwNiAxODIuNDQ1MzEzIDM1LjMxNjQwNiBaIE0gMTM5LjEzMjgxMyAxLjY0MDYyNSBMIDE3NC41MTk1MzEgMS42NDA2MjUgQyAxOTcuMzA0Njg4IDEuNjQwNjI1IDIxNC4yNTM5MDYgNS4yNjE3MTkgMjI1LjMzOTg0NCAxMC45MDYyNSBDIDIzNi40MDYyNSAxNi41MTk1MzEgMjQ2Ljc1MzkwNiAyNS42ODc1IDI1My45MjE4NzUgMzguMzQ3NjU2IEMgMjYxLjA4NTkzOCA1MS4wMjM0MzggMjY0LjY3NTc4MSA2NS44MjAzMTMgMjY0LjY3NTc4MSA4Mi43NSBDIDI2NC42NzU3ODEgOTQuNzY5NTMxIDI2Mi42NTYyNSAxMDUuODA0Njg4IDI1OC42NTIzNDQgMTE1LjkxMDE1NiBDIDI1NC42NTIzNDQgMTI2LjAxOTUzMSAyNDkuMTA5Mzc1IDEzNC4zNTU0NjkgMjQyLjA0Njg3NSAxNDEuMDExNzE5IEMgMjM0Ljk3MjY1NiAxNDcuNjY0MDYzIDIyNi4wOTM3NSAxNTIuMjk2ODc1IDIxNy44Nzg5MDYgMTU0LjgyNDIxOSBDIDIwOS42MzY3MTkgMTU3LjM1MTU2MyAxOTUuMzM5ODQ0IDE1OC42NDA2MjUgMTc1LjAzOTA2MyAxNTguNjQwNjI1IEwgMTM5LjEzMjgxMyAxNTguNjQwNjI1ICIvPgo8cGF0aCBzdHlsZT0iZmlsbDojZmZmIiBkPSJNIDI3My44NTU0NjkgMy4yMzgyODEgTCAzMjEuMzMyMDMxIDMuMjM4MjgxIEwgMzIxLjMzMjAzMSAyMi4yNzM0MzggTCAyOTIuMDc4MTI1IDIyLjI3MzQzOCBMIDI5Mi4wNzgxMjUgMzMuNzE0ODQ0IEwgMzIxLjMzMjAzMSAzMy43MTQ4NDQgTCAzMjEuMzMyMDMxIDUyLjMxNjQwNiBMIDI5Mi4wNzgxMjUgNTIuMzE2NDA2IEwgMjkyLjA3ODEyNSA3MS4wNzAzMTMgTCAzMjIuMzk4NDM4IDcxLjA3MDMxMyBMIDMyMi4zOTg0MzggOTAuMDU0Njg4IEwgMjczLjg1NTQ2OSA5MC4wNTQ2ODggIi8+CjxwYXRoIHN0eWxlPSJmaWxsOiNmZmYiIGQ9Ik0gMzI5LjY5OTIxOSAzLjIzODI4MSBMIDM1MC43Njk1MzEgMy4yMzgyODEgTCAzNjcuNDg4MjgxIDI4LjQyMTg3NSBMIDM4NC42Njc5NjkgMy4yMzgyODEgTCA0MDMuNTM1MTU2IDMuMjM4MjgxIEwgMzc4LjE4MzU5NCA0NC42NTIzNDQgTCA0MDcuMjI2NTYzIDkwLjA1NDY4OCBMIDM4Ni4zODI4MTMgOTAuMDU0Njg4IEwgMzY3LjY4MzU5NCA2MS4zMTI1IEwgMzQ5LjQ2NDg0NCA5MC4wNTQ2ODggTCAzMjguNzE0ODQ0IDkwLjA1NDY4OCBMIDM1Ny4xNTYyNSA0NS4yOTY4NzUgIi8+CjxwYXRoIHN0eWxlPSJmaWxsOiNmZmYiIGQ9Ik0gNDA3LjM1NTQ2OSAxMDcuNzY5NTMxIEwgMjY3LjU4MjAzMSAxMDcuNzY5NTMxIEwgMjczLjczMDQ2OSA5NS4xOTE0MDYgTCA0MDcuMzU1NDY5IDk1LjE5MTQwNiAiLz4KPGcgY2xpcC1wYXRoPSJ1cmwoI2NsaXAxKSIgY2xpcC1ydWxlPSJub256ZXJvIj4KPHBhdGggc3R5bGU9ImZpbGw6I2ZmZiIgZD0iTSAxNTQuMjg1MTU2IDMwLjYxMzI4MSBMIDEzMC4yMDMxMjUgNTQuODgyODEzIEMgMTE2LjgyMDMxMyA0MC44NzUgMTAyLjg5NDUzMSAzMy41ODk4NDQgODUuMDY2NDA2IDMzLjg0Mzc1IEMgNjcuNzgxMjUgMzQuMDkzNzUgMzguNjA5Mzc1IDQzLjI3MzQzOCAzNS4xNDg0MzggNzkuNzg1MTU2IEMgMzMuNzIyNjU2IDk0Ljc0MjE4OCA0MC44NTU0NjkgMTA4LjU1NDY4OCA0OS4wMDM5MDYgMTE2LjA4MjAzMSBDIDcwLjAwMzkwNiAxMzUuNTA3ODEzIDk1LjExNzE4OCAxMzIuNDE3OTY5IDEwNy4zMjQyMTkgMTI3LjAzMTI1IEMgMTE0Ljg5ODQzOCAxMjMuNzE0ODQ0IDExOC4xNzU3ODEgMTIxLjE5MTQwNiAxMjQuNTM5MDYzIDExMC43MTg3NSBMIDgxLjgyNDIxOSAxMTAuNzE4NzUgTCA4MS44MjQyMTkgNzguMjI2NTYzIEwgMTY0LjA5NzY1NiA3OC4yMjY1NjMgTCAxNjQuMzE2NDA2IDg0LjgxMjUgQyAxNjQuMzE2NDA2IDk4LjQ3NjU2MyAxNjAuNzM4MjgxIDExNC4xNDQ1MzEgMTUzLjU2MjUgMTI2LjQxMDE1NiBDIDE0Ni40MDIzNDQgMTM4LjYyNSAxMzcuMTE3MTg4IDE0Ny45NzI2NTYgMTI1LjczNDM3NSAxNTQuMzc1IEMgMTE0LjM0NzY1NiAxNjAuODU5Mzc1IDEwMC45ODQzNzUgMTY0LjA1ODU5NCA4NS42NTYyNSAxNjQuMDU4NTk0IEMgNjkuMjQ2MDk0IDE2NC4wNTg1OTQgNTQuNjEzMjgxIDE2MC41NTA3ODEgNDEuNzQ2MDk0IDE1My41MzEyNSBDIDI4Ljg5NDUzMSAxNDYuNDg4MjgxIDE4LjcxODc1IDEzNi40OTIxODggMTEuMjMwNDY5IDEyMy40OTIxODggQyAzLjc0MjE4OCAxMTAuNDkyMTg4IDAgOTYuNDU3MDMxIDAgODEuNDE0MDYzIEMgMCA2MC43OTI5NjkgNi45MTQwNjMgNDIuODU1NDY5IDIwLjc0NjA5NCAyNy41ODIwMzEgQyAzNy4xNTYyNSA5LjQyOTY4OCA1OC42MjEwOTQgMC41MTU2MjUgODQuOTI1NzgxIDAuMDI3MzQzOCBDIDEwMi43OTY4NzUgLTAuMjk2ODc1IDExMS40MTQwNjMgMi44NDc2NTYgMTIzLjM2MzI4MSA3Ljg0Mzc1IEMgMTMzLjQ4NDM3NSAxMi4wNzAzMTMgMTQ0LjYwNTQ2OSAyMC4zNTE1NjMgMTU0LjI4NTE1NiAzMC42MTMyODEgIi8+CjwvZz4KPGcgY2xpcC1wYXRoPSJ1cmwoI2NsaXAyKSIgY2xpcC1ydWxlPSJub256ZXJvIj4KPHBhdGggc3R5bGU9ImZpbGw6I2ZmZiIgZD0iTSA0MjIuODI0MjE5IDk3LjY2NDA2MyBMIDQyNC4wNzAzMTMgOTcuNjY0MDYzIEMgNDI1LjQ4ODI4MSA5Ny42NjQwNjMgNDI2LjY5NTMxMyA5Ny4xNTYyNSA0MjYuNjk1MzEzIDk1LjgwODU5NCBDIDQyNi42OTUzMTMgOTQuODU1NDY5IDQyNi4wMjM0MzggOTMuOTAyMzQ0IDQyNC4wNzAzMTMgOTMuOTAyMzQ0IEwgNDIyLjgyNDIxOSA5My45ODQzNzUgWiBNIDQyMi44MjQyMTkgMTAzLjY3MTg3NSBMIDQyMS4wOTc2NTYgMTAzLjY3MTg3NSBMIDQyMS4wOTc2NTYgOTIuODM1OTM4IEwgNDI0LjE3MTg3NSA5Mi41ODIwMzEgQyA0MjUuODAwNzgxIDkyLjU4MjAzMSA0MjYuODc4OTA2IDkyLjkxNzk2OSA0MjcuNTIzNDM4IDkzLjM5NDUzMSBMIDQyOC41MjM0MzggOTUuNjEzMjgxIEMgNDI4LjUyMzQzOCA5Ny4wNzQyMTkgNDI3LjU2NjQwNiA5Ny45MTQwNjMgNDI2LjQwMjM0NCA5OC4yODEyNSBMIDQyNi40MDIzNDQgOTguMzYzMjgxIEMgNDI3LjM0Mzc1IDk4LjUzNTE1NiA0MjguMDAzOTA2IDk5LjM3NSA0MjguMjE0ODQ0IDEwMC45NzY1NjMgQyA0MjguNDY0ODQ0IDEwMi42NjAxNTYgNDI4LjczMDQ2OSAxMDMuMzA4NTk0IDQyOC45MTQwNjMgMTAzLjY3MTg3NSBMIDQyNy4wODk4NDQgMTAzLjY3MTg3NSBDIDQyNi44MzU5MzggMTAzLjMwODU5NCA0MjYuNTg1OTM4IDEwMi4zMjQyMTkgNDI2LjM1OTM3NSAxMDAuODk0NTMxIEMgNDI2LjEwNTQ2OSA5OS41MTU2MjUgNDI1LjQwNjI1IDk5LjAxMTcxOSA0MjQuMDMxMjUgOTkuMDExNzE5IEwgNDIyLjgyNDIxOSA5OS4wMTE3MTkgWiBNIDQyNC42MzI4MTMgODkuODAwNzgxIEMgNDIwLjM2NzE4OCA4OS44MDA3ODEgNDE2Ljg3MTA5NCA5My40ODA0NjkgNDE2Ljg3MTA5NCA5OCBDIDQxNi44NzEwOTQgMTAyLjYzMjgxMyA0MjAuMzY3MTg4IDEwNi4yNTM5MDYgNDI0LjY3NTc4MSAxMDYuMjUzOTA2IEMgNDI5IDEwNi4yODEyNSA0MzIuNDM3NSAxMDIuNjMyODEzIDQzMi40Mzc1IDk4LjAyNzM0NCBDIDQzMi40Mzc1IDkzLjQ4MDQ2OSA0MjkgODkuODAwNzgxIDQyNC42NzU3ODEgODkuODAwNzgxIFogTSA0MjQuNjc1NzgxIDg4LjMwODU5NCBDIDQyOS45ODA0NjkgODguMzA4NTk0IDQzNC4yMDcwMzEgOTIuNjA5Mzc1IDQzNC4yMDcwMzEgOTggQyA0MzQuMjA3MDMxIDEwMy40NzY1NjMgNDI5Ljk4MDQ2OSAxMDcuNzY5NTMxIDQyNC42MzI4MTMgMTA3Ljc2OTUzMSBDIDQxOS4zMzk4NDQgMTA3Ljc2OTUzMSA0MTUuMDE5NTMxIDEwMy40NzY1NjMgNDE1LjAxOTUzMSA5OCBDIDQxNS4wMTk1MzEgOTIuNjA5Mzc1IDQxOS4zMzk4NDQgODguMzA4NTk0IDQyNC42MzI4MTMgODguMzA4NTk0ICIvPgo8L2c+CjwvZz4KPC9zdmc+Cg==',
			'55.7'
		);
	}

	public function print_admin_notices() {
		foreach ( gdex_get_admin_notices() as $notice ) {
			include __DIR__ . '/partials/gdex-admin-notice.php';
		}

		gdex_clear_admin_notices();
	}

	/**
	 * Increase http request timeout to 15 sec
	 *
	 * @param $timeout
	 * @param $url
	 *
	 * @return int
	 */
	public function increase_http_request_timeout( $timeout, $url ) {
		return 30;
	}

	public function add_product_weight_notice() {
		$screen = get_current_screen();
		if ( $screen->id !== 'product' ) {
			return;
		}
		?>
        <div class="notice notice-info gdex-notice">
            <p>Please enter product weight in order to get exact shipping estimate from GDEX.</p>
        </div>
		<?php
	}

	public function log_api_response( $response, string $context, string $class, array $parsed_args, string $url ) {
		$isGdexApiRequest = strpos( $url, GDEX_TESTING ? GDEX_TESTING_API_URL : GDEX_API_URL ) === 0;
		if ( ! $isGdexApiRequest ) {
			return;
		}

		if ( is_wp_error( $response ) ) {
			//@todo log wp error?
			return;
		}

		gdex_api_log( print_r( [
			'request'  => [
				'url'     => $url,
				'headers' => $parsed_args['headers'],
				'data'    => $parsed_args['body'],
				'type'    => $parsed_args['method'],
			],
			'response' => [
				'status_code' => $response['response']['code'],
				'body'        => $response['body'],
			],
		], true ) );
	}

}
