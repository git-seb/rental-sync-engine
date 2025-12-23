<?php
/**
 * Logger class for the Rental Sync Engine
 *
 * @package RentalSyncEngine\Core
 */

namespace RentalSyncEngine\Core;

/**
 * Class Logger
 */
class Logger {
    
    /**
     * Initialize the logger
     */
    public static function init() {
        // Hook into WordPress actions if needed
    }
    
    /**
     * Log a message to the database
     *
     * @param string $pms_provider PMS provider name
     * @param string $sync_type Type of sync (property, availability, booking)
     * @param string $status Status (success, error, warning)
     * @param string $message Log message
     * @param array $data Additional data to log
     * @return int|false Log ID on success, false on failure
     */
    public static function log($pms_provider, $sync_type, $status, $message, $data = array()) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'rental_sync_logs';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'pms_provider' => sanitize_text_field($pms_provider),
                'sync_type' => sanitize_text_field($sync_type),
                'status' => sanitize_text_field($status),
                'message' => sanitize_text_field($message),
                'data' => maybe_serialize($data),
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Log success message
     *
     * @param string $pms_provider PMS provider name
     * @param string $sync_type Type of sync
     * @param string $message Log message
     * @param array $data Additional data
     * @return int|false
     */
    public static function success($pms_provider, $sync_type, $message, $data = array()) {
        return self::log($pms_provider, $sync_type, 'success', $message, $data);
    }
    
    /**
     * Log error message
     *
     * @param string $pms_provider PMS provider name
     * @param string $sync_type Type of sync
     * @param string $message Log message
     * @param array $data Additional data
     * @return int|false
     */
    public static function error($pms_provider, $sync_type, $message, $data = array()) {
        return self::log($pms_provider, $sync_type, 'error', $message, $data);
    }
    
    /**
     * Log warning message
     *
     * @param string $pms_provider PMS provider name
     * @param string $sync_type Type of sync
     * @param string $message Log message
     * @param array $data Additional data
     * @return int|false
     */
    public static function warning($pms_provider, $sync_type, $message, $data = array()) {
        return self::log($pms_provider, $sync_type, 'warning', $message, $data);
    }
    
    /**
     * Get logs from the database
     *
     * @param array $args Query arguments
     * @return array Array of log entries
     */
    public static function get_logs($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'pms_provider' => '',
            'sync_type' => '',
            'status' => '',
            'limit' => 100,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $table_name = $wpdb->prefix . 'rental_sync_logs';
        $where = array('1=1');
        
        if (!empty($args['pms_provider'])) {
            $where[] = $wpdb->prepare('pms_provider = %s', $args['pms_provider']);
        }
        
        if (!empty($args['sync_type'])) {
            $where[] = $wpdb->prepare('sync_type = %s', $args['sync_type']);
        }
        
        if (!empty($args['status'])) {
            $where[] = $wpdb->prepare('status = %s', $args['status']);
        }
        
        $where_clause = implode(' AND ', $where);
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        
        $sql = "SELECT * FROM $table_name WHERE $where_clause ORDER BY $orderby LIMIT %d OFFSET %d";
        $sql = $wpdb->prepare($sql, $args['limit'], $args['offset']);
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Clean up old logs
     *
     * @param int $days Number of days to keep logs
     * @return int|false Number of rows deleted
     */
    public static function cleanup_old_logs($days = 30) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'rental_sync_logs';
        $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        return $wpdb->query(
            $wpdb->prepare("DELETE FROM $table_name WHERE created_at < %s", $date)
        );
    }
}
