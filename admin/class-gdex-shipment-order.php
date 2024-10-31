<?php

class Gdex_Shipment_Order {

	const SERVICE_TYPE_LODGE_IN = 'lodge in';
	const SERVICE_TYPE_PICK_UP = 'pick up';

	const PICK_UP_TRANSPORTATION_VAN = 'van';
	const PICK_UP_TRANSPORTATION_MOTORBIKE = 'motorbike';
	const PICK_UP_TRANSPORTATION_TRUCK = 'truck';

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
		add_action( 'init', [ $this, 'register_shipment_order_post_type' ] );
		add_filter( 'gutenberg_can_edit_post_type', [ $this, 'disable_gutenberg' ], 10, 2 );
		add_filter( 'use_block_editor_for_post_type', [ $this, 'disable_gutenberg' ], 10, 2 );

		add_action( 'add_meta_boxes', [ $this, 'remove_submitdiv_meta_box' ] );
		add_action( 'add_meta_boxes', [ $this, 'add_shipment_order_data_meta_box' ] );
		add_action( 'add_meta_boxes', [ $this, 'add_shipment_order_consignments_meta_box' ] );
		add_action( 'add_meta_boxes', [ $this, 'add_shipment_order_actions_meta_box' ] );

		add_action( 'save_post', [ $this, 'save_shipment_order_data' ], 10, 3 );
		add_action( 'save_post', [ $this, 'save_shipment_order_consignments' ], 10, 3 );
		add_action( 'save_post', [ $this, 'submit_shipment_order_to_api' ], 100, 3 );

		add_action( 'wp_ajax_gdex-get-shipment-order-pick-up-dates', [ $this, 'ajax_get_shipment_order_pick_up_dates' ] );

		add_filter( 'bulk_actions-edit-shop_order', [ $this, 'add_create_shipment_order_bulk_actions' ], 20 );
		add_filter( 'handle_bulk_actions-edit-shop_order', [ $this, 'handle_create_shipment_order_bulk_actions' ], 10, 3 );

