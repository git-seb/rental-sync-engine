<?php
namespace RentalSyncEngine\PMS\Hostaway;

use RentalSyncEngine\Core\ApiClient;
use RentalSyncEngine\Core\Settings;

class Client extends ApiClient {
    private $client_id;
    private $client_secret;
    private $access_token;
    
    public function __construct() {
        parent::__construct('https://api.hostaway.com/v1/');
        $credentials = Settings::get_provider_credentials('ha');
        $this->client_id = $credentials['client_id'] ?? '';
        $this->client_secret = $credentials['client_secret'] ?? '';
        $this->authenticate();
    }
    
    protected function get_auth_headers() {
        return array(
            'Authorization' => 'Bearer ' . $this->access_token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        );
    }
    
    private function authenticate() {
        try {
            $response = $this->post('accessTokens', array(
                'grant_type' => 'client_credentials',
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'scope' => 'general'
            ));
            $this->access_token = $response['access_token'] ?? '';
        } catch (\Exception $e) {
            throw new \Exception('Authentication failed: ' . $e->getMessage());
        }
    }
    
    public function get_properties() {
        return $this->get('listings');
    }
    
    public function get_property($property_id) {
        return $this->get('listings/' . $property_id);
    }
    
    public function get_availability($property_id, $start_date, $end_date) {
        return $this->get('listings/' . $property_id . '/calendar', array(
            'startDate' => $start_date,
            'endDate' => $end_date
        ));
    }
    
    public function get_reservations($params = array()) {
        return $this->get('reservations', $params);
    }
    
    public function get_reservation($reservation_id) {
        return $this->get('reservations/' . $reservation_id);
    }
    
    public function create_reservation($reservation_data) {
        return $this->post('reservations', $reservation_data);
    }
    
    public function update_reservation($reservation_id, $reservation_data) {
        return $this->put('reservations/' . $reservation_id, $reservation_data);
    }
}
