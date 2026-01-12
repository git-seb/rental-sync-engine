<?php
/**
 * WooCommerce Integration for the Rental Sync Engine
 *
 * @package RentalSyncEngine\Integration
 */

namespace RentalSyncEngine\Integration;

use RentalSyncEngine\Core\Logger;

/**
 * Class WooCommerceIntegration
 */
class WooCommerceIntegration {
    
    /**
     * Initialize WooCommerce integration
     */
    public static function init() {
        // Hook into WooCommerce order creation
        add_action('woocommerce_new_order', array(__CLASS__, 'handle_new_order'), 10, 1);
        add_action('woocommerce_order_status_changed', array(__CLASS__, 'handle_order_status_change'), 10, 4);
    }
    
    /**
     * Create a WooCommerce product from PMS property data
     *
     * @param array $property_data Property data from PMS
     * @param string $pms_provider PMS provider code
     * @return int|false Product ID on success, false on failure
     */
    public static function create_product_from_property($property_data, $pms_provider) {
        try {
            // Create product
            $product = new \WC_Product_Simple();
            $product->set_name($property_data['name']);
            $product->set_description($property_data['description'] ?? '');
            $product->set_regular_price($property_data['price'] ?? 0);
            $product->set_status('publish');
            $product->set_catalog_visibility('visible');
            
            // Set product meta
            $product->update_meta_data('_rental_sync_pms_provider', $pms_provider);
            $product->update_meta_data('_rental_sync_pms_property_id', $property_data['id']);
            $product->update_meta_data('_rental_sync_property_data', $property_data);
            
            // Add property details
            if (isset($property_data['bedrooms'])) {
                $product->update_meta_data('_rental_bedrooms', $property_data['bedrooms']);
            }
            if (isset($property_data['bathrooms'])) {
                $product->update_meta_data('_rental_bathrooms', $property_data['bathrooms']);
            }
            if (isset($property_data['max_guests'])) {
                $product->update_meta_data('_rental_max_guests', $property_data['max_guests']);
            }
            
            $product_id = $product->save();
            
            // Save property mapping
            self::save_property_mapping($pms_provider, $property_data['id'], $product_id);
            
            Logger::success($pms_provider, 'property_sync', 'Property created: ' . $property_data['name'], array(
                'product_id' => $product_id,
                'property_id' => $property_data['id']
            ));
            
            return $product_id;
        } catch (\Exception $e) {
            Logger::error($pms_provider, 'property_sync', 'Failed to create product: ' . $e->getMessage(), $property_data);
            return false;
        }
    }
    
    /**
     * Update a WooCommerce product from PMS property data
     *
     * @param int $product_id WooCommerce product ID
     * @param array $property_data Property data from PMS
     * @param string $pms_provider PMS provider code
     * @return bool True on success, false on failure
     */
    public static function update_product_from_property($product_id, $property_data, $pms_provider) {
        try {
            $product = wc_get_product($product_id);
            
            if (!$product) {
                return false;
            }
            
            $product->set_name($property_data['name']);
            $product->set_description($property_data['description'] ?? '');
            $product->set_regular_price($property_data['price'] ?? 0);
            
            // Update property data
            $product->update_meta_data('_rental_sync_property_data', $property_data);
            
            if (isset($property_data['bedrooms'])) {
                $product->update_meta_data('_rental_bedrooms', $property_data['bedrooms']);
            }
            if (isset($property_data['bathrooms'])) {
                $product->update_meta_data('_rental_bathrooms', $property_data['bathrooms']);
            }
            if (isset($property_data['max_guests'])) {
                $product->update_meta_data('_rental_max_guests', $property_data['max_guests']);
            }
            
            $product->save();
            
            Logger::success($pms_provider, 'property_sync', 'Property updated: ' . $property_data['name'], array(
                'product_id' => $product_id,
                'property_id' => $property_data['id']
            ));
            
            return true;
        } catch (\Exception $e) {
            Logger::error($pms_provider, 'property_sync', 'Failed to update product: ' . $e->getMessage(), $property_data);
            return false;
        }
    }
    
