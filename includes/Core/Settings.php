<?php
/**
 * Settings class for the Rental Sync Engine
 *
 * @package RentalSyncEngine\Core
 */

namespace RentalSyncEngine\Core;

/**
 * Class Settings
 */
class Settings {
    
    /**
     * Initialize settings
     */
    public static function init() {
        add_action('admin_init', array(__CLASS__, 'register_settings'));
    }
    
    /**
     * Register plugin settings
     */
    public static function register_settings() {
        // General settings
        register_setting('rental_sync_engine_general', 'rental_sync_engine_enabled');
        register_setting('rental_sync_engine_general', 'rental_sync_engine_sync_frequency');
        register_setting('rental_sync_engine_general', 'rental_sync_engine_log_retention_days');
        register_setting('rental_sync_engine_general', 'rental_sync_engine_debug_mode');
        
        // Rentals United settings
        register_setting('rental_sync_engine_rentals_united', 'rental_sync_engine_ru_enabled');
        register_setting('rental_sync_engine_rentals_united', 'rental_sync_engine_ru_username');
        register_setting('rental_sync_engine_rentals_united', 'rental_sync_engine_ru_password');
        register_setting('rental_sync_engine_rentals_united', 'rental_sync_engine_ru_webhook_secret');
        
        // OwnerRez settings
        register_setting('rental_sync_engine_ownerrez', 'rental_sync_engine_or_enabled');
        register_setting('rental_sync_engine_ownerrez', 'rental_sync_engine_or_api_token');
        register_setting('rental_sync_engine_ownerrez', 'rental_sync_engine_or_webhook_secret');
        
        // Uplisting settings
        register_setting('rental_sync_engine_uplisting', 'rental_sync_engine_ul_enabled');
        register_setting('rental_sync_engine_uplisting', 'rental_sync_engine_ul_api_key');
        register_setting('rental_sync_engine_uplisting', 'rental_sync_engine_ul_webhook_secret');
        
        // Hostaway settings
        register_setting('rental_sync_engine_hostaway', 'rental_sync_engine_ha_enabled');
        register_setting('rental_sync_engine_hostaway', 'rental_sync_engine_ha_client_id');
        register_setting('rental_sync_engine_hostaway', 'rental_sync_engine_ha_client_secret');
        register_setting('rental_sync_engine_hostaway', 'rental_sync_engine_ha_webhook_secret');
    }
    
    /**
     * Get a setting value
     *
     * @param string $key Setting key
     * @param mixed $default Default value
     * @return mixed Setting value
     */
    public static function get($key, $default = null) {
        $value = get_option($key, $default);
        return $value;
    }
    
    /**
     * Update a setting value
     *
     * @param string $key Setting key
     * @param mixed $value Setting value
     * @return bool True on success, false on failure
     */
    public static function update($key, $value) {
        return update_option($key, $value);
    }
    
    /**
     * Check if a PMS provider is enabled
     *
     * @param string $provider Provider name (ru, or, ul, ha)
     * @return bool True if enabled, false otherwise
     */
    public static function is_provider_enabled($provider) {
        $key = 'rental_sync_engine_' . $provider . '_enabled';
        return self::get($key, 'no') === 'yes';
    }
    
    /**
     * Get PMS provider credentials
     *
     * @param string $provider Provider name (ru, or, ul, ha)
     * @return array Provider credentials
     */
    public static function get_provider_credentials($provider) {
        $credentials = array();
        
        switch ($provider) {
            case 'ru':
                $credentials['username'] = self::get('rental_sync_engine_ru_username', '');
                $credentials['password'] = self::get('rental_sync_engine_ru_password', '');
                break;
            case 'or':
                $credentials['api_token'] = self::get('rental_sync_engine_or_api_token', '');
                break;
            case 'ul':
                $credentials['api_key'] = self::get('rental_sync_engine_ul_api_key', '');
                break;
            case 'ha':
                $credentials['client_id'] = self::get('rental_sync_engine_ha_client_id', '');
                $credentials['client_secret'] = self::get('rental_sync_engine_ha_client_secret', '');
                break;
        }
        
        return $credentials;
    }
}
