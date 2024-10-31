<?php

if ( class_exists( 'WC_Emails', false ) ) {
	WC()->mailer();
}

class Gdex_Shipping_Email_Consignment_Ready extends WC_Email {

	public function __construct() {
		$this->plugin_id      = 'gdex_shipping_';
		$this->id             = 'consignment_ready';
		$this->customer_email = true;
		$this->title          = __( 'GDEX consignment ready', 'gdex' );
		$this->description    = __( 'Consignment on the way emails are sent to customers when their orders are ready for delivery.', 'gdex' );
		$this->template_base  = GDEX_PLUGIN_DIR_PATH . 'integrations/woocommerce/templates/';
		$this->template_html  = 'emails/consignment-ready.php';
		$this->template_plain = 'emails/plain/consignment-ready.php';
		$this->placeholders   = [
			'{consignment_number}' => '',
			'{order_date}'         => '',
			'{order_number}'       => '',
		];

		$this->subject = __( 'A shipment from {site_title} order is on the way!', 'gdex' );
		$this->heading = __( 'Your order is ready for delivery', 'gdex' );

		// Call parent constructor.
		parent::__construct();

		$this->manual = true;
	}

	public function get_default_additional_content(): string {
		return __( 'Thanks for using {site_url}!', 'woocommerce' );
	}


	public function trigger( $consignment_id ) {
		$this->setup_locale();

		$consignment = gdex_consignment_get( $consignment_id );

		$this->object                               = $consignment;
		$this->placeholders['{consignment_number}'] = gdex_consignment_number( $consignment );

		$order                                = gdex_consignment_order( $consignment );
		$this->recipient                      = $order->get_billing_email();
		$this->placeholders['{order_date}']   = wc_format_datetime( $order->get_date_created() );
		$this->placeholders['{order_number}'] = $order->get_order_number();

		if ( $this->is_enabled() && $this->get_recipient() ) {
			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}

		$this->restore_locale();
	}

	public function get_content_html(): string {
		return wc_get_template_html(
			$this->template_html,
			[
				'consignment'        => $this->object,
				'order'              => gdex_consignment_order( $this->object ),
				'email_heading'      => $this->get_heading(),
				'additional_content' => $this->get_additional_content(),
				'sent_to_admin'      => false,
				'plain_text'         => false,
				'email'              => $this,
			],
			'',
			$this->template_base,
		);
	}

	public function get_content_plain(): string {
		return wc_get_template_html(
			$this->template_plain,
			[
				'consignment'        => $this->object,
				'order'              => gdex_consignment_order( $this->object ),
				'additional_content' => $this->get_additional_content(),
				'sent_to_admin'      => false,
				'plain_text'         => true,
				'email'              => $this,
			]
		);
	}
}