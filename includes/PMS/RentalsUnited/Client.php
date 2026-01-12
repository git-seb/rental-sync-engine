<?php
/**
 * Rentals United API Client
 *
 * @package RentalSyncEngine\PMS\RentalsUnited
 */

namespace RentalSyncEngine\PMS\RentalsUnited;

use RentalSyncEngine\Core\ApiClient;
use RentalSyncEngine\Core\Settings;

/**
 * Class Client
 */
class Client extends ApiClient {
    
    /**
     * Username
     *
     * @var string
     */
    private $username;
    
    /**
     * Password
     *
     * @var string
     */
    private $password;
    
    /**
     * Constructor
     */
    public function __construct() {
        $api_url = Settings::get_api_url('ru', 'https://rm.rentalsunited.com/api');
        parent::__construct($api_url);
        
        $credentials = Settings::get_provider_credentials('ru');
        $this->username = $credentials['username'] ?? '';
        $this->password = $credentials['password'] ?? '';
    }
    
    /**
     * Get authentication headers
     *
     * @return array Authentication headers
     */
    protected function get_auth_headers() {
        return array(
            'Content-Type' => 'application/xml',
            'Authorization' => 'Basic ' . base64_encode($this->username . ':' . $this->password)
        );
    }
    
    /**
     * Get properties list
     *
     * @return array Properties data
     */
    public function get_properties() {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <Pull_ListOwnerProp_RQ>
            <Authentication>
                <UserName>' . $this->username . '</UserName>
                <Password>' . $this->password . '</Password>
            </Authentication>
        </Pull_ListOwnerProp_RQ>';
        
        try {
            $url = $this->base_url . '/Handler.ashx';
            $args = array(
                'method' => 'POST',
                'headers' => array('Content-Type' => 'application/xml'),
                'body' => $xml,
                'timeout' => $this->timeout,
                'sslverify' => true,
            );
            
            $response = wp_remote_post($url, $args);
            
            if (is_wp_error($response)) {
                $error_message = 'Failed to get properties: ' . $response->get_error_message();
                \RentalSyncEngine\Core\Logger::error('ru', 'api_request', $error_message);
                return array('error' => $error_message);
            }
            
            $body = wp_remote_retrieve_body($response);
            return $this->parse_xml_response($body);
        } catch (\Exception $e) {
            $error_message = 'Failed to get properties: ' . $e->getMessage();
            \RentalSyncEngine\Core\Logger::error('ru', 'api_request', $error_message);
            return array('error' => $error_message);
        }
    }
    
    /**
     * Get property details
     *
     * @param string $property_id Property ID
     * @return array Property data
     */
    public function get_property($property_id) {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <Pull_GetProp_RQ>
            <Authentication>
                <UserName>' . $this->username . '</UserName>
                <Password>' . $this->password . '</Password>
            </Authentication>
            <PropertyID>' . $property_id . '</PropertyID>
        </Pull_GetProp_RQ>';
        
        try {
            $url = $this->base_url . '/Handler.ashx';
            $args = array(
                'method' => 'POST',
                'headers' => array('Content-Type' => 'application/xml'),
                'body' => $xml,
                'timeout' => $this->timeout,
                'sslverify' => true,
            );
            
            $response = wp_remote_post($url, $args);
            
            if (is_wp_error($response)) {
                $error_message = 'Failed to get property: ' . $response->get_error_message();
                \RentalSyncEngine\Core\Logger::error('ru', 'api_request', $error_message);
                return array('error' => $error_message);
            }
            
            $body = wp_remote_retrieve_body($response);
            return $this->parse_xml_response($body);
        } catch (\Exception $e) {
            $error_message = 'Failed to get property: ' . $e->getMessage();
            \RentalSyncEngine\Core\Logger::error('ru', 'api_request', $error_message);
            return array('error' => $error_message);
        }
    }
    
