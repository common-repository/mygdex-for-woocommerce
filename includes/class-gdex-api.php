<?php

class Gdex_Api {

	const PARCEL_TYPE_PARCEL = 'parcel';
	const PARCEL_TYPE_DOCUMENT = 'document';

	protected $baseUrl = '';

	/**
	 * Headers
	 *
	 * @var array
	 */
	protected $headers = [
		'Content-Type'     => 'application/json',
		'User-Token'       => '',
		'Subscription-Key' => '',
	];

	/**
	 * Gdex_Api constructor.
	 *
	 * @param null $token
	 */
	public function __construct( $token = null ) {
		if ( empty( $token ) ) {
			$token = gdex_get_api_user_access_token();

			if ( empty( $token ) ) {
				throw new InvalidArgumentException( __( 'Token is required.', 'gdex' ) );
			}
		}

		$this->baseUrl = GDEX_TESTING ? GDEX_TESTING_API_URL : GDEX_API_URL;

		$this->headers['User-Token']       = $token;
		$this->headers['Subscription-Key'] = GDEX_TESTING ? GDEX_TESTING_API_SUBSCRIPTION_KEY : GDEX_API_SUBSCRIPTION_KEY;
	}

	/**
	 * Validate user access token
	 *
	 * @param $token
	 *
	 * @return array|\WP_Error
	 */
	public function get_user_token_validity() {
		return wp_remote_get( $this->baseUrl . 'GetUserTokenValidity', [ 'headers' => $this->headers ] );
	}

	/**
	 * Get user details
	 *
	 * @return array|\WP_Error
	 */
	public function get_user_details() {
		return wp_remote_get( $this->baseUrl . 'GetUserDetails', [ 'headers' => $this->headers ] );
	}

	/**
	 * Check ewallet balance
	 *
	 * @return array|\WP_Error
	 */
	public function check_ewallet_balance() {
		return wp_remote_get( $this->baseUrl . 'checkEWalletBalance', [ 'headers' => $this->headers ] );
	}

	/**
	 * Get shipping rates
	 *
	 * @param $shipments
	 *
	 * @return array|\WP_Error
	 */
	public function get_shipping_rates( $shipments ) {
		$body = [];

		foreach ( $shipments as $shipment ) {
			$body[] = array_merge( [
				'ReferenceNumber' => time(),
				'FromPostCode'    => '',
				'ToPostCode'      => '',
				'ParcelType'      => '',
				'Weight'          => '',
				'Country'         => '',
			], $shipment );
		}

		return wp_remote_post( $this->baseUrl . 'GetShippingRate', [
			'headers' => $this->headers,
			'body'    => json_encode( $body ),
		] );
	}

	/**
	 * Get shipping rate
	 *
	 * @param $shipment
	 *
	 * @return array|\WP_Error
	 */
	public function get_shipping_rate( $shipment ) {
		return $this->get_shipping_rates( [ $shipment ] );
	}

	/**
	 * Create consignment
	 *
	 * @param $consignments
	 * @param array $pick_up
	 * @param array $sender
	 *
	 * @return array|\WP_Error
	 */
	public function create_consignment( $consignments, $pick_up = [], $sender = [] ) {
		$body = array_merge( [
			'Name'     => '',
			'Mobile'   => '',
			'Email'    => '',
			'Address1' => '',
			'Address2' => '',
			'Address3' => '',
			'Postcode' => '',
			'City'     => '',
			'State'    => '',
		], $sender );

		if ( $pick_up ) {
			$body['Pickup'] = array_merge( [
				'Transportation'    => '',
				'ParcelReadyTime'   => '',
				'PickupDate'        => '',
				'PickupRemark'      => '',
				'IsTrolleyRequired' => false,
			], $pick_up );
		}

		foreach ( $consignments as $consignment ) {
			$body['Consignments'][] = array_merge( [
				'OrderId'         => '',
				'ShipmentContent' => '',
				'ParcelType'      => '',
				'ShipmentValue'   => '',
				'Pieces'          => '',
				'Weight'          => '',
				'Name'            => '',
				'Mobile'          => '',
				'Email'           => '',
				'Address1'        => '',
				'Address2'        => '',
				'Address3'        => '',
				'Postcode'        => '',
				'City'            => '',
				'State'           => '',
				'Country'         => '',
			], $consignment );
		}

		return wp_remote_post( $this->baseUrl . 'CreateConsignment', [
			'headers' => $this->headers,
			'body'    => json_encode( $body ),
		] );
	}

	public function get_last_shipment_status( $shipments ) {
		if ( ! is_array( $shipments ) ) {
			$shipments = [ $shipments ];
		}

		return wp_remote_post( $this->baseUrl . 'GetLastShipmentStatus', [
			'headers' => $this->headers,
			'body'    => json_encode( $shipments ),
		] );
	}

	public function get_consignments_image( $consignment_number ) {
		return wp_remote_get( $this->baseUrl . 'GetConsignmentsImage', [
			'headers' => $this->headers,
			'body'    => [ 'ConsignmentNumber' => $consignment_number ],
		] );
	}

	public function get_consignments_images_zip( $consignment_numbers ) {
		return wp_remote_post( $this->baseUrl . 'GetConsignmentsImagesZip', [
			'headers' => $this->headers,
			'body'    => json_encode( $consignment_numbers ),
		] );
	}

	public function cancel_consignment( $consignment_number ) {
		$cancel_consignment_url = add_query_arg( [
			'ConsignmentNumber' => $consignment_number,
		], $this->baseUrl . 'CancelConsignment' );

		return wp_remote_request( $cancel_consignment_url, [
			'method'  => 'PUT',
			'headers' => $this->headers,
			'body'    => $consignment_number, //@todo cannot be empty otherwise will have 411 length required error
		] );
	}

	public function get_pickup_date_listing( $postcode ) {
		return wp_remote_get( $this->baseUrl . 'GetPickupDateListing', [
			'headers' => $this->headers,
			'body'    => [ 'postcode' => $postcode ],
		] );
	}

	/**
	 * Get upcoming pickup details
	 *
	 * @return array|\WP_Error
	 */
	public function get_upcoming_pickup_details() {
		return wp_remote_get( $this->baseUrl . 'GetUpcomingPickupDetails', [ 'headers' => $this->headers ] );
	}

	/**
	 * Get pickup reference
	 *
	 * @param $consignment_no
	 *
	 * @return array|\WP_Error
	 */
	public function get_pickup_reference( $consignment_no ) {
		return wp_remote_get( $this->baseUrl . 'GetPickupReference', [
			'headers' => $this->headers,
			'body'    => [ 'consignmentNo' => $consignment_no, ],
		] );
	}

	/**
	 * Cancel pickup
	 *
	 * @param $pickup_number
	 *
	 * @return array|\WP_Error
	 */
	public function cancel_pickup( $pickup_number ) {
		$cancel_pickup_url = add_query_arg( [
			'PickupNumber' => $pickup_number,
		], $this->baseUrl . 'CancelPickup' );

		return wp_remote_request( $cancel_pickup_url, [
			'method'  => 'PUT',
			'headers' => $this->headers,
			'body'    => $pickup_number, //@todo cannot be empty otherwise will have 411 length required error
		] );
	}

	/**
	 * Get postcode locations
	 *
	 * @param $postcode
	 *
	 * @return array|\WP_Error
	 */
	public function get_postcode_locations( $postcode ) {
		return wp_remote_get( $this->baseUrl . 'GetPostcodeLocations', [
			'headers' => $this->headers,
			'body'    => [ 'Postcode' => $postcode, ],
		] );
	}
}