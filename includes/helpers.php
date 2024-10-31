<?php

/**
 * Return gdex settings
 *
 * @return array|string
 */
function gdex_get_settings( $setting = null ) {
	$settings = array_merge( [
		'gdex_api_user_access_token'       => '',
		'gdex_sender_load_from_api'        => 'yes',
		'gdex_sender_name'                 => '',
		'gdex_sender_email'                => '',
		'gdex_sender_mobile_number'        => '',
		'gdex_sender_address1'             => '',
		'gdex_sender_address2'             => '',
		'gdex_sender_postal_code'          => '',
		'gdex_sender_city'                 => '',
		'gdex_sender_location_id'          => '',
		'gdex_sender_location'             => '',
		'gdex_sender_state'                => '',
		'gdex_sender_country'              => '',
		'gdex_consignment_parcel_type'     => Gdex_Api::PARCEL_TYPE_PARCEL,
		'gdex_consignment_notify_customer' => 'no',
	], get_option( 'woocommerce_gdex_settings', [] ) );

	if ( $setting ) {
		return $settings[ $setting ];
	}

	return $settings;
}

/**
 * User access token
 *
 * @return array
 */
function gdex_api_user_access_token() {
	return gdex_get_settings( 'gdex_api_user_access_token' );
}

/**
 * Sender
 *
 * @param null $attribute
 *
 * @return array|mixed
 */
function gdex_sender( $attribute = null ) {
	$sender = [
		'name'          => gdex_get_settings( 'gdex_sender_name' ),
		'email'         => gdex_get_settings( 'gdex_sender_email' ),
		'mobile_number' => gdex_get_settings( 'gdex_sender_mobile_number' ),
		'address1'      => gdex_get_settings( 'gdex_sender_address1' ),
		'address2'      => gdex_get_settings( 'gdex_sender_address2' ),
		'postal_code'   => gdex_get_settings( 'gdex_sender_postal_code' ),
		'city'          => gdex_get_settings( 'gdex_sender_city' ),
		'location_id'   => gdex_get_settings( 'gdex_sender_location_id' ),
		'location'      => gdex_get_settings( 'gdex_sender_location' ),
		'state'         => gdex_get_settings( 'gdex_sender_state' ),
		'country'       => gdex_get_settings( 'gdex_sender_country' ),
	];

	if ( ! $attribute ) {
		return $sender;
	}

	return $sender[ $attribute ];
}

/**
 * Consignment parcel type
 *
 * @return array
 */
function gdex_consignment_parcel_type() {
	return gdex_get_settings( 'gdex_consignment_parcel_type' );
}

/**
 * Consignment notify customer
 *
 * @return bool
 */
function gdex_consignment_notify_customer(): bool {
	return gdex_get_settings( 'gdex_consignment_notify_customer' ) === 'yes';
}

/**
 * Validate gdex user access token
 *
 * @param $token
 *
 * @return bool
 */
function gdex_api_validate_user_access_token( $token ) {
	$respoonse      = ( new Gdex_Api( $token ) )->get_user_token_validity();
	$respoonse_code = wp_remote_retrieve_response_code( $respoonse );

	$isTokenValid = $respoonse_code === 200;

	return $isTokenValid;
}

/**
 * Get user access token
 *
 * @return string
 */
function gdex_get_api_user_access_token() {
	return gdex_get_settings( 'gdex_api_user_access_token' );
}

/**
 * Check if user access token exist
 *
 * @return bool
 */
function gdex_has_api_user_access_token() {
	return gdex_get_api_user_access_token() !== '';
}

/**
 * Get user details from api
 *
 * @param null $token
 *
 * @return array
 */
function gdex_api_get_user_details( $token = null ) {
	$response      = ( new Gdex_Api( $token ) )->get_user_details();
	$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

	return [
		'name'          => $response_body['data']['Name'],
		'email'         => $response_body['data']['Email'],
		'mobile_number' => $response_body['data']['MobileNumber'],
		'address1'      => $response_body['data']['Address1'],
		'address2'      => $response_body['data']['Address2'],
		'postal_code'   => $response_body['data']['PostalCode'],
		'city'          => $response_body['data']['City'],
		'location_id'   => $response_body['data']['LocationId'],
		'location'      => $response_body['data']['Location'],
		'state'         => $response_body['data']['State'],
		'country'       => $response_body['data']['Country'],
	];
}

/**
 * Check ewallet balance
 *
 * @param null $token
 *
 * @return float
 */
