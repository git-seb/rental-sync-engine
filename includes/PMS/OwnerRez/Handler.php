<?php
/**
 * OwnerRez Handler
 *
 * @package RentalSyncEngine\PMS\OwnerRez
 */

namespace RentalSyncEngine\PMS\OwnerRez;

use RentalSyncEngine\Core\PMSHandlerInterface;
use RentalSyncEngine\Core\Logger;
use RentalSyncEngine\Integration\WooCommerceIntegration;

/**
 * Class Handler
 */
class Handler implements PMSHandlerInterface {
    
    private static $instance = null;
    private $client;
    private $provider_code = 'or';
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public static function init() {
        $instance = self::get_instance();
        add_action('rental_sync_engine_push_booking', array($instance, 'handle_push_booking'), 10, 2);
    }
    
    private function __construct() {
        $this->client = new Client();
    }
    
    public function sync_properties() {
        try {
            $response = $this->client->get_properties();
            $properties = $response['items'] ?? array();
            
            $results = array('success' => 0, 'failed' => 0, 'errors' => array());
            
            foreach ($properties as $property) {
                try {
                    $property_data = $this->map_property_data($property);
                    $product_id = $this->get_existing_product_id($property['id']);
                    
                    if ($product_id) {
                        WooCommerceIntegration::update_product_from_property($product_id, $property_data, $this->provider_code);
                    } else {
                        WooCommerceIntegration::create_product_from_property($property_data, $this->provider_code);
                    }
                    
                    $results['success']++;
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = $e->getMessage();
                }
            }
            
            Logger::success($this->provider_code, 'property_sync', 'Properties synced', $results);
            return $results;
        } catch (\Exception $e) {
            Logger::error($this->provider_code, 'property_sync', $e->getMessage());
            return array('error' => $e->getMessage());
        }
    }
    
    public function sync_availability() {
        try {
            $mappings = $this->get_property_mappings();
            $results = array('success' => 0, 'failed' => 0, 'errors' => array());
            
            $start_date = date('Y-m-d');
            $end_date = date('Y-m-d', strtotime('+1 year'));
            
            foreach ($mappings as $mapping) {
                try {
                    $availability = $this->client->get_availability(
                        $mapping->pms_property_id,
                        $start_date,
                        $end_date
                    );
                    
                    $product = wc_get_product($mapping->wc_product_id);
                    if ($product) {
                        $product->update_meta_data('_rental_sync_availability', $availability);
                        $product->save();
                    }
                    
                    $results['success']++;
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = $e->getMessage();
                }
            }
            
            Logger::success($this->provider_code, 'availability_sync', 'Availability synced', $results);
            return $results;
        } catch (\Exception $e) {
            Logger::error($this->provider_code, 'availability_sync', $e->getMessage());
            return array('error' => $e->getMessage());
        }
    }
    
    public function sync_bookings() {
        try {
            $response = $this->client->get_bookings(array(
                'from' => date('Y-m-d', strtotime('-30 days')),
                'to' => date('Y-m-d', strtotime('+1 year'))
            ));
            
            $bookings = $response['items'] ?? array();
            $results = array('success' => 0, 'failed' => 0, 'errors' => array());
            
            foreach ($bookings as $booking) {
                try {
                    if (!$this->booking_exists($booking['id'])) {
                        $booking_data = $this->map_booking_data($booking);
                        WooCommerceIntegration::create_order_from_booking($booking_data, $this->provider_code);
                        $results['success']++;
                    }
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = $e->getMessage();
                }
            }
            
            Logger::success($this->provider_code, 'booking_sync', 'Bookings synced', $results);
            return $results;
        } catch (\Exception $e) {
            Logger::error($this->provider_code, 'booking_sync', $e->getMessage());
            return array('error' => $e->getMessage());
        }
    }
    
