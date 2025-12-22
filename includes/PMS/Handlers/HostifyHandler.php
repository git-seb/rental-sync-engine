<?php
/**
 * Hostify Handler
 *
 * @package RentalSyncEngine\PMS\Handlers
 */

namespace RentalSyncEngine\PMS\Handlers;

use RentalSyncEngine\PMS\AbstractPMSHandler;

/**
 * Hostify Handler Class
 */
class HostifyHandler extends AbstractPMSHandler {
    public function __construct($credentials) {
        $this->api_base_url = 'https://api.hostify.com/v1/';
        parent::__construct($credentials);
    }

    protected function validate_credentials() {
        if (empty($this->credentials['api_key'])) {
            throw new \Exception('Hostify requires API key');
        }
    }

    protected function get_auth_headers() {
        return array(
            'X-API-Key' => $this->credentials['api_key'],
            'Content-Type' => 'application/json',
        );
    }

    public function get_listings() {
        $response = $this->make_request('properties');
        return array_map(array($this, 'normalize_listing'), $response['data'] ?? array());
    }

    public function get_listing($listing_id) {
        $response = $this->make_request("properties/{$listing_id}");
        return $this->normalize_listing($response['data'] ?? array());
    }

    public function get_availability($listing_id, $start_date = null, $end_date = null) {
        $params = array();
        if ($start_date) $params['from'] = $start_date;
        if ($end_date) $params['to'] = $end_date;
        return $this->make_request("properties/{$listing_id}/calendar", 'GET', $params);
    }

    public function get_bookings($params = array()) {
        $response = $this->make_request('reservations', 'GET', $params);
        return array_map(array($this, 'normalize_booking'), $response['data'] ?? array());
    }

    public function get_booking($booking_id) {
        $response = $this->make_request("reservations/{$booking_id}");
        return $this->normalize_booking($response['data'] ?? array());
    }

    public function create_booking($booking_data) {
        $response = $this->make_request('reservations', 'POST', $this->prepare_booking_data($booking_data));
        return $this->normalize_booking($response['data'] ?? array());
    }

    public function update_booking($booking_id, $booking_data) {
        $response = $this->make_request("reservations/{$booking_id}", 'PUT', $this->prepare_booking_data($booking_data));
        return $this->normalize_booking($response['data'] ?? array());
    }

    public function cancel_booking($booking_id) {
        $response = $this->make_request("reservations/{$booking_id}/cancel", 'POST');
        return !empty($response['success']);
    }

    public function normalize_listing($raw_data) {
        return array(
            'id' => $raw_data['uid'] ?? '',
            'name' => $raw_data['name'] ?? '',
            'description' => $raw_data['description'] ?? '',
            'address' => array(
                'street' => $raw_data['address']['street'] ?? '',
                'city' => $raw_data['address']['city'] ?? '',
                'state' => $raw_data['address']['state'] ?? '',
                'zip' => $raw_data['address']['zip'] ?? '',
                'country' => $raw_data['address']['country'] ?? '',
            ),
            'bedrooms' => $raw_data['bedrooms'] ?? 0,
            'bathrooms' => $raw_data['bathrooms'] ?? 0,
            'max_guests' => $raw_data['guests'] ?? 0,
            'amenities' => $raw_data['amenities'] ?? array(),
            'images' => $raw_data['photos'] ?? array(),
            'pricing' => $raw_data['pricing'] ?? array(),
        );
    }

    public function normalize_booking($raw_data) {
        return array(
            'id' => $raw_data['uid'] ?? '',
            'listing_id' => $raw_data['propertyUid'] ?? '',
            'status' => $raw_data['status'] ?? 'confirmed',
            'check_in' => $raw_data['checkIn'] ?? '',
            'check_out' => $raw_data['checkOut'] ?? '',
            'guest_name' => ($raw_data['guest']['firstName'] ?? '') . ' ' . ($raw_data['guest']['lastName'] ?? ''),
            'guest_email' => $raw_data['guest']['email'] ?? '',
            'total_amount' => $raw_data['price']['total'] ?? 0,
            'currency' => $raw_data['price']['currency'] ?? 'USD',
            'number_of_guests' => $raw_data['guests'] ?? 1,
        );
    }

    private function prepare_booking_data($booking_data) {
        $name_parts = explode(' ', $booking_data['guest_name'], 2);
        return array(
            'propertyUid' => $booking_data['listing_id'],
            'checkIn' => $booking_data['check_in'],
            'checkOut' => $booking_data['check_out'],
            'guest' => array(
                'firstName' => $name_parts[0] ?? '',
                'lastName' => $name_parts[1] ?? '',
                'email' => $booking_data['guest_email'],
            ),
            'price' => array(
                'total' => $booking_data['total_amount'],
                'currency' => $booking_data['currency'] ?? 'USD',
            ),
            'guests' => $booking_data['number_of_guests'] ?? 1,
        );
    }

    public function verify_webhook_signature($payload, $signature) {
        $secret = $this->credentials['webhook_secret'] ?? '';
        $computed = hash_hmac('sha256', $payload, $secret);
        return hash_equals($computed, $signature);
    }
}