function gdex_api_get_check_ewallet_balance( $token = null ) {
	$response      = ( new Gdex_Api( $token ) )->check_ewallet_balance();
	$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

	$balance = $response_body['data'];

	return (float) $balance;
}

/**
 * Get shipping rate
 *
 * @param \WC_Order $order
 * @param null $token
 *
 * @return mixed
 */
function gdex_api_get_shipping_rate( $shipment, $token = null ) {
	$shipment = array_merge( [
		'from_postcode' => gdex_sender( 'postal_code' ),
		'to_postcode'   => '',
		'to_country'    => '',
		'weight'        => '',
		'type'          => gdex_consignment_parcel_type(),
	], $shipment );

	$response         = ( new Gdex_API( $token ) )->get_shipping_rate( [
		'FromPostCode' => $shipment['from_postcode'],
		'ToPostCode'   => $shipment['to_postcode'],
		'ParcelType'   => $shipment['type'],
		'Weight'       => $shipment['weight'],
		'Country'      => $shipment['to_country'],
	] );
	$response_code    = wp_remote_retrieve_response_code( $response );
	$response_message = wp_remote_retrieve_response_message( $response );
	$response_body    = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( is_wp_error( $response ) ) {
		/**
		 * @var \WP_Error $error
		 */
		$error = $response;
		throw new Exception( $error->get_error_message() );
	}

	if ( $response_code !== 200 ) {
		throw new Gdex_Api_Exception( $response_message, $response_code );
	}

	foreach ( $response_body['data'] as $result ) {
		if ( $result['HasError'] ) {
			throw new InvalidArgumentException( $result['Error'] );
		}

		$shipping_rate = $result['Rate'];

		return $shipping_rate;
	}
}

function gdex_api_create_consignment( $shipment_order, $token = null ) {
	$shipment_order      = gdex_shipment_order_get( $shipment_order );
	$shipment_order_meta = get_post_meta( $shipment_order->ID );

	$sender = [
		'Name'       => $shipment_order_meta['sender_name'][0],
		'Mobile'     => $shipment_order_meta['sender_mobile_number'][0],
		'Email'      => $shipment_order_meta['sender_email'][0],
		'Address1'   => $shipment_order_meta['sender_address1'][0],
		'Address2'   => $shipment_order_meta['sender_address2'][0],
		'Postcode'   => $shipment_order_meta['sender_postal_code'][0],
		'LocationId' => $shipment_order_meta['sender_location_id'][0],
		'Location'   => $shipment_order_meta['sender_location'][0],
		'City'       => $shipment_order_meta['sender_city'][0],
		'State'      => $shipment_order_meta['sender_state'][0],
	];

	$pick_up = [];
	if ( $shipment_order_meta['service_type'][0] === Gdex_Shipment_Order::SERVICE_TYPE_PICK_UP ) {
		$pick_up = [
			'Transportation'    => $shipment_order_meta['pick_up_transportation'][0],
			'ParcelReadyTime'   => $shipment_order_meta['pick_up_time'][0],
			'PickupDate'        => $shipment_order_meta['pick_up_date'][0],
			'PickupRemark'      => $shipment_order_meta['pick_up_remark'][0],
			'IsTrolleyRequired' => $shipment_order_meta['pick_up_trolley_required'][0] === 'yes',
		];
	}

	$consignments = [];

	foreach ( gdex_shipment_order_consignments( $shipment_order ) as $consignment ) {
		$consignment_meta = get_post_meta( $consignment->ID );

		$consignments[] = [
			'OrderId'         => $consignment_meta['order_id'][0],
			'ShipmentContent' => $consignment_meta['content'][0],
			'ParcelType'      => $consignment_meta['parcel_type'][0],
			'ShipmentValue'   => $consignment_meta['value'][0],
			'Pieces'          => $consignment_meta['pieces'][0],
			'Weight'          => $consignment_meta['weight'][0],
			'Name'            => $consignment_meta['name'][0],
			'Mobile'          => $consignment_meta['mobile'][0],
			'Email'           => $consignment_meta['email'][0],
			'Address1'        => $consignment_meta['address1'][0],
			'Address2'        => $consignment_meta['address2'][0],
			'Postcode'        => $consignment_meta['postcode'][0],
			'City'            => $consignment_meta['city'][0],
			'State'           => $consignment_meta['state'][0],
			'Country'         => $consignment_meta['country'][0],
		];
	}

	$response         = ( new Gdex_API( $token ) )->create_consignment( $consignments, $pick_up, $sender );
	$response_code    = wp_remote_retrieve_response_code( $response );
	$response_message = wp_remote_retrieve_response_message( $response );
	$response_body    = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( is_wp_error( $response ) ) {
		/**
		 * @var \WP_Error $error
		 */
		$error = $response;
		throw new Exception( $error->get_error_message() );
	}

	if ( $response_code !== 200 ) {
		throw new Gdex_Api_Exception( $response_message, $response_code );
	}

	$invoice = gdex_invoice_get( wp_insert_post( [
		'post_title'     => __( 'Invoice', 'gdex' ),
		'post_status'    => 'publish',
		'post_type'      => 'gdex-invoice',
		'post_parent'    => $shipment_order->ID,
		'comment_status' => 'closed',
		'ping_status'    => 'closed',
	] ) );

	update_post_meta( $invoice->ID, 'invoice_number', $response_body['data']['InvoiceNumber'] );
	update_post_meta( $invoice->ID, 'total', $response_body['data']['GrandTotal'] );

	foreach ( $response_body['data']['Consignments'] as $response ) {
		$order       = wc_get_order( $response['OrderId'] );
		$consignment = gdex_wc_order_latest_consignment( $order );

		update_post_meta( $consignment->ID, 'consignment_number', $response['ConsignmentNumber'] );
		update_post_meta( $consignment->ID, 'rate', $response['Rate'] );

		$consignment_number        = gdex_consignment_number( $consignment );
		$consignment_shipping_rate = get_post_meta( $consignment->ID, 'rate', true );

		$order->add_order_note( sprintf(
			__( 'Consignment %s is created, shipping rate is RM%s', 'gdex' ),
			$consignment_number,
			number_format( $consignment_shipping_rate, 2 )
		) );

		if ( gdex_consignment_notify_customer() ) {
			try {
				gdex_consignment_send_notify_customer( $consignment );
			} catch ( Exception $exception ) {
				gdex_log( __METHOD__ . " - consignment #{$consignment_number}: {$exception->getMessage()}" );
			}
		}
	}
}

