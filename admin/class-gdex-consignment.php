<?php

class Gdex_Consignment {

	const STATUS_PENDING = 'Pending';
	const STATUS_DELIVERED = 'Delivered';
	const STATUS_RETURNED = 'Returned';
	const STATUS_CLAIMED = 'Claimed';
	const STATUS_CANCELLED = 'Cancelled';
	const STATUS_EXPIRED = 'Expired';

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
		add_action( 'init', [ $this, 'register_consignment_post_type' ] );
		add_filter( 'gutenberg_can_edit_post_type', [ $this, 'disable_gutenberg' ], 10, 2 );
		add_filter( 'use_block_editor_for_post_type', [ $this, 'disable_gutenberg' ], 10, 2 );

		add_action( 'admin_print_footer_scripts', [ $this, 'add_print_notes_nonce' ] );

		add_action( 'wp_ajax_gdex-consignment-quote-shipping-rate', [ $this, 'ajax_quote_shipping_rate' ] );
		add_action( 'wp_ajax_gdex_print_consignment_note', [ $this, 'ajax_print_note' ] );
		add_action( 'wp_ajax_gdex_print_consignment_notes', [ $this, 'ajax_print_notes' ] );
		add_action( 'wp_ajax_gdex_cancel_consignment', [ $this, 'ajax_cancel' ] );

		add_filter( 'bulk_actions-edit-gdex-consignment', [ $this, 'add_print_bulk_actions' ], 20 );

		add_action( 'parse_query', [ $this, 'search_by_consignment_number' ] );
		add_filter( 'get_search_query', [ $this, 'search_label' ] );
		add_filter( 'the_posts', [ $this, 'fetch_consignments_statuses' ] );

		add_filter( 'manage_gdex-consignment_posts_columns', [ $this, 'set_list_table_columns' ] );
		add_action( 'manage_gdex-consignment_posts_custom_column', [ $this, 'render_list_table_consignment_number_column' ], 10, 2 );
		add_action( 'manage_gdex-consignment_posts_custom_column', [ $this, 'render_list_table_order_number_column' ], 10, 2 );
		add_action( 'manage_gdex-consignment_posts_custom_column', [ $this, 'render_list_table_submitted_at_column' ], 10, 2 );
		add_action( 'manage_gdex-consignment_posts_custom_column', [ $this, 'render_list_table_receiver_column' ], 10, 2 );
		add_action( 'manage_gdex-consignment_posts_custom_column', [ $this, 'render_list_table_pick_up_column' ], 10, 2 );
		add_action( 'manage_gdex-consignment_posts_custom_column', [ $this, 'render_list_table_status_column' ], 10, 2 );
		add_action( 'manage_gdex-consignment_posts_custom_column', [ $this, 'render_list_table_shipping_rate_column' ], 10, 2 );
		add_action( 'manage_gdex-consignment_posts_custom_column', [ $this, 'render_list_table_actions_column_print' ], 10, 2 );
		add_action( 'manage_gdex-consignment_posts_custom_column', [ $this, 'render_list_table_actions_column_cancel' ], 10, 2 );
		add_filter( 'post_row_actions', [ $this, 'set_row_actions' ], 10, 2 );

		add_filter( 'manage_edit-shop_order_columns', [ $this, 'add_order_consignment_column' ], 20 );
		add_action( 'manage_shop_order_posts_custom_column', [ $this, 'render_order_consignment_column' ], 10, 2 );

