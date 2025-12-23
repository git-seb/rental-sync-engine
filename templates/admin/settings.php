<?php
/**
 * Admin Settings Template
 *
 * @package RentalSyncEngine
 */

if (!defined('ABSPATH')) {
    exit;
}

use RentalSyncEngine\Core\Settings;

// Handle form submission
if (isset($_POST['rental_sync_save_settings'])) {
    check_admin_referer('rental_sync_engine_settings');
    
    $settings_to_save = array(
        'rental_sync_engine_enabled',
        'rental_sync_engine_sync_frequency',
        'rental_sync_engine_log_retention_days',
        'rental_sync_engine_debug_mode',
        'rental_sync_engine_ru_enabled',
        'rental_sync_engine_ru_username',
        'rental_sync_engine_ru_password',
        'rental_sync_engine_ru_webhook_secret',
        'rental_sync_engine_or_enabled',
        'rental_sync_engine_or_api_token',
        'rental_sync_engine_or_webhook_secret',
        'rental_sync_engine_ul_enabled',
        'rental_sync_engine_ul_api_key',
        'rental_sync_engine_ul_webhook_secret',
        'rental_sync_engine_ha_enabled',
        'rental_sync_engine_ha_client_id',
        'rental_sync_engine_ha_client_secret',
        'rental_sync_engine_ha_webhook_secret',
    );
    
    foreach ($settings_to_save as $setting) {
        if (isset($_POST[$setting])) {
            update_option($setting, sanitize_text_field($_POST[$setting]));
        }
    }
    
    echo '<div class="notice notice-success"><p>' . __('Settings saved successfully.', 'rental-sync-engine') . '</p></div>';
}
?>

