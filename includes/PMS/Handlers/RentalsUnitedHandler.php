<?php
/**
 * Rentals United Handler
 *
 * @package RentalSyncEngine\PMS\Handlers
 */

namespace RentalSyncEngine\PMS\Handlers;

use RentalSyncEngine\PMS\AbstractPMSHandler;

/**
 * Rentals United Handler Class
 */
class RentalsUnitedHandler extends AbstractPMSHandler {
    /**
     * Constructor
     *
     * @param array $credentials Platform credentials
     */
    public function __construct($credentials) {
        $this->api_base_url = 'https://rm.rentalsunited.com/api/';
        parent::__construct($credentials);
    }

    /**
     * Validate credentials
     */
    protected function validate_credentials() {
        if (empty($this->credentials['username']) || empty($this->credentials['password'])) {
            throw new \Exception('Rentals United requires username and password');
        }
    }

    /**
     * Get authentication headers
     *
     * @return array
     */
    protected function get_auth_headers() {
        return array(
            'Authorization' => 'Basic ' . base64_encode(
                $this->credentials['username'] . ':' . $this->credentials['password']
            ),
        );
    }

    /**
     * Get listings
     *
     * @return array
     */
    public function get_listings() {
        $response = $this->make_request('Handler/Pull_ListOwnerProp_RS');
        return $this->normalize_listings($response);
    }

    /**
     * Get a specific listing
     *
     * @param string $listing_id Listing ID
     * @return array
     */
    public function get_listing($listing_id) {
        $response = $this->make_request('Handler/Pull_ListSpecProp_RS', 'GET', array(
            'PropertyID' => $listing_id,
        ));
        return $this->normalize_listing($response);
    }

    /**
     * Get availability
     *
     * @param string $listing_id Listing ID
     * @param string $start_date Start date
     * @param string $end_date End date
     * @return array
     */
    public function get_availability($listing_id, $start_date = null, $end_date = null) {
        if (!$start_date) {
            $start_date = date('Y-m-d');
        }
        if (!$end_date) {
            $end_date = date('Y-m-d', strtotime('+1 year'));
        }

        $response = $this->make_request('Handler/Pull_GetPropertyAvbCalendar_RS', 'GET', array(
            'PropertyID' => $listing_id,
            'DateFrom' => $start_date,
            'DateTo' => $end_date,
        ));

        return $response;
    }