function gdex_api_get_last_shipment_status( $consignment_numbers, $token = null ) {
	if ( ! is_array( $consignment_numbers ) ) {
		$consignment_numbers = [ $consignment_numbers ];
	}

	$response         = ( new Gdex_API( $token ) )->get_last_shipment_status( $consignment_numbers );
	$response_code    = wp_remote_retrieve_response_code( $response );
	$response_message = wp_remote_retrieve_response_message( $response );
	$response_body    = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( is_wp_error( $response ) ) {
		/**
		 * @var \WP_Error $error
		 */
		$error = $response;
		throw new Exception( $error->get_error_message() );
	}

	if ( $response_code !== 200 ) {
		throw new Gdex_Api_Exception( $response_message, $response_code );
	}

	$results = [];
	foreach ( $response_body['data'] as $status ) {
		$results[ $status['ConsignmentNote'] ] = $status['ConsignmentNoteStatus'];
	}

	return $results;
}

function gdex_api_get_consignment_image( $consignment_number, $token = null ) {
	$response         = ( new Gdex_API( $token ) )->get_consignments_image( $consignment_number );
	$response_code    = wp_remote_retrieve_response_code( $response );
	$response_message = wp_remote_retrieve_response_message( $response );
	$response_body    = wp_remote_retrieve_body( $response );

	if ( is_wp_error( $response ) ) {
		/**
		 * @var \WP_Error $error
		 */
		$error = $response;
		throw new Exception( $error->get_error_message() );
	}

	if ( $response_code !== 200 ) {
		throw new Gdex_Api_Exception( $response_message, $response_code );
	}

	return $response_body;
}

function gdex_api_get_consignment_images( $consignment_numbers, $token = null ) {
	$response         = ( new Gdex_API( $token ) )->get_consignments_images_zip( $consignment_numbers );
	$response_code    = wp_remote_retrieve_response_code( $response );
	$response_message = wp_remote_retrieve_response_message( $response );
	$response_body    = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( is_wp_error( $response ) ) {
		/**
		 * @var \WP_Error $error
		 */
		$error = $response;
		throw new Exception( $error->get_error_message() );
	}

	if ( $response_code !== 200 ) {
		throw new Gdex_Api_Exception( $response_message, $response_code );
	}

	return base64_decode( $response_body['data'] );
}

function gdex_api_cancel_consignment( $consignment_id, $token = null ) {
	$consignment        = gdex_consignment_get( $consignment_id );
	$consignment_number = gdex_consignment_number( $consignment );

	$response      = ( new Gdex_Api( $token ) )->cancel_consignment( $consignment_number );
	$response_code = wp_remote_retrieve_response_code( $response );
	$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( is_wp_error( $response ) ) {
		/**
		 * @var \WP_Error $error
		 */
		$error = $response;
		throw new Exception( $error->get_error_message() );
	}

	if ( $response_code !== 200 ) {
		throw new Gdex_Api_Exception( $response_body['message'], $response_code );
	}

	return true;
}

