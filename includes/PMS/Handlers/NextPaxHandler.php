<?php
/**
 * NextPax Handler
 *
 * @package RentalSyncEngine\PMS\Handlers
 */

namespace RentalSyncEngine\PMS\Handlers;

use RentalSyncEngine\PMS\AbstractPMSHandler;

/**
 * NextPax Handler Class
 */
class NextPaxHandler extends AbstractPMSHandler {
    public function __construct($credentials) {
        $this->api_base_url = 'https://api.nextpax.app/v1/';
        parent::__construct($credentials);
    }

    protected function validate_credentials() {
        if (empty($this->credentials['api_key'])) {
            throw new \Exception('NextPax requires API key');
        }
    }

    protected function get_auth_headers() {
        return array(
            'X-API-KEY' => $this->credentials['api_key'],
            'Content-Type' => 'application/json',
        );
    }

    public function get_listings() {
        $response = $this->make_request('properties');
        return array_map(array($this, 'normalize_listing'), $response['properties'] ?? array());
    }

    public function get_listing($listing_id) {
        $response = $this->make_request("properties/{$listing_id}");
        return $this->normalize_listing($response ?? array());
    }

    public function get_availability($listing_id, $start_date = null, $end_date = null) {
        $params = array('property_id' => $listing_id);
        if ($start_date) $params['from'] = $start_date;
        if ($end_date) $params['to'] = $end_date;
        return $this->make_request('availability', 'GET', $params);
    }

    public function get_bookings($params = array()) {
        $response = $this->make_request('bookings', 'GET', $params);
        return array_map(array($this, 'normalize_booking'), $response['bookings'] ?? array());
    }

    public function get_booking($booking_id) {
        $response = $this->make_request("bookings/{$booking_id}");
        return $this->normalize_booking($response ?? array());
    }

    public function create_booking($booking_data) {
        $response = $this->make_request('bookings', 'POST', $this->prepare_booking_data($booking_data));
        return $this->normalize_booking($response ?? array());
    }

    public function update_booking($booking_id, $booking_data) {
        $response = $this->make_request("bookings/{$booking_id}", 'PUT', $this->prepare_booking_data($booking_data));
        return $this->normalize_booking($response ?? array());
    }

    public function cancel_booking($booking_id) {
        $response = $this->make_request("bookings/{$booking_id}/cancel", 'POST');
        return !empty($response['success']);
    }

    public function normalize_listing($raw_data) {
        return array(
            'id' => $raw_data['id'] ?? '',
            'name' => $raw_data['title'] ?? '',
            'description' => $raw_data['description'] ?? '',
            'address' => array(
                'street' => $raw_data['street'] ?? '',
                'city' => $raw_data['city'] ?? '',
                'state' => $raw_data['region'] ?? '',
                'zip' => $raw_data['postal_code'] ?? '',
                'country' => $raw_data['country'] ?? '',
            ),
            'bedrooms' => $raw_data['bedrooms'] ?? 0,
            'bathrooms' => $raw_data['bathrooms'] ?? 0,
            'max_guests' => $raw_data['max_occupancy'] ?? 0,
            'amenities' => $raw_data['amenities'] ?? array(),
            'images' => $raw_data['images'] ?? array(),
            'pricing' => $raw_data['rates'] ?? array(),
        );
    }

    public function normalize_booking($raw_data) {
        return array(
            'id' => $raw_data['id'] ?? '',
            'listing_id' => $raw_data['property_id'] ?? '',
            'status' => $raw_data['status'] ?? 'confirmed',
            'check_in' => $raw_data['checkin_date'] ?? '',
            'check_out' => $raw_data['checkout_date'] ?? '',
            'guest_name' => $raw_data['guest_name'] ?? '',
            'guest_email' => $raw_data['guest_email'] ?? '',
            'total_amount' => $raw_data['total'] ?? 0,
            'currency' => $raw_data['currency'] ?? 'USD',
            'number_of_guests' => $raw_data['guests'] ?? 1,
        );
    }

    private function prepare_booking_data($booking_data) {
        return array(
            'property_id' => $booking_data['listing_id'],
            'checkin_date' => $booking_data['check_in'],
            'checkout_date' => $booking_data['check_out'],
            'guest_name' => $booking_data['guest_name'],
            'guest_email' => $booking_data['guest_email'],
            'total' => $booking_data['total_amount'],
            'currency' => $booking_data['currency'] ?? 'USD',
            'guests' => $booking_data['number_of_guests'] ?? 1,
        );
    }

    public function verify_webhook_signature($payload, $signature) {
        $secret = $this->credentials['webhook_secret'] ?? '';
        $computed = hash_hmac('sha256', $payload, $secret);
        return hash_equals($computed, $signature);
    }
}
