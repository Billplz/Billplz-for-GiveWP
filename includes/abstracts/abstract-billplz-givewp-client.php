<?php
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * API client class.
 * 
 * @since 4.0.0
 */
abstract class Billplz_GiveWP_Client {
    const PRODUCTION_API_URL = 'https://www.billplz.com/api/';
    const SANDBOX_API_URL = 'https://www.billplz-sandbox.com/api/';

    protected $api_key;
    protected $xsignature_key;
    protected $sandbox = true;

    /**
     * Constructor.
     * 
     * @since 4.0.0
     * 
     * @param string $api_key
     * @param string $xsignature_key
     * @param bool $sandbox
     */
    public function __construct( $api_key, $xsignature_key, bool $sandbox = true ) {
        $this->api_key = $api_key;
        $this->xsignature_key = $xsignature_key;
        $this->sandbox = $sandbox;
    }

    /**
     * HTTP request URL.
     * 
     * @since 4.0.0
     * 
     * @param string|null $route
     * @return string
     */
    private function get_url( $route = null ) {
        if ( $this->sandbox ) {
            return self::SANDBOX_API_URL . $route;
        } else {
            return self::PRODUCTION_API_URL . $route;
        }
    }

    /**
     * HTTP request headers.
     * 
     * @since 4.0.0
     * 
     * @return array
     */
    private function get_headers() {
        $headers = array(
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        );

        if ( $this->api_key ) {
            $headers['Authorization'] = 'Basic ' . base64_encode( $this->api_key . ':' );
        }

        return $headers;
    }

    /**
     * Handle HTTP GET request.
     * 
     * @since 4.0.0
     * 
     * @param string $route
     * @param array $params
     * @return array<integer, mixed>
     * @throws \Exception
     */
    protected function get( $route, $params = array() ) {
        return $this->request( $route, $params, 'GET' );
    }

    /**
     * Handle HTTP POST request.
     * 
     * @since 4.0.0
     * 
     * @param string $route
     * @param array $params
     * @return array<integer, mixed>
     * @throws \Exception
     */
    protected function post( $route, $params = array() ) {
        return $this->request( $route, $params );
    }

