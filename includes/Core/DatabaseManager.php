<?php
/**
 * Database Manager Class
 *
 * @package RentalSyncEngine\Core
 */

namespace RentalSyncEngine\Core;

/**
 * Database Manager Class
 * Handles database operations for listings and bookings
 */
class DatabaseManager {
    /**
     * Save or update a listing
     *
     * @param array $listing_data Listing data
     * @return int|false Listing ID or false on failure
     */
    public function save_listing($listing_data) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'rental_sync_listings';
        $now = current_time('mysql');

        $data = array(
            'pms_platform' => sanitize_text_field($listing_data['pms_platform']),
            'pms_listing_id' => sanitize_text_field($listing_data['pms_listing_id']),
            'wc_product_id' => isset($listing_data['wc_product_id']) ? absint($listing_data['wc_product_id']) : null,
            'listing_data' => wp_json_encode($listing_data['listing_data']),
            'sync_status' => sanitize_text_field($listing_data['sync_status'] ?? 'synced'),
            'last_synced' => $now,
            'updated_at' => $now,
        );

        // Check if listing already exists
        $existing = $this->get_listing_by_pms_id(
            $listing_data['pms_platform'],
            $listing_data['pms_listing_id']
        );

        if ($existing) {
            // Update existing listing
            $wpdb->update(
                $table_name,
                $data,
                array('id' => $existing['id']),
                array('%s', '%s', '%d', '%s', '%s', '%s', '%s'),
                array('%d')
            );
            return $existing['id'];
        } else {
            // Insert new listing
            $data['created_at'] = $now;
            $wpdb->insert(
                $table_name,
                $data,
                array('%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s')
            );
            return $wpdb->insert_id;
        }
    }

    /**
     * Get a listing by PMS ID
     *
     * @param string $platform PMS platform
     * @param string $pms_listing_id PMS listing ID
     * @return array|null
     */
    public function get_listing_by_pms_id($platform, $pms_listing_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'rental_sync_listings';
        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE pms_platform = %s AND pms_listing_id = %s",
                $platform,
                $pms_listing_id
            ),
            ARRAY_A
        );

        return $result;
    }

    /**
     * Get all listings
     *
     * @param array $args Query arguments
     * @return array
     */
    public function get_listings($args = array()) {
        global $wpdb;

        $defaults = array(
            'limit' => 100,
            'offset' => 0,
            'pms_platform' => null,
            'sync_status' => null,
        );

        $args = wp_parse_args($args, $defaults);
        $table_name = $wpdb->prefix . 'rental_sync_listings';

        $where = array('1=1');
        if ($args['pms_platform']) {
            $where[] = $wpdb->prepare('pms_platform = %s', $args['pms_platform']);
        }
        if ($args['sync_status']) {
            $where[] = $wpdb->prepare('sync_status = %s', $args['sync_status']);
        }

        $where_clause = implode(' AND ', $where);

        $query = $wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE {$where_clause} ORDER BY updated_at DESC LIMIT %d OFFSET %d",
            $args['limit'],
            $args['offset']
        );

        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * Save or update a booking
     *
     * @param array $booking_data Booking data
     * @return int|false Booking ID or false on failure
     */
    public function save_booking($booking_data) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'rental_sync_bookings';
        $now = current_time('mysql');

        $data = array(
            'pms_platform' => sanitize_text_field($booking_data['pms_platform']),
            'pms_booking_id' => sanitize_text_field($booking_data['pms_booking_id']),
            'pms_listing_id' => sanitize_text_field($booking_data['pms_listing_id']),
            'wc_order_id' => isset($booking_data['wc_order_id']) ? absint($booking_data['wc_order_id']) : null,
            'booking_data' => wp_json_encode($booking_data['booking_data']),
            'booking_status' => sanitize_text_field($booking_data['booking_status']),
            'check_in_date' => sanitize_text_field($booking_data['check_in_date']),
            'check_out_date' => sanitize_text_field($booking_data['check_out_date']),
            'guest_name' => sanitize_text_field($booking_data['guest_name'] ?? ''),
            'guest_email' => sanitize_email($booking_data['guest_email'] ?? ''),
            'total_amount' => isset($booking_data['total_amount']) ? floatval($booking_data['total_amount']) : 0,
            'last_synced' => $now,
            'updated_at' => $now,
        );

        // Check if booking already exists
        $existing = $this->get_booking_by_pms_id(
            $booking_data['pms_platform'],
            $booking_data['pms_booking_id']
        );

        if ($existing) {
            // Update existing booking
            $wpdb->update(
                $table_name,
                $data,
                array('id' => $existing['id']),
                array('%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s'),
                array('%d')
            );
            return $existing['id'];
        } else {
            // Insert new booking
            $data['created_at'] = $now;
            $wpdb->insert(
                $table_name,
                $data,
                array('%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s')
            );
            return $wpdb->insert_id;
        }
    }

    /**
     * Get a booking by PMS ID
     *
     * @param string $platform PMS platform
     * @param string $pms_booking_id PMS booking ID
     * @return array|null
     */
    public function get_booking_by_pms_id($platform, $pms_booking_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'rental_sync_bookings';
        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE pms_platform = %s AND pms_booking_id = %s",
                $platform,
                $pms_booking_id
            ),
            ARRAY_A
        );

        return $result;
    }

    /**
     * Get all bookings
     *
     * @param array $args Query arguments
     * @return array
     */
    public function get_bookings($args = array()) {
        global $wpdb;

        $defaults = array(
            'limit' => 100,
            'offset' => 0,
            'pms_platform' => null,
            'booking_status' => null,
        );

        $args = wp_parse_args($args, $defaults);
        $table_name = $wpdb->prefix . 'rental_sync_bookings';

        $where = array('1=1');
        if ($args['pms_platform']) {
            $where[] = $wpdb->prepare('pms_platform = %s', $args['pms_platform']);
        }
        if ($args['booking_status']) {
            $where[] = $wpdb->prepare('booking_status = %s', $args['booking_status']);
        }

        $where_clause = implode(' AND ', $where);

        $query = $wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE {$where_clause} ORDER BY check_in_date DESC LIMIT %d OFFSET %d",
            $args['limit'],
            $args['offset']
        );

        return $wpdb->get_results($query, ARRAY_A);
    }
}
