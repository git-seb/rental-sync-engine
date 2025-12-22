<?php
/**
 * WooCommerce Order Manager
 *
 * @package RentalSyncEngine\WooCommerce
 */

namespace RentalSyncEngine\WooCommerce;

use RentalSyncEngine\Core\Logger;

/**
 * Order Manager Class
 * Handles WooCommerce order creation and product management
 */
class OrderManager {
    /**
     * Logger instance
     *
     * @var Logger
     */
    private $logger;

    /**
     * Constructor
     *
     * @param Logger $logger Logger instance
     */
    public function __construct($logger) {
        $this->logger = $logger;
        $this->init_hooks();
    }

    /**
     * Initialize WooCommerce hooks
     */
    private function init_hooks() {
        // Hook into order creation to push to PMS
        add_action('woocommerce_new_order', array($this, 'on_order_created'), 10, 1);
        add_action('woocommerce_order_status_cancelled', array($this, 'on_order_cancelled'), 10, 1);
    }

    /**
     * Create or update a WooCommerce product from listing data
     *
     * @param array  $listing Listing data
     * @param string $platform Platform name
     * @return int|false Product ID or false on failure
     */
    public function create_or_update_product($listing, $platform) {
        try {
            // Check if product already exists
            $existing_products = get_posts(array(
                'post_type' => 'product',
                'meta_query' => array(
                    array(
                        'key' => '_rental_sync_pms_platform',
                        'value' => $platform,
                    ),
                    array(
                        'key' => '_rental_sync_listing_id',
                        'value' => $listing['id'],
                    ),
                ),
                'posts_per_page' => 1,
            ));

            if (!empty($existing_products)) {
                $product_id = $existing_products[0]->ID;
                $this->update_product($product_id, $listing);
                return $product_id;
            }

            // Create new product
            $product = new \WC_Product();
            $product->set_name($listing['name']);
            $product->set_description($listing['description']);
            $product->set_status('publish');
            $product->set_catalog_visibility('visible');
            $product->set_sold_individually(true);

            // Set pricing if available
            if (isset($listing['pricing']['base_price'])) {
                $product->set_regular_price($listing['pricing']['base_price']);
            }

            $product_id = $product->save();

            // Save custom meta data
            update_post_meta($product_id, '_rental_sync_pms_platform', $platform);
            update_post_meta($product_id, '_rental_sync_listing_id', $listing['id']);
            update_post_meta($product_id, '_rental_sync_listing_data', wp_json_encode($listing));

            // Set product images
            if (!empty($listing['images'])) {
                $this->set_product_images($product_id, $listing['images']);
            }

            // Add property details as attributes
            $this->set_product_attributes($product_id, array(
                'bedrooms' => $listing['bedrooms'],
                'bathrooms' => $listing['bathrooms'],
                'max_guests' => $listing['max_guests'],
            ));

            $this->logger->info(
                sprintf('Created product %d for listing %s', $product_id, $listing['id']),
                'woocommerce',
                array('product_id' => $product_id, 'listing_id' => $listing['id'])
            );

            return $product_id;

        } catch (\Exception $e) {
            $this->logger->error(
                sprintf('Failed to create product: %s', $e->getMessage()),
                'woocommerce'
            );
            return false;
        }
    }

    /**
     * Update an existing product
     *
     * @param int   $product_id Product ID
     * @param array $listing Listing data
     */
    private function update_product($product_id, $listing) {
        $product = wc_get_product($product_id);
        if (!$product) {
            return;
        }

        $product->set_name($listing['name']);
        $product->set_description($listing['description']);

        if (isset($listing['pricing']['base_price'])) {
            $product->set_regular_price($listing['pricing']['base_price']);
        }

        $product->save();

        update_post_meta($product_id, '_rental_sync_listing_data', wp_json_encode($listing));
    }

    /**
     * Set product images
     *
     * @param int   $product_id Product ID
     * @param array $images Array of image URLs
     */
    private function set_product_images($product_id, $images) {
        if (empty($images)) {
            return;
        }

        $image_ids = array();
        foreach ($images as $index => $image_url) {
            $attachment_id = $this->upload_image_from_url($image_url, $product_id);
            if ($attachment_id) {
                $image_ids[] = $attachment_id;
            }
        }

        if (!empty($image_ids)) {
            // Set first image as featured
            set_post_thumbnail($product_id, $image_ids[0]);

            // Set remaining images in gallery
            if (count($image_ids) > 1) {
                update_post_meta($product_id, '_product_image_gallery', implode(',', array_slice($image_ids, 1)));
            }
        }
    }

    /**
     * Upload image from URL
     *
     * @param string $image_url Image URL
     * @param int    $product_id Product ID
     * @return int|false Attachment ID or false on failure
     */
    private function upload_image_from_url($image_url, $product_id) {
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $tmp = download_url($image_url);
        if (is_wp_error($tmp)) {
            return false;
        }

        $file_array = array(
            'name' => basename($image_url),
            'tmp_name' => $tmp,
        );

        $attachment_id = media_handle_sideload($file_array, $product_id);

        if (is_wp_error($attachment_id)) {
            @unlink($file_array['tmp_name']);
            return false;
        }

        return $attachment_id;
    }

    /**
     * Set product attributes
     *
     * @param int   $product_id Product ID
     * @param array $attributes Attributes array
     */
    private function set_product_attributes($product_id, $attributes) {
        $product_attributes = array();

        foreach ($attributes as $key => $value) {
            $attribute = new \WC_Product_Attribute();
            $attribute->set_name(ucfirst(str_replace('_', ' ', $key)));
            $attribute->set_options(array($value));
            $attribute->set_visible(true);
            $product_attributes[] = $attribute;
        }

        $product = wc_get_product($product_id);
        $product->set_attributes($product_attributes);
        $product->save();
    }

