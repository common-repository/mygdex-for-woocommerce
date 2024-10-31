<?php

class Gdex_Shipping extends WC_Shipping_Method {

	/**
	 * Init and hook in the integration.
	 */
	public function __construct( $instance_id = 0 ) {
		$this->id                 = 'gdex';
		$this->instance_id        = absint( $instance_id );
		$this->method_title       = __( 'GDEX', 'gdex' );
		$this->method_description = __( 'To start creating GDEX consignments, please fill in your user credentials as shown in your GDEX user portal.',
			'gdex' );

		$this->init();
	}

	/**
	 * Initialize gdex shipping.
	 */
	public function init() {
		$this->init_settings();
		$this->init_form_fields();

		add_action( 'woocommerce_update_options_shipping_' . $this->id, [ $this, 'process_admin_options' ] );
		add_action( 'woocommerce_update_options_shipping_' . $this->id, [ $this, 'display_errors' ] );
		add_action( 'woocommerce_update_options_shipping_' . $this->id, [ $this, 'init_settings' ] );
		add_action( 'woocommerce_update_options_shipping_' . $this->id, [ $this, 'init_form_fields' ] );

		add_filter( "woocommerce_settings_api_sanitized_fields_{$this->id}", [ $this, 'use_sender_details_from_api' ] );
	}

	/**
	 * Initialise form fields.
	 */
	public function init_form_fields() {
		$this->form_fields = [];
		$this->form_fields += $this->init_api_form_fields();

		if ( gdex_has_api_user_access_token() ) {
			$this->form_fields += $this->init_sender_form_fields();
			$this->form_fields += $this->init_consignment_form_fields();
		}
	}

	/**
	 * Initialise api form fields
	 */
	public function init_api_form_fields() {
		$fields['gdex_api'] = [
			'title'       => __( 'API', 'gdex' ),
			'type'        => 'title',
			'description' => __( 'Configure your access towards the GDEX APIs by means of authentication.', 'gdex' ),
		];

		$fields['gdex_api_user_access_token'] = [
			'title'             => __( 'User Access Token', 'gdex' ),
			'type'              => 'text',
			'description'       => __( 'Go to <a href="https://my.gdexpress.com/dashboard/UpdateProfile" target="_blank">myGDEX User Profile</a> page, generate and obtain your user access token.',
				'gdex' ),
			'custom_attributes' => [ 'required' => 'required' ],
		];

		return $fields;
	}

