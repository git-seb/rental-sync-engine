<?php
/**
 * Abstract PMS Handler
 *
 * @package RentalSyncEngine\PMS
 */

namespace RentalSyncEngine\PMS;

/**
 * Abstract PMS Handler Class
 * Base class for all PMS platform integrations
 */
abstract class AbstractPMSHandler {
    /**
     * Platform credentials
     *
     * @var array
     */
    protected $credentials;

    /**
     * API base URL
     *
     * @var string
     */
    protected $api_base_url;

    /**
     * Constructor
     *
     * @param array $credentials Platform credentials
     */
    public function __construct($credentials) {
        $this->credentials = $credentials;
        $this->validate_credentials();
    }

    /**
     * Validate credentials
     *
     * @throws \Exception If credentials are invalid
     */
    abstract protected function validate_credentials();

    /**
     * Get listings from PMS
     *
     * @return array Array of listings
     */
    abstract public function get_listings();

    /**
     * Get a specific listing
     *
     * @param string $listing_id Listing ID
     * @return array Listing data
     */
    abstract public function get_listing($listing_id);

    /**
     * Get availability for a listing
     *
     * @param string $listing_id Listing ID
     * @param string $start_date Start date (Y-m-d)
     * @param string $end_date End date (Y-m-d)
     * @return array Availability data
     */
    abstract public function get_availability($listing_id, $start_date = null, $end_date = null);

    /**
     * Get bookings from PMS
     *
     * @param array $params Query parameters
     * @return array Array of bookings
     */
    abstract public function get_bookings($params = array());

    /**
     * Get a specific booking
     *
     * @param string $booking_id Booking ID
     * @return array Booking data
     */
    abstract public function get_booking($booking_id);

    /**
     * Create a booking in PMS
     *
     * @param array $booking_data Booking data
     * @return array Created booking data
     */
    abstract public function create_booking($booking_data);

    /**
     * Update a booking in PMS
     *
     * @param string $booking_id Booking ID
     * @param array  $booking_data Booking data
     * @return array Updated booking data
     */
    abstract public function update_booking($booking_id, $booking_data);

    /**
     * Cancel a booking in PMS
     *
     * @param string $booking_id Booking ID
     * @return bool Success status
     */
    abstract public function cancel_booking($booking_id);

    /**
     * Make an API request
     *
     * @param string $endpoint API endpoint
     * @param string $method HTTP method
     * @param array  $data Request data
     * @param array  $headers Additional headers
     * @return array Response data
     * @throws \Exception If request fails
     */
    protected function make_request($endpoint, $method = 'GET', $data = array(), $headers = array()) {
        $url = $this->api_base_url . $endpoint;

        $args = array(
            'method' => $method,
            'headers' => array_merge($this->get_auth_headers(), $headers),
            'timeout' => 30,
        );

        if (!empty($data)) {
            if ($method === 'GET') {
                $url = add_query_arg($data, $url);
            } else {
                $args['body'] = wp_json_encode($data);
                $args['headers']['Content-Type'] = 'application/json';
            }
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            throw new \Exception($response->get_error_message());
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($status_code < 200 || $status_code >= 300) {
            throw new \Exception(
                sprintf('API request failed with status %d: %s', $status_code, $body)
            );
        }

        return json_decode($body, true);
    }

    /**
     * Get authentication headers
     *
     * @return array Authentication headers
     */
    abstract protected function get_auth_headers();

    /**
     * Normalize listing data to common format
     *
     * @param array $raw_data Raw listing data from PMS
     * @return array Normalized listing data
     */
    abstract public function normalize_listing($raw_data);

    /**
     * Normalize booking data to common format
     *
     * @param array $raw_data Raw booking data from PMS
     * @return array Normalized booking data
     */
    abstract public function normalize_booking($raw_data);

    /**
     * Verify webhook signature
     *
     * @param string $payload Webhook payload
     * @param string $signature Webhook signature
     * @return bool Signature valid
     */
    abstract public function verify_webhook_signature($payload, $signature);
}
