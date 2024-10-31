<?php

$shipment = $post;

$sender       = gdex_sender();
$pickup_dates = gdex_api_get_pickup_date_listing( $sender['postal_code'] );
?>
<div class="row" v-cloak>
    <div class="col-6">
        <h4>Sender Details</h4>
        <p
            class="form-field gdex_shipment_order_sender_name_field"
            :class="{ 'form-field-error': $v.sender_name.$error }"
        >
            <label for="gdex_shipment_order_sender_name"><?= __( 'Name', 'gdex' ) ?></label>
            <input
                v-model="$v.sender_name.$model"
                id="gdex_shipment_order_sender_name"
                type="text"
                class="short"
                name="sender_name"
                value="<?= $sender['name'] ?>"
                required
            >
            <small
                v-if="!$v.sender_name.required"
                class="error"
            >
				<?= sprintf( __( 'The %s field is required.', 'gdex' ), __( 'name', 'gdex' ) ) ?>
            </small>
        </p>
        <p
            class="form-field gdex_shipment_order_sender_email_field"
            :class="{ 'form-field-error': $v.sender_email.$error }"
        >
            <label for="gdex_shipment_order_sender_email"><?= __( 'Email', 'gdex' ) ?></label>
            <input
                v-model="$v.sender_email.$model"
                id="gdex_shipment_order_sender_email"
                type="email"
                class="short"
                name="sender_email"
                value="<?= $sender['email']; ?>"
                required
            >
            <small
                v-if="!$v.sender_email.required"
                class="error"
            >
				<?= sprintf( __( 'The %s field is required.', 'gdex' ), __( 'email', 'gdex' ) ) ?>
            </small>
            <small
                v-if="!$v.sender_email.email"
                class="error"
            >
				<?= sprintf( __( 'The %s field must be a valid email address.', 'gdex' ), __( 'email', 'gdex' ) ) ?>
            </small>
        </p>
        <p
            class="form-field gdex_shipment_order_sender_mobile_number_field"
            :class="{ 'form-field-error': $v.sender_mobile_number.$error }"
        >
            <label for="gdex_shipment_order_sender_mobile_number"><?= __( 'Mobile Number', 'gdex' ) ?></label>
            <input
                v-model="$v.sender_mobile_number.$model"
                id="gdex_shipment_order_sender_mobile_number"
                type="tel"
                class="short"
                name="sender_mobile_number"
                value="<?= $sender['mobile_number'] ?>"
                required
            >
            <small
                v-if="!$v.sender_mobile_number.required"
                class="error"
            >
				<?= sprintf( __( 'The %s field is required.', 'gdex' ), __( 'mobile number', 'gdex' ) ) ?>
            </small>
        </p>
        <p
            class="form-field gdex_shipment_order_sender_address1_field"
            :class="{ 'form-field-error': $v.sender_address1.$error }"
        >
            <label for="gdex_shipment_order_sender_address1"><?= __( 'Address Line 1', 'gdex' ) ?></label>
            <input
                v-model="$v.sender_address1.$model"
                id="gdex_shipment_order_sender_address1"
                type="text"
                class="short"
                name="sender_address1"
                value="<?= $sender['address1'] ?>"
                required
            >
            <small
                v-if="!$v.sender_address1.required"
                class="error"
            >
				<?= sprintf( __( 'The %s field is required.', 'gdex' ), __( 'address line 1', 'gdex' ) ) ?>
            </small>
        </p>
        <p
            class="form-field gdex_shipment_order_sender_address2_field"
            :class="{ 'form-field-error': $v.sender_address2.$error }"
        >
            <label for="gdex_shipment_order_sender_address2"><?= __( 'Address Line 2', 'gdex' ) ?></label>
            <input
                v-model="$v.sender_address2.$model"
                id="gdex_shipment_order_sender_address1"
                type="text"
                class="short"
                name="sender_address2"
                value="<?= $sender['address2'] ?>"
            >
        </p>
        <p
            class="form-field gdex_shipment_order_sender_postal_code_field"
            :class="{ 'form-field-error': $v.sender_postal_code.$error }"
        >
            <label for="gdex_shipment_order_sender_postal_code">
				<?= __( 'Postal Code', 'gdex' ) ?>
                <span
                    class="spinner"
                    :class="{ 'is-active': states.is_updating_pick_up_dates }"
                ></span>
            </label>
            <input
                v-model="$v.sender_postal_code.$model"
                id="gdex_shipment_order_sender_postal_code"
                type="text"
                class="short"
                name="sender_postal_code"
                value="<?= $sender['postal_code'] ?>"
                required
                maxlength="6"
                @blur="handle_sender_postal_code_on_blur"
            >
            <small
                v-if="!$v.sender_postal_code.required"
                class="error"
            >
				<?= sprintf( __( 'The %s field is required.', 'gdex' ), __( 'postal code', 'gdex' ) ) ?>
            </small>
            <small
                v-if="!$v.sender_postal_code.maxLength"
                class="error"
            >
				<?= sprintf( __( 'The %s may not be greater than %s characters.', 'gdex' ), __( 'postal code', 'gdex' ), 6 ) ?>
            </small>
            <small
                v-if="!$v.sender_postal_code.exists"
                class="error"
            >
				<?= sprintf( __( 'The selected %s is invalid.', 'gdex' ), __( 'postal code', 'gdex' ), 6 ) ?>
            </small>
        </p>
        <p
            class="form-field gdex_shipment_order_sender_location_id_field"
            :class="{ 'form-field-error': $v.sender_location_id.$error }"
        >
            <label for="gdex_shipment_order_sender_location_id">
				<?= __( 'Location', 'gdex' ) ?>
                <span
                    class="spinner"
                    :class="{ 'is-active': states.is_updating_locations }"
                ></span>
            </label>
            <select
                v-model="$v.sender_location_id.$model"
                id="gdex_shipment_order_sender_location_id"
                name="sender_location_id"
                @change="update_place"
            >
                <option
                    v-for="location of options.locations"
                    :value="location.id"
                >
                    {{ location.name }}
                </option>
            </select>
            <small
                v-if="!$v.sender_location_id.required"
                class="error"
            >
				<?= sprintf( __( 'The %s field is required.', 'gdex' ), __( 'location', 'gdex' ) ) ?>
            </small>
        </p>
        <input
            v-model="$v.sender_location.$model"
            id="gdex_shipment_order_sender_location"
            type="hidden"
            class="short"
            name="sender_location"
            value="<?= $sender['city'] ?>"
            required
            readonly
        >
        <p
            class="form-field gdex_shipment_order_sender_city_field"
            :class="{ 'form-field-error': $v.sender_city.$error }"
        >
            <label for="gdex_shipment_order_sender_city"><?= __( 'City', 'gdex' ) ?></label>
            <input
                v-model="$v.sender_city.$model"
                id="gdex_shipment_order_sender_city"
                type="text"
                class="short"
                name="sender_city"
                value="<?= $sender['city'] ?>"
                required
                readonly
            >
            <small
                v-if="!$v.sender_city.required"
                class="error"
            >
				<?= sprintf( __( 'The %s field is required.', 'gdex' ), __( 'city', 'gdex' ) ) ?>
            </small>
        </p>
        <p
            class="form-field gdex_shipment_order_sender_state_field"
            :class="{ 'form-field-error': $v.sender_state.$error }"
        >
            <label for="gdex_shipment_order_sender_state"><?= __( 'State', 'gdex' ) ?></label>
            <input
                v-model="$v.sender_state.$model"
                id="gdex_shipment_order_sender_state"
                type="text"
                class="short"
                name="sender_state"
                value="<?= $sender['state'] ?>"
                required
                readonly
            >
            <small
                v-if="!$v.sender_state.required"
                class="error"
            >
				<?= sprintf( __( 'The %s field is required.', 'gdex' ), __( 'state', 'gdex' ) ) ?></small>
        </p>
    </div>
    <div class="col-6">
        <h4>Service</h4>
        <p
            class="form-field gdex_shipment_order_service_type_field"
            :class="{ 'form-field-error': $v.service_type.$error }"
        >
            <label for="gdex_shipment_order_service_type"><?= __( 'Type', 'gdex' ) ?></label>
            <select
                v-model="$v.service_type.$model"
                id="gdex_shipment_order_service_type"
                name="service_type"
                class="select short"
            >
                <option value="<?= Gdex_Shipment_Order::SERVICE_TYPE_LODGE_IN ?>">
					<?= __( 'Lodge In', 'gdex' ) ?>
                </option>
                <option value="<?= Gdex_Shipment_Order::SERVICE_TYPE_PICK_UP ?>">
					<?= __( 'Pick Up', 'gdex' ) ?>
                </option>
            </select>
            <small
                v-if="!$v.service_type.required"
                class="error"
            >
				<?= sprintf( __( 'The %s field is required.', 'gdex' ), __( 'type', 'gdex' ) ) ?>
            </small>
        </p>
        <div v-show="isPickUp">
            <h4>Pickup Details</h4>
            <p
                class="form-field gdex_shipment_order_pick_up_date_field"
                :class="{ 'form-field-error': $v.pick_up_date.$error }"
            >
                <label for="gdex_shipment_order_pick_up_date">
					<?= __( 'Date', 'gdex' ) ?>
                    <span
                        class="spinner"
                        :class="{ 'is-active': states.is_updating_pick_up_dates }"
                    ></span>
                </label>
                <select
                    v-model="$v.pick_up_date.$model"
                    id="gdex_shipment_order_pick_up_date"
                    name="pick_up_date"
                    class="select short"
                >
                    <option
                        v-for="pick_up_date of options.pick_up_dates"
                        :value="pick_up_date.value"
                    >
                        {{ pick_up_date.date }} - {{ pick_up_date.day }}
                    </option>
                </select>
                <template v-if="states.is_sender_postal_code_valid">
                    <small
                        v-if="!$v.pick_up_date.required"
                        class="error"
                    >
						<?= sprintf( __( 'The %s field is required.', 'gdex' ), __( 'date', 'gdex' ) ) ?>
                    </small>
                </template>
                <input
                    type="hidden"
                    id="gdex_get_shipment_order_pick_up_dates_nonce"
                    name="gdex_get_shipment_order_pick_up_dates_nonce"
                    value="<?= wp_create_nonce( 'gdex-get-shipment-order-pick-up-dates' ) ?>"
                >
                <input
                    type="hidden"
                    id="gdex_get_place_nonce"
                    name="gdex_get_place_nonce"
                    value="<?= wp_create_nonce( 'gdex-get-place' ) ?>"
                >
            </p>
            <p
                class="form-field gdex_shipment_order_pick_up_time_field"
                :class="{ 'form-field-error': $v.pick_up_time.$error }"
            >
                <label for="gdex_shipment_order_pick_up_time"><?= __( 'Time', 'gdex' ) ?></label>
                <select
                    v-model="$v.pick_up_time.$model"
                    id="gdex_shipment_order_pick_up_time"
                    name="pick_up_time"
                    class="select short"
                >
                    <option value="09:00:00"><?= date_i18n( 'g:i a', strtotime( '9:00 am' ) ) ?></option>
                    <option value="09:30:00"><?= date_i18n( 'g:i a', strtotime( '9:30 am' ) ) ?></option>
                    <option value="10:00:00"><?= date_i18n( 'g:i a', strtotime( '10:00 am' ) ) ?></option>
                    <option value="10:30:00"><?= date_i18n( 'g:i a', strtotime( '10:30 am' ) ) ?></option>
                    <option value="11:00:00"><?= date_i18n( 'g:i a', strtotime( '11:00 am' ) ) ?></option>
                    <template v-if="!is_pick_up_saturday">
                        <option value="11:30:00"><?= date_i18n( 'g:i a', strtotime( '11:30 am' ) ) ?></option>
                        <option value="12:00:00"><?= date_i18n( 'g:i a', strtotime( '12:00 pm' ) ) ?></option>
                        <option value="12:30:00"><?= date_i18n( 'g:i a', strtotime( '12:30 pm' ) ) ?></option>
                        <option value="13:00:00"><?= date_i18n( 'g:i a', strtotime( '1:00 pm' ) ) ?></option>
                        <option value="13:30:00"><?= date_i18n( 'g:i a', strtotime( '1:30 pm' ) ) ?></option>
                        <option value="14:00:00"><?= date_i18n( 'g:i a', strtotime( '2:00 pm' ) ) ?></option>
                        <option value="14:30:00"><?= date_i18n( 'g:i a', strtotime( '2:30 pm' ) ) ?></option>
                        <option value="15:00:00"><?= date_i18n( 'g:i a', strtotime( '3:00 pm' ) ) ?></option>
                        <option value="15:30:00"><?= date_i18n( 'g:i a', strtotime( '3:30 pm' ) ) ?></option>
                        <option value="16:00:00"><?= date_i18n( 'g:i a', strtotime( '4:00 pm' ) ) ?></option>
                        <option value="16:30:00"><?= date_i18n( 'g:i a', strtotime( '4:30 pm' ) ) ?></option>
                    </template>
                </select>
                <small
                    v-if="!$v.pick_up_time.required"
                    class="error"
                >
					<?= sprintf( __( 'The %s field is required.', 'gdex' ), __( 'time', 'gdex' ) ) ?>
                </small>
            </p>
            <p
                class="form-field gdex_shipment_order_pick_up_transportation_field"
                :class="{ 'form-field-error': $v.pick_up_transportation.$error }"
            >
                <label for="gdex_shipment_order_pick_up_transportation"><?= __( 'Transportation', 'gdex' ) ?></label>
                <select
                    v-model="$v.pick_up_transportation.$model"
                    id="gdex_shipment_order_pick_up_transportation"
                    name="pick_up_transportation"
                    class="select short"
                >
                    <option value="<?= Gdex_Shipment_Order::PICK_UP_TRANSPORTATION_VAN; ?>"><?= __( 'Van', 'gdex' ) ?></option>
                    <option value="<?= Gdex_Shipment_Order::PICK_UP_TRANSPORTATION_MOTORBIKE; ?>"><?= __( 'Motorbike', 'gdex' ) ?></option>
                    <option value="<?= Gdex_Shipment_Order::PICK_UP_TRANSPORTATION_TRUCK; ?>"><?= __( 'Truck', 'gdex' ) ?></option>
                </select>
                <small
                    v-if="!$v.pick_up_transportation.required"
                    class="error"
                >
					<?= sprintf( __( 'The %s field is required.', 'gdex' ), __( 'transportation', 'gdex' ) ) ?>
                </small>
            </p>
            <p
                class="form-field gdex_shipment_order_pick_up_trolley_required_field"
                :class="{ 'form-field-error': $v.pick_up_trolley_required.$error }"
            >
                <label for="gdex_shipment_order_pick_up_trolley_required"><?= __( 'Trolley Required?', 'gdex' ) ?></label>
                <select
                    v-model="$v.pick_up_trolley_required.$model"
                    id="gdex_shipment_order_pick_up_trolley_required"
                    name="pick_up_trolley_required"
                    class="select short"
                >
                    <option value="yes"><?= __( 'Yes', 'gdex' ) ?></option>
                    <option value="no"><?= __( 'No', 'gdex' ) ?></option>
                </select>
                <small
                    v-if="!$v.pick_up_trolley_required.required"
                    class="error"
                >
					<?= sprintf( __( 'The %s field is required.', 'gdex' ), __( 'trolley', 'gdex' ) ) ?>
                </small>
            </p>
            <p
                class="form-field gdex_shipment_order_pick_up_remark_field"
            >
                <label for="gdex_shipment_order_pick_up_remark">Remark</label>
                <textarea
                    v-model="$v.pick_up_remark.$model"
                    id="gdex_shipment_order_pick_up_remark"
                    class="short"
                    name="pick_up_remark"
                    rows="2"
                    cols="20"
                ></textarea>
            </p>
        </div>
    </div>
    <input
        type="hidden"
        id="gdex_shipment_order_data_meta_box_nonce"
        name="gdex_shipment_order_data_meta_box_nonce"
        value="<?= wp_create_nonce( 'gdex-save-shipment-order-data' ) ?>"
    >