/**
 * Gdex api get pickup date listing
 *
 * @param $postcode
 * @param null $token
 *
 * @return array|\WP_Error
 * @throws \Exception
 */
function gdex_api_get_pickup_date_listing( $postcode, $token = null ) {
	$response      = ( new Gdex_Api( $token ) )->get_pickup_date_listing( $postcode );
	$response_code = wp_remote_retrieve_response_code( $response );
	$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( is_wp_error( $response ) ) {
		return [];
	}

	if ( $response_code !== 200 ) {
		throw new Gdex_Api_Exception( __( 'Invalid postcode.', 'gdex' ), $response_code );
	}

	$dates = [];
	foreach ( $response_body['data'] as $date ) {
		$dates[] = new DateTime( $date, new DateTimeZone( GDEX_TIMEZONE ) );
	}

	return $dates;
}

function gdex_api_get_upcoming_pickup_details( $token = null ) {
	$response      = ( new Gdex_Api( $token ) )->get_upcoming_pickup_details();
	$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

	$timezone = new DateTimeZone( GDEX_TIMEZONE );

	$pickups = [];
	foreach ( (array) $response_body['data'] as $pickup ) {
		$ready_date = new DateTime( $pickup['PickupTime'], $timezone );
		$ready_time = new DateTime( $pickup['ParcelReadyTime'], $timezone );

		$ready_at                       = $ready_date->setTime( $ready_time->format( 'H' ), $ready_time->format( 'i' ),
			$ready_time->format( 's' ) );
		$ready_at_timestamp_with_offset = $ready_at->getTimestamp() + $ready_at->getOffset();
		$ready_at_date_i18n             = date( wc_date_format() . ' ' . wc_time_format(), $ready_at_timestamp_with_offset );

		$is_today = $ready_at->format( 'Y-m-d' ) === ( new DateTime( 'now', $timezone ) )->format( 'Y-m-d' );

		$pickups[] = [
			'id'                  => $pickup['PickupNo'],
			'address'             => $pickup['PickupAddress'],
			'ready_at'            => $ready_at,
			'ready_at_date_i18n'  => $ready_at_date_i18n,
			'remark'              => $pickup['PickupRemark'],
			'transportation'      => $pickup['Transportation'],
			'is_trolley_required' => $pickup['IsTrolleyRequired'],
			'status'              => $pickup['Status'],
			'can_cancel'          => ! $is_today,
		];
	}

	return $pickups;
}

function gdex_api_get_pickup_reference( $consignment_number, $token = null ) {
	$response      = ( new Gdex_Api( $token ) )->get_pickup_reference( $consignment_number );
	$response_code = wp_remote_retrieve_response_code( $response );
	$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( is_wp_error( $response ) ) {
		/**
		 * @var \WP_Error $error
		 */
		$error = $response;
		throw new Exception( $error->get_error_message() );
	}

	if ( $response_code !== 200 ) {
		throw new Gdex_Api_Exception( __( $response_body['message'], 'gdex' ), $response_code );
	}

	return $response_body['data'];
}

function gdex_api_cancel_pickup( $pickup_number, $token = null ) {
	$response      = ( new Gdex_Api( $token ) )->cancel_pickup( $pickup_number );
	$response_code = wp_remote_retrieve_response_code( $response );
	$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( is_wp_error( $response ) ) {
		/**
		 * @var \WP_Error $error
		 */
		$error = $response;
		throw new Exception( $error->get_error_message() );
	}

	if ( $response_code !== 200 ) {
		throw new Gdex_Api_Exception( $response_body['message'], $response_code );
	}

	return true;
}

function gdex_api_get_postcode_locations( $postcode, $token = null ) {
	$response      = ( new Gdex_Api( $token ) )->get_postcode_locations( $postcode );
	$response_code = wp_remote_retrieve_response_code( $response );
	$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( is_wp_error( $response ) ) {
		/**
		 * @var \WP_Error $error
		 */
		$error = $response;
		throw new Exception( $error->get_error_message() );
	}

	if ( $response_code !== 200 ) {
		throw new Gdex_Api_Exception( $response_body['message'], $response_code );
	}

	$locations = [];
	foreach ( $response_body['data']['DistrictList'] as $district ) {
		foreach ( $district['LocationList'] as $location ) {
			$locations[] = [
				'id'    => $location['LocationId'],
				'name'  => $location['Location'],
				'city'  => $district['District'],
				'state' => $response_body['data']['State'],
			];
		}
	}

	return $locations;
}

