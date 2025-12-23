<?php
/**
 * Rentals United Handler
 *
 * @package RentalSyncEngine\PMS\RentalsUnited
 */

namespace RentalSyncEngine\PMS\RentalsUnited;

use RentalSyncEngine\Core\PMSHandlerInterface;
use RentalSyncEngine\Core\Logger;
use RentalSyncEngine\Integration\WooCommerceIntegration;

/**
 * Class Handler
 */
class Handler implements PMSHandlerInterface {
    
    /**
     * Instance
     *
     * @var Handler
     */
    private static $instance = null;
    
    /**
     * API client
     *
     * @var Client
     */
    private $client;
    
    /**
     * PMS provider code
     *
     * @var string
     */
    private $provider_code = 'ru';
    
    /**
     * Get instance
     *
     * @return Handler
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize handler
     */
    public static function init() {
        $instance = self::get_instance();
        
        // Hook into actions
        add_action('rental_sync_engine_push_booking', array($instance, 'handle_push_booking'), 10, 2);
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->client = new Client();
    }
    
    /**
     * Sync properties
     *
     * @return array Sync results
     */
    public function sync_properties() {
        try {
            $properties = $this->client->get_properties();
            $results = array(
                'success' => 0,
                'failed' => 0,
                'errors' => array()
            );
            
            if (isset($properties['Properties']['Property'])) {
                $property_list = $properties['Properties']['Property'];
                
                // Handle single property response
                if (!isset($property_list[0])) {
                    $property_list = array($property_list);
                }
                
                foreach ($property_list as $property) {
                    try {
                        // Get full property details
                        $property_details = $this->client->get_property($property['ID']);
                        
                        // Map property data
                        $property_data = $this->map_property_data($property_details);
                        
                        // Check if product already exists
                        $product_id = $this->get_existing_product_id($property['ID']);
                        
                        if ($product_id) {
                            WooCommerceIntegration::update_product_from_property($product_id, $property_data, $this->provider_code);
                        } else {
                            WooCommerceIntegration::create_product_from_property($property_data, $this->provider_code);
                        }
                        
                        $results['success']++;
                    } catch (\Exception $e) {
                        $results['failed']++;
                        $results['errors'][] = $e->getMessage();
                        Logger::error($this->provider_code, 'property_sync', $e->getMessage(), $property);
                    }
                }
            }
            
            Logger::success($this->provider_code, 'property_sync', 'Properties synced', $results);
            return $results;
        } catch (\Exception $e) {
            Logger::error($this->provider_code, 'property_sync', $e->getMessage());
            return array('error' => $e->getMessage());
        }
    }
    
    /**
     * Sync availability
     *
     * @return array Sync results
     */
    public function sync_availability() {
        try {
            $results = array(
                'success' => 0,
                'failed' => 0,
                'errors' => array()
            );
            
            // Get all mapped properties
            $mappings = $this->get_property_mappings();
            
            $date_from = date('Y-m-d');
            $date_to = date('Y-m-d', strtotime('+1 year'));
            
            foreach ($mappings as $mapping) {
                try {
                    $availability = $this->client->get_availability(
                        $mapping->pms_property_id,
                        $date_from,
                        $date_to
                    );
                    
                    // Update product stock based on availability
                    $this->update_product_availability($mapping->wc_product_id, $availability);
                    
                    $results['success']++;
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = $e->getMessage();
                    Logger::error($this->provider_code, 'availability_sync', $e->getMessage(), array(
                        'property_id' => $mapping->pms_property_id
                    ));
                }
            }
            
            Logger::success($this->provider_code, 'availability_sync', 'Availability synced', $results);
            return $results;
        } catch (\Exception $e) {
            Logger::error($this->provider_code, 'availability_sync', $e->getMessage());
            return array('error' => $e->getMessage());
        }
    }
    
    /**
     * Sync bookings
     *
     * @return array Sync results
     */
    public function sync_bookings() {
        try {
            $results = array(
                'success' => 0,
                'failed' => 0,
                'errors' => array()
            );
            
            $date_from = date('Y-m-d', strtotime('-30 days'));
            $date_to = date('Y-m-d', strtotime('+1 year'));
            
            $reservations = $this->client->get_reservations($date_from, $date_to);
            
            if (isset($reservations['Reservations']['Reservation'])) {
                $reservation_list = $reservations['Reservations']['Reservation'];
                
                // Handle single reservation response
                if (!isset($reservation_list[0])) {
                    $reservation_list = array($reservation_list);
                }
                
                foreach ($reservation_list as $reservation) {
                    try {
                        $result = $this->pull_booking($reservation['ID']);
                        
                        if (isset($result['error'])) {
                            $results['failed']++;
                            $results['errors'][] = $result['error'];
                        } else {
                            $results['success']++;
                        }
                    } catch (\Exception $e) {
                        $results['failed']++;
                        $results['errors'][] = $e->getMessage();
                    }
                }
            }
            
            Logger::success($this->provider_code, 'booking_sync', 'Bookings synced', $results);
            return $results;
        } catch (\Exception $e) {
            Logger::error($this->provider_code, 'booking_sync', $e->getMessage());
            return array('error' => $e->getMessage());
        }
    }
    
