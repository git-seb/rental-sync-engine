<?php
/**
 * Base API Client for PMS integrations
 *
 * @package RentalSyncEngine\Core
 */

namespace RentalSyncEngine\Core;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Abstract class ApiClient
 */
abstract class ApiClient {
    
    /**
     * HTTP client
     *
     * @var Client
     */
    protected $client;
    
    /**
     * API base URL
     *
     * @var string
     */
    protected $base_url;
    
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
        $this->client = new Client(array(
            'base_uri' => $this->base_url,
            'timeout' => 30,
            'verify' => true,
        ));
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
        return $this->request('GET', $endpoint, array(
            'query' => $params
        ));
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
        return $this->request('POST', $endpoint, array(
            'json' => $data
        ));
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
        return $this->request('PUT', $endpoint, array(
            'json' => $data
        ));
    }
    
    /**
     * Make a DELETE request
     *
     * @param string $endpoint API endpoint
     * @return array Response data
     * @throws \Exception
     */
    protected function delete($endpoint) {
        return $this->request('DELETE', $endpoint);
    }
    
    /**
     * Make an HTTP request
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array $options Request options
     * @return array Response data
     * @throws \Exception
     */
    protected function request($method, $endpoint, $options = array()) {
        // Check rate limit
        $this->check_rate_limit();
        
        // Add authentication headers
        $options['headers'] = array_merge(
            $this->get_auth_headers(),
            isset($options['headers']) ? $options['headers'] : array()
        );
        
        try {
            $response = $this->client->request($method, $endpoint, $options);
            
            // Update rate limit
            $this->update_rate_limit($response);
            
            $body = $response->getBody()->getContents();
            return json_decode($body, true);
        } catch (GuzzleException $e) {
            throw new \Exception('API request failed: ' . $e->getMessage());
        }
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
     * @param \Psr\Http\Message\ResponseInterface $response HTTP response
     */
    protected function update_rate_limit($response) {
        $this->rate_limit['requests']++;
        
        // Check for rate limit headers
        if ($response->hasHeader('X-RateLimit-Remaining')) {
            $remaining = (int) $response->getHeaderLine('X-RateLimit-Remaining');
            $this->rate_limit['requests'] = $this->rate_limit['max_requests'] - $remaining;
        }
        
        if ($response->hasHeader('X-RateLimit-Reset')) {
            $this->rate_limit['reset_time'] = (int) $response->getHeaderLine('X-RateLimit-Reset');
        }
    }
}
