<?php
/**
 * Logger Class
 *
 * @package RentalSyncEngine\Core
 */

namespace RentalSyncEngine\Core;

/**
 * Logger Class
 * Handles logging of sync operations and errors
 */
class Logger {
    /**
     * Log levels
     */
    const LEVEL_DEBUG = 'debug';
    const LEVEL_INFO = 'info';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';

    /**
     * Log a debug message
     *
     * @param string $message Log message
     * @param string $type Log type
     * @param array  $context Additional context
     */
    public function debug($message, $type = 'general', $context = array()) {
        $this->log(self::LEVEL_DEBUG, $message, $type, $context);
    }

    /**
     * Log an info message
     *
     * @param string $message Log message
     * @param string $type Log type
     * @param array  $context Additional context
     */
    public function info($message, $type = 'general', $context = array()) {
        $this->log(self::LEVEL_INFO, $message, $type, $context);
    }

    /**
     * Log a warning message
     *
     * @param string $message Log message
     * @param string $type Log type
     * @param array  $context Additional context
     */
    public function warning($message, $type = 'general', $context = array()) {
        $this->log(self::LEVEL_WARNING, $message, $type, $context);
    }

    /**
     * Log an error message
     *
     * @param string $message Log message
     * @param string $type Log type
     * @param array  $context Additional context
     */
    public function error($message, $type = 'general', $context = array()) {
        $this->log(self::LEVEL_ERROR, $message, $type, $context);
    }

    /**
     * Log a message
     *
     * @param string $level Log level
     * @param string $message Log message
     * @param string $type Log type
     * @param array  $context Additional context
     */
    private function log($level, $message, $type, $context) {
        global $wpdb;

        // Check if logging is enabled for this level
        $settings = get_option('rental_sync_engine_settings', array());
        $min_level = isset($settings['log_level']) ? $settings['log_level'] : 'info';

        if (!$this->should_log($level, $min_level)) {
            return;
        }

        // Insert into database
        $table_name = $wpdb->prefix . 'rental_sync_logs';
        $wpdb->insert(
            $table_name,
            array(
                'log_type' => sanitize_text_field($type),
                'log_level' => sanitize_text_field($level),
                'message' => sanitize_textarea_field($message),
                'context' => wp_json_encode($context),
                'created_at' => current_time('mysql'),
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );

        // Also log to WordPress debug.log if WP_DEBUG is enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                '[Rental Sync Engine] [%s] [%s] %s',
                strtoupper($level),
                $type,
                $message
            ));
        }
    }

    /**
     * Check if a log level should be logged based on minimum level
     *
     * @param string $level Current log level
     * @param string $min_level Minimum log level
     * @return bool
     */
    private function should_log($level, $min_level) {
        $levels = array(
            self::LEVEL_DEBUG => 0,
            self::LEVEL_INFO => 1,
            self::LEVEL_WARNING => 2,
            self::LEVEL_ERROR => 3,
        );

        return isset($levels[$level], $levels[$min_level]) && $levels[$level] >= $levels[$min_level];
    }

    /**
     * Get logs from database
     *
     * @param array $args Query arguments
     * @return array
     */
    public function get_logs($args = array()) {
        global $wpdb;

        $defaults = array(
            'limit' => 100,
            'offset' => 0,
            'log_type' => null,
            'log_level' => null,
            'order' => 'DESC',
        );

        $args = wp_parse_args($args, $defaults);
        $table_name = $wpdb->prefix . 'rental_sync_logs';

        $where = array('1=1');
        if ($args['log_type']) {
            $where[] = $wpdb->prepare('log_type = %s', $args['log_type']);
        }
        if ($args['log_level']) {
            $where[] = $wpdb->prepare('log_level = %s', $args['log_level']);
        }

        $where_clause = implode(' AND ', $where);
        
        // Validate order direction - use whitelist approach for security
        $allowed_orders = array('ASC', 'DESC');
        $order = in_array(strtoupper($args['order']), $allowed_orders, true) ? strtoupper($args['order']) : 'DESC';

        // Build query with validated ORDER direction
        $query = $wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE {$where_clause} ORDER BY created_at {$order} LIMIT %d OFFSET %d",
            $args['limit'],
            $args['offset']
        );

        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * Clear old logs
     *
     * @param int $days Number of days to keep
     * @return int Number of deleted rows
     */
    public function clear_old_logs($days = 30) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'rental_sync_logs';
        $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        return $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$table_name} WHERE created_at < %s",
                $date
            )
        );
    }
}
