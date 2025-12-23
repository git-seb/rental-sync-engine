<?php
/**
 * PMS Handler Interface
 *
 * @package RentalSyncEngine\Core
 */

namespace RentalSyncEngine\Core;

/**
 * Interface PMSHandlerInterface
 */
interface PMSHandlerInterface {
    
    /**
     * Get handler instance
     *
     * @return mixed Handler instance
     */
    public static function get_instance();
    
    /**
     * Initialize the handler
     */
    public static function init();
    
    /**
     * Sync properties from PMS to WooCommerce
     *
     * @return array Sync results
     */
    public function sync_properties();
    
    /**
     * Sync availability from PMS to WooCommerce
     *
     * @return array Sync results
     */
    public function sync_availability();
    
    /**
     * Sync bookings between PMS and WooCommerce
     *
     * @return array Sync results
     */
    public function sync_bookings();
    
    /**
     * Push a booking to PMS
     *
     * @param int $order_id WooCommerce order ID
     * @return array Result
     */
    public function push_booking($order_id);
    
    /**
     * Pull a booking from PMS
     *
     * @param string $booking_id PMS booking ID
     * @return array Result
     */
    public function pull_booking($booking_id);
}