function gdex_get_country_alpha3( $name ) {
	if ( ! empty( wc()->countries->get_countries()[ $name ] ) ) {
		$name = wc()->countries->get_countries()[ $name ];
	}

	return ( new Gdex_Country )->name( $name )[ Gdex_Country::KEY_ALPHA3 ];
}

/**
 * @throws Gdex_Api_Exception
 */
function gdex_get_place( $postal_code, $reset = false ) {
	if ( empty( $postal_code ) ) {
		return [];
	}

	$place = get_transient( "gdex_place_{$postal_code}" );
	if ( empty( $place ) || $reset ) {
		$place = gdex_api_get_postcode_locations( $postal_code );
		set_transient( "gdex_place_{$postal_code}", $place, WEEK_IN_SECONDS );
	}

	return $place;
}

/**
 * Order shipping estimate
 *
 * @param $order
 *
 * @return mixed
 *
 */
function gdex_wc_order_shipping_estimate( $order ) {
	$order = wc_get_order( $order );
	if ( ! $order ) {
		throw new InvalidArgumentException( __( 'Invalid order object', 'gdex' ) );
	}

	$shipping_estimate = $order->get_meta( 'gdex_shipping_estimate' );

	return (float) $shipping_estimate;
}

/**
 * Order shipping estimate quoted date time
 *
 * @param $order
 *
 * @return \DateTime
 * @throws \Exception
 */
function gdex_wc_order_shipping_estimate_quoted_at( $order ) {
	$order = wc_get_order( $order );
	if ( ! $order ) {
		throw new InvalidArgumentException( __( 'Invalid order object', 'gdex' ) );
	}

	$shipping_estimate_quoted_at = $order->get_meta( 'gdex_shipping_estimate_quoted_at' );
	if ( ! $shipping_estimate_quoted_at ) {
		return;
	}

	return ( new DateTime( '@' . $shipping_estimate_quoted_at ) )
		->setTimezone( gdex_timezone() );
}

/**
 * Order shipping estimate quoted timestamp with offset
 *
 * @param $order
 *
 * @return int
 * @throws \Exception
 */
function gdex_wc_order_shipping_estimate_quoted_at_timestamp_with_offset( $order ) {
	$quoted_at = gdex_wc_order_shipping_estimate_quoted_at( $order );
	if ( ! $quoted_at ) {
		return;
	}

	return $quoted_at->getTimestamp() + $quoted_at->getOffset();
}

/**
 * Order shipping estimate quoted date i18n
 *
 * @param $order
 *
 * @return string
 * @throws \Exception
 */
function gdex_wc_order_shipping_estimate_quoted_at_date_i18n( $order ) {
	$quoted_attimestamp_with_offset = gdex_wc_order_shipping_estimate_quoted_at_timestamp_with_offset( $order );
	if ( ! $quoted_attimestamp_with_offset ) {
		return;
	}

	return date_i18n( wc_date_format() . ' ' . wc_time_format(), $quoted_attimestamp_with_offset );
}

/**
 * Quote shipping estimate
 *
 * @param $order
 *
 * @return bool|\WC_Order|\WC_Refund
 */
function gdex_wc_order_quote_shipping_estimate( $order, $shipment = [], $reset = false ) {
	if ( is_bool( $shipment ) ) {
		$reset    = $shipment;
		$shipment = [];
	}

	if ( $reset ) {
		$order = gdex_wc_order_reset_shipping_estimate( $order );
	}

	$order = wc_get_order( $order );
	if ( ! $order ) {
		throw new InvalidArgumentException( __( 'Invalid order object', 'gdex' ) );
	}

	$shipment = array_merge( [
		'from_postcode' => gdex_sender( 'postal_code' ),
		'to_postcode'   => $order->get_shipping_postcode(),
		'to_country'    => $order->get_shipping_country() ? gdex_get_country_alpha3( $order->get_shipping_country() ) : '',
		'weight'        => gdex_wc_order_total_weight( $order ),
		'type'          => gdex_consignment_parcel_type(),
	], $shipment );

	$shipping_estimate = gdex_api_get_shipping_rate( $shipment );

	$order->update_meta_data( 'gdex_shipping_estimate', $shipping_estimate );
	$order->update_meta_data( 'gdex_shipping_estimate_quoted_at', time() );
	$order->save_meta_data();

	return $order;
}