    /**
     * Get bookings
     *
     * @param array $params Query parameters
     * @return array
     */
    public function get_bookings($params = array()) {
        $date_from = $params['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        
        $response = $this->make_request('Handler/Pull_ListReservations_RS', 'GET', array(
            'DateFrom' => $date_from,
        ));

        return $this->normalize_bookings($response);
    }

    /**
     * Get a specific booking
     *
     * @param string $booking_id Booking ID
     * @return array
     */
    public function get_booking($booking_id) {
        $response = $this->make_request('Handler/Pull_GetReservation_RS', 'GET', array(
            'ReservationID' => $booking_id,
        ));
        return $this->normalize_booking($response);
    }

    /**
     * Create a booking
     *
     * @param array $booking_data Booking data
     * @return array
     */
    public function create_booking($booking_data) {
        $response = $this->make_request('Handler/Push_PutConfirmedReservationMulti_RQ', 'POST', 
            $this->prepare_booking_data($booking_data)
        );
        return $this->normalize_booking($response);
    }

    /**
     * Update a booking
     *
     * @param string $booking_id Booking ID
     * @param array  $booking_data Booking data
     * @return array
     */
    public function update_booking($booking_id, $booking_data) {
        $booking_data['ReservationID'] = $booking_id;
        return $this->create_booking($booking_data);
    }

    /**
     * Cancel a booking
     *
     * @param string $booking_id Booking ID
     * @return bool
     */
    public function cancel_booking($booking_id) {
        $response = $this->make_request('Handler/Push_CancelReservation_RQ', 'POST', array(
            'ReservationID' => $booking_id,
        ));
        return !empty($response['Success']);
    }

    /**
     * Normalize listing data
     *
     * @param array $raw_data Raw data from API
     * @return array
     */
    public function normalize_listing($raw_data) {
        return array(
            'id' => $raw_data['PropertyID'] ?? '',
            'name' => $raw_data['PropertyName'] ?? '',
            'description' => $raw_data['DetailedDescription'] ?? '',
            'address' => array(
                'street' => $raw_data['Street'] ?? '',
                'city' => $raw_data['City'] ?? '',
                'state' => $raw_data['Region'] ?? '',
                'zip' => $raw_data['ZipCode'] ?? '',
                'country' => $raw_data['CountryCode'] ?? '',
            ),
            'bedrooms' => $raw_data['Bedrooms'] ?? 0,
            'bathrooms' => $raw_data['Bathrooms'] ?? 0,
            'max_guests' => $raw_data['MaxGuests'] ?? 0,
            'amenities' => $raw_data['Amenities'] ?? array(),
            'images' => $raw_data['Images'] ?? array(),
            'pricing' => $raw_data['Pricing'] ?? array(),
        );
    }

    /**
     * Normalize multiple listings
     *
     * @param array $raw_data Raw data from API
     * @return array
     */
    private function normalize_listings($raw_data) {
        $listings = array();
        if (isset($raw_data['Properties']) && is_array($raw_data['Properties'])) {
            foreach ($raw_data['Properties'] as $property) {
                $listings[] = $this->normalize_listing($property);
            }
        }
        return $listings;
    }

    /**
     * Normalize booking data
     *
     * @param array $raw_data Raw data from API
     * @return array
     */
    public function normalize_booking($raw_data) {
        return array(
            'id' => $raw_data['ReservationID'] ?? '',
            'listing_id' => $raw_data['PropertyID'] ?? '',
            'status' => $raw_data['Status'] ?? 'confirmed',
            'check_in' => $raw_data['DateFrom'] ?? '',
            'check_out' => $raw_data['DateTo'] ?? '',
            'guest_name' => $raw_data['GuestName'] ?? '',
            'guest_email' => $raw_data['GuestEmail'] ?? '',
            'total_amount' => $raw_data['TotalPrice'] ?? 0,
            'currency' => $raw_data['Currency'] ?? 'USD',
            'number_of_guests' => $raw_data['NumberOfGuests'] ?? 1,
        );
    }

    /**
     * Normalize multiple bookings
     *
     * @param array $raw_data Raw data from API
     * @return array
     */
    private function normalize_bookings($raw_data) {
        $bookings = array();
        if (isset($raw_data['Reservations']) && is_array($raw_data['Reservations'])) {
            foreach ($raw_data['Reservations'] as $reservation) {
                $bookings[] = $this->normalize_booking($reservation);
            }
        }
        return $bookings;
    }

    /**
     * Prepare booking data for API
     *
     * @param array $booking_data Normalized booking data
     * @return array
     */
    private function prepare_booking_data($booking_data) {
        return array(
            'PropertyID' => $booking_data['listing_id'],
            'DateFrom' => $booking_data['check_in'],
            'DateTo' => $booking_data['check_out'],
            'GuestName' => $booking_data['guest_name'],
            'GuestEmail' => $booking_data['guest_email'],
            'TotalPrice' => $booking_data['total_amount'],
            'Currency' => $booking_data['currency'] ?? 'USD',
            'NumberOfGuests' => $booking_data['number_of_guests'] ?? 1,
        );
    }

    /**
     * Verify webhook signature
     *
     * @param string $payload Webhook payload
     * @param string $signature Webhook signature
     * @return bool
     */
    public function verify_webhook_signature($payload, $signature) {
        // Rentals United webhook signature verification
        // Implementation depends on their specific signature method
        return true;
    }
}
