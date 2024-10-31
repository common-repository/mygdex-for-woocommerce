<?php

$wallet_balance = gdex_api_get_check_ewallet_balance();
?>
<div v-cloak>
    <p>
        <strong>Wallet Balance</strong><br>
        RM <?= number_format_i18n( $wallet_balance, 2 ) ?>
        <a class="button" href="https://my.gdexpress.com/dashboard/ewallet" target="_blank">
			<?= __( 'Top up', 'gdex' ) ?>
        </a>
    </p>

    <p>
        <strong>Shipping Rate</strong><br>
        RM {{ total.toFixed(2) }}<br>
        <small
            v-if="this.is_insufficient_balance"
            class="error"
        >
			<?= __( 'Insufficient fund.', 'gdex' ) ?>
        </small>
    </p>

    <input
        type="hidden"
        name="post_title"
        value="Shipment Order"
    >

    <input
        type="hidden"
        name="post_status"
        value="publish"
    >

    <input
        type="hidden"
        name="gdex_shipment_order_submit_nonce"
        value="<?= wp_create_nonce( 'gdex-shipment-order-submit' ) ?>"
    >

    <button
        type="submit"
        id="gdex-shipment-order-actions-meta-box-submit-button"
        class="button button-primary"
        :disabled="!can_submit"
        @click.prevent="submit"
    >
		<?= __( 'Create', 'gdex' ) ?>
    </button>
</div>
<script>
  var gdex_shipment_order_actions_meta_box = {
    wallet_balance: <?= $wallet_balance ?>
  }
</script>
