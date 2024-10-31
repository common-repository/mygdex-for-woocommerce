<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$consignment_number = gdex_consignment_number( $consignment );

do_action( 'woocommerce_email_header', $email_heading, $email );

?>
    <p><?php printf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $order->get_billing_first_name() ) ); ?></p>
    <p><?php esc_html_e( 'Your order is on the way. Track your shipment to see the delivery status.', 'gdex' ); ?></p>
    <p><?php printf( esc_html__( 'GDEX tracking number: %s', 'gdex' ), "<a href=\"https://gdexpress.com/tracking/?consignmentno={$consignment_number}\">{$consignment_number}</a>" ); ?></p>
<?php

do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email );