<?php
/**
 * Sync Scheduler for the Rental Sync Engine
 *
 * @package RentalSyncEngine\Core
 */

namespace RentalSyncEngine\Core;

/**
 * Class SyncScheduler
 */
class SyncScheduler {
    
    /**
     * Initialize sync scheduler
     */
    public static function init() {
        add_action('rental_sync_engine_hourly_sync', array(__CLASS__, 'run_scheduled_sync'));
        add_action('wp_ajax_rental_sync_manual_trigger', array(__CLASS__, 'handle_manual_sync'));
    }
    
    /**
     * Run scheduled sync for all enabled providers
     */
    public static function run_scheduled_sync() {
        $providers = array('ru', 'or', 'ul', 'ha');
        
        foreach ($providers as $provider) {
            if (Settings::is_provider_enabled($provider)) {
                self::sync_provider($provider);
            }
        }
        
        // Cleanup old logs
        $retention_days = Settings::get('rental_sync_engine_log_retention_days', 30);
        Logger::cleanup_old_logs($retention_days);
    }
    
    /**
     * Sync a specific provider
     *
     * @param string $provider Provider code (ru, or, ul, ha)
     * @param array $options Sync options
     * @return array Sync results
     */
    public static function sync_provider($provider, $options = array()) {
        $defaults = array(
            'sync_properties' => true,
            'sync_availability' => true,
            'sync_bookings' => true,
        );
        
        $options = wp_parse_args($options, $defaults);
        $results = array();
        
        try {
            switch ($provider) {
                case 'ru':
                    $handler = \RentalSyncEngine\PMS\RentalsUnited\Handler::get_instance();
                    break;
                case 'or':
                    $handler = \RentalSyncEngine\PMS\OwnerRez\Handler::get_instance();
                    break;
                case 'ul':
                    $handler = \RentalSyncEngine\PMS\Uplisting\Handler::get_instance();
                    break;
                case 'ha':
                    $handler = \RentalSyncEngine\PMS\Hostaway\Handler::get_instance();
                    break;
                default:
                    throw new \Exception('Unknown provider: ' . $provider);
            }
            
            if ($options['sync_properties']) {
                $results['properties'] = $handler->sync_properties();
            }
            
            if ($options['sync_availability']) {
                $results['availability'] = $handler->sync_availability();
            }
            
            if ($options['sync_bookings']) {
                $results['bookings'] = $handler->sync_bookings();
            }
            
            return $results;
        } catch (\Exception $e) {
            Logger::error($provider, 'scheduled_sync', $e->getMessage(), array(
                'trace' => $e->getTraceAsString()
            ));
            return array('error' => $e->getMessage());
        }
    }
    
    /**
     * Handle manual sync AJAX request
     */
    public static function handle_manual_sync() {
        check_ajax_referer('rental-sync-engine-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'), 403);
        }
        
        $provider = isset($_POST['provider']) ? sanitize_text_field($_POST['provider']) : '';
        $sync_type = isset($_POST['sync_type']) ? sanitize_text_field($_POST['sync_type']) : 'all';
        
        if (empty($provider)) {
            wp_send_json_error(array('message' => 'Provider is required'), 400);
        }
        
        $options = array(
            'sync_properties' => in_array($sync_type, array('all', 'properties')),
            'sync_availability' => in_array($sync_type, array('all', 'availability')),
            'sync_bookings' => in_array($sync_type, array('all', 'bookings')),
        );
        
        $results = self::sync_provider($provider, $options);
        
        if (isset($results['error'])) {
            wp_send_json_error($results);
        } else {
            wp_send_json_success($results);
        }
    }
}
