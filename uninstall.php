<?php
/**
 * Uninstall script for Rental Sync Engine
 * 
 * Cleans up plugin data when uninstalled
 */

// Exit if uninstall not called from WordPress
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Load the plugin file to get constants and autoloader
require_once plugin_dir_path(__FILE__) . 'rental-sync-engine.php';

// Delete plugin options
delete_option('rental_sync_engine_version');
delete_option('rental_sync_engine_settings');
delete_option('rental_sync_engine_pms_credentials');

// Delete all transients
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_rental_sync_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_rental_sync_%'");

// Drop custom database tables
$table_names = array(
    $wpdb->prefix . 'rental_sync_logs',
    $wpdb->prefix . 'rental_sync_bookings',
    $wpdb->prefix . 'rental_sync_listings',
);

foreach ($table_names as $table_name) {
    $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
}

// Clear scheduled cron events
$cron_hooks = array(
    'rental_sync_engine_sync_listings',
    'rental_sync_engine_sync_availability',
    'rental_sync_engine_sync_bookings',
);

foreach ($cron_hooks as $hook) {
    $timestamp = wp_next_scheduled($hook);
    if ($timestamp) {
        wp_unschedule_event($timestamp, $hook);
    }
}

// Clear all cron schedules
wp_clear_scheduled_hook('rental_sync_engine_sync_listings');
wp_clear_scheduled_hook('rental_sync_engine_sync_availability');
wp_clear_scheduled_hook('rental_sync_engine_sync_bookings');