function gdex_shipment_order_get( $shipment_order_id ) {
	$shipment_order = get_post( $shipment_order_id );
	if ( ! $shipment_order || $shipment_order->post_type !== 'gdex-shipment-order' ) {
		throw new InvalidArgumentException( __( 'Invalid shipment order object', 'gdex' ) );
	}

	return $shipment_order;
}

/**
 * Get shipment order invoice
 *
 * @param $shipment_order_id
 *
 * @return \WP_Post[]
 */
function gdex_shipment_order_consignments( $shipment_order_id ) {
	$shipment_order = gdex_shipment_order_get( $shipment_order_id );

	return get_children( [
		'post_parent' => $shipment_order->ID,
		'post_type'   => 'gdex-consignment',
	] );
}

/**
 * Get shipment order invoice
 *
 * @param $shipment_order_id
 *
 * @return \WP_Post[]
 */
function gdex_shipment_order_invoice( $shipment_order_id ) {
	$shipment_order = gdex_shipment_order_get( $shipment_order_id );

	return get_children( [
		'post_parent' => $shipment_order->ID,
		'post_type'   => 'gdex-invoice',
	] );
}

function gdex_invoice_get( $invoice_id ) {
	$invoice = get_post( $invoice_id );
	if ( ! $invoice || $invoice->post_type !== 'gdex-invoice' ) {
		throw new InvalidArgumentException( __( 'Invalid invoice object', 'gdex' ) );
	}

	return $invoice;
}

function gdex_consignment_get( $consignment_id ) {
	$consignment = get_post( $consignment_id );
	if ( ! $consignment || $consignment->post_type !== 'gdex-consignment' ) {
		throw new InvalidArgumentException( __( 'Invalid consignment object', 'gdex' ) );
	}

	return $consignment;
}

/**
 * @param array $consignments
 *
 * @return array
 * @throws \Exception
 */
function gdex_consignment_statuses( $consignments ) {
	global $gdex;

	$asked_statuses             = [];
	$non_cached_consignments    = [];
	$to_be_queried_consignments = [];

	if ( empty( $gdex['consignment_statuses'] ) ) {
		$gdex['consignment_statuses'] = [];
	}

	if ( ! is_array( $consignments ) ) {
		$consignments = [ $consignments ];
	}

	foreach ( $consignments as $consignment ) {
		$consignment = gdex_consignment_get( $consignment );

		if ( empty( $gdex['consignment_statuses'][ $consignment->ID ] ) ) {
			$non_cached_consignments[] = $consignment;
		} else {
			$asked_statuses[ $consignment->ID ] = $gdex['consignment_statuses'][ $consignment->ID ];
		}
	}

	foreach ( $non_cached_consignments as $index => $consignment ) {
		$consignment_status = get_post_meta( $consignment->ID, 'consignment_status', true );

		if ( $consignment_status ) {
			$gdex['consignment_statuses'][ $consignment->ID ] = $asked_statuses[ $consignment->ID ] = $consignment_status;
		} else {
			$to_be_queried_consignments[] = $consignment;
		}
	}

	if ( $to_be_queried_consignments ) {
		try {
			$queried_consignment_statuses = gdex_api_get_last_shipment_status( array_map( 'gdex_consignment_number', $to_be_queried_consignments ) );
		} catch ( Gdex_Api_Exception $exception ) {
			gdex_add_admin_notice(
				"Unable to query shipment status, please try again or contact support if problem persists: <strong>Gdex API request failed ({$exception->getMessage()})</strong>",
				'error'
			);
		}

		foreach ( $to_be_queried_consignments as $consignment ) {
			$consignment_status = $queried_consignment_statuses[ gdex_consignment_number( $consignment ) ] ?? null;
			if ( in_array( $consignment_status, [
				Gdex_Consignment::STATUS_DELIVERED,
				Gdex_Consignment::STATUS_RETURNED,
				Gdex_Consignment::STATUS_CLAIMED,
				Gdex_Consignment::STATUS_CANCELLED,
			] ) ) {
				update_post_meta( $consignment->ID, 'consignment_status', $consignment_status );
			}

			$gdex['consignment_statuses'][ $consignment->ID ] = $asked_statuses[ $consignment->ID ] = $consignment_status;
		}
	}

	return $asked_statuses;
}

function gdex_consignment_status( $consignment_id ) {
	$consignment = gdex_consignment_get( $consignment_id );

	return gdex_consignment_statuses( $consignment )[ $consignment->ID ];
}

