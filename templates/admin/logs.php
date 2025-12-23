<?php
/**
 * Admin Logs Template
 *
 * @package RentalSyncEngine
 */

if (!defined('ABSPATH')) {
    exit;
}

use RentalSyncEngine\Core\Logger;

$pms_filter = isset($_GET['pms']) ? sanitize_text_field($_GET['pms']) : '';
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$per_page = 50;
$page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($page - 1) * $per_page;

$logs = Logger::get_logs(array(
    'pms_provider' => $pms_filter,
    'status' => $status_filter,
    'limit' => $per_page,
    'offset' => $offset
));
?>

<div class="wrap rental-sync-engine-logs">
    <h1><?php _e('Sync Logs', 'rental-sync-engine'); ?></h1>
    
    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get">
                <input type="hidden" name="page" value="rental-sync-engine-logs" />
                
                <select name="pms">
                    <option value=""><?php _e('All Providers', 'rental-sync-engine'); ?></option>
                    <option value="ru" <?php selected($pms_filter, 'ru'); ?>><?php _e('Rentals United', 'rental-sync-engine'); ?></option>
                    <option value="or" <?php selected($pms_filter, 'or'); ?>><?php _e('OwnerRez', 'rental-sync-engine'); ?></option>
                    <option value="ul" <?php selected($pms_filter, 'ul'); ?>><?php _e('Uplisting', 'rental-sync-engine'); ?></option>
                    <option value="ha" <?php selected($pms_filter, 'ha'); ?>><?php _e('Hostaway', 'rental-sync-engine'); ?></option>
                </select>
                
                <select name="status">
                    <option value=""><?php _e('All Statuses', 'rental-sync-engine'); ?></option>
                    <option value="success" <?php selected($status_filter, 'success'); ?>><?php _e('Success', 'rental-sync-engine'); ?></option>
                    <option value="error" <?php selected($status_filter, 'error'); ?>><?php _e('Error', 'rental-sync-engine'); ?></option>
                    <option value="warning" <?php selected($status_filter, 'warning'); ?>><?php _e('Warning', 'rental-sync-engine'); ?></option>
                </select>
                
                <input type="submit" class="button" value="<?php _e('Filter', 'rental-sync-engine'); ?>" />
            </form>
        </div>
    </div>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('Date', 'rental-sync-engine'); ?></th>
                <th><?php _e('Provider', 'rental-sync-engine'); ?></th>
                <th><?php _e('Sync Type', 'rental-sync-engine'); ?></th>
                <th><?php _e('Status', 'rental-sync-engine'); ?></th>
                <th><?php _e('Message', 'rental-sync-engine'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($logs)) : ?>
                <?php foreach ($logs as $log) : ?>
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
                    <td colspan="5"><?php _e('No logs found.', 'rental-sync-engine'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
