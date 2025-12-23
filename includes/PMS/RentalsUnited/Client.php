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
        parent::__construct('https://rm.rentalsunited.com/api/');
        
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
            $response = $this->client->request('POST', 'Handler.ashx', array(
                'headers' => array('Content-Type' => 'application/xml'),
                'body' => $xml
            ));
            
            return $this->parse_xml_response($response->getBody()->getContents());
        } catch (\Exception $e) {
            throw new \Exception('Failed to get properties: ' . $e->getMessage());
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
            $response = $this->client->request('POST', 'Handler.ashx', array(
                'headers' => array('Content-Type' => 'application/xml'),
                'body' => $xml
            ));
            
            return $this->parse_xml_response($response->getBody()->getContents());
        } catch (\Exception $e) {
            throw new \Exception('Failed to get property: ' . $e->getMessage());
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
            $response = $this->client->request('POST', 'Handler.ashx', array(
                'headers' => array('Content-Type' => 'application/xml'),
                'body' => $xml
            ));
            
            return $this->parse_xml_response($response->getBody()->getContents());
        } catch (\Exception $e) {
            throw new \Exception('Failed to get availability: ' . $e->getMessage());
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
            $response = $this->client->request('POST', 'Handler.ashx', array(
                'headers' => array('Content-Type' => 'application/xml'),
                'body' => $xml
            ));
            
            return $this->parse_xml_response($response->getBody()->getContents());
        } catch (\Exception $e) {
            throw new \Exception('Failed to get reservations: ' . $e->getMessage());
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
            $response = $this->client->request('POST', 'Handler.ashx', array(
                'headers' => array('Content-Type' => 'application/xml'),
                'body' => $xml
            ));
            
            return $this->parse_xml_response($response->getBody()->getContents());
        } catch (\Exception $e) {
            throw new \Exception('Failed to create reservation: ' . $e->getMessage());
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