function gdex_consignment_order( $consignment_id ) {
	$consignment = gdex_consignment_get( $consignment_id );

	$order_id = get_post_meta( $consignment->ID, 'order_id', true );
	$order    = wc_get_order( $order_id );

	return $order;
}

function gdex_consignment_number( $consignment_id ) {
	$consignment        = gdex_consignment_get( $consignment_id );
	$consignment_number = get_post_meta( $consignment->ID, 'consignment_number', true );

	return $consignment_number;
}

function gdex_consignment_shipment_order( $consignment_id ) {
	$consignment = gdex_consignment_get( $consignment_id );

	$shipment_order = gdex_shipment_order_get( wp_get_post_parent_id( $consignment ) );

	return $shipment_order;
}

/**
 * Gdex consignment pick up
 *
 * @param $consignment_id
 *
 * @return array|void
 */
function gdex_consignment_pick_up( $consignment_id ) {
	$consignment = gdex_consignment_get( $consignment_id );

	$shipment_order = gdex_consignment_shipment_order( $consignment );

	$service_type = get_post_meta( $shipment_order->ID, 'service_type', true );
	if ( $service_type !== Gdex_Shipment_Order::SERVICE_TYPE_PICK_UP ) {
		return;
	}

	try {
		$pick_up_reference = gdex_consignment_pick_up_reference( $consignment );
		if ( ! $pick_up_reference ) {
			update_post_meta( $shipment_order->ID, 'service_type', Gdex_Shipment_Order::SERVICE_TYPE_LODGE_IN );

			return;
		}

		return [
			'no'               => $pick_up_reference['PickupNo'],
			'date'             => get_post_meta( $shipment_order->ID, 'pick_up_date', true ),
			'time'             => get_post_meta( $shipment_order->ID, 'pick_up_time', true ),
			'transportation'   => get_post_meta( $shipment_order->ID, 'pick_up_transportation', true ),
			'trolley_required' => get_post_meta( $shipment_order->ID, 'pick_up_trolley_required', true ),
			'remark'           => get_post_meta( $shipment_order->ID, 'pick_up_remark', true ),
			'status'           => $pick_up_reference['Status'],
		];
	} catch ( Exception $exception ) {
	}
}

function gdex_consignment_pick_up_reference( $consignment_id ) {
	$consignment        = gdex_consignment_get( $consignment_id );
	$consignment_number = gdex_consignment_number( $consignment );

	$pickup_reference = gdex_api_get_pickup_reference( $consignment_number );

	return $pickup_reference;
}


function gdex_consignment_quote_shipping_rate( $order, $shipment = [] ) {
	$order = wc_get_order( $order );
	if ( ! $order ) {
		throw new InvalidArgumentException( __( 'Invalid order object', 'gdex' ) );
	}

	$shipment = array_merge( [
		'from_postcode' => gdex_sender( 'postal_code' ),
		'to_postcode'   => $order->get_shipping_postcode(),
		'to_country'    => $order->get_shipping_country() ? gdex_get_country_alpha3( $order->get_shipping_country() ) : '',
		'weight'        => gdex_wc_order_total_weight( $order ),
		'type'          => gdex_consignment_parcel_type(),
	], $shipment );

	$shipping_rate = gdex_api_get_shipping_rate( $shipment );

	return $shipping_rate;
}

function gdex_consignment_print_note( $consignment_id, $download = false ) {
	$consignment        = gdex_consignment_get( $consignment_id );
	$consignment_number = gdex_consignment_number( $consignment );

	$note = gdex_api_get_consignment_image( $consignment_number );

	header( "Content-type: application/pdf" );
	header( "Expires: -1" );

	if ( $download ) {
		header( "Content-Disposition:attachment; filename={$consignment_number}.pdf" );
	}

	echo $note;

	wp_die();
}

function gdex_consignment_print_notes( $consignments ) {
	$consignment_numbers = array_map( function ( $consignment ) {
		return gdex_consignment_number( $consignment );
	}, $consignments );

	$zip = gdex_api_get_consignment_images( $consignment_numbers );

	header( 'Content-type: application/zip' );
	header( 'Expires: -1' );
	header( 'Content-Disposition:attachment; filename=consignments.zip' );

	echo $zip;

	wp_die();
}

function gdex_consignment_cancel( $consignment_id ) {
	$consignment = gdex_consignment_get( $consignment_id );

	gdex_api_cancel_consignment( $consignment );

	return true;
}

function gdex_consignment_send_notify_customer( $consignment_id ) {
	$consignment = gdex_consignment_get( $consignment_id );

	$email = new Gdex_Shipping_Email_Consignment_Ready();
	$email->trigger( $consignment );
}