    /**
     * Update product availability based on calendar data
     *
     * @param int   $product_id Product ID
     * @param array $availability Availability data
     */
    public function update_product_availability($product_id, $availability) {
        // Update stock status based on availability
        $product = wc_get_product($product_id);
        if (!$product) {
            return;
        }

        // Store availability data as meta
        update_post_meta($product_id, '_rental_sync_availability', wp_json_encode($availability));

        // Check if product is available today
        $today = date('Y-m-d');
        $is_available = $this->is_date_available($availability, $today);

        $product->set_stock_status($is_available ? 'instock' : 'outofstock');
        $product->save();
    }

    /**
     * Check if a date is available
     *
     * @param array  $availability Availability data
     * @param string $date Date to check
     * @return bool
     */
    private function is_date_available($availability, $date) {
        // Simple availability check - can be enhanced based on PMS format
        if (isset($availability['available_dates'])) {
            return in_array($date, $availability['available_dates']);
        }
        return true;
    }

    /**
     * Create or update a WooCommerce order from booking data
     *
     * @param array  $booking Booking data
     * @param string $platform Platform name
     * @return int|false Order ID or false on failure
     */
    public function create_or_update_order($booking, $platform) {
        try {
            // Find the product for this listing
            $products = get_posts(array(
                'post_type' => 'product',
                'meta_query' => array(
                    array(
                        'key' => '_rental_sync_pms_platform',
                        'value' => $platform,
                    ),
                    array(
                        'key' => '_rental_sync_listing_id',
                        'value' => $booking['listing_id'],
                    ),
                ),
                'posts_per_page' => 1,
            ));

            if (empty($products)) {
                throw new \Exception(sprintf('Product not found for listing %s', $booking['listing_id']));
            }

            $product_id = $products[0]->ID;

            // Create order
            $order = wc_create_order();
            $order->add_product(wc_get_product($product_id), 1);

            // Set customer information
            $order->set_billing_first_name($booking['guest_name']);
            $order->set_billing_email($booking['guest_email']);

            // Set order total
            $order->set_total($booking['total_amount']);

            // Save booking metadata
            $order->update_meta_data('_rental_sync_pms_platform', $platform);
            $order->update_meta_data('_rental_sync_booking_id', $booking['id']);
            $order->update_meta_data('_rental_sync_check_in', $booking['check_in']);
            $order->update_meta_data('_rental_sync_check_out', $booking['check_out']);
            $order->update_meta_data('_rental_sync_booking_data', wp_json_encode($booking));

            // Set order status based on booking status
            $status = $this->map_booking_status($booking['status']);
            $order->set_status($status);

            $order->save();

            $this->logger->info(
                sprintf('Created order %d for booking %s', $order->get_id(), $booking['id']),
                'woocommerce',
                array('order_id' => $order->get_id(), 'booking_id' => $booking['id'])
            );

            return $order->get_id();

        } catch (\Exception $e) {
            $this->logger->error(
                sprintf('Failed to create order: %s', $e->getMessage()),
                'woocommerce'
            );
            return false;
        }
    }

    /**
     * Map booking status to WooCommerce order status
     *
     * @param string $booking_status Booking status
     * @return string WooCommerce order status
     */
    private function map_booking_status($booking_status) {
        $status_map = array(
            'confirmed' => 'processing',
            'pending' => 'pending',
            'cancelled' => 'cancelled',
            'completed' => 'completed',
        );

        return $status_map[$booking_status] ?? 'processing';
    }

    /**
     * Prepare booking data from WooCommerce order
     *
     * @param \WC_Order $order WooCommerce order
     * @return array Booking data
     */
    public function prepare_booking_data_from_order($order) {
        $items = $order->get_items();
        $first_item = reset($items);
        $product_id = $first_item->get_product_id();

        $platform = get_post_meta($product_id, '_rental_sync_pms_platform', true);
        $listing_id = get_post_meta($product_id, '_rental_sync_listing_id', true);

        return array(
            'listing_id' => $listing_id,
            'check_in' => $order->get_meta('_rental_sync_check_in'),
            'check_out' => $order->get_meta('_rental_sync_check_out'),
            'guest_name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            'guest_email' => $order->get_billing_email(),
            'total_amount' => $order->get_total(),
            'currency' => $order->get_currency(),
            'number_of_guests' => 1,
        );
    }

    /**
     * Handle order creation
     *
     * @param int $order_id Order ID
     */
    public function on_order_created($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // Check if this is a rental sync order (already synced from PMS)
        if ($order->get_meta('_rental_sync_booking_id')) {
            return;
        }

        // This is a new order created in WooCommerce - push to PMS
        $items = $order->get_items();
        if (empty($items)) {
            return;
        }

        $first_item = reset($items);
        $product_id = $first_item->get_product_id();
        $platform = get_post_meta($product_id, '_rental_sync_pms_platform', true);

        if ($platform) {
            // Queue push to PMS (will be handled by sync manager)
            do_action('rental_sync_engine_push_booking', $order_id, $platform);
        }
    }

    /**
     * Handle order cancellation
     *
     * @param int $order_id Order ID
     */
    public function on_order_cancelled($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $platform = $order->get_meta('_rental_sync_pms_platform');
        $booking_id = $order->get_meta('_rental_sync_booking_id');

        if ($platform && $booking_id) {
            // Queue cancellation to PMS
            do_action('rental_sync_engine_cancel_booking', $booking_id, $platform);
        }
    }
}