    /**
     * Push booking to PMS
     *
     * @param int $order_id WooCommerce order ID
     * @return array Result
     */
    public function push_booking($order_id) {
        try {
            $order = wc_get_order($order_id);
            
            if (!$order) {
                throw new \Exception('Order not found');
            }
            
            // Get booking data from order
            $booking_data = $this->map_order_to_booking($order);
            
            // Create reservation in PMS
            $result = $this->client->create_reservation($booking_data);
            
            // Update order meta with PMS booking ID
            if (isset($result['ReservationID'])) {
                $order->update_meta_data('_rental_sync_pms_booking_id', $result['ReservationID']);
                $order->save();
                
                Logger::success($this->provider_code, 'booking_push', 'Booking pushed to PMS', array(
                    'order_id' => $order_id,
                    'booking_id' => $result['ReservationID']
                ));
            }
            
            return $result;
        } catch (\Exception $e) {
            Logger::error($this->provider_code, 'booking_push', $e->getMessage(), array('order_id' => $order_id));
            return array('error' => $e->getMessage());
        }
    }
    
    /**
     * Pull booking from PMS
     *
     * @param string $booking_id PMS booking ID
     * @return array Result
     */
    public function pull_booking($booking_id) {
        try {
            // Check if booking already exists
            if ($this->booking_exists($booking_id)) {
                return array('message' => 'Booking already exists');
            }
            
            // In a real implementation, we would fetch the specific booking details
            // For now, we'll use the booking data from the sync
            $booking_data = array(
                'id' => $booking_id,
                'property_id' => '',
                'check_in' => '',
                'check_out' => '',
                'total' => 0,
                'guest' => array()
            );
            
            $order_id = WooCommerceIntegration::create_order_from_booking($booking_data, $this->provider_code);
            
            return array('order_id' => $order_id);
        } catch (\Exception $e) {
            Logger::error($this->provider_code, 'booking_pull', $e->getMessage(), array('booking_id' => $booking_id));
            return array('error' => $e->getMessage());
        }
    }
    
    /**
     * Handle push booking action
     *
     * @param int $order_id Order ID
     * @param string $provider Provider code
     */
    public function handle_push_booking($order_id, $provider) {
        if ($provider === $this->provider_code) {
            $this->push_booking($order_id);
        }
    }
    
    /**
     * Map property data from PMS format
     *
     * @param array $property_details Property details from PMS
     * @return array Mapped property data
     */
    private function map_property_data($property_details) {
        $property = $property_details['Property'] ?? array();
        
        return array(
            'id' => $property['ID'] ?? '',
            'name' => $property['Name'] ?? '',
            'description' => $property['Description'] ?? '',
            'price' => $property['StandardGuests'] ?? 0,
            'bedrooms' => $property['Bedrooms'] ?? 0,
            'bathrooms' => $property['Bathrooms'] ?? 0,
            'max_guests' => $property['MaxGuests'] ?? 0,
        );
    }
    
    /**
     * Map order to booking data
     *
     * @param \WC_Order $order WooCommerce order
     * @return array Booking data
     */
    private function map_order_to_booking($order) {
        $items = $order->get_items();
        $item = reset($items);
        $product_id = $item->get_product_id();
        $pms_property_id = get_post_meta($product_id, '_rental_sync_pms_property_id', true);
        
        return array(
            'property_id' => $pms_property_id,
            'check_in' => $order->get_meta('_rental_check_in'),
            'check_out' => $order->get_meta('_rental_check_out'),
            'guest_name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            'email' => $order->get_billing_email(),
            'total' => $order->get_total(),
        );
    }
    
    /**
     * Get existing product ID by PMS property ID
     *
     * @param string $property_id PMS property ID
     * @return int|false Product ID or false
     */
    private function get_existing_product_id($property_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'rental_sync_property_mappings';
        
        $product_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT wc_product_id FROM $table_name WHERE pms_provider = %s AND pms_property_id = %s",
                $this->provider_code,
                $property_id
            )
        );
        
        return $product_id ? (int) $product_id : false;
    }
    
    /**
     * Get property mappings
     *
     * @return array Property mappings
     */
    private function get_property_mappings() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'rental_sync_property_mappings';
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE pms_provider = %s AND sync_enabled = 1",
                $this->provider_code
            )
        );
    }
    
    /**
     * Check if booking exists
     *
     * @param string $booking_id PMS booking ID
     * @return bool True if exists, false otherwise
     */
    private function booking_exists($booking_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'rental_sync_booking_mappings';
        
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE pms_provider = %s AND pms_booking_id = %s",
                $this->provider_code,
                $booking_id
            )
        );
        
        return $exists > 0;
    }
    
    /**
     * Update product availability
     *
     * @param int $product_id Product ID
     * @param array $availability Availability data
     */
    private function update_product_availability($product_id, $availability) {
        // Update product meta with availability calendar
        update_post_meta($product_id, '_rental_sync_availability', $availability);
    }
}