/**
 * Reset order shipping estimate
 *
 * @param $order
 *
 * @return bool|\WC_Order|\WC_Refund
 */
function gdex_wc_order_reset_shipping_estimate( $order ) {
	$order = wc_get_order( $order );
	if ( ! $order ) {
		throw new InvalidArgumentException( __( 'Invalid order object', 'gdex' ) );
	}

	$order->delete_meta_data( 'gdex_shipping_estimate' );
	$order->delete_meta_data( 'gdex_shipping_estimate_quoted_at' );
	$order->save_meta_data();

	return $order;
}

/**
 * Get order total weight
 *
 * @param \WC_Order $order
 *
 * @return float|int
 */
function gdex_wc_order_total_weight( WC_Order $order ) {
	$weights = array_map( function ( WC_Order_Item $item ) {
		$product = $item->get_product();
		if ( ! $product ) {
			return 0;
		}

		$product_weight = $product->get_weight();
		if ( ! $product_weight ) {
			return 0;
		}

		return $product_weight * $item->get_quantity();
	}, $order->get_items() );

	return array_sum( $weights );
}

function gdex_wc_order_categories( WC_Order $order ) {
	$categories = [];

	foreach ( $order->get_items() as $item ) {
		$product = $item->get_product();
		if ( $product instanceof WC_Product_Variation ) {
			$product = wc_get_product( $product->get_parent_id() );
		}

		$product_categories = get_the_terms( $product->get_id(), 'product_cat' );

		foreach ( $product_categories as $product_category ) {
			$categories[ $product_category->term_id ] = $product_category;
		}
	}

	return $categories;
}

function gdex_wc_order_category_list( WC_Order $order ) {
	return implode( ', ', array_map( function ( WP_Term $term ) {
		return $term->name;
	}, gdex_wc_order_categories( $order ) ) );
}

function gdex_wc_order_consignments( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		throw new InvalidArgumentException( __( 'Invalid order object', 'gdex' ) );
	}

	return get_posts( [
		'post_type'  => 'gdex-consignment',
		'meta_query' => [
			[
				'key'   => 'order_id',
				'value' => $order->get_id(),
			],
		],
	] );
}

function gdex_wc_order_latest_consignment( $order_id ) {
	foreach ( gdex_wc_order_consignments( $order_id ) as $consignment ) {
		return $consignment;
	}
}

/**
 * Add admin notice
 *
 * @param $notice
 * @param $type
 *
 * @throws \Exception
 */
function gdex_add_admin_notice( $notice, $type ) {
	if ( ! is_array( $notice ) ) {
		$notice = [
			'message' => $notice,
		];
	}

	if ( empty( $notice['message'] ) ) {
		throw new \RuntimeException( 'notice message is missing.' );
	}

	$notice['message'] = wp_kses_post( $notice['message'] );

	$notice = array_merge( [
		'title'   => '',
		'message' => '',
		'list'    => [],
	], $notice );

	$notice['type'] = $type;

	$notices   = gdex_get_admin_notices();
	$notices[] = $notice;

	gdex_set_admin_notices( $notices );
}

/**
 * Get admin noitces
 *
 * @return false|mixed|void
 */
function gdex_get_admin_notices() {
	return get_option( 'gdex_admin_notices', [] );
}

/**
 * @param $notices
 *
 * @return bool
 */
function gdex_set_admin_notices( $notices ) {
	return update_option( 'gdex_admin_notices', $notices );
}

/**
 * Clean admin notices
 */
function gdex_clear_admin_notices() {
	delete_option( 'gdex_admin_notices' );
}

/**
 * Log message
 *
 * @param $message
 * @param string $level
 */
function gdex_log( $message, $level = WC_Log_Levels::NOTICE ) {
	( new WC_Logger )->add( 'gdex', $message, $level );
}

/**
 * Log message
 *
 * @param $message
 * @param string $level
 */
function gdex_api_log( $message, $level = WC_Log_Levels::NOTICE ) {
	( new WC_Logger )->add( 'gdex-api', $message, $level );
}

/**
 * Include admin partial
 *
 * @param $file
 */
function gdex_admin_partial( $file ) {
	include_once GDEX_PLUGIN_DIR_PATH . "admin/partials/{$file}.php";
}

function gdex_timezone() {
	$timezone = get_option( 'timezone_string' );
	if ( ! $timezone ) {
		$gmt_offset = get_option( 'gmt_offset' );
		if ( $gmt_offset >= 0 ) {
			$timezone = "+{$gmt_offset}";
		}
	}

	return new DateTimeZone( $timezone );
}