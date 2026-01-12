<?php
/**
 * Plugin Name: Rental Sync Engine
 * Plugin URI: https://github.com/git-seb/rental-sync-engine
 * Description: Integrates WordPress/WooCommerce with multiple PMS systems (Rentals United, OwnerRez, Uplisting, Hostaway) for two-way property, availability, and booking synchronization.
 * Version: 1.0.0
 * Author: Rental Sync Engine Team
 * Author URI: https://github.com/git-seb
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: rental-sync-engine
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 7.1
 * WC tested up to: 10.4.3
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('RENTAL_SYNC_ENGINE_VERSION', '1.0.0');
define('RENTAL_SYNC_ENGINE_FILE', __FILE__);
define('RENTAL_SYNC_ENGINE_PATH', plugin_dir_path(__FILE__));
define('RENTAL_SYNC_ENGINE_URL', plugin_dir_url(__FILE__));
define('RENTAL_SYNC_ENGINE_BASENAME', plugin_basename(__FILE__));

// Require manual autoloader
require_once RENTAL_SYNC_ENGINE_PATH . 'includes/autoload.php';

// Declare HPOS compatibility
add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables',
            __FILE__,
            true
        );
    }
});

/**
 * Main plugin class
 */
class Rental_Sync_Engine {
    
    /**
     * Single instance of the class
     *
     * @var Rental_Sync_Engine
     */
    private static $instance = null;
    
    /**
     * Get single instance of the class
     *
     * @return Rental_Sync_Engine
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Check if WooCommerce is active
        add_action('plugins_loaded', array($this, 'init'), 10);
        
        // Activation and deactivation hooks
        register_activation_hook(RENTAL_SYNC_ENGINE_FILE, array($this, 'activate'));
        register_deactivation_hook(RENTAL_SYNC_ENGINE_FILE, array($this, 'deactivate'));
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }
        
        // Initialize components
        $this->init_hooks();
        $this->init_components();
    }
    
    /**
     * Initialize a component class if it exists
     *
     * @param string $class_name Fully qualified class name
     * @return bool True if class was initialized, false otherwise
     */
    private function init_class($class_name) {
        if (class_exists($class_name)) {
            $class_name::init();
            return true;
        } else {
            error_log("Rental Sync Engine: Fatal Error - Missing class {$class_name}.");
            return false;
        }
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Load text domain for translations
        add_action('init', array($this, 'load_textdomain'));
        
        // Admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Enqueue admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    /**
     * Initialize plugin components
     */
    private function init_components() {
        // Initialize logger with fallback error handling
        $this->init_class('RentalSyncEngine\Core\Logger');
        
        // Initialize settings - critical component
        if (!$this->init_class('RentalSyncEngine\Core\Settings')) {
            return; // Settings is critical, stop initialization if missing
        }
        
        // Initialize other core components
        $this->init_class('RentalSyncEngine\Core\WebhookRouter');
        $this->init_class('RentalSyncEngine\Core\SyncScheduler');
        
        // Initialize WooCommerce integration
        $this->init_class('RentalSyncEngine\Integration\WooCommerceIntegration');
        
        // Initialize PMS handlers only if enabled
        if (\RentalSyncEngine\Core\Settings::is_provider_enabled('ru')) {
            $this->init_class('RentalSyncEngine\PMS\RentalsUnited\Handler');
        }
        if (\RentalSyncEngine\Core\Settings::is_provider_enabled('or')) {
            $this->init_class('RentalSyncEngine\PMS\OwnerRez\Handler');
        }
        if (\RentalSyncEngine\Core\Settings::is_provider_enabled('ul')) {
            $this->init_class('RentalSyncEngine\PMS\Uplisting\Handler');
        }
        if (\RentalSyncEngine\Core\Settings::is_provider_enabled('ha')) {
            $this->init_class('RentalSyncEngine\PMS\Hostaway\Handler');
        }
    }
    
    /**
     * Load plugin text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain('rental-sync-engine', false, dirname(RENTAL_SYNC_ENGINE_BASENAME) . '/languages');
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Rental Sync Engine', 'rental-sync-engine'),
            __('Rental Sync', 'rental-sync-engine'),
            'manage_options',
            'rental-sync-engine',
            array($this, 'render_admin_page'),
            'dashicons-update',
            56
        );
        
        // Add submenu pages
        add_submenu_page(
            'rental-sync-engine',
            __('Settings', 'rental-sync-engine'),
            __('Settings', 'rental-sync-engine'),
            'manage_options',
            'rental-sync-engine-settings',
            array($this, 'render_settings_page')
        );
        
        add_submenu_page(
            'rental-sync-engine',
            __('Sync Logs', 'rental-sync-engine'),
            __('Sync Logs', 'rental-sync-engine'),
            'manage_options',
            'rental-sync-engine-logs',
            array($this, 'render_logs_page')
        );
        
        add_submenu_page(
            'rental-sync-engine',
            __('Manual Sync', 'rental-sync-engine'),
            __('Manual Sync', 'rental-sync-engine'),
            'manage_options',
            'rental-sync-engine-manual-sync',
            array($this, 'render_manual_sync_page')
        );
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'rental-sync-engine') === false) {
            return;
        }
        
        wp_enqueue_style(
            'rental-sync-engine-admin',
            RENTAL_SYNC_ENGINE_URL . 'assets/css/admin.css',
            array(),
            RENTAL_SYNC_ENGINE_VERSION
        );
        
        wp_enqueue_script(
            'rental-sync-engine-admin',
            RENTAL_SYNC_ENGINE_URL . 'assets/js/admin.js',
            array('jquery'),
            RENTAL_SYNC_ENGINE_VERSION,
            true
        );
        
        wp_localize_script('rental-sync-engine-admin', 'rentalSyncEngine', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('rental-sync-engine-nonce'),
            'i18n' => array(
                'syncStarted' => __('Synchronization started', 'rental-sync-engine'),
                'syncCompleted' => __('Synchronization completed', 'rental-sync-engine'),
                'syncFailed' => __('Synchronization failed', 'rental-sync-engine'),
            )
        ));
    }
    
    /**
     * Render main admin page
     */
    public function render_admin_page() {
        include RENTAL_SYNC_ENGINE_PATH . 'templates/admin/dashboard.php';
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        include RENTAL_SYNC_ENGINE_PATH . 'templates/admin/settings.php';
    }
    
    /**
     * Render logs page
     */
    public function render_logs_page() {
        include RENTAL_SYNC_ENGINE_PATH . 'templates/admin/logs.php';
    }
    
    /**
     * Render manual sync page
     */
    public function render_manual_sync_page() {
        include RENTAL_SYNC_ENGINE_PATH . 'templates/admin/manual-sync.php';
    }
    
    /**
     * Get property count
     *
     * @return int
     */
    private function get_property_count() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rental_sync_property_mappings';
        return $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE sync_enabled = 1");
    }
    
