<?php
/**
 * Hostaway Handler
 *
 * @package RentalSyncEngine\PMS\Handlers
 */

namespace RentalSyncEngine\PMS\Handlers;

use RentalSyncEngine\PMS\AbstractPMSHandler;

/**
 * Hostaway Handler Class
 */
class HostawayHandler extends AbstractPMSHandler {
    /**
     * Constructor
     */
    public function __construct($credentials) {
        $this->api_base_url = 'https://api.hostaway.com/v1/';
        parent::__construct($credentials);
    }

    protected function validate_credentials() {
        if (empty($this->credentials['api_key']) || empty($this->credentials['api_secret'])) {
            throw new \Exception('Hostaway requires API key and secret');
        }
    }

    protected function get_auth_headers() {
        return array(
            'Authorization' => 'Bearer ' . $this->get_access_token(),
            'Content-Type' => 'application/json',
        );
    }

    private function get_access_token() {
        // Cache token for 24 hours
        $token = get_transient('rental_sync_hostaway_token');
        if ($token) {
            return $token;
        }

        // Get new token
        $response = wp_remote_post($this->api_base_url . 'accessTokens', array(
            'body' => wp_json_encode(array(
                'grant_type' => 'client_credentials',
                'client_id' => $this->credentials['api_key'],
                'client_secret' => $this->credentials['api_secret'],
                'scope' => 'general',
            )),
            'headers' => array('Content-Type' => 'application/json'),
        ));

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $token = $body['access_token'] ?? '';
        
        if ($token) {
            set_transient('rental_sync_hostaway_token', $token, 86400);
        }

        return $token;
    }

    public function get_listings() {
        $response = $this->make_request('listings');
        $listings = array();
        if (isset($response['result'])) {
            foreach ($response['result'] as $listing) {
                $listings[] = $this->normalize_listing($listing);
            }
        }
        return $listings;
    }

    public function get_listing($listing_id) {
        $response = $this->make_request("listings/{$listing_id}");
        return $this->normalize_listing($response['result'] ?? array());
    }

    public function get_availability($listing_id, $start_date = null, $end_date = null) {
        $params = array('listingId' => $listing_id);
        if ($start_date) $params['startDate'] = $start_date;
        if ($end_date) $params['endDate'] = $end_date;
        return $this->make_request('calendars', 'GET', $params);
    }

    public function get_bookings($params = array()) {
        $response = $this->make_request('reservations', 'GET', $params);
        $bookings = array();
        if (isset($response['result'])) {
            foreach ($response['result'] as $booking) {
                $bookings[] = $this->normalize_booking($booking);
            }
        }
        return $bookings;
    }

    public function get_booking($booking_id) {
        $response = $this->make_request("reservations/{$booking_id}");
        return $this->normalize_booking($response['result'] ?? array());
    }

    public function create_booking($booking_data) {
        $response = $this->make_request('reservations', 'POST', $this->prepare_booking_data($booking_data));
        return $this->normalize_booking($response['result'] ?? array());
    }

    public function update_booking($booking_id, $booking_data) {
        $response = $this->make_request("reservations/{$booking_id}", 'PUT', $this->prepare_booking_data($booking_data));
        return $this->normalize_booking($response['result'] ?? array());
    }

    public function cancel_booking($booking_id) {
        $response = $this->make_request("reservations/{$booking_id}", 'DELETE');
        return isset($response['success']) && $response['success'];
    }

    public function normalize_listing($raw_data) {
        return array(
            'id' => $raw_data['id'] ?? '',
            'name' => $raw_data['name'] ?? '',
            'description' => $raw_data['description'] ?? '',
            'address' => array(
                'street' => $raw_data['address'] ?? '',
                'city' => $raw_data['city'] ?? '',
                'state' => $raw_data['state'] ?? '',
                'zip' => $raw_data['zipcode'] ?? '',
                'country' => $raw_data['countryCode'] ?? '',
            ),
            'bedrooms' => $raw_data['bedrooms'] ?? 0,
            'bathrooms' => $raw_data['bathrooms'] ?? 0,
            'max_guests' => $raw_data['accommodates'] ?? 0,
            'amenities' => $raw_data['amenities'] ?? array(),
            'images' => $raw_data['images'] ?? array(),
            'pricing' => array('base_price' => $raw_data['price'] ?? 0),
        );
    }

    public function normalize_booking($raw_data) {
        return array(
            'id' => $raw_data['id'] ?? '',
            'listing_id' => $raw_data['listingId'] ?? '',
            'status' => $raw_data['status'] ?? 'confirmed',
            'check_in' => $raw_data['arrivalDate'] ?? '',
            'check_out' => $raw_data['departureDate'] ?? '',
            'guest_name' => $raw_data['guestName'] ?? '',
            'guest_email' => $raw_data['guestEmail'] ?? '',
            'total_amount' => $raw_data['totalPrice'] ?? 0,
            'currency' => $raw_data['currency'] ?? 'USD',
            'number_of_guests' => $raw_data['numberOfGuests'] ?? 1,
        );
    }

    private function prepare_booking_data($booking_data) {
        return array(
            'listingId' => $booking_data['listing_id'],
            'arrivalDate' => $booking_data['check_in'],
            'departureDate' => $booking_data['check_out'],
            'guestName' => $booking_data['guest_name'],
            'guestEmail' => $booking_data['guest_email'],
            'totalPrice' => $booking_data['total_amount'],
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
