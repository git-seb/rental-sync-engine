<?php
/**
 * Main Plugin Class
 *
 * @package RentalSyncEngine
 */

namespace RentalSyncEngine;

use RentalSyncEngine\Core\Admin;
use RentalSyncEngine\Core\Logger;
use RentalSyncEngine\Core\SyncManager;
use RentalSyncEngine\Core\DatabaseManager;
use RentalSyncEngine\Core\CronManager;
use RentalSyncEngine\Webhooks\WebhookManager;
use RentalSyncEngine\WooCommerce\OrderManager;

/**
 * Main Plugin Class
 */
class Plugin {
    /**
     * Plugin instance
     *
     * @var Plugin
     */
    private static $instance = null;

    /**
     * Logger instance
     *
     * @var Logger
     */
    private $logger;

    /**
     * Admin instance
     *
     * @var Admin
     */
    private $admin;

    /**
     * Sync Manager instance
     *
     * @var SyncManager
     */
    private $sync_manager;

    /**
     * Database Manager instance
     *
     * @var DatabaseManager
     */
    private $database_manager;

    /**
     * Cron Manager instance
     *
     * @var CronManager
     */
    private $cron_manager;

    /**
     * Webhook Manager instance
     *
     * @var WebhookManager
     */
    private $webhook_manager;

    /**
     * Order Manager instance
     *
     * @var OrderManager
     */
    private $order_manager;

    /**
     * Get plugin instance
     *
     * @return Plugin
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
        $this->init_dependencies();
        $this->init_hooks();
    }

    /**
     * Initialize plugin dependencies
     */
    private function init_dependencies() {
        // Initialize core components
        $this->logger = new Logger();
        $this->database_manager = new DatabaseManager();
        $this->order_manager = new OrderManager($this->logger);
        $this->sync_manager = new SyncManager($this->logger, $this->database_manager, $this->order_manager);
        $this->cron_manager = new CronManager($this->sync_manager, $this->logger);
        $this->webhook_manager = new WebhookManager($this->sync_manager, $this->logger);
        
        // Initialize admin only in admin context
        if (is_admin()) {
            $this->admin = new Admin($this->logger, $this->sync_manager);
        }
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Initialize webhook endpoints
        add_action('rest_api_init', array($this->webhook_manager, 'register_routes'));
        
        // Initialize cron schedules
        add_action('init', array($this->cron_manager, 'register_schedules'));
        
        // Add custom cron intervals
        add_filter('cron_schedules', array($this->cron_manager, 'add_custom_intervals'));
        
        // Enqueue admin scripts and styles
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        }
    }

    /**
     * Enqueue admin assets
     *
     * @param string $hook Current admin page hook
     */
    public function enqueue_admin_assets($hook) {
        // Only load on plugin settings page
        if (strpos($hook, 'rental-sync-engine') === false) {
            return;
        }

        // Enqueue admin CSS
        wp_enqueue_style(
            'rental-sync-engine-admin',
            RENTAL_SYNC_ENGINE_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            RENTAL_SYNC_ENGINE_VERSION
        );

        // Enqueue admin JS
        wp_enqueue_script(
            'rental-sync-engine-admin',
            RENTAL_SYNC_ENGINE_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            RENTAL_SYNC_ENGINE_VERSION,
            true
        );

        // Localize script
        wp_localize_script(
            'rental-sync-engine-admin',
            'rentalSyncEngine',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('rental-sync-engine-admin'),
                'strings' => array(
                    'syncInProgress' => __('Sync in progress...', 'rental-sync-engine'),
                    'syncComplete' => __('Sync completed successfully!', 'rental-sync-engine'),
                    'syncError' => __('Sync failed. Please check the logs.', 'rental-sync-engine'),
                ),
            )
        );
    }

    /**
     * Get logger instance
     *
     * @return Logger
     */
    public function get_logger() {
        return $this->logger;
    }

    /**
     * Get sync manager instance
     *
     * @return SyncManager
     */
    public function get_sync_manager() {
        return $this->sync_manager;
    }

    /**
     * Get database manager instance
     *
     * @return DatabaseManager
     */
    public function get_database_manager() {
        return $this->database_manager;
    }

    /**
     * Get order manager instance
     *
     * @return OrderManager
     */
    public function get_order_manager() {
        return $this->order_manager;
    }
}