    public function push_booking($order_id) {
        try {
            $order = wc_get_order($order_id);
            if (!$order) {
                throw new \Exception('Order not found');
            }
            
            $booking_data = $this->map_order_to_booking($order);
            $result = $this->client->create_booking($booking_data);
            
            if (isset($result['id'])) {
                $order->update_meta_data('_rental_sync_pms_booking_id', $result['id']);
                $order->save();
                
                Logger::success($this->provider_code, 'booking_push', 'Booking pushed', array(
                    'order_id' => $order_id,
                    'booking_id' => $result['id']
                ));
            }
            
            return $result;
        } catch (\Exception $e) {
            Logger::error($this->provider_code, 'booking_push', $e->getMessage(), array('order_id' => $order_id));
            return array('error' => $e->getMessage());
        }
    }
    
    public function pull_booking($booking_id) {
        try {
            if ($this->booking_exists($booking_id)) {
                return array('message' => 'Booking already exists');
            }
            
            $booking = $this->client->get_booking($booking_id);
            $booking_data = $this->map_booking_data($booking);
            $order_id = WooCommerceIntegration::create_order_from_booking($booking_data, $this->provider_code);
            
            return array('order_id' => $order_id);
        } catch (\Exception $e) {
            Logger::error($this->provider_code, 'booking_pull', $e->getMessage(), array('booking_id' => $booking_id));
            return array('error' => $e->getMessage());
        }
    }
    
    public function handle_push_booking($order_id, $provider) {
        if ($provider === $this->provider_code) {
            $this->push_booking($order_id);
        }
    }
    
    private function map_property_data($property) {
        return array(
            'id' => $property['id'] ?? '',
            'name' => $property['name'] ?? '',
            'description' => $property['description'] ?? '',
            'price' => $property['baseRate'] ?? 0,
            'bedrooms' => $property['bedrooms'] ?? 0,
            'bathrooms' => $property['bathrooms'] ?? 0,
            'max_guests' => $property['sleeps'] ?? 0,
        );
    }
    
    private function map_booking_data($booking) {
        return array(
            'id' => $booking['id'] ?? '',
            'property_id' => $booking['propertyId'] ?? '',
            'check_in' => $booking['arrival'] ?? '',
            'check_out' => $booking['departure'] ?? '',
            'total' => $booking['totalAmount'] ?? 0,
            'guest' => array(
                'first_name' => $booking['guestFirstName'] ?? '',
                'last_name' => $booking['guestLastName'] ?? '',
                'email' => $booking['guestEmail'] ?? '',
                'phone' => $booking['guestPhone'] ?? '',
            ),
        );
    }
    
    private function map_order_to_booking($order) {
        $items = $order->get_items();
        $item = reset($items);
        $product_id = $item->get_product_id();
        $pms_property_id = \RentalSyncEngine\Integration\WooCommerceIntegration::get_product_pms_property_id($product_id);
        
        return array(
            'propertyId' => $pms_property_id ?: '',
            'arrival' => $order->get_meta('_rental_check_in'),
            'departure' => $order->get_meta('_rental_check_out'),
            'guestFirstName' => $order->get_billing_first_name(),
            'guestLastName' => $order->get_billing_last_name(),
            'guestEmail' => $order->get_billing_email(),
            'totalAmount' => $order->get_total(),
        );
    }
    
    private function get_existing_product_id($property_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rental_sync_property_mappings';
        $product_id = $wpdb->get_var($wpdb->prepare(
            "SELECT wc_product_id FROM $table_name WHERE pms_provider = %s AND pms_property_id = %s",
            $this->provider_code, $property_id
        ));
        return $product_id ? (int) $product_id : false;
    }
    
    private function get_property_mappings() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rental_sync_property_mappings';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE pms_provider = %s AND sync_enabled = 1",
            $this->provider_code
        ));
    }
    
    private function booking_exists($booking_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rental_sync_booking_mappings';
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE pms_provider = %s AND pms_booking_id = %s",
            $this->provider_code, $booking_id
        ));
        return $exists > 0;
    }
}