		add_filter( 'redirect_post_location', [ $this, 'redirect_post_location_to_list' ], 10, 2 );
	}

	public function register_shipment_order_post_type() {
		$labels = [
			'name'                  => _x( 'Shipment Orders', 'Post Type General Name', 'gdex' ),
			'singular_name'         => _x( 'Shipment Order', 'Post Type Singular Name', 'gdex' ),
			'menu_name'             => _x( 'Shipment Orders', 'Admin Menu text', 'gdex' ),
			'name_admin_bar'        => _x( 'Shipment Order', 'Add New on Toolbar', 'gdex' ),
			'archives'              => __( 'Shipment Order Archives', 'gdex' ),
			'attributes'            => __( 'Shipment Order Attributes', 'gdex' ),
			'parent_item_colon'     => __( 'Parent Shipment Order:', 'gdex' ),
			//						'all_items'             => __( 'All Shipment Orders', 'gdex' ),
			'add_new_item'          => __( 'Add New Shipment Order', 'gdex' ),
			'add_new'               => __( 'Add New', 'gdex' ),
			'new_item'              => __( 'New Shipment Order', 'gdex' ),
			'edit_item'             => __( 'Edit Shipment Order', 'gdex' ),
			'update_item'           => __( 'Update Shipment Order', 'gdex' ),
			'view_item'             => __( 'View Shipment Order', 'gdex' ),
			'view_items'            => __( 'View Shipment Orders', 'gdex' ),
			'search_items'          => __( 'Search Shipment Order', 'gdex' ),
			'not_found'             => __( 'Not found', 'gdex' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'gdex' ),
			'featured_image'        => __( 'Featured Image', 'gdex' ),
			'set_featured_image'    => __( 'Set featured image', 'gdex' ),
			'remove_featured_image' => __( 'Remove featured image', 'gdex' ),
			'use_featured_image'    => __( 'Use as featured image', 'gdex' ),
			'insert_into_item'      => __( 'Insert into Shipment Order', 'gdex' ),
			'uploaded_to_this_item' => __( 'Uploaded to this Shipment Order', 'gdex' ),
			'items_list'            => __( 'Shipment Orders list', 'gdex' ),
			'items_list_navigation' => __( 'Shipment Orders list navigation', 'gdex' ),
			'filter_items_list'     => __( 'Filter Shipment Orders list', 'gdex' ),
		];

		$args = [
			'label'               => __( 'Shipment Order', 'gdex' ),
			'description'         => __( '', 'gdex' ),
			'labels'              => $labels,
			'menu_icon'           => '',
			'supports'            => false,
			'taxonomies'          => [],
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => false,
			'menu_position'       => 60,
			'show_in_admin_bar'   => false,
			'show_in_nav_menus'   => false,
			'can_export'          => false,
			'has_archive'         => false,
			'hierarchical'        => false,
			'exclude_from_search' => false,
			'show_in_rest'        => false,
			'publicly_queryable'  => false,
			'capability_type'     => 'post',
		];

		register_post_type( 'gdex-shipment-order', $args );
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
		return $post_type === 'gdex-shipment-order' ? false : $can_edit;
	}

	/**
	 * Remove submit meta box
	 */
	public function remove_submitdiv_meta_box() {
		remove_meta_box( 'submitdiv', 'gdex-shipment-order', 'side' );
	}

	/**
	 * Add sender meta box
	 */
	public function add_shipment_order_data_meta_box() {
		add_meta_box(
			'gdex-shipment-order-data-meta-box',
			__( 'Shipment Details', 'gdex' ),
			[ $this, 'render_shipment_order_data_meta_box' ],
			'gdex-shipment-order',
			'normal',
			'high'
		);
	}

	/**
	 * Render sender meta box
	 */
	public function render_shipment_order_data_meta_box( $post ) {
		include_once __DIR__ . '/partials/gdex-shipment-order-data-meta-box.php';
	}

	/**
	 * Save shipment order data meta box
	 *
	 * @param $post_id
	 * @param $post
	 * @param $update
	 *
	 * @return mixed
	 */
	public function save_shipment_order_data( $post_id, $post, $update ) {
		if ( empty( $_REQUEST['gdex_shipment_order_data_meta_box_nonce'] ) ) {
			return $post_id;
		}

		if ( ! check_admin_referer( 'gdex-save-shipment-order-data', 'gdex_shipment_order_data_meta_box_nonce' ) ) {
			return $post_id;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		if ( $post->post_type !== 'gdex-shipment-order' ) {
			return $post_id;
		}

		if ( $post->post_status === 'auto-draft' ) {
			return $post_id;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			return $post_id;
		}

		$shipment_fields = [
			'sender_name',
			'sender_email',
			'sender_mobile_number',
			'sender_address1',
			'sender_address2',
			'sender_postal_code',
			'sender_location_id',
			'sender_location',
			'sender_city',
			'sender_state',
			'service_type',
		];

		if ( wc_clean( $_REQUEST['service_type'] ) === Gdex_Shipment_Order::SERVICE_TYPE_PICK_UP ) {
			$shipment_fields = array_merge( $shipment_fields, [
				'pick_up_date',
				'pick_up_time',
				'pick_up_transportation',
				'pick_up_trolley_required',
				'pick_up_remark',
			] );
		}

		foreach ( $shipment_fields as $field ) {
			update_post_meta( $post_id, $field, wc_clean( $_REQUEST[ $field ] ) );
		}
	}

	public function save_shipment_order_consignments( $post_id, $post, $update ) {
		if ( empty( $_REQUEST['gdex_shipment_order_consignments_meta_box_nonce'] ) ) {
			return $post_id;
		}

		if ( ! check_admin_referer( 'gdex-save-shipment-order-consignments',
			'gdex_shipment_order_consignments_meta_box_nonce' ) ) {
			return $post_id;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		if ( $post->post_type !== 'gdex-shipment-order' ) {
			return $post_id;
		}

		if ( $post->post_status === 'auto-draft' ) {
			return $post_id;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			return $post_id;
		}

		foreach ( wc_clean( $_REQUEST['consignments'] ) as $consignment ) {
			$consignment_id = wp_insert_post( [
				'post_title'     => __( 'Consignment', 'gdex' ),
				'post_status'    => 'publish',
				'post_type'      => 'gdex-consignment',
				'post_parent'    => $post_id,
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
			] );


			$order = wc_get_order( $consignment['order_id'] );

			$consignment += [
				'value'    => $order->get_total(),
				'name'     => $order->get_formatted_shipping_full_name(),
				'mobile' => method_exists( $order, 'get_shipping_phone' ) && $order->get_shipping_phone()
					? $order->get_shipping_phone()
					: $order->get_billing_phone(),
				'email'    => $order->get_billing_email(),
				'address1' => $order->get_shipping_address_1(),
				'address2' => $order->get_shipping_address_2(),
				'postcode' => $order->get_shipping_postcode(),
				'city'     => $order->get_shipping_city(),
				'state'    => WC()->countries->get_states( $order->get_shipping_country() )[ $order->get_shipping_state() ],
				'country'  => gdex_get_country_alpha3( $order->get_shipping_country() ),
			];

			foreach ( $consignment as $field => $value ) {
				update_post_meta( $consignment_id, $field, $value );
			}
		}
	}

	public function submit_shipment_order_to_api( $post_id, $post, $update ) {
		if ( empty( $_REQUEST['gdex_shipment_order_submit_nonce'] ) ) {
			return $post_id;
		}

		if ( ! check_admin_referer( 'gdex-shipment-order-submit', 'gdex_shipment_order_submit_nonce' ) ) {
			return $post_id;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		if ( $post->post_type !== 'gdex-shipment-order' ) {
			return $post_id;
		}

		if ( $post->post_status === 'auto-draft' ) {
			return $post_id;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			return $post_id;
		}

		try {
			gdex_api_create_consignment( $post );
		} catch ( Gdex_Api_Exception $exception ) {
			wp_delete_post( $post->ID, true );

			$child_posts = get_posts( [
				'post_parent' => $post->ID,
				'post_type'   => 'any'
			] );
			foreach ( $child_posts as $child_post ) {
				wp_delete_post( $child_post->ID, true );
			}

			gdex_add_admin_notice(
				"Unable to create shipment order, please try again or contact support if problem persists: <strong>Gdex API request failed ({$exception->getMessage()})</strong>",
				'error'
			);

			wp_redirect( $_REQUEST['_wp_http_referer'] );
			exit;
		}
	}

	public function ajax_get_shipment_order_pick_up_dates() {
		check_ajax_referer( 'gdex-get-shipment-order-pick-up-dates', 'nonce' );

		$postcode = wc_clean( $_REQUEST['postcode'] );
		if ( ! $postcode ) {
			throw new InvalidArgumentException( __( 'Invalid post code', 'gdex' ) );
		}

		try {
			$dates = array_map( function ( DateTime $date ) {
				return [
					'day'   => $date->format( 'l' ),
					'date'  => $date->format( 'Y-m-d' ),
					'value' => $date->format( 'Y-m-d\T00:00:00' ),
				];
			}, gdex_api_get_pickup_date_listing( $postcode ) );

			wp_send_json_success( [
				'dates' => $dates,
			] );
		} catch ( Exception $exception ) {
			wp_send_json_error( [
				'message' => $exception->getMessage(),
			] );
		}

		wp_die();
	}

	/**
	 * Add actions meta box
	 */
	public function add_shipment_order_consignments_meta_box() {
		add_meta_box(
			'gdex-shipment-order-consignments-meta-box',
			__( 'Consignments', 'gdex' ),
			[ $this, 'render_shipment_order_consignments_meta_box' ],
			'gdex-shipment-order',
			'normal'
		);
	}

	/**
	 * Render sender meta box
	 */
	public function render_shipment_order_consignments_meta_box( $post ) {
		include_once dirname( __FILE__ ) . '/partials/gdex-shipment-order-consignments-meta-box.php';
	}

	/**
	 * Add actions meta box
	 */
	public function add_shipment_order_actions_meta_box() {
		add_meta_box(
			'gdex-shipment-order-actions-meta-box',
			__( 'Order Actions', 'gdex' ),
			[ $this, 'render_shipment_order_actions_meta_box' ],
			'gdex-shipment-order',
			'side',
			'high'
		);
	}

	/**
	 * Render sender meta box
	 */
	public function render_shipment_order_actions_meta_box( $post ) {
		include_once dirname( __FILE__ ) . '/partials/gdex-shipment-order-actions-meta-box.php';
	}

	/**
	 * Add get shipping estimate bulk actions
	 *
	 * @param $actions
	 *
	 * @return mixed
	 */
	public function add_create_shipment_order_bulk_actions( $actions ) {
		$actions['gdex_create_shipment_order'] = __( 'Create GDEX Shipment', 'gdex' );

		return $actions;
	}

	/**
	 * Handle create shipment order bulk actions
	 *
	 * @param $redirect_url
	 * @param $action
	 * @param $order_ids
	 *
	 * @return string
	 */
	public function handle_create_shipment_order_bulk_actions( $redirect_url, $action, $order_ids ) {
		if ( $action !== 'gdex_create_shipment_order' ) {
			return $redirect_url;
		}

		return add_query_arg( [
			'orders' => $order_ids,
		], admin_url( 'post-new.php?post_type=gdex-shipment-order' ) );
	}

	public function redirect_post_location_to_list( $location, $post_id ) {
		$shipment_order = get_post( $post_id );
		if ( ! $shipment_order || $shipment_order->post_type !== 'gdex-shipment-order' ) {
			return $location;
		}

		return admin_url( 'edit.php?post_type=gdex-consignment' );
	}

}