</div>
<script>
  var gdex_shipment_order_data_meta_box = {
    sender_name: "<?= __( $sender['name'], 'gdex' ) ?>",
    sender_email: "<?= __( $sender['email'], 'gdex' ) ?>",
    sender_mobile_number: "<?= __( $sender['mobile_number'], 'gdex' ) ?>",
    sender_address1: "<?= __( $sender['address1'], 'gdex' ) ?>",
    sender_address2: "<?= __( $sender['address2'], 'gdex' ) ?>",
    sender_postal_code: "<?= __( $sender['postal_code'], 'gdex' ) ?>",
    sender_location_id: "<?= __( $sender['location_id'], 'gdex' ) ?>",
    sender_location: "<?= __( $sender['location'], 'gdex' ) ?>",
    sender_city: "<?= __( $sender['city'], 'gdex' ) ?>",
    sender_state: "<?= __( $sender['state'], 'gdex' ) ?>",
    service_type: "<?= Gdex_Shipment_Order::SERVICE_TYPE_LODGE_IN ?>",
    pick_up_transportation: "<?= Gdex_Shipment_Order::PICK_UP_TRANSPORTATION_VAN ?>",
    locations: <?= json_encode( gdex_get_place( $sender['postal_code'] ), JSON_THROW_ON_ERROR ) ?>
  }
</script>
