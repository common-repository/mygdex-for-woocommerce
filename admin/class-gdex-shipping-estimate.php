<?php

class Gdex_Shipping_Estimate {

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
		add_action( 'woocommerce_checkout_update_order_meta', [
			$this,
			'quote_shipping_estimate_on_checkout_success'
		] );

		add_filter( 'bulk_actions-edit-shop_order', [ $this, 'add_get_shipping_estimate_bulk_actions' ], 20 );
		add_filter( 'handle_bulk_actions-edit-shop_order', [
			$this,
			'handle_get_shipping_estimate_bulk_actions'
		], 10, 3 );

		add_filter( 'manage_edit-shop_order_columns', [ $this, 'add_order_shipping_estimate_column_header' ], 20 );
		add_action( 'manage_shop_order_posts_custom_column', [ $this, 'add_order_shipping_estimate_column_content' ] );

		add_action( 'add_meta_boxes', [ $this, 'add_shipping_estimate_meta_box' ] );

		add_action( 'wp_ajax_gdex-quote-shipping-estimate', [ $this, 'ajax_quote_shipping_estimate' ] );
	}

	/**
	 * Quote shipping estimate on checkout
	 *
	 * @param $order_id
	 * @param $posted_data
	 * @param \WC_Order $order
	 */
	public function quote_shipping_estimate_on_checkout_success( $order_id ) {
		try {
			$order = wc_get_order( $order_id );
			gdex_wc_order_quote_shipping_estimate( $order );
		} catch ( Exception $e ) {
			gdex_log( __METHOD__ . " - order #{$order->get_id()}: {$e->getMessage()}" );
		}
	}

	/**
	 * Add get shipping estimate bulk actions
	 *
	 * @param $actions
	 *
	 * @return array
	 */
	public function add_get_shipping_estimate_bulk_actions( $actions ) {
		$actions['gdex_get_shipping_estimate'] = __( 'Get GDEX estimate', 'gdex' );

		return $actions;
	}

	/**
	 * Handle get shipping estimate bulk actions
	 *
	 * @param $redirect_url
	 * @param $action
	 * @param $order_ids
	 *
	 * @return string
	 */
	public function handle_get_shipping_estimate_bulk_actions( $redirect_url, $action, $order_ids ) {
		if ( $action !== 'gdex_get_shipping_estimate' ) {
			return $redirect_url;
		}

		$success_orders = [];
		$failed_orders  = [];

		foreach ( $order_ids as $order_id ) {
			try {
				gdex_wc_order_quote_shipping_estimate( $order_id, true );
				$success_orders[] = $order_id;
			} catch ( Exception $exception ) {
				$failed_orders[] = $order_id;
			}
		}

		if ( $success_orders ) {
			$notice['message'] = __( 'Selected orders shipping estimate successfully quoted.', 'gdex' );
			$notice['list']    = $success_orders;

			gdex_add_admin_notice( $notice, 'success' );
		}

		if ( $failed_orders ) {
			$notice['message'] = __( 'Selected orders shipping estimate failed to quote.', 'gdex' );
			$notice['list']    = $failed_orders;

			gdex_add_admin_notice( $notice, 'error' );
		}

		return $redirect_url;
	}

	/**
	 * Add estimate column header
	 *
	 * @param $columns
	 *
	 * @return array
	 */
	public function add_order_shipping_estimate_column_header( $columns ) {
		$new_columns = [];

		foreach ( $columns as $column_name => $column_info ) {
			$new_columns[ $column_name ] = $column_info;

			if ( 'order_total' === $column_name ) {
				$new_columns['gdex_shipping_estimate'] = __( 'GDEX Shipping Estimate', 'gdex' );
			}
		}

		return $new_columns;
	}

	/**
	 * Add estimate column content
	 *
	 * @param $column
	 *
	 * @throws \Exception
	 */
	public function add_order_shipping_estimate_column_content( $column ) {
		global $post;

		if ( $column !== 'gdex_shipping_estimate' ) {
			return;
		}

		$order = wc_get_order( $post->ID );

		$shipping_estimate = gdex_wc_order_shipping_estimate( $order );
		if ( ! $shipping_estimate ) {
			$weight = gdex_wc_order_total_weight( $order );
			if ( $weight ) {
				echo ' - ';
			} else {
				?>
                <p class="error">
                    <i class="fas fa-exclamation-circle"></i> <?= __( 'Total weight is zero', 'gdex' ) ?>
                </p>
				<?php
			}

			return;
		}

		echo wc_price( $shipping_estimate, [ 'currency' => $order->get_currency() ] );

		$date_format     = get_option( 'date_format' );
		$time_format     = get_option( 'time_format' );
		$datetime_format = $date_format . ' ' . $time_format;

		$shipping_estimate_quoted_at_timestamp_with_offset = gdex_wc_order_shipping_estimate_quoted_at_timestamp_with_offset( $order );
		?>
        <br>
        <small>
            <time
                datetime="<?php
				echo date_i18n( 'c', $shipping_estimate_quoted_at_timestamp_with_offset ); ?>"
                title="Quoted at <?php
				echo date_i18n( $datetime_format, $shipping_estimate_quoted_at_timestamp_with_offset ); ?>"
            >
				<?php
				echo date_i18n( $date_format, $shipping_estimate_quoted_at_timestamp_with_offset ); ?>
            </time>
        </small>
		<?php
	}

	/**
	 * Add shipping estimate meta box
	 */
	public function add_shipping_estimate_meta_box() {
		global $post;

		$order = wc_get_order( $post );
		if ( ! $order ) {
			return;
		}

		$consignments = gdex_wc_order_consignments( $order );

		if ( ! $consignments ) {
			add_meta_box(
				'gdex-shipping-estimate-meta-box',
				__( 'GDEX Shipping Estimate', 'gdex' ),
				[ $this, 'render_shipping_estimate_meta_box' ],
				'shop_order',
				'side',
				'high'
			);
		}
	}

	/**
	 * Render shipping estimate meta box
	 */
	public function render_shipping_estimate_meta_box() {
		include_once dirname( __FILE__ ) . '/partials/gdex-shipment-estimate-meta-box.php';
	}

	public function ajax_quote_shipping_estimate() {
		check_ajax_referer( 'gdex-quote-shipping-estimate', 'nonce' );

		$order = wc_get_order( wc_clean( $_REQUEST['order_id'] ) );
		if ( ! $order ) {
			throw new InvalidArgumentException( __( 'Invalid order', 'gdex' ) );
		}

		$order = gdex_wc_order_quote_shipping_estimate( wc_clean( $_REQUEST['order_id'] ) );

		$shipping_estimate                     = gdex_wc_order_shipping_estimate( $order );
		$shipping_estimate_quoted_at_date_i18n = gdex_wc_order_shipping_estimate_quoted_at_date_i18n( $order );

		wp_send_json_success( [
			'estimate'  => $shipping_estimate,
			'quoted_at' => $shipping_estimate_quoted_at_date_i18n,
		] );

		wp_die();
	}
}