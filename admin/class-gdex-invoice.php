<?php

class Gdex_Invoice {

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
		add_action( 'init', [ $this, 'register_invoice_post_type' ], 30 );

		add_action( 'admin_menu', [ $this, 'add_invoice_submenu' ], 25 );

		add_action( 'parse_query', [ $this, 'search_by_invoice_number' ] );
		add_filter( 'get_search_query', [ $this, 'search_label' ] );

		add_filter( 'manage_gdex-invoice_posts_columns', [ $this, 'set_list_table_columns' ] );
		add_action( 'manage_gdex-invoice_posts_custom_column', [
			$this,
			'render_list_table_submitted_at_column'
		], 10, 2 );
		add_action( 'manage_gdex-invoice_posts_custom_column', [ $this, 'render_list_table_invoice_number_column' ], 10,
			2 );
		add_action( 'manage_gdex-invoice_posts_custom_column', [ $this, 'render_list_table_total_column' ], 10, 2 );
		add_filter( 'post_row_actions', [ $this, 'set_row_actions' ], 10, 2 );
	}

	public function register_invoice_post_type() {
		$labels = [
			'name'                  => _x( 'Invoices', 'Post Type General Name', 'gdex' ),
			'singular_name'         => _x( 'Invoice', 'Post Type Singular Name', 'gdex' ),
			'menu_name'             => _x( 'Invoices', 'Admin Menu text', 'gdex' ),
			'name_admin_bar'        => _x( 'Invoice', 'Add New on Toolbar', 'gdex' ),
			'archives'              => __( 'Invoice Archives', 'gdex' ),
			'attributes'            => __( 'Invoice Attributes', 'gdex' ),
			'parent_item_colon'     => __( 'Parent Invoice:', 'gdex' ),
			'all_items'             => __( ' Invoices', 'gdex' ),
			'add_new_item'          => __( 'Add New Invoice', 'gdex' ),
			'add_new'               => __( 'Add New', 'gdex' ),
			'new_item'              => __( 'New Invoice', 'gdex' ),
			'edit_item'             => __( 'Edit Invoice', 'gdex' ),
			'update_item'           => __( 'Update Invoice', 'gdex' ),
			'view_item'             => __( 'View Invoice', 'gdex' ),
			'view_items'            => __( 'View Invoices', 'gdex' ),
			'search_items'          => __( 'Search Invoice', 'gdex' ),
			'not_found'             => __( 'Not found', 'gdex' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'gdex' ),
			'featured_image'        => __( 'Featured Image', 'gdex' ),
			'set_featured_image'    => __( 'Set featured image', 'gdex' ),
			'remove_featured_image' => __( 'Remove featured image', 'gdex' ),
			'use_featured_image'    => __( 'Use as featured image', 'gdex' ),
			'insert_into_item'      => __( 'Insert into Invoice', 'gdex' ),
			'uploaded_to_this_item' => __( 'Uploaded to this Invoice', 'gdex' ),
			'items_list'            => __( 'Invoices list', 'gdex' ),
			'items_list_navigation' => __( 'Invoices list navigation', 'gdex' ),
			'filter_items_list'     => __( 'Filter Invoices list', 'gdex' ),
		];

		$args = [
			'label'               => __( 'Invoice', 'gdex' ),
			'description'         => __( '', 'gdex' ),
			'labels'              => $labels,
			'menu_icon'           => '',
			'supports'            => [],
			'taxonomies'          => [],
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => false,
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

		register_post_type( 'gdex-invoice', $args );
	}

	public function add_invoice_submenu() {
		add_submenu_page(
			'gdex',
			__( 'Invoices', 'gdex' ),
			__( 'Invoices', 'gdex' ),
			'manage_woocommerce',
			'edit.php?post_type=gdex-invoice',
			null
		);
	}

	/**
	 * Search by consignment number
	 *
	 * @param \WP_Query $query
	 */
	public function search_by_invoice_number( WP_Query $query ) {
		global $current_screen;

		if ( ! $current_screen || $current_screen->id !== 'edit-gdex-invoice' ) {
			return;
		}

		if ( empty( $query->query_vars['s'] ) ) {
			return;
		}

		$invoices = get_posts( [
			'post_type'  => 'gdex-invoice',
			'meta_query' => [
				[
					'key'   => 'invoice_number',
					'value' => $query->query_vars['s'],
				],
			],
		] );

		$query->query_vars['post__in'] = array_merge( array_map( function ( WP_Post $invoice ) {
			return $invoice->ID;
		}, $invoices ), [ 0 ] );

		$query->query_vars['gdex_invoice_search'] = true;

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

		if ( 'edit.php' !== $pagenow || 'gdex-invoice' !== $typenow || ! get_query_var( 'gdex_invoice_search' ) || ! isset( $_REQUEST['s'] ) ) {
			return $query;
		}

		return wc_clean( wp_unslash( $_REQUEST['s'] ) );
	}

	public function set_list_table_columns( $columns ) {
		$new_columns = [
			'cb'             => $columns['cb'],
			'invoice_number' => __( 'Invoice', 'gdex' ),
			'submitted_at'   => __( 'Date', 'gdex' ),
			'total'          => __( 'Total (RM)', 'gdex' ),
		];

		return $new_columns;
	}

	public function render_list_table_submitted_at_column( $column, $post_id ) {
		if ( $column !== 'submitted_at' ) {
			return;
		}

		$submitted_at = get_post( $post_id )->post_date;

		echo date_i18n( wc_date_format() . ' ' . wc_time_format(), strtotime( $submitted_at ) );
	}

	public function render_list_table_invoice_number_column( $column, $post_id ) {
		if ( $column !== 'invoice_number' ) {
			return;
		}

		$invoice_number = get_post_meta( $post_id, 'invoice_number', true )
		?>
        <strong><?php
			echo $invoice_number; ?></strong>
		<?php
	}

	public function render_list_table_total_column( $column, $post_id ) {
		if ( $column !== 'total' ) {
			return;
		}

		$total = get_post_meta( $post_id, 'total', true );
		echo wc_price( $total );
	}

	public function set_row_actions( $actions, WP_Post $post ) {
		if ( $post->post_type !== 'gdex-invoice' ) {
			return $actions;
		}

		$new_actions = [];
		if ( ! empty( $actionsp['trash'] ) ) {
			$new_actions['trash'] = $actions['trash'];
		}

		return $new_actions;
	}

}