	/**
	 * Iniialise sender form fields
	 */
	public function init_sender_form_fields() {
		$locationOptions           = [];
		$locationOptionsAttributes = [];
		foreach ( gdex_get_place( gdex_get_settings( 'gdex_sender_postal_code' ) ) as $location ) {
			$locationOptions[ $location['id'] ]           = $location['name'];
			$locationOptionsAttributes[ $location['id'] ] = [
				'data-city'  => $location['city'],
				'data-state' => $location['state'],
			];
		}

		$fields['gdex_sender'] = [
			'title'       => __( 'Sender Details', 'gdex' ),
			'type'        => 'title',
			'description' => __( 'Configure your sender details underneath.', 'gdex' ),
		];

		$fields['gdex_sender_load_from_api'] = [
			'title'       => __( 'Default Sender Details', 'gdex' ),
			'type'        => 'checkbox',
			'label'       => __( 'Use user detail from myGDEX.', 'gdex' ),
			'class'       => 'gdex-sender-load-from-api-input',
			'description' => __( 'Uncheck to override sender details below.', 'gdex' ),
		];

		$fields['gdex_sender_name'] = [
			'title'             => __( 'Name', 'gdex' ),
			'type'              => 'text',
			'class'             => 'gdex-sender-input gdex-sender-input-editable',
			'custom_attributes' => [ 'required' => 'required' ],
		];

		$fields['gdex_sender_email'] = [
			'title'             => __( 'Email', 'gdex' ),
			'type'              => 'email',
			'class'             => 'gdex-sender-input gdex-sender-input-editable',
			'custom_attributes' => [ 'required' => 'required' ],
		];

		$fields['gdex_sender_mobile_number'] = [
			'title'             => __( 'Mobile Number', 'gdex' ),
			'type'              => 'tel',
			'class'             => 'gdex-sender-input gdex-sender-input-editable',
			'custom_attributes' => [ 'required' => 'required' ],
		];

		$fields['gdex_sender_address1'] = [
			'title'             => __( 'Address Line 1', 'gdex' ),
			'type'              => 'text',
			'class'             => 'gdex-sender-input gdex-sender-input-editable',
			'custom_attributes' => [ 'required' => 'required' ],
		];

		$fields['gdex_sender_address2'] = [
			'title' => __( 'Address Line 2', 'gdex' ),
			'type'  => 'text',
			'class' => 'gdex-sender-input gdex-sender-input-editable',
		];

		$fields['gdex_sender_postal_code'] = [
			'title'             => __( 'Postal Code', 'gdex' ),
			'type'              => 'text',
			'class'             => 'gdex-sender-input gdex-sender-input-editable',
			'custom_attributes' => [ 'required' => 'required', 'maxlength' => 5 ],
		];

		$fields['gdex_sender_location_id'] = [
			'title'              => __( 'Location', 'gdex' ),
			'type'               => 'select',
			'options'            => $locationOptions,
			'class'              => 'gdex-sender-input',
			'custom_attributes'  => [ 'required' => 'required' ],
			'options_attributes' => $locationOptionsAttributes,
		];

		$fields['gdex_sender_location'] = [
			'title'             => __( 'Location', 'gdex' ),
			'type'              => 'text',
			'class'             => 'gdex-sender-input',
			'custom_attributes' => [ 'required' => 'required', 'readonly' => 'readonly' ],
		];

		$fields['gdex_sender_city'] = [
			'title'             => __( 'City', 'gdex' ),
			'type'              => 'text',
			'class'             => 'gdex-sender-input',
			'custom_attributes' => [ 'required' => 'required', 'readonly' => 'readonly' ],
		];

		$fields['gdex_sender_state'] = [
			'title'             => __( 'State', 'gdex' ),
			'type'              => 'text',
			'class'             => 'gdex-sender-input',
			'custom_attributes' => [ 'required' => 'required', 'readonly' => 'readonly' ],
		];

		$fields['gdex_sender_country'] = [
			'title'             => __( 'Country', 'gdex' ),
			'type'              => 'text',
			'class'             => 'gdex-sender-input',
			'custom_attributes' => [ 'required' => 'required', 'readonly' => 'readonly' ],
		];

		//		 make sender fields readonly if load from api
		//		if ( gdex_get_settings( 'gdex_sender_load_from_api' ) === 'yes' ) {
		//			$fields['gdex_sender_name']['custom_attributes']['readonly']          = 'readonly';
		//			$fields['gdex_sender_email']['custom_attributes']['readonly']         = 'readonly';
		//			$fields['gdex_sender_mobile_number']['custom_attributes']['readonly'] = 'readonly';
		//			$fields['gdex_sender_address1']['custom_attributes']['readonly']      = 'readonly';
		//			$fields['gdex_sender_address2']['custom_attributes']['readonly']      = 'readonly';
		//			$fields['gdex_sender_postal_code']['custom_attributes']['readonly']   = 'readonly';
		//		}

		return $fields;
	}

	/**
	 * Iniialise consignment form fields
	 */
	public function init_consignment_form_fields() {
		$fields['gdex_consignment'] = [
			'title'       => __( 'Consignment', 'gdex' ),
			'type'        => 'title',
			'description' => __( 'Configure your consignment parameters underneath.', 'gdex' ),
		];

		$fields['gdex_consignment_parcel_type'] = [
			'title'       => __( 'Parcel Type', 'gdex' ),
			'type'        => 'select',
			'options'     => [
				Gdex_Api::PARCEL_TYPE_PARCEL   => 'Parcel',
				Gdex_Api::PARCEL_TYPE_DOCUMENT => 'Document',
			],
			'description' => __( 'Default parcel type used for shipping rate quote and consignment creation.', 'gdex' ),
		];

		$fields['gdex_consignment_notify_customer'] = [
			'title'       => __( 'Notify customer?', 'gdex' ),
			'type'        => 'checkbox',
			'label'       => __( 'Notify customer when consignment is created.', 'gdex' ),
			'description' => __( 'Consignment on the way emails are sent to customers when their orders are ready for delivery.', 'gdex' ),
		];

		return $fields;
	}