		add_action( 'add_meta_boxes', [ $this, 'add_consignment_meta_box' ] );
	}

	public function register_consignment_post_type() {
		$labels = [
			'name'                  => _x( 'Consignments', 'Post Type General Name', 'gdex' ),
			'singular_name'         => _x( 'Consignment', 'Post Type Singular Name', 'gdex' ),
			'menu_name'             => _x( 'Consignments', 'Admin Menu text', 'gdex' ),
			'name_admin_bar'        => _x( 'Consignment', 'Add New on Toolbar', 'gdex' ),
			'archives'              => __( 'Consignment Archives', 'gdex' ),
			'attributes'            => __( 'Consignment Attributes', 'gdex' ),
			'parent_item_colon'     => __( 'Parent Consignment:', 'gdex' ),
			'all_items'             => __( ' Consignments', 'gdex' ),
			'add_new_item'          => __( 'Add New Consignment', 'gdex' ),
			'add_new'               => __( 'Add New', 'gdex' ),
			'new_item'              => __( 'New Consignment', 'gdex' ),
			'edit_item'             => __( 'Edit Consignment', 'gdex' ),
			'update_item'           => __( 'Update Consignment', 'gdex' ),
			'view_item'             => __( 'View Consignment', 'gdex' ),
			'view_items'            => __( 'View Consignments', 'gdex' ),
			'search_items'          => __( 'Search Consignment', 'gdex' ),
			'not_found'             => __( 'Not found', 'gdex' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'gdex' ),
			'featured_image'        => __( 'Featured Image', 'gdex' ),
			'set_featured_image'    => __( 'Set featured image', 'gdex' ),
			'remove_featured_image' => __( 'Remove featured image', 'gdex' ),
			'use_featured_image'    => __( 'Use as featured image', 'gdex' ),
			'insert_into_item'      => __( 'Insert into Consignment', 'gdex' ),
			'uploaded_to_this_item' => __( 'Uploaded to this Consignment', 'gdex' ),
			'items_list'            => __( 'Consignments list', 'gdex' ),
			'items_list_navigation' => __( 'Consignments list navigation', 'gdex' ),
			'filter_items_list'     => __( 'Filter Consignments list', 'gdex' ),
		];

		$args = [
			'label'               => __( 'Consignment', 'gdex' ),
			'description'         => __( '', 'gdex' ),
			'labels'              => $labels,
			'menu_icon'           => '',
			'supports'            => [],
			'taxonomies'          => [],
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => 'gdex',
			'menu_position'       => 60,
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => false,
			'can_export'          => false,
			'has_archive'         => false,
			'hierarchical'        => false,
			'exclude_from_search' => false,
			'show_in_rest'        => true,
			'publicly_queryable'  => false,
			'capability_type'     => 'post',
		];

		register_post_type( 'gdex-consignment', $args );
	}

	/**
	 * Disable gutenberg
	 *
	 * @param $can_edit
	 * @param $post_type
	 *
	 * @return bool
	 */
	public function disable_gutenberg( $can_edit, $post_type ) {
		return $post_type === 'gdex-consignment' ? false : $can_edit;
	}

	public function add_print_notes_nonce() {
		global $current_screen;

		if ( ! $current_screen || $current_screen->id !== 'edit-gdex-consignment' ) {
			return;
		}

		?>
        <script>
          var gdex_consignments = {
            print_notes: {
              action: 'gdex_print_consignment_notes',
              consignment_ids: [],
              nonce: "<?= wp_create_nonce( 'gdex-print-consignment-notes' ) ?>"
            }
          }
        </script>
		<?php
	}

	public function ajax_quote_shipping_rate() {
		check_ajax_referer( 'gdex-consignment-quote-shipping-rate', 'nonce' );

		$order = wc_get_order( wc_clean( $_REQUEST['order_id'] ) );
		if ( ! $order ) {
			throw new InvalidArgumentException( __( 'Invalid order', 'gdex' ) );
		}

		try {
			$shipping_rate = gdex_consignment_quote_shipping_rate( wc_clean( $_REQUEST['order_id'] ), [
				'from_postcode' => wc_clean( $_REQUEST['sender_postal_code'] ),
				'weight'        => wc_clean( $_REQUEST['weight'] ),
				'type'          => wc_clean( $_REQUEST['type'] ),
			] );

			wp_send_json_success( [
				'rate' => $shipping_rate,
			] );
		} catch ( Exception $exception ) {
			wp_send_json_error( [
				'message' => $exception->getMessage(),
			] );
		}

		wp_die();
	}

	public function ajax_print_note() {
		check_ajax_referer( 'gdex-print-consignment-note', 'gdex-consignment-print-note-nonce' );

		$consignment        = gdex_consignment_get( wc_clean( $_REQUEST['consignment_id'] ) );
		$consignment_number = gdex_consignment_number( $consignment );

		try {
			gdex_consignment_print_note( $consignment );
		} catch ( Exception $exception ) {
			gdex_add_admin_notice(
				sprintf( __( 'Consignment <strong>%s</strong> failed to print: %s.', 'gdex' ), $consignment_number,
					$exception->getMessage() ),
				'error'
			);

			return wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'edit.php?post_type=gdex-consignment' ) );
		}

		wp_die();
	}

	public function ajax_print_notes() {
		check_ajax_referer( 'gdex-print-consignment-notes', 'nonce' );

		$consignment_ids = wc_clean( $_REQUEST['consignment_ids'] ) ?: [];

		$consignments = array_map( 'gdex_consignment_get', $consignment_ids );

		try {
			gdex_consignment_print_notes( $consignments );
		} catch ( Exception $exception ) {
			gdex_add_admin_notice(
				sprintf( __( 'Consignments failed to print: %s.', 'gdex' ), $exception->getMessage() ),
				'error'
			);

			return wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'edit.php?post_type=gdex-consignment' ) );
		}

		wp_die();
	}

	public function ajax_cancel() {
		check_ajax_referer( 'gdex-cancel-consignment', 'gdex-consignment-cancel-nonce' );

		$consignment        = gdex_consignment_get( wc_clean( $_REQUEST['consignment_id'] ) );
		$consignment_number = gdex_consignment_number( $consignment );

		try {
			gdex_consignment_cancel( $consignment );

			gdex_add_admin_notice(
				sprintf( __( 'Consignment <strong>%s</strong> successfully cancelled.', 'gdex' ), $consignment_number ),
				'success'
			);
		} catch ( Exception $exception ) {
			gdex_add_admin_notice(
				sprintf( __( 'Consignment <strong>%s</strong> failed to cancel: %s.', 'gdex' ), $consignment_number,
					$exception->getMessage() ),
				'error'
			);
		}

		return wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'edit.php?post_type=gdex-consignment' ) );
	}

	/**
	 * Add get shipping estimate bulk actions
	 *
	 * @param $actions
	 *
	 * @return mixed
	 */
	public function add_print_bulk_actions( $actions ) {
		$actions['gdex_print_consignments'] = __( 'Print Consignments', 'gdex' );

		return $actions;
	}

	/**
	 * Search by consignment number
	 *
	 * @param \WP_Query $query
	 */
	public function search_by_consignment_number( WP_Query $query ) {
		global $current_screen;

		if ( ! $current_screen || $current_screen->id !== 'edit-gdex-consignment' ) {
			return;
		}

		if ( empty( $query->query_vars['s'] ) ) {
			return;
		}

		$consignments = get_posts( [
			'post_type'  => 'gdex-consignment',
			'meta_query' => [
				[
					'key'   => 'consignment_number',
					'value' => $query->query_vars['s'],
				],
			],
		] );

		$query->query_vars['post__in'] = array_merge( array_map( function ( WP_Post $consignment ) {
			return $consignment->ID;
		}, $consignments ), [ 0 ] );

		$query->query_vars['gdex_consignment_search'] = true;

		unset( $query->query_vars['s'] );
	}

	/**
	 * Change the label when searching consignment.
	 *
	 * @param mixed $query Current search query.
	 *
	 * @return string
	 */
	public function search_label( $query ) {
		global $pagenow, $typenow;

		if ( 'edit.php' !== $pagenow || 'gdex-consignment' !== $typenow || ! get_query_var( 'gdex_consignment_search' ) || ! isset( $_REQUEST['s'] ) ) {
			return $query;
		}

		return wc_clean( wp_unslash( $_REQUEST['s'] ) );
	}

	public function fetch_consignments_statuses( $posts ) {
		global $pagenow, $typenow, $gdex;

		if ( 'edit.php' !== $pagenow || 'gdex-consignment' !== $typenow ) {
			return $posts;
		}

		$posts = array_filter( $posts, function ( WP_Post $post ) {
			if ( $post->post_type === 'gdex-consignment' ) {
				return $post;
			}
		} );

		// pre-query consignment status
		gdex_consignment_statuses( $posts );

		return $posts;
	}

	public function set_list_table_columns( $columns ) {
		$new_columns = [
			'cb'                 => $columns['cb'],
			'consignment_number' => __( 'Consignment', 'gdex' ),
			'order_number'       => __( 'Order', 'gdex' ),
			'submitted_at'       => __( 'Date', 'gdex' ),
			'receiver'           => __( 'Receiver', 'gdex' ),
			'pick_up'            => __( 'Pick Up', 'gdex' ),
			'shipping_rate'      => __( 'Shipping Rate', 'gdex' ),
			'status'             => __( 'Status', 'gdex' ),
			'actions'            => __( 'Actions', 'gdex' ),
		];

		return $new_columns;
	}

	public function render_list_table_consignment_number_column( $column, $post_id ) {
		if ( $column !== 'consignment_number' ) {
			return;
		}

		$consignment_number = get_post_meta( $post_id, 'consignment_number', true );
		$parcel_type        = get_post_meta( $post_id, 'parcel_type', true );
		$content            = get_post_meta( $post_id, 'content', true );
		$pieces             = get_post_meta( $post_id, 'pieces', true );
		$weight             = get_post_meta( $post_id, 'weight', true );
		?>
        <strong><?= $consignment_number; ?> - <?= __( ucfirst( $parcel_type ), 'gdex' ) ?></strong><br>
		<?= $content ?><br>
        <br>
		<?= __( 'Pieces', 'gdex' ) ?>: <?= $pieces ?><br>
		<?= __( 'Weight', 'gdex' ) ?>: <?= $weight ?>Kg<br>
		<?php
	}

	public function render_list_table_order_number_column( $column, $post_id ) {
		if ( $column !== 'order_number' ) {
			return;
		}

		$order_id = get_post_meta( $post_id, 'order_id', true );
		$order    = wc_get_order( $order_id );
		?>
        <a href="<?= admin_url( "post.php?post={$order_id}&action=edit" ) ?>">#<?= $order_id ?></a><br>
		<?= wc_price( $order->get_total() ) ?>
		<?php
	}

	public function render_list_table_submitted_at_column( $column, $post_id ) {
		if ( $column !== 'submitted_at' ) {
			return;
		}

		$submitted_at = get_post( $post_id )->post_date;

		echo date_i18n( wc_date_format() . ' ' . wc_time_format(), strtotime( $submitted_at ) );
	}

	public function render_list_table_receiver_column( $column, $post_id ) {
		if ( $column !== 'receiver' ) {
			return;
		}

		$receiver   = [];
		$receiver[] = get_post_meta( $post_id, 'name', true );
		$receiver[] = get_post_meta( $post_id, 'address1', true );
		$receiver[] = get_post_meta( $post_id, 'address2', true );
		$receiver[] = get_post_meta( $post_id, 'postcode', true );
		$receiver[] = get_post_meta( $post_id, 'city', true );
		$receiver[] = get_post_meta( $post_id, 'state', true );
		$receiver[] = get_post_meta( $post_id, 'country', true );
		$receiver[] = get_post_meta( $post_id, 'mobile', true );
		$receiver   = array_filter( $receiver );

		echo implode( '<br>', $receiver );
	}

	public function render_list_table_pick_up_column( $column, $post_id ) {
		if ( $column !== 'pick_up' ) {
			return;
		}

		$pick_up_meta = gdex_consignment_pick_up( $post_id );
		if ( ! $pick_up_meta ) {
			return;
		}

		$pick_up   = [];
		$pick_up[] = $pick_up_meta['no'];
		$pick_up[] = date_i18n( wc_date_format(), strtotime( $pick_up_meta['date'] ) ) . ' ' . date_i18n( wc_time_format(),
				strtotime( $pick_up_meta['time'] ) );
		$pick_up[] = $pick_up_meta['transportation'];

		echo implode( '<br>', $pick_up );
	}

	public function render_list_table_status_column( $column, $post_id ) {
		if ( $column !== 'status' ) {
			return;
		}

		echo gdex_consignment_status( $post_id );
	}

	public function render_list_table_shipping_rate_column( $column, $post_id ) {
		if ( $column !== 'shipping_rate' ) {
			return;
		}

		$rate = get_post_meta( $post_id, 'rate', true );

		echo wc_price( $rate );
	}

	public function render_list_table_actions_column_print( $column, $post_id ) {
		if ( $column !== 'actions' ) {
			return;
		}

		$consignment        = gdex_consignment_get( $post_id );
		$consignment_status = gdex_consignment_status( $consignment );

		if ( $consignment_status !== Gdex_Consignment::STATUS_PENDING ) {
			return;
		}

		$print_url = add_query_arg( [
			'action'         => 'gdex_print_consignment_note',
			'consignment_id' => $post_id,
		], admin_url( 'admin-ajax.php' ) );

		$print_nonce_url = wp_nonce_url( $print_url, 'gdex-print-consignment-note', 'gdex-consignment-print-note-nonce' );
		?>
        <a
            class="button print-button"
            href="<?= $print_nonce_url ?>"
            target="_blank"
        >
			<?= __( 'Print', 'gdex' ) ?>
        </a>
		<?php
	}

	public function render_list_table_actions_column_cancel( $column, $post_id ) {
		if ( $column !== 'actions' ) {
			return;
		}

		$consignment        = gdex_consignment_get( $post_id );
		$consignment_status = gdex_consignment_status( $consignment );

		if ( $consignment_status !== Gdex_Consignment::STATUS_PENDING ) {
			return;
		}

		//		//@todo cannot cancel if created 5 days ago?
		//		$created_at = new DateTime( $consignment->post_date_gmt, new DateTimeZone( GDEX_TIMEZONE ) );
		//		$today      = new DateTime( 'now', new DateTimeZone( GDEX_TIMEZONE ) );
		//
		//		$diff_days = $today->diff( $created_at );
		//		if ( $diff_days->days > 5 ) {
		//			return;
		//		}

		$cancel_url = add_query_arg( [
			'action'         => 'gdex_cancel_consignment',
			'consignment_id' => $post_id,
		], admin_url( 'admin-ajax.php' ) );

		$cancel_nonce_url = wp_nonce_url( $cancel_url, 'gdex-cancel-consignment', 'gdex-consignment-cancel-nonce' );
		?>
        <a
            class="button"
            href="<?= $cancel_nonce_url ?>"
        >
			<?= __( 'Cancel', 'gdex' ) ?>
        </a>
		<?php
	}

	public function set_row_actions( $actions, WP_Post $post ) {
		if ( $post->post_type !== 'gdex-consignment' ) {
			return $actions;
		}

		$new_actions = [];
		if ( ! empty( $actionsp['trash'] ) ) {
			$new_actions['trash'] = $actions['trash'];
		}

		return $new_actions;
	}

	/**
	 * Add estimate column header
	 *
	 * @param $columns
	 *
	 * @return array
	 */
	public function add_order_consignment_column( $columns ) {
		$new_columns = [];

		foreach ( $columns as $column_name => $column_info ) {
			$new_columns[ $column_name ] = $column_info;

			if ( 'order_total' === $column_name ) {
				$new_columns['gdex_consignment'] = __( 'GDEX Consignment', 'gdex' );
			}
		}

		return $new_columns;
	}

	public function render_order_consignment_column( $column, $post_id ) {
		if ( $column !== 'gdex_consignment' ) {
			return;
		}

		$consignments = gdex_wc_order_consignments( $post_id );

		foreach ( $consignments as $consignment ) {
			echo gdex_consignment_number( $consignment );

			return;
		}
	}

	public function add_consignment_meta_box() {
		global $post;

		$order = wc_get_order( $post );
		if ( ! $order ) {
			return;
		}

		$consignments = gdex_wc_order_consignments( $order );

		if ( $consignments ) {
			add_meta_box(
				'gdex-consignment-meta-box',
				__( 'GDEX Consignment', 'gdex' ),
				[ $this, 'render_consignment_meta_box' ],
				'shop_order',
				'side',
				'high'
			);
		}
	}

	public function render_consignment_meta_box() {
		include_once __DIR__ . '/partials/gdex-consigment-meta-box.php';
	}

}