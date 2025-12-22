<?php
/**
 * Admin Class
 *
 * @package RentalSyncEngine\Core
 */

namespace RentalSyncEngine\Core;

/**
 * Admin Class
 * Handles WordPress admin interface
 */
class Admin {
    /**
     * Logger instance
     *
     * @var Logger
     */
    private $logger;

    /**
     * Sync Manager instance
     *
     * @var SyncManager
     */
    private $sync_manager;

    /**
     * Constructor
     *
     * @param Logger      $logger Logger instance
     * @param SyncManager $sync_manager Sync Manager instance
     */
    public function __construct($logger, $sync_manager) {
        $this->logger = $logger;
        $this->sync_manager = $sync_manager;
        $this->init_hooks();
    }

    /**
     * Initialize admin hooks
     */
    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_rental_sync_manual_sync', array($this, 'handle_manual_sync'));
        add_action('wp_ajax_rental_sync_clear_logs', array($this, 'handle_clear_logs'));
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
            array($this, 'render_dashboard_page'),
            'dashicons-update',
            56
        );

        add_submenu_page(
            'rental-sync-engine',
            __('Dashboard', 'rental-sync-engine'),
            __('Dashboard', 'rental-sync-engine'),
            'manage_options',
            'rental-sync-engine',
            array($this, 'render_dashboard_page')
        );

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
            __('PMS Platforms', 'rental-sync-engine'),
            __('PMS Platforms', 'rental-sync-engine'),
            'manage_options',
            'rental-sync-engine-platforms',
            array($this, 'render_platforms_page')
        );

        add_submenu_page(
            'rental-sync-engine',
            __('Sync Logs', 'rental-sync-engine'),
            __('Sync Logs', 'rental-sync-engine'),
            'manage_options',
            'rental-sync-engine-logs',
            array($this, 'render_logs_page')
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('rental_sync_engine_settings', 'rental_sync_engine_settings');
        register_setting('rental_sync_engine_credentials', 'rental_sync_engine_pms_credentials');
    }

    /**
     * Render dashboard page
     */
    public function render_dashboard_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Rental Sync Engine Dashboard', 'rental-sync-engine'); ?></h1>
            
            <div class="rental-sync-dashboard">
                <?php $this->render_sync_status(); ?>
                <?php $this->render_quick_actions(); ?>
                <?php $this->render_stats(); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render sync status
     */
    private function render_sync_status() {
        ?>
        <div class="card">
            <h2><?php esc_html_e('Sync Status', 'rental-sync-engine'); ?></h2>
            
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Type', 'rental-sync-engine'); ?></th>
                        <th><?php esc_html_e('Last Sync', 'rental-sync-engine'); ?></th>
                        <th><?php esc_html_e('Next Sync', 'rental-sync-engine'); ?></th>
                        <th><?php esc_html_e('Status', 'rental-sync-engine'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sync_types = array(
                        'rental_sync_engine_sync_listings' => 'Listings',
                        'rental_sync_engine_sync_availability' => 'Availability',
                        'rental_sync_engine_sync_bookings' => 'Bookings',
                    );

                    foreach ($sync_types as $hook => $label) {
                        $next_sync = wp_next_scheduled($hook);
                        ?>
                        <tr>
                            <td><?php echo esc_html($label); ?></td>
                            <td><?php echo esc_html($this->get_last_sync_time($hook)); ?></td>
                            <td><?php echo $next_sync ? esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $next_sync)) : __('Not scheduled', 'rental-sync-engine'); ?></td>
                            <td><span class="status-badge status-active"><?php esc_html_e('Active', 'rental-sync-engine'); ?></span></td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Get last sync time for a hook
     *
     * @param string $hook Hook name
     * @return string
     */
    private function get_last_sync_time($hook) {
        $last_run = get_option('rental_sync_last_run_' . $hook);
        if ($last_run) {
            return date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $last_run);
        }
        return __('Never', 'rental-sync-engine');
    }

    /**
     * Render quick actions
     */
    private function render_quick_actions() {
        ?>
        <div class="card">
            <h2><?php esc_html_e('Quick Actions', 'rental-sync-engine'); ?></h2>
            
            <div class="quick-actions">
                <button class="button button-primary button-hero" data-action="sync-listings">
                    <?php esc_html_e('Sync Listings', 'rental-sync-engine'); ?>
                </button>
                
                <button class="button button-primary button-hero" data-action="sync-availability">
                    <?php esc_html_e('Sync Availability', 'rental-sync-engine'); ?>
                </button>
                
                <button class="button button-primary button-hero" data-action="sync-bookings">
                    <?php esc_html_e('Sync Bookings', 'rental-sync-engine'); ?>
                </button>
            </div>
            
            <div id="sync-result" style="margin-top: 20px;"></div>
        </div>
        <?php
    }

    /**
     * Render statistics
     */
    private function render_stats() {
        global $wpdb;

        $listings_table = $wpdb->prefix . 'rental_sync_listings';
        $bookings_table = $wpdb->prefix . 'rental_sync_bookings';

        $total_listings = $wpdb->get_var("SELECT COUNT(*) FROM {$listings_table}");
        $total_bookings = $wpdb->get_var("SELECT COUNT(*) FROM {$bookings_table}");
        $active_bookings = $wpdb->get_var("SELECT COUNT(*) FROM {$bookings_table} WHERE booking_status = 'confirmed'");
        ?>
        <div class="card">
            <h2><?php esc_html_e('Statistics', 'rental-sync-engine'); ?></h2>
            
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-value"><?php echo esc_html($total_listings); ?></div>
                    <div class="stat-label"><?php esc_html_e('Total Listings', 'rental-sync-engine'); ?></div>
                </div>
                
                <div class="stat-box">
                    <div class="stat-value"><?php echo esc_html($total_bookings); ?></div>
                    <div class="stat-label"><?php esc_html_e('Total Bookings', 'rental-sync-engine'); ?></div>
                </div>
                
                <div class="stat-box">
                    <div class="stat-value"><?php echo esc_html($active_bookings); ?></div>
                    <div class="stat-label"><?php esc_html_e('Active Bookings', 'rental-sync-engine'); ?></div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (isset($_POST['rental_sync_settings_submit'])) {
            check_admin_referer('rental_sync_settings');
            
            $settings = array(
                'sync_frequency' => sanitize_text_field($_POST['sync_frequency'] ?? 'hourly'),
                'enable_real_time_sync' => isset($_POST['enable_real_time_sync']),
                'enable_webhooks' => isset($_POST['enable_webhooks']),
                'log_level' => sanitize_text_field($_POST['log_level'] ?? 'info'),
            );
            
            update_option('rental_sync_engine_settings', $settings);
            
            // Reschedule cron jobs
            $cron_manager = new CronManager($this->sync_manager, $this->logger);
            $cron_manager->reschedule_jobs($settings['sync_frequency']);
            
            echo '<div class="notice notice-success"><p>' . esc_html__('Settings saved successfully!', 'rental-sync-engine') . '</p></div>';
        }

        $settings = get_option('rental_sync_engine_settings', array());
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Rental Sync Engine Settings', 'rental-sync-engine'); ?></h1>
            
            <form method="post">
                <?php wp_nonce_field('rental_sync_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e('Sync Frequency', 'rental-sync-engine'); ?></th>
                        <td>
                            <select name="sync_frequency">
                                <option value="fifteen_minutes" <?php selected($settings['sync_frequency'] ?? 'hourly', 'fifteen_minutes'); ?>><?php esc_html_e('Every 15 Minutes', 'rental-sync-engine'); ?></option>
                                <option value="thirty_minutes" <?php selected($settings['sync_frequency'] ?? 'hourly', 'thirty_minutes'); ?>><?php esc_html_e('Every 30 Minutes', 'rental-sync-engine'); ?></option>
                                <option value="hourly" <?php selected($settings['sync_frequency'] ?? 'hourly', 'hourly'); ?>><?php esc_html_e('Hourly', 'rental-sync-engine'); ?></option>
                                <option value="twicedaily" <?php selected($settings['sync_frequency'] ?? 'hourly', 'twicedaily'); ?>><?php esc_html_e('Twice Daily', 'rental-sync-engine'); ?></option>
                                <option value="daily" <?php selected($settings['sync_frequency'] ?? 'hourly', 'daily'); ?>><?php esc_html_e('Daily', 'rental-sync-engine'); ?></option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php esc_html_e('Enable Real-Time Sync', 'rental-sync-engine'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="enable_real_time_sync" value="1" <?php checked($settings['enable_real_time_sync'] ?? true); ?>>
                                <?php esc_html_e('Enable real-time synchronization when orders are created', 'rental-sync-engine'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php esc_html_e('Enable Webhooks', 'rental-sync-engine'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="enable_webhooks" value="1" <?php checked($settings['enable_webhooks'] ?? true); ?>>
                                <?php esc_html_e('Enable webhook receivers for real-time updates', 'rental-sync-engine'); ?>
                            </label>
                            <p class="description">
                                <?php
                                printf(
                                    esc_html__('Webhook URL: %s', 'rental-sync-engine'),
                                    '<code>' . esc_url(rest_url('rental-sync-engine/v1/webhook/{platform}')) . '</code>'
                                );
                                ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php esc_html_e('Log Level', 'rental-sync-engine'); ?></th>
                        <td>
                            <select name="log_level">
                                <option value="debug" <?php selected($settings['log_level'] ?? 'info', 'debug'); ?>><?php esc_html_e('Debug', 'rental-sync-engine'); ?></option>
                                <option value="info" <?php selected($settings['log_level'] ?? 'info', 'info'); ?>><?php esc_html_e('Info', 'rental-sync-engine'); ?></option>
                                <option value="warning" <?php selected($settings['log_level'] ?? 'info', 'warning'); ?>><?php esc_html_e('Warning', 'rental-sync-engine'); ?></option>
                                <option value="error" <?php selected($settings['log_level'] ?? 'info', 'error'); ?>><?php esc_html_e('Error', 'rental-sync-engine'); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="rental_sync_settings_submit" class="button button-primary" value="<?php esc_attr_e('Save Settings', 'rental-sync-engine'); ?>">
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Render platforms page
     */
    public function render_platforms_page() {
        if (isset($_POST['rental_sync_platforms_submit'])) {
            check_admin_referer('rental_sync_platforms');
            
            $credentials = array();
            $platforms = \RentalSyncEngine\PMS\PMSFactory::get_supported_platforms();
            
            foreach ($platforms as $platform) {
                if (isset($_POST['platforms'][$platform]['enabled'])) {
                    $credentials[$platform] = array(
                        'enabled' => true,
                    );
                    
                    // Add platform-specific credentials
                    foreach ($_POST['platforms'][$platform] as $key => $value) {
                        if ($key !== 'enabled') {
                            $credentials[$platform][$key] = sanitize_text_field($value);
                        }
                    }
                }
            }
            
            update_option('rental_sync_engine_pms_credentials', $credentials);
            echo '<div class="notice notice-success"><p>' . esc_html__('Platform credentials saved successfully!', 'rental-sync-engine') . '</p></div>';
        }

        $credentials = get_option('rental_sync_engine_pms_credentials', array());
        $platform_names = \RentalSyncEngine\PMS\PMSFactory::get_platform_names();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('PMS Platform Configuration', 'rental-sync-engine'); ?></h1>
            
            <form method="post">
                <?php wp_nonce_field('rental_sync_platforms'); ?>
                
                <?php foreach ($platform_names as $platform => $name): ?>
                    <div class="card platform-card">
                        <h2>
                            <label>
                                <input type="checkbox" name="platforms[<?php echo esc_attr($platform); ?>][enabled]" value="1" <?php checked(!empty($credentials[$platform]['enabled'])); ?>>
                                <?php echo esc_html($name); ?>
                            </label>
                        </h2>
                        
                        <div class="platform-credentials">
                            <?php $this->render_platform_fields($platform, $credentials[$platform] ?? array()); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <p class="submit">
                    <input type="submit" name="rental_sync_platforms_submit" class="button button-primary" value="<?php esc_attr_e('Save Credentials', 'rental-sync-engine'); ?>">
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Render platform-specific credential fields
     *
     * @param string $platform Platform name
     * @param array  $credentials Existing credentials
     */
    private function render_platform_fields($platform, $credentials) {
        $fields = $this->get_platform_fields($platform);
        
        foreach ($fields as $field => $label) {
            $value = $credentials[$field] ?? '';
            ?>
            <p>
                <label>
                    <?php echo esc_html($label); ?>:<br>
                    <input type="text" name="platforms[<?php echo esc_attr($platform); ?>][<?php echo esc_attr($field); ?>]" value="<?php echo esc_attr($value); ?>" class="regular-text">
                </label>
            </p>
            <?php
        }
    }

    /**
     * Get credential fields for a platform
     *
     * @param string $platform Platform name
     * @return array
     */
    private function get_platform_fields($platform) {
        $fields = array(
            'rentals_united' => array(
                'username' => __('Username', 'rental-sync-engine'),
                'password' => __('Password', 'rental-sync-engine'),
            ),
            'hostaway' => array(
                'api_key' => __('API Key', 'rental-sync-engine'),
                'api_secret' => __('API Secret', 'rental-sync-engine'),
                'webhook_secret' => __('Webhook Secret', 'rental-sync-engine'),
            ),
            'hostify' => array(
                'api_key' => __('API Key', 'rental-sync-engine'),
                'webhook_secret' => __('Webhook Secret', 'rental-sync-engine'),
            ),
            'uplisting' => array(
                'api_token' => __('API Token', 'rental-sync-engine'),
                'webhook_secret' => __('Webhook Secret', 'rental-sync-engine'),
            ),
            'nextpax' => array(
                'api_key' => __('API Key', 'rental-sync-engine'),
                'webhook_secret' => __('Webhook Secret', 'rental-sync-engine'),
            ),
            'ownerrez' => array(
                'username' => __('Username', 'rental-sync-engine'),
                'token' => __('API Token', 'rental-sync-engine'),
                'webhook_secret' => __('Webhook Secret', 'rental-sync-engine'),
            ),
        );

        return $fields[$platform] ?? array();
    }

    /**
     * Render logs page
     */
    public function render_logs_page() {
        $logs = $this->logger->get_logs(array('limit' => 100));
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Sync Logs', 'rental-sync-engine'); ?></h1>
            
            <button class="button" id="clear-logs"><?php esc_html_e('Clear Old Logs', 'rental-sync-engine'); ?></button>
            
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Time', 'rental-sync-engine'); ?></th>
                        <th><?php esc_html_e('Level', 'rental-sync-engine'); ?></th>
                        <th><?php esc_html_e('Type', 'rental-sync-engine'); ?></th>
                        <th><?php esc_html_e('Message', 'rental-sync-engine'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo esc_html($log['created_at']); ?></td>
                            <td><span class="log-level log-level-<?php echo esc_attr($log['log_level']); ?>"><?php echo esc_html($log['log_level']); ?></span></td>
                            <td><?php echo esc_html($log['log_type']); ?></td>
                            <td><?php echo esc_html($log['message']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Handle manual sync AJAX request
     */
    public function handle_manual_sync() {
        check_ajax_referer('rental-sync-engine-admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'rental-sync-engine')));
        }

        $action = sanitize_text_field($_POST['sync_action'] ?? '');

        try {
            $result = array();

            switch ($action) {
                case 'sync-listings':
                    $result = $this->sync_manager->sync_all_listings();
                    break;

                case 'sync-availability':
                    $result = $this->sync_manager->sync_all_availability();
                    break;

                case 'sync-bookings':
                    $result = $this->sync_manager->sync_all_bookings();
                    break;

                default:
                    throw new \Exception(__('Invalid sync action', 'rental-sync-engine'));
            }

            wp_send_json_success($result);

        } catch (\Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }

    /**
     * Handle clear logs AJAX request
     */
    public function handle_clear_logs() {
        check_ajax_referer('rental-sync-engine-admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'rental-sync-engine')));
        }

        $deleted = $this->logger->clear_old_logs(30);
        wp_send_json_success(array('deleted' => $deleted));
    }
}
