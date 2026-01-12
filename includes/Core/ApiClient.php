<?php
/**
 * Base API Client for PMS integrations
 *
 * @package RentalSyncEngine\Core
 */

namespace RentalSyncEngine\Core;

/**
 * Abstract class ApiClient
 */
abstract class ApiClient {
    
    /**
     * API base URL
     *
     * @var string
     */
    protected $base_url;
    
    /**
     * Request timeout in seconds
     *
     * @var int
     */
    protected $timeout = 30;
    
    /**
     * Rate limit tracker
     *
     * @var array
     */
    protected $rate_limit = array(
        'requests' => 0,
        'reset_time' => 0,
        'max_requests' => 100,
        'window' => 60 // seconds
    );
    
    /**
     * Constructor
     *
     * @param string $base_url API base URL
     */
    public function __construct($base_url) {
        $this->base_url = rtrim($base_url, '/');
    }
    
    /**
     * Make a GET request
     *
     * @param string $endpoint API endpoint
     * @param array $params Query parameters
     * @return array Response data
     * @throws \Exception
     */
    protected function get($endpoint, $params = array()) {
        $url = $this->build_url($endpoint, $params);
        $args = $this->prepare_request_args('GET');
        
        $response = wp_remote_get($url, $args);
        return $this->handle_response($response);
    }
    
    /**
     * Make a POST request
     *
     * @param string $endpoint API endpoint
     * @param array $data Request body data
     * @return array Response data
     * @throws \Exception
     */
    protected function post($endpoint, $data = array()) {
        $url = $this->build_url($endpoint);
        $args = $this->prepare_request_args('POST', $data);
        
        $response = wp_remote_post($url, $args);
        return $this->handle_response($response);
    }
    
    /**
     * Make a PUT request
     *
     * @param string $endpoint API endpoint
     * @param array $data Request body data
     * @return array Response data
     * @throws \Exception
     */
    protected function put($endpoint, $data = array()) {
        $url = $this->build_url($endpoint);
        $args = $this->prepare_request_args('PUT', $data);
        
        $response = wp_remote_request($url, $args);
        return $this->handle_response($response);
    }
    
    /**
     * Make a DELETE request
     *
     * @param string $endpoint API endpoint
     * @return array Response data
     * @throws \Exception
     */
    protected function delete($endpoint) {
        $url = $this->build_url($endpoint);
        $args = $this->prepare_request_args('DELETE');
        
        $response = wp_remote_request($url, $args);
        return $this->handle_response($response);
    }
    
    /**
     * Build full URL from endpoint and parameters
     *
     * @param string $endpoint API endpoint
     * @param array $params Query parameters
     * @return string Full URL
     */
    protected function build_url($endpoint, $params = array()) {
        $url = $this->base_url . '/' . ltrim($endpoint, '/');
        
        if (!empty($params)) {
            $url = add_query_arg($params, $url);
        }
        
        return $url;
    }
    
    /**
     * Prepare request arguments for wp_remote_*() functions
     *
     * @param string $method HTTP method
     * @param array $data Request body data (for POST/PUT)
     * @return array Request arguments
     */
    protected function prepare_request_args($method, $data = array()) {
        // Check rate limit
        $this->check_rate_limit();
        
        // Get authentication headers
        $headers = $this->get_auth_headers();
        
        // Build request args
        $args = array(
            'method' => $method,
            'headers' => $headers,
            'timeout' => $this->timeout,
            'sslverify' => true,
        );
        
        // Add body data for POST/PUT requests
        if (!empty($data) && in_array($method, array('POST', 'PUT'))) {
            // Check if we need JSON encoding based on Content-Type
            if (isset($headers['Content-Type']) && $headers['Content-Type'] === 'application/json') {
                $args['body'] = wp_json_encode($data);
            } else {
                // For XML and other content types, data should be provided as string
                $args['body'] = $data;
            }
        }
        
        return $args;
    }
    
    /**
     * Handle HTTP response
     *
     * @param array|\WP_Error $response WordPress HTTP response
     * @return array Response data
     * @throws \Exception
     */
    protected function handle_response($response) {
        // Check for WP_Error
        if (is_wp_error($response)) {
            throw new \Exception('API request failed: ' . $response->get_error_message());
        }
        
        // Get response code
        $code = wp_remote_retrieve_response_code($response);
        
        // Check for HTTP errors
        if ($code < 200 || $code >= 300) {
            $body = wp_remote_retrieve_body($response);
            throw new \Exception('API request failed with status ' . $code . ': ' . $body);
        }
        
        // Update rate limit
        $this->update_rate_limit($response);
        
        // Get response body
        $body = wp_remote_retrieve_body($response);
        
        // Try to decode JSON response
        $data = json_decode($body, true);
        
        // Return decoded data or raw body if not JSON
        return $data !== null ? $data : array('body' => $body);
    }
    
    /**
     * Make a custom HTTP request (for special cases)
     * This is exposed for subclasses that need more control
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array $custom_args Custom request arguments
     * @return array Response data
     * @throws \Exception
     */
    protected function request($method, $endpoint, $custom_args = array()) {
        // Check rate limit
        $this->check_rate_limit();
        
        $url = $this->build_url($endpoint);
        
        // Merge with default args
        $default_args = array(
            'method' => $method,
            'timeout' => $this->timeout,
            'sslverify' => true,
            'headers' => $this->get_auth_headers(),
        );
        
        $args = wp_parse_args($custom_args, $default_args);
        
        // Make request
        $response = wp_remote_request($url, $args);
        
        return $this->handle_response($response);
    }
    
    /**
     * Get authentication headers
     *
     * @return array Authentication headers
     */
    abstract protected function get_auth_headers();
    
    /**
     * Check rate limit
     *
     * @throws \Exception
     */
    protected function check_rate_limit() {
        // Reset counter if window has passed
        if (time() > $this->rate_limit['reset_time']) {
            $this->rate_limit['requests'] = 0;
            $this->rate_limit['reset_time'] = time() + $this->rate_limit['window'];
        }
        
        // Check if limit reached
        if ($this->rate_limit['requests'] >= $this->rate_limit['max_requests']) {
            $wait_time = $this->rate_limit['reset_time'] - time();
            if ($wait_time > 0) {
                sleep($wait_time);
                $this->rate_limit['requests'] = 0;
                $this->rate_limit['reset_time'] = time() + $this->rate_limit['window'];
            }
        }
    }
    
    /**
     * Update rate limit from response headers
     *
     * @param array $response HTTP response from wp_remote_*()
     */
    protected function update_rate_limit($response) {
        $this->rate_limit['requests']++;
        
        // Get response headers
        $headers = wp_remote_retrieve_headers($response);
        
        // Check for rate limit headers
        if (isset($headers['x-ratelimit-remaining'])) {
            $remaining = (int) $headers['x-ratelimit-remaining'];
            $this->rate_limit['requests'] = $this->rate_limit['max_requests'] - $remaining;
        }
        
        if (isset($headers['x-ratelimit-reset'])) {
            $this->rate_limit['reset_time'] = (int) $headers['x-ratelimit-reset'];
        }
    }
}