    /**
     * Get property availability
     *
     * @param string $property_id Property ID
     * @param string $date_from Start date (Y-m-d)
     * @param string $date_to End date (Y-m-d)
     * @return array Availability data
     */
    public function get_availability($property_id, $date_from, $date_to) {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <Pull_GetPropertyAvbCalendar_RQ>
            <Authentication>
                <UserName>' . $this->username . '</UserName>
                <Password>' . $this->password . '</Password>
            </Authentication>
            <PropertyID>' . $property_id . '</PropertyID>
            <DateFrom>' . $date_from . '</DateFrom>
            <DateTo>' . $date_to . '</DateTo>
        </Pull_GetPropertyAvbCalendar_RQ>';
        
        try {
            $url = $this->base_url . '/Handler.ashx';
            $args = array(
                'method' => 'POST',
                'headers' => array('Content-Type' => 'application/xml'),
                'body' => $xml,
                'timeout' => $this->timeout,
                'sslverify' => true,
            );
            
            $response = wp_remote_post($url, $args);
            
            if (is_wp_error($response)) {
                $error_message = 'Failed to get availability: ' . $response->get_error_message();
                \RentalSyncEngine\Core\Logger::error('ru', 'api_request', $error_message);
                return array('error' => $error_message);
            }
            
            $body = wp_remote_retrieve_body($response);
            return $this->parse_xml_response($body);
        } catch (\Exception $e) {
            $error_message = 'Failed to get availability: ' . $e->getMessage();
            \RentalSyncEngine\Core\Logger::error('ru', 'api_request', $error_message);
            return array('error' => $error_message);
        }
    }
    
    /**
     * Get reservations
     *
     * @param string $date_from Start date (Y-m-d)
     * @param string $date_to End date (Y-m-d)
     * @return array Reservations data
     */
    public function get_reservations($date_from, $date_to) {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <Pull_ListReservations_RQ>
            <Authentication>
                <UserName>' . $this->username . '</UserName>
                <Password>' . $this->password . '</Password>
            </Authentication>
            <DateFrom>' . $date_from . '</DateFrom>
            <DateTo>' . $date_to . '</DateTo>
        </Pull_ListReservations_RQ>';
        
        try {
            $url = $this->base_url . '/Handler.ashx';
            $args = array(
                'method' => 'POST',
                'headers' => array('Content-Type' => 'application/xml'),
                'body' => $xml,
                'timeout' => $this->timeout,
                'sslverify' => true,
            );
            
            $response = wp_remote_post($url, $args);
            
            if (is_wp_error($response)) {
                $error_message = 'Failed to get reservations: ' . $response->get_error_message();
                \RentalSyncEngine\Core\Logger::error('ru', 'api_request', $error_message);
                return array('error' => $error_message);
            }
            
            $body = wp_remote_retrieve_body($response);
            return $this->parse_xml_response($body);
        } catch (\Exception $e) {
            $error_message = 'Failed to get reservations: ' . $e->getMessage();
            \RentalSyncEngine\Core\Logger::error('ru', 'api_request', $error_message);
            return array('error' => $error_message);
        }
    }
    
    /**
     * Create a reservation
     *
     * @param array $booking_data Booking data
     * @return array Response data
     */
    public function create_reservation($booking_data) {
        $xml = $this->build_reservation_xml($booking_data);
        
        try {
            $url = $this->base_url . '/Handler.ashx';
            $args = array(
                'method' => 'POST',
                'headers' => array('Content-Type' => 'application/xml'),
                'body' => $xml,
                'timeout' => $this->timeout,
                'sslverify' => true,
            );
            
            $response = wp_remote_post($url, $args);
            
            if (is_wp_error($response)) {
                $error_message = 'Failed to create reservation: ' . $response->get_error_message();
                \RentalSyncEngine\Core\Logger::error('ru', 'api_request', $error_message);
                return array('error' => $error_message);
            }
            
            $body = wp_remote_retrieve_body($response);
            return $this->parse_xml_response($body);
        } catch (\Exception $e) {
            $error_message = 'Failed to create reservation: ' . $e->getMessage();
            \RentalSyncEngine\Core\Logger::error('ru', 'api_request', $error_message);
            return array('error' => $error_message);
        }
    }
    
    /**
     * Parse XML response
     *
     * @param string $xml XML string
     * @return array Parsed data
     */
    private function parse_xml_response($xml) {
        $data = simplexml_load_string($xml);
        return json_decode(json_encode($data), true);
    }
    
    /**
     * Build reservation XML
     *
     * @param array $booking_data Booking data
     * @return string XML string
     */
    private function build_reservation_xml($booking_data) {
        return '<?xml version="1.0" encoding="UTF-8"?>
        <Push_PutReservation_RQ>
            <Authentication>
                <UserName>' . $this->username . '</UserName>
                <Password>' . $this->password . '</Password>
            </Authentication>
            <Reservation>
                <PropertyID>' . $booking_data['property_id'] . '</PropertyID>
                <DateFrom>' . $booking_data['check_in'] . '</DateFrom>
                <DateTo>' . $booking_data['check_out'] . '</DateTo>
                <GuestName>' . htmlspecialchars($booking_data['guest_name']) . '</GuestName>
                <Email>' . htmlspecialchars($booking_data['email']) . '</Email>
                <Total>' . $booking_data['total'] . '</Total>
            </Reservation>
        </Push_PutReservation_RQ>';
    }
}
