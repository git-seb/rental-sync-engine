<?php
namespace RentalSyncEngine\PMS\Uplisting;

use RentalSyncEngine\Core\ApiClient;
use RentalSyncEngine\Core\Settings;

class Client extends ApiClient {
    private $api_key;
    
    public function __construct() {
        parent::__construct('https://api.uplisting.io/v1/');
        $credentials = Settings::get_provider_credentials('ul');
        $this->api_key = $credentials['api_key'] ?? '';
    }
    
    protected function get_auth_headers() {
        return array(
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        );
    }
    
    public function get_properties() {
        return $this->get('properties');
    }
    
    public function get_property($property_id) {
        return $this->get('properties/' . $property_id);
    }
    
    public function get_availability($property_id, $start_date, $end_date) {
        return $this->get('properties/' . $property_id . '/calendar', array(
            'start_date' => $start_date,
            'end_date' => $end_date
        ));
    }
    
    public function get_bookings($params = array()) {
        return $this->get('bookings', $params);
    }
    
    public function get_booking($booking_id) {
        return $this->get('bookings/' . $booking_id);
    }
    
    public function create_booking($booking_data) {
        return $this->post('bookings', $booking_data);
    }
}