	/**
	 * Validate api user access token field
	 *
	 * @param $key
	 * @param $value
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function validate_gdex_api_user_access_token_field( $key, $value ) {
		$is_token_valid = gdex_api_validate_user_access_token( $value );
		if ( ! $is_token_valid ) {
			throw new Exception( __( 'user access token is invalid.', 'gdex' ) );
		}

		return $value;
	}

	/**
	 * Load sender details from api
	 *
	 * @param $settings
	 * @param $shipping_method
	 *
	 * @return array
	 */
	public function use_sender_details_from_api( $settings ) {
		$old_settings = gdex_get_settings();

		if ( $old_settings['gdex_api_user_access_token'] !== $settings['gdex_api_user_access_token'] ) {
			$settings['gdex_sender_load_from_api'] = 'yes';
		}

		if ( isset( $settings['gdex_sender_load_from_api'] ) && $settings['gdex_sender_load_from_api'] === 'yes' ) {
			$sender_details = gdex_api_get_user_details( $settings['gdex_api_user_access_token'] );

			$settings['gdex_sender_name']          = $sender_details['name'];
			$settings['gdex_sender_email']         = $sender_details['email'];
			$settings['gdex_sender_mobile_number'] = $sender_details['mobile_number'];
			$settings['gdex_sender_address1']      = $sender_details['address1'];
			$settings['gdex_sender_address2']      = $sender_details['address2'];
			$settings['gdex_sender_postal_code']   = $sender_details['postal_code'];
			$settings['gdex_sender_city']          = $sender_details['city'];
			$settings['gdex_sender_location_id']   = $sender_details['location_id'];
			$settings['gdex_sender_location']      = $sender_details['location'];
			$settings['gdex_sender_state']         = $sender_details['state'];
			$settings['gdex_sender_country']       = $sender_details['country'];
		}

		return $settings;
	}

	/**
	 * Generate Select HTML.
	 *
	 * @param string $key Field key.
	 * @param array $data Field data.
	 *
	 * @return string
	 * @since  1.0.0
	 */
	public function generate_select_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$defaults  = [
			'title'              => '',
			'disabled'           => false,
			'class'              => '',
			'css'                => '',
			'placeholder'        => '',
			'type'               => 'text',
			'desc_tip'           => false,
			'description'        => '',
			'custom_attributes'  => [],
			'options'            => [],
			'options_attributes' => [],
		];

		$data  = wp_parse_args( $data, $defaults );
		$value = $this->get_option( $key );

		foreach ( $data['options_attributes'] as &$attributes ) {
			$html = [];
			foreach ( $attributes as $attribute_name => $attribute_value ) {
				$html[] = "{$attribute_name}=\"{$attribute_value}\"";
			}

			$attributes = implode( ' ', $html );
		}

		ob_start();
		?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label
                    for="<?= esc_attr( $field_key ) ?>"
                >
					<?= wp_kses_post( $data['title'] ) ?>
					<?= $this->get_tooltip_html( $data ) ?>
                </label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?= wp_kses_post( $data['title'] ) ?></span></legend>
                    <select
                        class="select <?= esc_attr( $data['class'] ) ?>"
                        name="<?= esc_attr( $field_key ) ?>"
                        id="<?= esc_attr( $field_key ) ?>"
                        style="<?= esc_attr( $data['css'] ) ?>"
						<?= disabled( $data['disabled'], true, false ) ?>
						<?= $this->get_custom_attribute_html( $data ) ?>
                    >
						<?php foreach ( (array) $data['options'] as $option_key => $option_value ) : ?>
							<?php if ( is_array( $option_value ) ) : ?>
                                <optgroup label="<?php echo esc_attr( $option_key ); ?>">
									<?php foreach ( $option_value as $option_key_inner => $option_value_inner ) : ?>
                                        <option
                                            value="<?= esc_attr( $option_key_inner ) ?>"
											<?= selected( (string) $option_key_inner, esc_attr( $value ), false ) ?>
											<?= $data['options_attributes'][ $option_key ] ?? '' ?>
                                        >
											<?= esc_html( $option_value_inner ) ?>
                                        </option>
									<?php endforeach; ?>
                                </optgroup>
							<?php else : ?>
                                <option
                                    value="<?= esc_attr( $option_key ) ?>"
									<?= selected( (string) $option_key, esc_attr( $value ), false ) ?>
									<?= $data['options_attributes'][ $option_key ] ?? '' ?>
                                >
									<?= esc_html( $option_value ) ?>
                                </option>
							<?php endif; ?>
						<?php endforeach; ?>
                    </select>
					<?= $this->get_description_html( $data ) ?>
                </fieldset>
            </td>
        </tr>
		<?php

		return ob_get_clean();
	}
}