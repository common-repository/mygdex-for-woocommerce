<?php

$orders = array_map( 'wc_get_order', wc_clean( $_REQUEST['orders'] ) );

$consignments = array_map( function ( WC_Order $order ) {
	return [
		'order_id'         => $order->get_id(),
		'shipping_address' => $order->get_formatted_shipping_address(),
		'content'          => gdex_wc_order_category_list( $order ),
		'parcel_type'      => gdex_consignment_parcel_type(),
		'value'            => $order->get_total(),
		'pieces'           => 1,
		'weight'           => gdex_wc_order_total_weight( $order ) ?: 0.1,
		'name'             => $order->get_formatted_shipping_full_name(),
		'rate'             => gdex_wc_order_shipping_estimate( $order ),
		'is_updating_rate' => false,
	];
}, $orders );

?>
<table
    class="wp-list-table widefat fixed striped posts"
>
    <thead>
    <tr>
        <th class="manage-column column-shop-order-id"><?php
			_e( 'Order ID#', 'gdex' ); ?></th>
        <th class="manage-column column-shop-order-shipping-address"><?php
			_e( 'Shipping Address', 'gdex' ); ?></th>
        <th class="manage-column column-gdex-consignment-parcel-type"><?php
			_e( 'Parcel Type', 'gdex' ); ?></th>
        <th class="manage-column column-gdex-consignment-content"><?php
			_e( 'Content', 'gdex' ); ?></th>
        <th class="manage-column column-gdex-consignment-pieces"><?php
			_e( 'Pieces', 'gdex' ); ?></th>
        <th class="manage-column column-gdex-consignment-weight"><?php
			_e( 'Weight (Kg)', 'gdex' ); ?></th>
        <th class="manage-column column-gdex-consignment-value"><?php
			_e( 'Value (RM)', 'gdex' ); ?></th>
        <th class="manage-column column-gdex-consignment-rate"><?php
			_e( 'Shipping Rate (RM)', 'gdex' ); ?></th>
    </tr>
    </thead>
    <tbody id="the-list">
    <tr v-for="(v, index) in $v.consignments.$each.$iter" v-cloak>
        <td>
            {{ v.$model.order_id }}
            <input
                type="hidden"
                :name=" 'consignments[' + index + '][order_id]' "
                :value=" v.$model.order_id"
            >
        </td>
        <td v-html="v.$model.shipping_address"></td>
        <td>
            <p
                class="form-field"
                :class="{ 'form-field-error': v.parcel_type.$error }"
            >
                <select
                    v-model="v.parcel_type.$model"
                    :name=" 'consignments[' + index + '][parcel_type]' "
                    class="select short"
                    @change="handle_consignment_parcel_type_on_change( v.$model, $event )"
                >
                    <option value="<?= Gdex_Api::PARCEL_TYPE_DOCUMENT ?>"><?= __( 'Document', 'gdex' ) ?></option>
                    <option value="<?= Gdex_Api::PARCEL_TYPE_PARCEL ?>"><?= __( 'Parcel', 'gdex' ) ?></option>
                </select>
                <small
                    v-if="!v.parcel_type.required"
                    class="error"
                >
					<?= sprintf( __( 'The %s field is required.', 'gdex' ), __( 'parcel type', 'gdex' ) ) ?>
                </small>
            </p>
        </td>
        <td>
            {{ v.$model.content }}
            <input
                type="hidden"
                :name=" 'consignments[' + index + '][content]' "
                :value="v.$model.content"
            >
        </td>
        <td>
            <p
                class="form-field"
                :class="{ 'form-field-error': v.pieces.$error }"
            >
                <select
                    v-model.number="v.pieces.$model"
                    :name=" 'consignments[' + index + '][pieces]' "
                    class="select short"
                >
                    <option>1</option>
                    <option>2</option>
                    <option>3</option>
                    <option>4</option>
                    <option>5</option>
                </select>
                <small
                    v-if="!v.pieces.required"
                    class="error"
                >
					<?= sprintf( __( 'The %s field is required.', 'gdex' ), __( 'pieces', 'gdex' ) ) ?>
                </small>
            </p>
        </td>
        <td>
            <p
                class="form-field"
                :class="{ 'form-field-error': v.weight.$error }"
            >
                <input
                    v-model.number="v.weight.$model"
                    type="number"
                    class="select short"
                    :name=" 'consignments[' + index + '][weight]' "
                    min="0.1"
                    @blur="handle_consignment_weight_on_blur( v.$model, $event )"
                >
                <small
                    v-if="!v.weight.required"
                    class="error"
                >
					<?= sprintf( __( 'The %s field is required.', 'gdex' ), __( 'weight', 'gdex' ) ) ?>
                </small>
                <small
                    v-if="!v.weight.min"
                    class="error"
                >
					<?= sprintf( __( 'The %s must be at least %s.', 'gdex' ), __( 'weight', 'gdex' ), 0.1 ) ?>
                </small>
            </p>
        </td>
        <td>
            {{ v.$model.value }}
        </td>
        <td>
            {{ v.$model.rate.toFixed(2) }}
            <span
                class="spinner"
                :class="{ 'is-active': v.$model.is_updating_rate }"
            ></span>
            <input
                type="hidden"
                :name=" 'consignments[' + index + '][rate]' "
                :value="v.$model.rate.toFixed(2)"
            >
        </td>
    </tr>
    </tbody>
    <tfoot>
    <tr v-cloak>
        <td colspan="6"></td>
        <td>Total</td>
        <td>{{ total.toFixed(2) }}</td>
    </tr>
    </tfoot>
</table>
<input
    type="hidden"
    id="gdex_consignment_quote_shipping_rate_nonce"
    name="gdex_consignment_quote_shipping_rate_nonce"
    value="<?= wp_create_nonce( 'gdex-consignment-quote-shipping-rate' ) ?>"
>
<input
    type="hidden"
    id="gdex_shipment_order_consignments_meta_box_nonce"
    name="gdex_shipment_order_consignments_meta_box_nonce"
    value="<?= wp_create_nonce( 'gdex-save-shipment-order-consignments' ) ?>"
>
<script>
  var gdex_shipment_order_consignments_meta_box = {
    consignments: <?= json_encode( $consignments, JSON_THROW_ON_ERROR ) ?>
  }
</script>
