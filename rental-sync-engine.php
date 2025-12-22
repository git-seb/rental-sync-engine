<?php
/**
 * Plugin Name: Rental Sync Engine
 * Plugin URI: https://github.com/git-seb/rental-sync-engine
 * Description: A production-ready WordPress plugin for syncing WooCommerce with multiple PMS systems supporting two-way booking synchronization.
 * Version: 1.0.0
 * Author: git-seb
 * Author URI: https://github.com/git-seb
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: rental-sync-engine
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * WC requires at least: 7.0
 * WC tested up to: 9.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('RENTAL_SYNC_ENGINE_VERSION', '1.0.0');
define('RENTAL_SYNC_ENGINE_PLUGIN_FILE', __FILE__);
define('RENTAL_SYNC_ENGINE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RENTAL_SYNC_ENGINE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RENTAL_SYNC_ENGINE_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Require Composer autoloader
if (file_exists(RENTAL_SYNC_ENGINE_PLUGIN_DIR . 'vendor/autoload.php')) {
    require_once RENTAL_SYNC_ENGINE_PLUGIN_DIR . 'vendor/autoload.php';
}

// Initialize the plugin
add_action('plugins_loaded', 'rental_sync_engine_init', 20);

/**
 * Initialize the Rental Sync Engine plugin
 */
function rental_sync_engine_init() {
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'rental_sync_engine_woocommerce_missing_notice');
        return;
    }

    // Load plugin text domain
    load_plugin_textdomain('rental-sync-engine', false, dirname(plugin_basename(__FILE__)) . '/languages');

    // Initialize the main plugin class
    \RentalSyncEngine\Plugin::get_instance();
}

/**
 * Display admin notice if WooCommerce is not active
 */
function rental_sync_engine_woocommerce_missing_notice() {
    ?>
    <div class="notice notice-error">
        <p>
            <?php
            echo wp_kses_post(
                sprintf(
                    __('<strong>Rental Sync Engine</strong> requires WooCommerce to be installed and active. You can download WooCommerce %shere%s.', 'rental-sync-engine'),
                    '<a href="https://wordpress.org/plugins/woocommerce/" target="_blank">',
                    '</a>'
                )
            );
            ?>
        </p>
    </div>
    <?php
}

/**
 * Plugin activation hook
 */
register_activation_hook(__FILE__, 'rental_sync_engine_activate');

function rental_sync_engine_activate() {
    // Check PHP version
    if (version_compare(PHP_VERSION, '8.0', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            esc_html__('Rental Sync Engine requires PHP 8.0 or higher. Please upgrade your PHP version.', 'rental-sync-engine'),
            esc_html__('Plugin Activation Error', 'rental-sync-engine'),
            array('back_link' => true)
        );
    }

    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            esc_html__('Rental Sync Engine requires WooCommerce to be installed and active.', 'rental-sync-engine'),
            esc_html__('Plugin Activation Error', 'rental-sync-engine'),
            array('back_link' => true)
        );
    }

    // Create database tables and set default options
    if (class_exists('\RentalSyncEngine\Core\Installer')) {
        \RentalSyncEngine\Core\Installer::activate();
    }

    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Plugin deactivation hook
 */
register_deactivation_hook(__FILE__, 'rental_sync_engine_deactivate');

function rental_sync_engine_deactivate() {
    // Clear scheduled cron jobs
    if (class_exists('\RentalSyncEngine\Core\Installer')) {
        \RentalSyncEngine\Core\Installer::deactivate();
    }

    // Flush rewrite rules
    flush_rewrite_rules();
}
