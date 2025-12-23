<?php
/**
 * OwnerRez API Client
 *
 * @package RentalSyncEngine\PMS\OwnerRez
 */

namespace RentalSyncEngine\PMS\OwnerRez;

use RentalSyncEngine\Core\ApiClient;
use RentalSyncEngine\Core\Settings;

/**
 * Class Client
 */
class Client extends ApiClient {
    
    /**
     * API token
     *
     * @var string
     */
    private $api_token;
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct('https://api.ownerrez.com/v2/');
        
        $credentials = Settings::get_provider_credentials('or');
        $this->api_token = $credentials['api_token'] ?? '';
    }
    
    /**
     * Get authentication headers
     *
     * @return array Authentication headers
     */
    protected function get_auth_headers() {
        return array(
            'Authorization' => 'Bearer ' . $this->api_token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        );
    }
    
    /**
     * Get properties
     *
     * @return array Properties data
     */
    public function get_properties() {
        return $this->get('properties');
    }
    
    /**
     * Get property details
     *
     * @param string $property_id Property ID
     * @return array Property data
     */
    public function get_property($property_id) {
        return $this->get('properties/' . $property_id);
    }
    
    /**
     * Get property availability
     *
     * @param string $property_id Property ID
     * @param string $start_date Start date
     * @param string $end_date End date
     * @return array Availability data
     */
    public function get_availability($property_id, $start_date, $end_date) {
        return $this->get('properties/' . $property_id . '/availability', array(
            'start' => $start_date,
            'end' => $end_date
        ));
    }
    
    /**
     * Get bookings
     *
     * @param array $params Query parameters
     * @return array Bookings data
     */
    public function get_bookings($params = array()) {
        return $this->get('bookings', $params);
    }
    
    /**
     * Get booking details
     *
     * @param string $booking_id Booking ID
     * @return array Booking data
     */
    public function get_booking($booking_id) {
        return $this->get('bookings/' . $booking_id);
    }
    
    /**
     * Create booking
     *
     * @param array $booking_data Booking data
     * @return array Response data
     */
    public function create_booking($booking_data) {
        return $this->post('bookings', $booking_data);
    }
    
    /**
     * Update booking
     *
     * @param string $booking_id Booking ID
     * @param array $booking_data Booking data
     * @return array Response data
     */
    public function update_booking($booking_id, $booking_data) {
        return $this->put('bookings/' . $booking_id, $booking_data);
    }
}
