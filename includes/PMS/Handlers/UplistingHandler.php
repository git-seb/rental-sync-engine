<?php
/**
 * Uplisting Handler
 *
 * @package RentalSyncEngine\PMS\Handlers
 */

namespace RentalSyncEngine\PMS\Handlers;

use RentalSyncEngine\PMS\AbstractPMSHandler;

/**
 * Uplisting Handler Class
 */
class UplistingHandler extends AbstractPMSHandler {
    public function __construct($credentials) {
        $this->api_base_url = 'https://api.uplisting.io/v2/';
        parent::__construct($credentials);
    }

    protected function validate_credentials() {
        if (empty($this->credentials['api_token'])) {
            throw new \Exception('Uplisting requires API token');
        }
    }

    protected function get_auth_headers() {
        return array(
            'Authorization' => 'Bearer ' . $this->credentials['api_token'],
            'Content-Type' => 'application/json',
        );
    }

    public function get_listings() {
        $response = $this->make_request('listings');
        return array_map(array($this, 'normalize_listing'), $response['data'] ?? array());
    }

    public function get_listing($listing_id) {
        $response = $this->make_request("listings/{$listing_id}");
        return $this->normalize_listing($response['data'] ?? array());
    }

    public function get_availability($listing_id, $start_date = null, $end_date = null) {
        $params = array();
        if ($start_date) $params['start_date'] = $start_date;
        if ($end_date) $params['end_date'] = $end_date;
        return $this->make_request("listings/{$listing_id}/availability", 'GET', $params);
    }

    public function get_bookings($params = array()) {
        $response = $this->make_request('bookings', 'GET', $params);
        return array_map(array($this, 'normalize_booking'), $response['data'] ?? array());
    }

    public function get_booking($booking_id) {
        $response = $this->make_request("bookings/{$booking_id}");
        return $this->normalize_booking($response['data'] ?? array());
    }

    public function create_booking($booking_data) {
        $response = $this->make_request('bookings', 'POST', $this->prepare_booking_data($booking_data));
        return $this->normalize_booking($response['data'] ?? array());
    }

    public function update_booking($booking_id, $booking_data) {
        $response = $this->make_request("bookings/{$booking_id}", 'PATCH', $this->prepare_booking_data($booking_data));
        return $this->normalize_booking($response['data'] ?? array());
    }

    public function cancel_booking($booking_id) {
        $response = $this->make_request("bookings/{$booking_id}", 'DELETE');
        return !empty($response['success']);
    }

    public function normalize_listing($raw_data) {
        return array(
            'id' => $raw_data['id'] ?? '',
            'name' => $raw_data['name'] ?? '',
            'description' => $raw_data['description'] ?? '',
            'address' => array(
                'street' => $raw_data['address_line_1'] ?? '',
                'city' => $raw_data['city'] ?? '',
                'state' => $raw_data['state'] ?? '',
                'zip' => $raw_data['postcode'] ?? '',
                'country' => $raw_data['country_code'] ?? '',
            ),
            'bedrooms' => $raw_data['bedrooms'] ?? 0,
            'bathrooms' => $raw_data['bathrooms'] ?? 0,
            'max_guests' => $raw_data['max_guests'] ?? 0,
            'amenities' => $raw_data['amenities'] ?? array(),
            'images' => $raw_data['images'] ?? array(),
            'pricing' => $raw_data['pricing'] ?? array(),
        );
    }

    public function normalize_booking($raw_data) {
        return array(
            'id' => $raw_data['id'] ?? '',
            'listing_id' => $raw_data['listing_id'] ?? '',
            'status' => $raw_data['status'] ?? 'confirmed',
            'check_in' => $raw_data['check_in_date'] ?? '',
            'check_out' => $raw_data['check_out_date'] ?? '',
            'guest_name' => $raw_data['guest_name'] ?? '',
            'guest_email' => $raw_data['guest_email'] ?? '',
            'total_amount' => $raw_data['total_price'] ?? 0,
            'currency' => $raw_data['currency'] ?? 'USD',
            'number_of_guests' => $raw_data['number_of_guests'] ?? 1,
        );
    }

    private function prepare_booking_data($booking_data) {
        return array(
            'listing_id' => $booking_data['listing_id'],
            'check_in_date' => $booking_data['check_in'],
            'check_out_date' => $booking_data['check_out'],
            'guest_name' => $booking_data['guest_name'],
            'guest_email' => $booking_data['guest_email'],
            'total_price' => $booking_data['total_amount'],
            'currency' => $booking_data['currency'] ?? 'USD',
            'number_of_guests' => $booking_data['number_of_guests'] ?? 1,
        );
    }

    public function verify_webhook_signature($payload, $signature) {
        $secret = $this->credentials['webhook_secret'] ?? '';
        $computed = hash_hmac('sha256', $payload, $secret);
        return hash_equals($computed, $signature);
    }
}
