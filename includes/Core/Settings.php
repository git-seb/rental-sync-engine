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
        add_action('admin_notices', array(__CLASS__, 'display_admin_notices'));
        add_action('wp_ajax_rental_sync_test_connection', array(__CLASS__, 'handle_test_connection'));
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
        register_setting('rental_sync_engine_rentals_united', 'rental_sync_engine_ru_api_url', array(
            'sanitize_callback' => array(__CLASS__, 'sanitize_api_url')
        ));
        
        // OwnerRez settings
        register_setting('rental_sync_engine_ownerrez', 'rental_sync_engine_or_enabled');
        register_setting('rental_sync_engine_ownerrez', 'rental_sync_engine_or_api_token');
        register_setting('rental_sync_engine_ownerrez', 'rental_sync_engine_or_webhook_secret');
        register_setting('rental_sync_engine_ownerrez', 'rental_sync_engine_or_api_url', array(
            'sanitize_callback' => array(__CLASS__, 'sanitize_api_url')
        ));
        
        // Uplisting settings
        register_setting('rental_sync_engine_uplisting', 'rental_sync_engine_ul_enabled');
        register_setting('rental_sync_engine_uplisting', 'rental_sync_engine_ul_api_key');
        register_setting('rental_sync_engine_uplisting', 'rental_sync_engine_ul_webhook_secret');
        register_setting('rental_sync_engine_uplisting', 'rental_sync_engine_ul_api_url', array(
            'sanitize_callback' => array(__CLASS__, 'sanitize_api_url')
        ));
        
        // Hostaway settings
        register_setting('rental_sync_engine_hostaway', 'rental_sync_engine_ha_enabled');
        register_setting('rental_sync_engine_hostaway', 'rental_sync_engine_ha_client_id');
        register_setting('rental_sync_engine_hostaway', 'rental_sync_engine_ha_client_secret');
        register_setting('rental_sync_engine_hostaway', 'rental_sync_engine_ha_webhook_secret');
        register_setting('rental_sync_engine_hostaway', 'rental_sync_engine_ha_api_url', array(
            'sanitize_callback' => array(__CLASS__, 'sanitize_api_url')
        ));
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
     * Sanitize API URL
     *
     * @param string $url API URL to sanitize
     * @return string Sanitized URL
     */
    public static function sanitize_api_url($url) {
        $url = sanitize_text_field($url);
        
        // If empty, return empty (will use default)
        if (empty($url)) {
            return '';
        }
        
        // Validate URL format
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            self::add_admin_notice(__('Invalid API URL format. Please enter a valid URL.', 'rental-sync-engine'), 'error');
            return '';
        }
        
        // Ensure URL uses HTTPS for security
        if (strpos($url, 'https://') !== 0) {
            self::add_admin_notice(__('API URL must use HTTPS for security. URL was not saved.', 'rental-sync-engine'), 'error');
            return '';
        }
        
        // Validate URL is reachable
        $provider_key = self::get_provider_key_from_url_option();
        if ($provider_key && self::get($provider_key . '_enabled') === 'yes') {
            self::validate_api_url($url);
        }
        
        return rtrim($url, '/');
    }
    
    /**
     * Get provider key from the current URL option being saved
     *
     * @return string|null Provider key or null
     */
    private static function get_provider_key_from_url_option() {
        // Check which API URL is being saved from the POST data
        if (isset($_POST['rental_sync_engine_ru_api_url'])) {
            return 'rental_sync_engine_ru';
        } elseif (isset($_POST['rental_sync_engine_or_api_url'])) {
            return 'rental_sync_engine_or';
        } elseif (isset($_POST['rental_sync_engine_ul_api_url'])) {
            return 'rental_sync_engine_ul';
        } elseif (isset($_POST['rental_sync_engine_ha_api_url'])) {
            return 'rental_sync_engine_ha';
        }
        return null;
    }
    
    /**
     * Validate API URL by making a test request
     *
     * @param string $url API URL to validate
     * @return bool True if valid, false otherwise
     */
    public static function validate_api_url($url) {
        // Make a simple HEAD request to check if the URL is reachable
        $response = wp_remote_head($url, array(
            'timeout' => 10,
            'sslverify' => true,
        ));
        
        if (is_wp_error($response)) {
            self::add_admin_notice(
                sprintf(__('Warning: Could not reach API URL: %s. Please verify the URL is correct.', 'rental-sync-engine'), $response->get_error_message()),
                'warning'
            );
            return false;
        }
        
        $code = wp_remote_retrieve_response_code($response);
        
        // Accept 2xx, 3xx, 401, and 403 as valid (401/403 means API exists but needs auth)
        if (($code >= 200 && $code < 400) || $code === 401 || $code === 403) {
            self::add_admin_notice(__('API URL validated successfully.', 'rental-sync-engine'), 'success');
            return true;
        }
        
        self::add_admin_notice(
            sprintf(__('Warning: API URL returned status code %d. Please verify the URL is correct.', 'rental-sync-engine'), $code),
            'warning'
        );
        return false;
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
     * Get API URL for a provider
     *
     * @param string $provider Provider name (ru, or, ul, ha)
     * @param string $default Default URL
     * @return string API URL
     */
    public static function get_api_url($provider, $default = '') {
        $key = 'rental_sync_engine_' . $provider . '_api_url';
        $url = self::get($key, '');
        
        // If no custom URL is set, use the default
        if (empty($url)) {
            return $default;
        }
        
        return $url;
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
    
    /**
     * Test connection to a PMS provider
     *
     * @param string $provider Provider code (ru, or, ul, ha)
     * @return array Result with 'success' boolean and 'message' string
     */
    public static function test_connection($provider) {
        try {
            $client = null;
            
            switch ($provider) {
                case 'ru':
                    if (class_exists('RentalSyncEngine\PMS\RentalsUnited\Client')) {
                        $client = new \RentalSyncEngine\PMS\RentalsUnited\Client();
                        $result = $client->get_properties();
                        return array(
                            'success' => true,
                            'message' => __('Connection successful! Found properties.', 'rental-sync-engine')
                        );
                    }
                    break;
                case 'or':
                    if (class_exists('RentalSyncEngine\PMS\OwnerRez\Client')) {
                        $client = new \RentalSyncEngine\PMS\OwnerRez\Client();
                        $result = $client->get_properties();
                        return array(
                            'success' => true,
                            'message' => __('Connection successful! Found properties.', 'rental-sync-engine')
                        );
                    }
                    break;
                case 'ul':
                    if (class_exists('RentalSyncEngine\PMS\Uplisting\Client')) {
                        $client = new \RentalSyncEngine\PMS\Uplisting\Client();
                        $result = $client->get_properties();
                        return array(
                            'success' => true,
                            'message' => __('Connection successful! Found properties.', 'rental-sync-engine')
                        );
                    }
                    break;
                case 'ha':
                    if (class_exists('RentalSyncEngine\PMS\Hostaway\Client')) {
                        $client = new \RentalSyncEngine\PMS\Hostaway\Client();
                        $result = $client->get_properties();
                        return array(
                            'success' => true,
                            'message' => __('Connection successful! Found properties.', 'rental-sync-engine')
                        );
                    }
                    break;
            }
            
            return array(
                'success' => false,
                'message' => __('Provider not available or not configured.', 'rental-sync-engine')
            );
        } catch (\Exception $e) {
            Logger::error($provider, 'connection_test', $e->getMessage());
            return array(
                'success' => false,
                'message' => __('Connection failed: ', 'rental-sync-engine') . $e->getMessage()
            );
        }
    }
    
    /**
     * Handle AJAX test connection request
     */
    public static function handle_test_connection() {
        check_ajax_referer('rental-sync-engine-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'rental-sync-engine')), 403);
        }
        
        $provider = isset($_POST['provider']) ? sanitize_text_field($_POST['provider']) : '';
        
        if (empty($provider)) {
            wp_send_json_error(array('message' => __('Provider is required', 'rental-sync-engine')), 400);
        }
        
        $result = self::test_connection($provider);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Add an admin notice
     *
     * @param string $message Notice message
     * @param string $type Notice type (success, error, warning, info)
     */
    public static function add_admin_notice($message, $type = 'info') {
        $notices = get_transient('rental_sync_engine_admin_notices');
        if (!is_array($notices)) {
            $notices = array();
        }
        
        $notices[] = array(
            'message' => $message,
            'type' => $type
        );
        
        set_transient('rental_sync_engine_admin_notices', $notices, 60);
    }
    
    /**
     * Display admin notices
     */
    public static function display_admin_notices() {
        $notices = get_transient('rental_sync_engine_admin_notices');
        
        if (is_array($notices) && !empty($notices)) {
            foreach ($notices as $notice) {
                $class = 'notice notice-' . esc_attr($notice['type']);
                printf('<div class="%s"><p>%s</p></div>', $class, esc_html($notice['message']));
            }
            
            delete_transient('rental_sync_engine_admin_notices');
        }
    }
}