<div class="wrap rental-sync-engine-settings">
    <h1><?php _e('Rental Sync Engine Settings', 'rental-sync-engine'); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('rental_sync_engine_settings'); ?>
        
        <h2 class="nav-tab-wrapper">
            <a href="#general" class="nav-tab nav-tab-active"><?php _e('General', 'rental-sync-engine'); ?></a>
            <a href="#rentals-united" class="nav-tab"><?php _e('Rentals United', 'rental-sync-engine'); ?></a>
            <a href="#ownerrez" class="nav-tab"><?php _e('OwnerRez', 'rental-sync-engine'); ?></a>
            <a href="#uplisting" class="nav-tab"><?php _e('Uplisting', 'rental-sync-engine'); ?></a>
            <a href="#hostaway" class="nav-tab"><?php _e('Hostaway', 'rental-sync-engine'); ?></a>
        </h2>
        
        <div id="general" class="tab-content active">
            <h2><?php _e('General Settings', 'rental-sync-engine'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="rental_sync_engine_enabled"><?php _e('Enable Sync', 'rental-sync-engine'); ?></label></th>
                    <td>
                        <select name="rental_sync_engine_enabled" id="rental_sync_engine_enabled">
                            <option value="yes" <?php selected(Settings::get('rental_sync_engine_enabled', 'yes'), 'yes'); ?>><?php _e('Yes', 'rental-sync-engine'); ?></option>
                            <option value="no" <?php selected(Settings::get('rental_sync_engine_enabled', 'yes'), 'no'); ?>><?php _e('No', 'rental-sync-engine'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="rental_sync_engine_sync_frequency"><?php _e('Sync Frequency', 'rental-sync-engine'); ?></label></th>
                    <td>
                        <select name="rental_sync_engine_sync_frequency" id="rental_sync_engine_sync_frequency">
                            <option value="hourly" <?php selected(Settings::get('rental_sync_engine_sync_frequency', 'hourly'), 'hourly'); ?>><?php _e('Hourly', 'rental-sync-engine'); ?></option>
                            <option value="twicedaily" <?php selected(Settings::get('rental_sync_engine_sync_frequency', 'hourly'), 'twicedaily'); ?>><?php _e('Twice Daily', 'rental-sync-engine'); ?></option>
                            <option value="daily" <?php selected(Settings::get('rental_sync_engine_sync_frequency', 'hourly'), 'daily'); ?>><?php _e('Daily', 'rental-sync-engine'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="rental_sync_engine_log_retention_days"><?php _e('Log Retention (days)', 'rental-sync-engine'); ?></label></th>
                    <td>
                        <input type="number" name="rental_sync_engine_log_retention_days" id="rental_sync_engine_log_retention_days" value="<?php echo esc_attr(Settings::get('rental_sync_engine_log_retention_days', 30)); ?>" min="1" max="365" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="rental_sync_engine_debug_mode"><?php _e('Debug Mode', 'rental-sync-engine'); ?></label></th>
                    <td>
                        <select name="rental_sync_engine_debug_mode" id="rental_sync_engine_debug_mode">
                            <option value="yes" <?php selected(Settings::get('rental_sync_engine_debug_mode', 'no'), 'yes'); ?>><?php _e('Yes', 'rental-sync-engine'); ?></option>
                            <option value="no" <?php selected(Settings::get('rental_sync_engine_debug_mode', 'no'), 'no'); ?>><?php _e('No', 'rental-sync-engine'); ?></option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>
        
        <div id="rentals-united" class="tab-content">
            <h2><?php _e('Rentals United Settings', 'rental-sync-engine'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="rental_sync_engine_ru_enabled"><?php _e('Enable Rentals United', 'rental-sync-engine'); ?></label></th>
                    <td>
                        <select name="rental_sync_engine_ru_enabled" id="rental_sync_engine_ru_enabled">
                            <option value="yes" <?php selected(Settings::get('rental_sync_engine_ru_enabled', 'no'), 'yes'); ?>><?php _e('Yes', 'rental-sync-engine'); ?></option>
                            <option value="no" <?php selected(Settings::get('rental_sync_engine_ru_enabled', 'no'), 'no'); ?>><?php _e('No', 'rental-sync-engine'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="rental_sync_engine_ru_username"><?php _e('Username', 'rental-sync-engine'); ?></label></th>
                    <td>
                        <input type="text" name="rental_sync_engine_ru_username" id="rental_sync_engine_ru_username" value="<?php echo esc_attr(Settings::get('rental_sync_engine_ru_username', '')); ?>" class="regular-text" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="rental_sync_engine_ru_password"><?php _e('Password', 'rental-sync-engine'); ?></label></th>
                    <td>
                        <input type="password" name="rental_sync_engine_ru_password" id="rental_sync_engine_ru_password" value="<?php echo esc_attr(Settings::get('rental_sync_engine_ru_password', '')); ?>" class="regular-text" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="rental_sync_engine_ru_webhook_secret"><?php _e('Webhook Secret', 'rental-sync-engine'); ?></label></th>
                    <td>
                        <input type="text" name="rental_sync_engine_ru_webhook_secret" id="rental_sync_engine_ru_webhook_secret" value="<?php echo esc_attr(Settings::get('rental_sync_engine_ru_webhook_secret', '')); ?>" class="regular-text" />
                        <p class="description"><?php printf(__('Webhook URL: %s', 'rental-sync-engine'), home_url('/rental-sync-webhook/rentals-united')); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div id="ownerrez" class="tab-content">
            <h2><?php _e('OwnerRez Settings', 'rental-sync-engine'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="rental_sync_engine_or_enabled"><?php _e('Enable OwnerRez', 'rental-sync-engine'); ?></label></th>
                    <td>
                        <select name="rental_sync_engine_or_enabled" id="rental_sync_engine_or_enabled">
                            <option value="yes" <?php selected(Settings::get('rental_sync_engine_or_enabled', 'no'), 'yes'); ?>><?php _e('Yes', 'rental-sync-engine'); ?></option>
                            <option value="no" <?php selected(Settings::get('rental_sync_engine_or_enabled', 'no'), 'no'); ?>><?php _e('No', 'rental-sync-engine'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="rental_sync_engine_or_api_token"><?php _e('API Token', 'rental-sync-engine'); ?></label></th>
                    <td>
                        <input type="text" name="rental_sync_engine_or_api_token" id="rental_sync_engine_or_api_token" value="<?php echo esc_attr(Settings::get('rental_sync_engine_or_api_token', '')); ?>" class="regular-text" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="rental_sync_engine_or_webhook_secret"><?php _e('Webhook Secret', 'rental-sync-engine'); ?></label></th>
                    <td>
                        <input type="text" name="rental_sync_engine_or_webhook_secret" id="rental_sync_engine_or_webhook_secret" value="<?php echo esc_attr(Settings::get('rental_sync_engine_or_webhook_secret', '')); ?>" class="regular-text" />
                        <p class="description"><?php printf(__('Webhook URL: %s', 'rental-sync-engine'), home_url('/rental-sync-webhook/ownerrez')); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div id="uplisting" class="tab-content">
            <h2><?php _e('Uplisting Settings', 'rental-sync-engine'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="rental_sync_engine_ul_enabled"><?php _e('Enable Uplisting', 'rental-sync-engine'); ?></label></th>
                    <td>
                        <select name="rental_sync_engine_ul_enabled" id="rental_sync_engine_ul_enabled">
                            <option value="yes" <?php selected(Settings::get('rental_sync_engine_ul_enabled', 'no'), 'yes'); ?>><?php _e('Yes', 'rental-sync-engine'); ?></option>
                            <option value="no" <?php selected(Settings::get('rental_sync_engine_ul_enabled', 'no'), 'no'); ?>><?php _e('No', 'rental-sync-engine'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="rental_sync_engine_ul_api_key"><?php _e('API Key', 'rental-sync-engine'); ?></label></th>
                    <td>
                        <input type="text" name="rental_sync_engine_ul_api_key" id="rental_sync_engine_ul_api_key" value="<?php echo esc_attr(Settings::get('rental_sync_engine_ul_api_key', '')); ?>" class="regular-text" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="rental_sync_engine_ul_webhook_secret"><?php _e('Webhook Secret', 'rental-sync-engine'); ?></label></th>
                    <td>
                        <input type="text" name="rental_sync_engine_ul_webhook_secret" id="rental_sync_engine_ul_webhook_secret" value="<?php echo esc_attr(Settings::get('rental_sync_engine_ul_webhook_secret', '')); ?>" class="regular-text" />
                        <p class="description"><?php printf(__('Webhook URL: %s', 'rental-sync-engine'), home_url('/rental-sync-webhook/uplisting')); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div id="hostaway" class="tab-content">
            <h2><?php _e('Hostaway Settings', 'rental-sync-engine'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="rental_sync_engine_ha_enabled"><?php _e('Enable Hostaway', 'rental-sync-engine'); ?></label></th>
                    <td>
                        <select name="rental_sync_engine_ha_enabled" id="rental_sync_engine_ha_enabled">
                            <option value="yes" <?php selected(Settings::get('rental_sync_engine_ha_enabled', 'no'), 'yes'); ?>><?php _e('Yes', 'rental-sync-engine'); ?></option>
                            <option value="no" <?php selected(Settings::get('rental_sync_engine_ha_enabled', 'no'), 'no'); ?>><?php _e('No', 'rental-sync-engine'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="rental_sync_engine_ha_client_id"><?php _e('Client ID', 'rental-sync-engine'); ?></label></th>
                    <td>
                        <input type="text" name="rental_sync_engine_ha_client_id" id="rental_sync_engine_ha_client_id" value="<?php echo esc_attr(Settings::get('rental_sync_engine_ha_client_id', '')); ?>" class="regular-text" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="rental_sync_engine_ha_client_secret"><?php _e('Client Secret', 'rental-sync-engine'); ?></label></th>
                    <td>
                        <input type="password" name="rental_sync_engine_ha_client_secret" id="rental_sync_engine_ha_client_secret" value="<?php echo esc_attr(Settings::get('rental_sync_engine_ha_client_secret', '')); ?>" class="regular-text" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="rental_sync_engine_ha_webhook_secret"><?php _e('Webhook Secret', 'rental-sync-engine'); ?></label></th>
                    <td>
                        <input type="text" name="rental_sync_engine_ha_webhook_secret" id="rental_sync_engine_ha_webhook_secret" value="<?php echo esc_attr(Settings::get('rental_sync_engine_ha_webhook_secret', '')); ?>" class="regular-text" />
                        <p class="description"><?php printf(__('Webhook URL: %s', 'rental-sync-engine'), home_url('/rental-sync-webhook/hostaway')); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <p class="submit">
            <input type="submit" name="rental_sync_save_settings" class="button button-primary" value="<?php _e('Save Settings', 'rental-sync-engine'); ?>" />
        </p>
    </form>
</div>