    /**
     * Handle HTTP request.
     * 
     * @since 4.0.0
     * 
     * @param string $route
     * @param array $params
     * @param string $method
     * @return array<integer, mixed>
     * @throws \Exception
     */
    protected function request( $route, $params = array(), $method = 'POST' ) {
        if ( !$this->api_key ) {
            throw new Exception( __( 'Missing API key', 'billplz-givewp' ) );
        }

        $url = $this->get_url( $route );
        $headers = $this->get_headers();

        $args = array(
            'headers' => $headers,
            'body' => $params,
            'timeout' => 30,
        );

        if ( $method === 'POST' ) {
            $args['body'] = wp_json_encode( $params );
        }

        switch ( $method ) {
            case 'GET':
                $response = wp_remote_get( $url, $args );
                break;

            case 'POST':
                $response = wp_remote_post( $url, $args );
                break;

            default:
                $args['method'] = $method;
                $response = wp_remote_request( $url, $args );
        }

        if ( is_wp_error( $response ) ) {
            throw new Exception( $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        return array( $code, $body );
    }

    /**
     * Get IPN response.
     * 
     * @since 4.0.0
     * 
     * @return array
     * @throws \Exception
     */
    public function get_ipn_response() {
        if ( !in_array( $_SERVER['REQUEST_METHOD'], array( 'GET', 'POST' ) ) ) {
            throw new Exception( __( 'Invalid IPN response', 'billplz-givewp' ) );
        }

        if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
            $response = $this->get_valid_ipn_callback_response();
        } else {
            $response = $this->get_valid_ipn_redirect_response();
        }

        if ( !$response ) {
            throw new Exception( __( 'Invalid IPN response', 'billplz-givewp' ) );
        }

        return $response;
    }

    /**
     * Get IPN (callback) response.
     * 
     * @since 4.0.0
     * 
     * @return array
     * @throws \Exception
     */
    private function get_valid_ipn_callback_response() {
        $required_params = $this->get_ipn_callback_params();
        $optional_params = $this->get_ipn_optional_params();

        $allowed_params = array();
        $params = array_merge( $required_params, $optional_params );

        foreach ( $params as $param ) {
            // Skip if optional parameters are not passed in the URL
            if ( in_array( $param, $optional_params ) && !isset( $_POST[ $param ] ) ) {
                continue;
            }

            if ( !isset( $_POST[ $param ] ) ) {
                throw new Exception( sprintf( __( 'Missing IPN parameter - %s', 'billplz-givewp' ), $param ) );
            }

            $allowed_params[ $param ] = trim( sanitize_text_field( $_POST[ $param ] ) );
        }

        // Returns only the allowed response data
        return $allowed_params;
    }

    /**
     * Get IPN (redirect) response.
     * 
     * @since 4.0.0
     * 
     * @return array
     * @throws \Exception
     */
    private function get_valid_ipn_redirect_response() {
        $required_params = $this->get_ipn_redirect_params();
        $optional_params = $this->get_ipn_optional_params();

        $allowed_params = array();
        $params = array_merge( $required_params, $optional_params );

        foreach ( $params as $param ) {
            // Skip if optional parameters are not passed in the URL
            if ( in_array( $param, $optional_params ) && !isset( $_GET['billplz'][ $param ] ) ) {
                continue;
            }

            if ( !isset( $_GET['billplz'][ $param ] ) ) {
                throw new Exception( sprintf( __( 'Missing IPN parameter - %s', 'billplz-givewp' ), $param ) );
            }

            $param_new_key = $param;

            if ( $param != 'x_signature' ) {
                $param_new_key = 'billplz' . $param;
            }

            $allowed_params[ $param_new_key ] = trim( sanitize_text_field( $_GET['billplz'][ $param ] ) );
        }

        // Returns only the allowed response data
        return $allowed_params;
    }

    /**
     * Required parameters for IPN (callback) response.
     * 
     * @since 4.0.0
     * 
     * @return array
     */
    private function get_ipn_callback_params() {
        return array(
            'amount',
            'collection_id',
            'due_at',
            'email',
            'id',
            'mobile',
            'name',
            'paid_amount',
            'paid_at',
            'paid',
            'state',
            'transaction_id',
            'transaction_status',
            'url',
            'x_signature',
        );
    }

    /**
     * Required parameters for IPN (redirect) response.
     * 
     * @since 4.0.0
     * 
     * @return array
     */
    private function get_ipn_redirect_params() {
        return array(
            'id',
            'paid_at',
            'paid',
            'transaction_id',
            'transaction_status',
            'x_signature',
        );
    }

    /**
     * Optional parameters for IPN response (both callback and redirect) if Extra Payment Completion Information is enabled.
     * 
     * @since 4.0.0
     * 
     * @return array
     */
    private function get_ipn_optional_params() {
        return array(
            'transaction_id',
            'transaction_status',
        );
    }

    /**
     * Validate the IPN response.
     * 
     * @since 4.0.0
     * 
     * @return bool
     * @throws \Exception
     */
    public function validate_ipn_response( $response ) {
        if ( !$this->verify_signature( $response ) ) {
            throw new Exception( __( 'Signature mismatch', 'billplz-givewp' ) );
        }

        return true;
    }

    /**
     * Verify the signature value in the IPN response.
     * 
     * @since 4.0.0
     * 
     * @return bool
     * @throws \Exception
     */
    private function verify_signature( $response ) {
        $ipn_signature = isset( $response['x_signature'] ) ? $response['x_signature'] : null;

        if ( !$ipn_signature ) {
            throw new Exception( __( 'Missing IPN signature', 'billplz-givewp' ) );
        }

        unset( $response['x_signature'] );

        $data = array();

        foreach ( $response as $key => $value ) {
            $data[] = $key . $value;
        }

        // Generate a signature using the response data and X-Signature from Billplz dashboard
        $encoded_data = implode( '|', $data );
        $generated_signature = hash_hmac( 'sha256', $encoded_data, $this->xsignature_key );

        // Compare the generated signature value with the signature value in the IPN response
        return $ipn_signature == $generated_signature;
    }
}
