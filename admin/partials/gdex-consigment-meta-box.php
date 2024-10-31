<?php

global $post;

$order = wc_get_order( $post );

$consignment = gdex_wc_order_latest_consignment( $order );
if ( ! $consignment ) {
	return;
}

$consignment_number      = gdex_consignment_number( $consignment );
$consignment_parcel_type = get_post_meta( $consignment->ID, 'parcel_type', true );
$consignment_pieces      = get_post_meta( $consignment->ID, 'pieces', true );
$consignment_weight      = get_post_meta( $consignment->ID, 'weight', true );
$consignment_rate        = get_post_meta( $consignment->ID, 'rate', true );
$consignment_status      = gdex_consignment_status( $consignment );

try {
	$consignment_pick_up = gdex_consignment_pick_up( $consignment );
} catch ( Exception $e ) {
	$consignment_pick_up = false;
}

$consignment_print_url = add_query_arg( [
	'action'         => 'gdex_print_consignment_note',
	'consignment_id' => $consignment->ID,
], admin_url( 'admin-ajax.php' ) );

$consignment_print_nonce_url = wp_nonce_url( $consignment_print_url, 'gdex-print-consignment-note',
	'gdex-consignment-print-note-nonce' );
?>
    <p>
        <strong><?= __( 'Consignment Number', 'gdex' ) ?></strong><br>
		<?= $consignment_number ?>
    </p>
    <p>
        <strong><?= __( 'Parcel Type', 'gdex' ) ?></strong><br>
		<?= __( ucfirst( $consignment_parcel_type ), 'gdex' ) ?>
    </p>
    <p>
        <strong><?= __( 'Pieces', 'gdex' ) ?></strong><br>
		<?= $consignment_pieces; ?>
    </p>
    <p>
        <strong><?= __( 'Weight (Kg)', 'gdex' ) ?></strong><br>
		<?= $consignment_rate; ?>
    </p>
<?php if ( $consignment_pick_up ): ?>
    <p>
        <strong><?= __( 'Pick Up', 'gdex' ); ?></strong><br>
		<?= date_i18n( wc_date_format(), strtotime( $consignment_pick_up['date'] ) ) . ' ' . date_i18n( wc_time_format(), strtotime( $consignment_pick_up['time'] ) ) ?>
        <br>
		<?= $consignment_pick_up['transportation']; ?>
    </p>
<?php endif; ?>
    <p>
        <strong><?= __( 'Shipping Rate (RM)', 'gdex' ) ?></strong><br>
		<?= $consignment_rate ?>
    </p>
    <p>
        <strong><?= __( 'Status', 'gdex' ) ?></strong><br>
		<?= __( $consignment_status, 'gdex' ) ?>
    </p>
    <hr>
<?php if ( $consignment_status === 'Pending' ): ?>
    <a
        class="button"
        href="<?= $consignment_print_nonce_url ?>"
        target="_blank"
    >
		<?= __( 'Print Note', 'gdex' ) ?>
    </a>
<?php endif; ?>