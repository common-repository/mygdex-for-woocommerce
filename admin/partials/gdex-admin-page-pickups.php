<?php

$pickups = gdex_api_get_upcoming_pickup_details();

usort( $pickups, function ( $pickup1, $pickup2 ) {
	$pickup1_timestamp = $pickup1['ready_at']->getTimestamp();
	$pickup2_timestamp = $pickup2['ready_at']->getTimestamp();

	if ( $pickup1_timestamp < $pickup2_timestamp ) {
		return - 1;
	}

	if ( $pickup1_timestamp > $pickup2_timestamp ) {
		return 1;
	}

	return 0;
} );
?>
<div
    id="gdex-pickup-wrap"
    class="wrap"
>
    <h1 class="wp-heading-inline"><?= __( 'Pickups', 'gdex' ) ?></h1>
    <hr class="wp-header-end">
    <h2 class="screen-reader-text"><?= __( 'Filter pickup', 'gdex' ) ?></h2>
    <form id="posts-filter" method="get">
        <h2 class="screen-reader-text">Pickups list</h2>
        <table class="wp-list-table widefat fixed striped posts">
            <thead>
            <tr>
                <th class="manage-column column-id"><?= __( 'ID#', 'gdex' ) ?></th>
                <th class="manage-column column-date"><?= __( 'Date', 'gdex' ) ?></th>
                <th class="manage-column column-transport"><?= __( 'Transport', 'gdex' ) ?></th>
                <th class="manage-column column-actions"><?= __( 'Actions', 'gdex' ) ?></th>
            </tr>
            </thead>
            <tbody id="the-list">
			<?php if ( $pickups ): ?>
				<?php foreach ( $pickups as $pickup ): ?>
					<?php
					$cancel_url = add_query_arg( [
						'action'    => 'gdex_cancel_pickup',
						'pickup_id' => $pickup['id'],
						'redirect'  => true,
					], admin_url( 'admin-ajax.php' ) );

					$cancel_nonce_url = wp_nonce_url( $cancel_url, 'gdex-cancel-pickup' );
					?>
                    <tr>
                        <td class="column-id"><?= $pickup['id'] ?></td>
                        <td class="column-date"><?= $pickup['ready_at_date_i18n'] ?></td>
                        <td class="column-transport"><?= $pickup['transportation'] ?></td>
                        <td class="column-actions">
                            <p>
								<?php if ( $pickup['can_cancel'] ): ?>
                                    <a
                                        class="button wc-action-button cancel"
                                        href="<?= $cancel_nonce_url ?>"
                                        aria-label="Cancel"
                                    >
										<?= __( 'Cancel', 'gdex' ) ?>
                                    </a>
								<?php endif; ?>
                            </p>
                        </td>
                    </tr>
				<?php endforeach; ?>
			<?php else: ?>
                <tr class="no-items">
                    <td class="colspanchange" colspan="4"><?= __( 'No pickups found', 'gdex' ) ?></td>
                </tr>
			<?php endif; ?>
            </tbody>
            <tfoot>
            <tr>
                <th class="manage-column column-id"><?= __( 'ID#', 'gdex' ) ?></th>
                <th class="manage-column column-date"><?= __( 'Date', 'gdex' ) ?></th>
                <th class="manage-column column-transport"><?= __( 'Transport', 'gdex' ) ?></th>
                <th class="manage-column column-actions"><?= __( 'Actions', 'gdex' ) ?></th>
            </tr>
            </tfoot>
        </table>
    </form>
</div>