<?php
/**
 * Installer Class
 *
 * @package RentalSyncEngine\Core
 */

namespace RentalSyncEngine\Core;

/**
 * Installer Class
 * Handles plugin activation, deactivation, and database setup
 */
class Installer {
    /**
     * Run on plugin activation
     */
    public static function activate() {
        self::create_tables();
        self::create_default_options();
        self::schedule_cron_jobs();
    }

    /**
     * Run on plugin deactivation
     */
    public static function deactivate() {
        self::clear_cron_jobs();
    }

    /**
     * Create custom database tables
     */
    private static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Logs table
        $logs_table = $wpdb->prefix . 'rental_sync_logs';
        $logs_sql = "CREATE TABLE IF NOT EXISTS {$logs_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            log_type varchar(50) NOT NULL,
            log_level varchar(20) NOT NULL,
            message text NOT NULL,
            context longtext,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY log_type (log_type),
            KEY log_level (log_level),
            KEY created_at (created_at)
        ) {$charset_collate};";

        // Listings table
        $listings_table = $wpdb->prefix . 'rental_sync_listings';
        $listings_sql = "CREATE TABLE IF NOT EXISTS {$listings_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            pms_platform varchar(50) NOT NULL,
            pms_listing_id varchar(255) NOT NULL,
            wc_product_id bigint(20) unsigned,
            listing_data longtext,
            last_synced datetime,
            sync_status varchar(20) DEFAULT 'pending',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY pms_listing (pms_platform, pms_listing_id),
            KEY wc_product_id (wc_product_id),
            KEY sync_status (sync_status)
        ) {$charset_collate};";

        // Bookings table
        $bookings_table = $wpdb->prefix . 'rental_sync_bookings';
        $bookings_sql = "CREATE TABLE IF NOT EXISTS {$bookings_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            pms_platform varchar(50) NOT NULL,
            pms_booking_id varchar(255) NOT NULL,
            pms_listing_id varchar(255) NOT NULL,
            wc_order_id bigint(20) unsigned,
            booking_data longtext,
            booking_status varchar(50) NOT NULL,
            check_in_date date NOT NULL,
            check_out_date date NOT NULL,
            guest_name varchar(255),
            guest_email varchar(255),
            total_amount decimal(10,2),
            last_synced datetime,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY pms_booking (pms_platform, pms_booking_id),
            KEY wc_order_id (wc_order_id),
            KEY booking_status (booking_status),
            KEY check_in_date (check_in_date)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($logs_sql);
        dbDelta($listings_sql);
        dbDelta($bookings_sql);
    }

    /**
     * Create default plugin options
     */
    private static function create_default_options() {
        $default_settings = array(
            'sync_frequency' => 'hourly',
            'enable_real_time_sync' => true,
            'enable_webhooks' => true,
            'log_level' => 'info',
        );

        add_option('rental_sync_engine_settings', $default_settings);
        add_option('rental_sync_engine_version', RENTAL_SYNC_ENGINE_VERSION);
        add_option('rental_sync_engine_pms_credentials', array());
    }

    /**
     * Schedule cron jobs
     */
    private static function schedule_cron_jobs() {
        // Schedule listing sync
        if (!wp_next_scheduled('rental_sync_engine_sync_listings')) {
            wp_schedule_event(time(), 'hourly', 'rental_sync_engine_sync_listings');
        }

        // Schedule availability sync
        if (!wp_next_scheduled('rental_sync_engine_sync_availability')) {
            wp_schedule_event(time(), 'hourly', 'rental_sync_engine_sync_availability');
        }

        // Schedule booking sync
        if (!wp_next_scheduled('rental_sync_engine_sync_bookings')) {
            wp_schedule_event(time(), 'hourly', 'rental_sync_engine_sync_bookings');
        }
    }

    /**
     * Clear scheduled cron jobs
     */
    private static function clear_cron_jobs() {
        wp_clear_scheduled_hook('rental_sync_engine_sync_listings');
        wp_clear_scheduled_hook('rental_sync_engine_sync_availability');
        wp_clear_scheduled_hook('rental_sync_engine_sync_bookings');
    }
}
