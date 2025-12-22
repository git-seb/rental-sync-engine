<?php
/**
 * OwnerRez Handler
 *
 * @package RentalSyncEngine\PMS\Handlers
 */

namespace RentalSyncEngine\PMS\Handlers;

use RentalSyncEngine\PMS\AbstractPMSHandler;

/**
 * OwnerRez Handler Class
 */
class OwnerRezHandler extends AbstractPMSHandler {
    public function __construct($credentials) {
        $this->api_base_url = 'https://api.ownerreservations.com/v2/';
        parent::__construct($credentials);
    }

    protected function validate_credentials() {
        if (empty($this->credentials['username']) || empty($this->credentials['token'])) {
            throw new \Exception('OwnerRez requires username and token');
        }
    }

    protected function get_auth_headers() {
        return array(
            'Authorization' => 'Bearer ' . $this->credentials['token'],
            'X-OwnerRez-Username' => $this->credentials['username'],
            'Content-Type' => 'application/json',
        );
    }

    public function get_listings() {
        $response = $this->make_request('properties');
        return array_map(array($this, 'normalize_listing'), $response['items'] ?? array());
    }

    public function get_listing($listing_id) {
        $response = $this->make_request("properties/{$listing_id}");
        return $this->normalize_listing($response ?? array());
    }

    public function get_availability($listing_id, $start_date = null, $end_date = null) {
        $params = array();
        if ($start_date) $params['startDate'] = $start_date;
        if ($end_date) $params['endDate'] = $end_date;
        return $this->make_request("properties/{$listing_id}/calendar", 'GET', $params);
    }

    public function get_bookings($params = array()) {
        $response = $this->make_request('bookings', 'GET', $params);
        return array_map(array($this, 'normalize_booking'), $response['items'] ?? array());
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
            'name' => $raw_data['name'] ?? '',
            'description' => $raw_data['description'] ?? '',
            'address' => array(
                'street' => $raw_data['address']['addressLine1'] ?? '',
                'city' => $raw_data['address']['city'] ?? '',
                'state' => $raw_data['address']['state'] ?? '',
                'zip' => $raw_data['address']['postalCode'] ?? '',
                'country' => $raw_data['address']['country'] ?? '',
            ),
            'bedrooms' => $raw_data['bedrooms'] ?? 0,
            'bathrooms' => $raw_data['bathrooms'] ?? 0,
            'max_guests' => $raw_data['maxOccupancy'] ?? 0,
            'amenities' => $raw_data['amenities'] ?? array(),
            'images' => $raw_data['photos'] ?? array(),
            'pricing' => $raw_data['rates'] ?? array(),
        );
    }

    public function normalize_booking($raw_data) {
        return array(
            'id' => $raw_data['id'] ?? '',
            'listing_id' => $raw_data['propertyId'] ?? '',
            'status' => $raw_data['status'] ?? 'confirmed',
            'check_in' => $raw_data['arrival'] ?? '',
            'check_out' => $raw_data['departure'] ?? '',
            'guest_name' => $raw_data['guestName'] ?? '',
            'guest_email' => $raw_data['guestEmail'] ?? '',
            'total_amount' => $raw_data['totalAmount'] ?? 0,
            'currency' => $raw_data['currency'] ?? 'USD',
            'number_of_guests' => $raw_data['numberOfGuests'] ?? 1,
        );
    }

    private function prepare_booking_data($booking_data) {
        return array(
            'propertyId' => $booking_data['listing_id'],
            'arrival' => $booking_data['check_in'],
            'departure' => $booking_data['check_out'],
            'guestName' => $booking_data['guest_name'],
            'guestEmail' => $booking_data['guest_email'],
            'totalAmount' => $booking_data['total_amount'],
            'currency' => $booking_data['currency'] ?? 'USD',
            'numberOfGuests' => $booking_data['number_of_guests'] ?? 1,
        );
    }

    public function verify_webhook_signature($payload, $signature) {
        $secret = $this->credentials['webhook_secret'] ?? '';
        $computed = hash_hmac('sha256', $payload, $secret);
        return hash_equals($computed, $signature);
    }
}