    /**
     * Create a WooCommerce order from PMS booking data
     *
     * @param array $booking_data Booking data from PMS
     * @param string $pms_provider PMS provider code
     * @return int|false Order ID on success, false on failure
     */
    public static function create_order_from_booking($booking_data, $pms_provider) {
        try {
            // Get product ID from property mapping
            $product_id = self::get_product_id_by_property($pms_provider, $booking_data['property_id']);
            
            if (!$product_id) {
                Logger::error($pms_provider, 'booking_sync', 'Product not found for property: ' . $booking_data['property_id'], $booking_data);
                return false;
            }
            
            // Create order
            $order = wc_create_order();
            $order->add_product(wc_get_product($product_id), 1);
            
            // Set order meta
            $order->update_meta_data('_rental_sync_pms_provider', $pms_provider);
            $order->update_meta_data('_rental_sync_pms_booking_id', $booking_data['id']);
            $order->update_meta_data('_rental_sync_booking_data', $booking_data);
            $order->update_meta_data('_rental_check_in', $booking_data['check_in']);
            $order->update_meta_data('_rental_check_out', $booking_data['check_out']);
            
            // Set customer info
            if (isset($booking_data['guest'])) {
                $order->set_billing_first_name($booking_data['guest']['first_name'] ?? '');
                $order->set_billing_last_name($booking_data['guest']['last_name'] ?? '');
                $order->set_billing_email($booking_data['guest']['email'] ?? '');
                $order->set_billing_phone($booking_data['guest']['phone'] ?? '');
            }
            
            // Set total
            $order->set_total($booking_data['total'] ?? 0);
            
            // Set order status
            $order->set_status('processing');
            
            $order->save();
            
            // Save booking mapping
            self::save_booking_mapping($pms_provider, $booking_data['id'], $order->get_id());
            
            Logger::success($pms_provider, 'booking_sync', 'Order created from booking: ' . $booking_data['id'], array(
                'order_id' => $order->get_id(),
                'booking_id' => $booking_data['id']
            ));
            
            return $order->get_id();
        } catch (\Exception $e) {
            Logger::error($pms_provider, 'booking_sync', 'Failed to create order: ' . $e->getMessage(), $booking_data);
            return false;
        }
    }
    
    /**
     * Handle new WooCommerce order
     *
     * @param int $order_id Order ID
     */
    public static function handle_new_order($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return;
        }
        
        // Check if order contains rental products
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $pms_provider = self::get_product_pms_provider($product_id);
            
            if (!empty($pms_provider)) {
                // Push booking to PMS
                do_action('rental_sync_engine_push_booking', $order_id, $pms_provider);
            }
        }
    }
    
    /**
     * Handle order status change
     *
     * @param int $order_id Order ID
     * @param string $old_status Old status
     * @param string $new_status New status
     * @param \WC_Order $order Order object
     */
    public static function handle_order_status_change($order_id, $old_status, $new_status, $order) {
        $pms_provider = $order->get_meta('_rental_sync_pms_provider');
        
        if (empty($pms_provider)) {
            return;
        }
        
        // Handle cancellations
        if ($new_status === 'cancelled') {
            do_action('rental_sync_engine_cancel_booking', $order_id, $pms_provider);
        }
    }
    
    /**
     * Save property mapping
     *
     * @param string $pms_provider PMS provider code
     * @param string $pms_property_id PMS property ID
     * @param int $wc_product_id WooCommerce product ID
     */
    private static function save_property_mapping($pms_provider, $pms_property_id, $wc_product_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'rental_sync_property_mappings';
        
        $wpdb->replace(
            $table_name,
            array(
                'pms_provider' => $pms_provider,
                'pms_property_id' => $pms_property_id,
                'wc_product_id' => $wc_product_id,
                'sync_enabled' => 1,
                'last_synced' => current_time('mysql')
            ),
            array('%s', '%s', '%d', '%d', '%s')
        );
    }
    
    /**
     * Save booking mapping
     *
     * @param string $pms_provider PMS provider code
     * @param string $pms_booking_id PMS booking ID
     * @param int $wc_order_id WooCommerce order ID
     */
    private static function save_booking_mapping($pms_provider, $pms_booking_id, $wc_order_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'rental_sync_booking_mappings';
        
        $wpdb->replace(
            $table_name,
            array(
                'pms_provider' => $pms_provider,
                'pms_booking_id' => $pms_booking_id,
                'wc_order_id' => $wc_order_id,
                'sync_status' => 'synced',
                'last_synced' => current_time('mysql')
            ),
            array('%s', '%s', '%d', '%s', '%s')
        );
    }
    
    /**
     * Get product ID by PMS property ID
     *
     * @param string $pms_provider PMS provider code
     * @param string $pms_property_id PMS property ID
     * @return int|false Product ID or false if not found
     */
    private static function get_product_id_by_property($pms_provider, $pms_property_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'rental_sync_property_mappings';
        
        $product_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT wc_product_id FROM $table_name WHERE pms_provider = %s AND pms_property_id = %s",
                $pms_provider,
                $pms_property_id
            )
        );
        
        return $product_id ? (int) $product_id : false;
    }
    
    /**
     * Get PMS provider from product
     *
     * @param int $product_id WooCommerce product ID
     * @return string|false PMS provider code or false if not found
     */
    public static function get_product_pms_provider($product_id) {
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return false;
        }
        
        return $product->get_meta('_rental_sync_pms_provider', true);
    }
    
    /**
     * Get PMS property ID from product
     *
     * @param int $product_id WooCommerce product ID
     * @return string|false PMS property ID or false if not found
     */
    public static function get_product_pms_property_id($product_id) {
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return false;
        }
        
        return $product->get_meta('_rental_sync_pms_property_id', true);
    }
}
