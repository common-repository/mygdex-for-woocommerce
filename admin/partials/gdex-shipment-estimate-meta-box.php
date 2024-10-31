<?php

global $post;

$order                   = wc_get_order( $post );
$order_shipping_postcode = $order->get_shipping_postcode();
$order_shipping_country  = $order->get_shipping_country() ? wc()->countries->get_countries()[ $order->get_shipping_country() ] : '';
$order_weight            = gdex_wc_order_total_weight( $order );

$shipping_estimate                     = gdex_wc_order_shipping_estimate( $order );
$shipping_estimate_quoted_at_date_i18n = gdex_wc_order_shipping_estimate_quoted_at_date_i18n( $order );

$woocommerce_weight_unit     = get_option( 'woocommerce_weight_unit' );
$woocommerce_currency_symbol = get_woocommerce_currency_symbol();

?>

<p class="form-field gdex-shipping-estimate-meta-box-postcode_field <?php
echo ! $order_shipping_postcode ? 'form-field-error' : ''; ?>">
    <label for="gdex-shipping-estimate-meta-box-postcode"><?= __( 'Postcode', 'gdex' ) ?></label>
    <input
        id="gdex-shipping-estimate-meta-box-postcode"
        type="text"
        name="postcode"
        value="<?= $order_shipping_postcode ?>"
        placeholder=""
        required="required"
        readonly="readonly"
        class="short"
    >
	<?php if ( ! $order_shipping_postcode ): ?>
        <small class="error">
			<?php
			echo sprintf( __( 'The %s field is required.', 'gdex' ), __( 'postcode', 'gdex' ) ) ?>
        </small>
	<?php endif; ?>
</p>

<p class="form-field gdex-shipping-estimate-meta-box-country_field <?= ! $order_shipping_country ? 'form-field-error' : '' ?>">
    <label for="gdex-shipping-estimate-meta-box-country"><?= __( 'Country', 'gdex' ); ?></label>
    <input
        id="gdex-shipping-estimate-meta-box-country"
        type="text"
        name="country"
        value="<?= $order_shipping_country ?>"
        placeholder=""
        required="required"
        readonly="readonly"
        class="short"
    >
	<?php if ( ! $order_shipping_country ): ?>
        <small class="error">
			<?= sprintf( __( 'The %s field is required.', 'gdex' ), __( 'country', 'gdex' ) ) ?>
        </small>
	<?php endif; ?>
</p>

<p class="form-field gdex-shipping-estimate-meta-box-postcode_field <?= ! $order_weight ? 'form-field-error' : '' ?>">
    <label for="gdex-shipping-estimate-meta-box-postcode">
		<?= sprintf( __( 'Weight (%s) based on items ordered', 'gdex' ), "<code>{$woocommerce_weight_unit}</code>" ) ?>
    </label>
    <input
        id="gdex-shipping-estimate-meta-box-postcode"
        type="text"
        name="weight"
        value="<?= $order_weight ?>"
        placeholder=""
        required="required"
        readonly="readonly"
        class="short"
    >
	<?php if ( ! $order_weight ): ?>
        <small class="error">
			<?= sprintf( __( 'The %s field is required. Please add weight in product data.', 'gdex' ), __( 'weight', 'gdex' ) ) ?>
        </small>
	<?php endif; ?>
</p>

<div v-show="has_estimate" v-cloak>
	<?php
	woocommerce_wp_text_input( [
		'id'                => 'gdex-shipping-estimate-meta-box-estimate',
		'name'              => 'estimate',
		'label'             => sprintf( __( 'Shipping Estimate (%s)', 'gdex' ),
			"<code>{$woocommerce_currency_symbol}</code>" ),
		'data_type'         => 'price',
		'custom_attributes' => [
			'v-model'  => 'estimate',
			'readonly' => 'readonly',
		],
		'description'       => sprintf( __( 'Quoted at %s', 'gdex' ), "<abbr>{{ quoted_at }}</abbr>" ),
	] );
	?>
</div>

<?php
woocommerce_wp_hidden_input( [
	'id'    => 'gdex-shipping-estimate-meta-box-nonce',
	'value' => wp_create_nonce( 'gdex-quote-shipping-estimate' ),
] );
?>

<hr>

<button
    type="button"
    id="gdex-shipping-estimate-meta-box-submit-button"
    class="button button-primary"
	<?= ! $order_shipping_postcode || ! $order_shipping_country || ! $order_weight ? 'disabled' : '' ?>
    @click="submit"
>
	<?= __( 'Get Latest Quote', 'gdex' ) ?>
</button>

<button
    type="button"
    class="button"
    data-target="<?= add_query_arg( [
		'post_type' => 'gdex-shipment-order',
		'orders'    => [ $order->get_id() ]
	], admin_url( 'post-new.php' ) ) ?>"
	<?= ! $order_shipping_postcode || ! $order_shipping_country ? 'disabled' : '' ?>
    @click.prevent="create_shipment_order"
>
	<?= __( 'Create Shipment Order', 'gdex' ) ?>
</button>

<script>
  var gdex_shipping_estimate_meta_box = <?= json_encode( [
	  'postcode'  => $order_shipping_postcode,
	  'country'   => $order_shipping_country,
	  'weight'    => (float) $order_weight ?: 0,
	  'estimate'  => (float) $shipping_estimate ?: 0,
	  'quoted_at' => $shipping_estimate_quoted_at_date_i18n,
  ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT ); ?>
</script>