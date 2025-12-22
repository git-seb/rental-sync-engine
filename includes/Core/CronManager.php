<?php
/**
 * Cron Manager Class
 *
 * @package RentalSyncEngine\Core
 */

namespace RentalSyncEngine\Core;

/**
 * Cron Manager Class
 * Manages scheduled sync operations
 */
class CronManager {
    /**
     * Sync Manager instance
     *
     * @var SyncManager
     */
    private $sync_manager;

    /**
     * Logger instance
     *
     * @var Logger
     */
    private $logger;

    /**
     * Constructor
     *
     * @param SyncManager $sync_manager Sync Manager instance
     * @param Logger      $logger Logger instance
     */
    public function __construct($sync_manager, $logger) {
        $this->sync_manager = $sync_manager;
        $this->logger = $logger;
    }

    /**
     * Register cron schedules
     */
    public function register_schedules() {
        // Hook sync functions to cron events
        add_action('rental_sync_engine_sync_listings', array($this, 'sync_listings'));
        add_action('rental_sync_engine_sync_availability', array($this, 'sync_availability'));
        add_action('rental_sync_engine_sync_bookings', array($this, 'sync_bookings'));
    }

    /**
     * Add custom cron intervals
     *
     * @param array $schedules Existing schedules
     * @return array Modified schedules
     */
    public function add_custom_intervals($schedules) {
        // Add 15 minute interval
        $schedules['fifteen_minutes'] = array(
            'interval' => 900,
            'display' => __('Every 15 Minutes', 'rental-sync-engine'),
        );

        // Add 30 minute interval
        $schedules['thirty_minutes'] = array(
            'interval' => 1800,
            'display' => __('Every 30 Minutes', 'rental-sync-engine'),
        );

        // Add 6 hour interval
        $schedules['six_hours'] = array(
            'interval' => 21600,
            'display' => __('Every 6 Hours', 'rental-sync-engine'),
        );

        return $schedules;
    }

    /**
     * Sync listings via cron
     */
    public function sync_listings() {
        $this->logger->info('Running scheduled listing sync', 'cron');
        $this->sync_manager->sync_all_listings();
    }

    /**
     * Sync availability via cron
     */
    public function sync_availability() {
        $this->logger->info('Running scheduled availability sync', 'cron');
        $this->sync_manager->sync_all_availability();
    }

    /**
     * Sync bookings via cron
     */
    public function sync_bookings() {
        $this->logger->info('Running scheduled booking sync', 'cron');
        $this->sync_manager->sync_all_bookings();
    }

    /**
     * Reschedule cron jobs based on settings
     *
     * @param string $frequency Sync frequency
     */
    public function reschedule_jobs($frequency) {
        // Clear existing schedules
        wp_clear_scheduled_hook('rental_sync_engine_sync_listings');
        wp_clear_scheduled_hook('rental_sync_engine_sync_availability');
        wp_clear_scheduled_hook('rental_sync_engine_sync_bookings');

        // Schedule with new frequency
        if (!wp_next_scheduled('rental_sync_engine_sync_listings')) {
            wp_schedule_event(time(), $frequency, 'rental_sync_engine_sync_listings');
        }

        if (!wp_next_scheduled('rental_sync_engine_sync_availability')) {
            wp_schedule_event(time(), $frequency, 'rental_sync_engine_sync_availability');
        }

        if (!wp_next_scheduled('rental_sync_engine_sync_bookings')) {
            wp_schedule_event(time(), $frequency, 'rental_sync_engine_sync_bookings');
        }

        $this->logger->info(
            sprintf('Rescheduled cron jobs with frequency: %s', $frequency),
            'cron'
        );
    }
}
