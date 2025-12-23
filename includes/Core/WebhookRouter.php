<?php
/**
 * Webhook Router for the Rental Sync Engine
 *
 * @package RentalSyncEngine\Core
 */

namespace RentalSyncEngine\Core;

/**
 * Class WebhookRouter
 */
class WebhookRouter {
    
    /**
     * Initialize webhook router
     */
    public static function init() {
        add_action('init', array(__CLASS__, 'add_rewrite_rules'));
        add_action('template_redirect', array(__CLASS__, 'handle_webhook'));
    }
    
    /**
     * Add rewrite rules for webhook endpoints
     */
    public static function add_rewrite_rules() {
        add_rewrite_rule(
            '^rental-sync-webhook/([^/]+)/?',
            'index.php?rental_sync_webhook=$matches[1]',
            'top'
        );
        
        add_filter('query_vars', function($vars) {
            $vars[] = 'rental_sync_webhook';
            return $vars;
        });
    }
    
    /**
     * Handle incoming webhook requests
     */
    public static function handle_webhook() {
        $provider = get_query_var('rental_sync_webhook');
        
        if (empty($provider)) {
            return;
        }
        
        // Verify request method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            self::send_response(array('error' => 'Method not allowed'), 405);
            return;
        }
        
        // Get request body
        $body = file_get_contents('php://input');
        $data = json_decode($body, true);
        
        // Route to appropriate handler
        try {
            switch ($provider) {
                case 'rentals-united':
                    $handler = new \RentalSyncEngine\PMS\RentalsUnited\WebhookHandler();
                    $result = $handler->handle($data);
                    break;
                case 'ownerrez':
                    $handler = new \RentalSyncEngine\PMS\OwnerRez\WebhookHandler();
                    $result = $handler->handle($data);
                    break;
                case 'uplisting':
                    $handler = new \RentalSyncEngine\PMS\Uplisting\WebhookHandler();
                    $result = $handler->handle($data);
                    break;
                case 'hostaway':
                    $handler = new \RentalSyncEngine\PMS\Hostaway\WebhookHandler();
                    $result = $handler->handle($data);
                    break;
                default:
                    self::send_response(array('error' => 'Unknown provider'), 404);
                    return;
            }
            
            self::send_response($result);
        } catch (\Exception $e) {
            Logger::error($provider, 'webhook', $e->getMessage(), array(
                'trace' => $e->getTraceAsString()
            ));
            self::send_response(array('error' => 'Internal server error'), 500);
        }
    }
    
    /**
     * Send webhook response
     *
     * @param array $data Response data
     * @param int $status_code HTTP status code
     */
    private static function send_response($data, $status_code = 200) {
        status_header($status_code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
