<?php
/**
 * Manual Class Autoloader for Rental Sync Engine
 * Replaces Composer's PSR-4 autoloader with a simple manual implementation
 *
 * @package RentalSyncEngine
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Autoload classes using PSR-4 standard
 *
 * @param string $class_name Fully qualified class name
 */
function rental_sync_engine_autoloader($class_name) {
    // Only autoload classes in our namespace
    $namespace_prefix = 'RentalSyncEngine\\';
    
    // Check if the class uses our namespace
    if (strpos($class_name, $namespace_prefix) !== 0) {
        return;
    }
    
    // Remove the namespace prefix
    $class_name_without_namespace = substr($class_name, strlen($namespace_prefix));
    
    // Replace namespace separators with directory separators
    $file_path = str_replace('\\', DIRECTORY_SEPARATOR, $class_name_without_namespace);
    
    // Build the full file path
    $file = RENTAL_SYNC_ENGINE_PATH . 'includes' . DIRECTORY_SEPARATOR . $file_path . '.php';
    
    // If the file exists, require it
    if (file_exists($file)) {
        require_once $file;
    }
}

// Register the autoloader
spl_autoload_register('rental_sync_engine_autoloader');
