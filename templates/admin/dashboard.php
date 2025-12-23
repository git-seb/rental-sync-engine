<?php
/**
 * Admin Dashboard Template
 *
 * @package RentalSyncEngine
 */

if (!defined('ABSPATH')) {
    exit;
}

use RentalSyncEngine\Core\Logger;

$recent_logs = Logger::get_logs(array('limit' => 10));
?>

<div class="wrap rental-sync-engine-dashboard">
    <h1><?php _e('Rental Sync Engine Dashboard', 'rental-sync-engine'); ?></h1>
    
    <div class="rental-sync-stats">
        <div class="stat-box">
            <h3><?php _e('Properties Synced', 'rental-sync-engine'); ?></h3>
            <p class="stat-number"><?php echo esc_html($this->get_property_count()); ?></p>
        </div>
        <div class="stat-box">
            <h3><?php _e('Active Bookings', 'rental-sync-engine'); ?></h3>
            <p class="stat-number"><?php echo esc_html($this->get_booking_count()); ?></p>
        </div>
        <div class="stat-box">
            <h3><?php _e('Sync Status', 'rental-sync-engine'); ?></h3>
            <p class="stat-status"><?php _e('All Systems Operational', 'rental-sync-engine'); ?></p>
        </div>
    </div>
    
    <div class="rental-sync-providers">
        <h2><?php _e('PMS Providers', 'rental-sync-engine'); ?></h2>
        <div class="provider-cards">
            <div class="provider-card">
                <h3>Rentals United</h3>
                <p class="status <?php echo $this->is_provider_enabled('ru') ? 'enabled' : 'disabled'; ?>">
                    <?php echo $this->is_provider_enabled('ru') ? __('Enabled', 'rental-sync-engine') : __('Disabled', 'rental-sync-engine'); ?>
                </p>
            </div>
            <div class="provider-card">
                <h3>OwnerRez</h3>
                <p class="status <?php echo $this->is_provider_enabled('or') ? 'enabled' : 'disabled'; ?>">
                    <?php echo $this->is_provider_enabled('or') ? __('Enabled', 'rental-sync-engine') : __('Disabled', 'rental-sync-engine'); ?>
                </p>
            </div>
            <div class="provider-card">
                <h3>Uplisting</h3>
                <p class="status <?php echo $this->is_provider_enabled('ul') ? 'enabled' : 'disabled'; ?>">
                    <?php echo $this->is_provider_enabled('ul') ? __('Enabled', 'rental-sync-engine') : __('Disabled', 'rental-sync-engine'); ?>
                </p>
            </div>
            <div class="provider-card">
                <h3>Hostaway</h3>
                <p class="status <?php echo $this->is_provider_enabled('ha') ? 'enabled' : 'disabled'; ?>">
                    <?php echo $this->is_provider_enabled('ha') ? __('Enabled', 'rental-sync-engine') : __('Disabled', 'rental-sync-engine'); ?>
                </p>
            </div>
        </div>
    </div>
    
    <div class="rental-sync-recent-logs">
        <h2><?php _e('Recent Sync Activity', 'rental-sync-engine'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Date', 'rental-sync-engine'); ?></th>
                    <th><?php _e('Provider', 'rental-sync-engine'); ?></th>
                    <th><?php _e('Type', 'rental-sync-engine'); ?></th>
                    <th><?php _e('Status', 'rental-sync-engine'); ?></th>
                    <th><?php _e('Message', 'rental-sync-engine'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($recent_logs)) : ?>
                    <?php foreach ($recent_logs as $log) : ?>
                        <tr>
                            <td><?php echo esc_html($log->created_at); ?></td>
                            <td><?php echo esc_html(strtoupper($log->pms_provider)); ?></td>
                            <td><?php echo esc_html($log->sync_type); ?></td>
                            <td><span class="status-badge status-<?php echo esc_attr($log->status); ?>"><?php echo esc_html($log->status); ?></span></td>
                            <td><?php echo esc_html($log->message); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="5"><?php _e('No sync activity yet.', 'rental-sync-engine'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