    /**
     * Get booking count
     *
     * @return int
     */
    private function get_booking_count() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rental_sync_booking_mappings';
        return $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE sync_status = 'synced'");
    }
    
    /**
     * Check if provider is enabled
     *
     * @param string $provider Provider code
     * @return bool
     */
    private function is_provider_enabled($provider) {
        return \RentalSyncEngine\Core\Settings::is_provider_enabled($provider);
    }
    
    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php _e('Rental Sync Engine requires WooCommerce to be installed and active.', 'rental-sync-engine'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        $this->create_tables();
        
        // Set default options
        $this->set_default_options();
        
        // Schedule cron jobs
        if (!wp_next_scheduled('rental_sync_engine_hourly_sync')) {
            wp_schedule_event(time(), 'hourly', 'rental_sync_engine_hourly_sync');
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled cron jobs
        wp_clear_scheduled_hook('rental_sync_engine_hourly_sync');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Sync logs table
        $table_name = $wpdb->prefix . 'rental_sync_logs';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            pms_provider varchar(50) NOT NULL,
            sync_type varchar(50) NOT NULL,
            status varchar(20) NOT NULL,
            message text,
            data longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY pms_provider (pms_provider),
            KEY sync_type (sync_type),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Property mappings table
        $table_name = $wpdb->prefix . 'rental_sync_property_mappings';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            pms_provider varchar(50) NOT NULL,
            pms_property_id varchar(255) NOT NULL,
            wc_product_id bigint(20) NOT NULL,
            sync_enabled tinyint(1) DEFAULT 1,
            last_synced datetime,
            metadata longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_mapping (pms_provider, pms_property_id),
            KEY wc_product_id (wc_product_id)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // Booking mappings table
        $table_name = $wpdb->prefix . 'rental_sync_booking_mappings';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            pms_provider varchar(50) NOT NULL,
            pms_booking_id varchar(255) NOT NULL,
            wc_order_id bigint(20) NOT NULL,
            sync_status varchar(20) NOT NULL,
            last_synced datetime,
            metadata longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_mapping (pms_provider, pms_booking_id),
            KEY wc_order_id (wc_order_id)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Set default options
     */
    private function set_default_options() {
        $defaults = array(
            'rental_sync_engine_enabled' => 'yes',
            'rental_sync_engine_sync_frequency' => 'hourly',
            'rental_sync_engine_log_retention_days' => 30,
            'rental_sync_engine_debug_mode' => 'no',
            // PMS integrations disabled by default
            'rental_sync_engine_ru_enabled' => 'no',
            'rental_sync_engine_or_enabled' => 'no',
            'rental_sync_engine_ul_enabled' => 'no',
            'rental_sync_engine_ha_enabled' => 'no',
            // Default API URLs for each PMS
            'rental_sync_engine_ru_api_url' => 'https://rm.rentalsunited.com/api',
            'rental_sync_engine_or_api_url' => 'https://api.ownerrez.com/v2',
            'rental_sync_engine_ul_api_url' => 'https://api.uplisting.io/v1',
            'rental_sync_engine_ha_api_url' => 'https://api.hostaway.com/v1',
        );
        
        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }
}

/**
 * Initialize the plugin
 */
function rental_sync_engine() {
    return Rental_Sync_Engine::get_instance();
}

// Initialize the plugin
rental_sync_engine();
