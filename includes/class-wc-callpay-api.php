<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Callpay_API class.
 *
 * Communicates with Enterprise API.
 */
class WC_Callpay_API {

	/**
	 * Secret API Username.
	 * @var string
	 */
	private static $username = '';

    /**
     * Secret API Password.
     * @var string
     */
    private static $password = '';

    private static $settings = [
        'api_endpoint' => 'agent.callpay.lh/api/v1/',
        'app_domain' => 'agent.callpay.lh'
    ];

    /**
     * @param $setting
     * @return mixed|null
     */
    public static function getSetting($setting) {
        $settings = self::$settings;
        if ( file_exists( dirname( __FILE__ ) . '/local-config.php' ) ) {
            $settings = array_merge($settings, require(dirname( __FILE__ ) . '/local-config.php'));
        }

        if (isset($settings[$setting])) {
            return $settings[$setting];
        }
        return null;
    }

	/**
	 * Set api username.
	 * @param string $username
	 */
	public static function set_username( $username ) {
		self::$username = $username;
	}

	/**
	 * Get secret key.
	 * @return string
	 */
	public static function get_username() {
		if ( ! self::$username ) {
			$options = get_option( 'woocommerce_callpay_settings' );

			if ( isset($options['username'])) {
				self::set_username( $options['username']  );
			}
		}
		return self::$username;
	}

    /**
     * Set api password.
     * @param string $password
     */
    public static function set_password( $password ) {
        self::$password = $password;
    }

    /**
     * Get api password.
     * @return string
     */
    public static function get_password() {
        if ( ! self::$password ) {
            $options = get_option( 'woocommerce_callpay_settings' );

            if ( isset($options['password'])) {
                self::set_password( $options['password']  );
            }
        }
        return self::$password;
    }

	/**
	 * Send the request to Enterprise's API
	 *
	 * @param mixed $request
	 * @param string $api
	 * @param $method
	 * @return array|WP_Error
	 */
	public static function request( $request, $api = 'user/login', $method = 'POST' ) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $endPoint = $protocol.self::getSetting('api_endpoint') ;
	    $url = $endPoint . $api;
		WC_Callpay::log( "{$api} request to ".$url . print_r( $request, true ) );

		$response = wp_remote_post(
            $url,
			array(
				'method'        => $method,
				'headers'       => array(
					'Authorization'  => 'Basic ' . base64_encode( self::get_username(). ':' . self::get_password() ),
				),
				'body'       => apply_filters( 'woocommerce_callpay_request_body', $request, $api ),
				'timeout'    => 70,
				'user-agent' => 'WooCommerce ' . WC()->version,
                'x-domain' => home_url( '/' )
			)
		);

        if (is_wp_error($response)) {
            WC_Callpay::log( "WP Error: ".$response->get_error_message());
            return $response;
        }

        WC_Callpay::log( "Response: ".$response['body']);

        $parsed_response = json_decode( $response['body']);

        if(empty( $response['body'] ) ) {
            WC_Callpay::log( "Error Response: " . print_r( $response, true ) );
            return new WP_Error( 'callpay_error', __( 'There was a problem connecting to the payment gateway.'.$parsed_response->name, 'woocommerce-gateway-callpay' ) );
        }

        if ( is_wp_error( $response )) {
			WC_Callpay::log( "Error Response: " . print_r( $response, true ) );
			return new WP_Error( 'callpay_error', __( 'There was a problem connecting to the payment gateway.'.$parsed_response->name, 'woocommerce-gateway-callpay' ) );
		}

		// Handle response
        return $parsed_response;
	}

    /**
     * Fetches an api token
     *
     * @return mixed
     * @throws Exception
     */
	public static function get_token_data() {
        $response = self::request( '', 'token', 'POST' );
        if ( is_wp_error( $response ) ) {
            WC_Callpay::log( 'Callpay Token API Error: '.$response->get_error_message() );
            throw new Exception('Callpay Token: '.$response->get_error_message());
        }
        return $response;
    }

    /**
     * @param $transaction_id
     * @return stdClass|WP_Error
     * @throws Exception
     */
    public static function get_transaction_data($transaction_id) {
        $response = self::request('', 'gateway-transaction/'.$transaction_id, 'GET');
        if ( is_wp_error( $response ) ) {
            WC_Callpay::log( 'Callpay Transaction API Error: '.$response->get_error_message() );
        }
        return $response;
    }

    /**
     * Fetches a payment key
     *
     * @param $data array
     * @return stdClass|WP_Error
     * @throws Exception
     */
    public static function get_payment_key_data($data = []) {
        if(!empty($data['card_token'])) {
            $data['payment_type'] = 'credit_card';
        } else {
            $data['payment_type'] = [
                'eft',
                'credit_card'
            ];
        }
        $query = http_build_query($data);
        $response = self::request( $query, 'eft/payment-key', 'POST' );
        if ( is_wp_error( $response ) ) {
            WC_Callpay::log( 'Callpay Payment Key API Error: '.$response->get_error_message() );
            throw new Exception('Callpay Payment Key: '.$response->get_error_message());
        }
        return $response;
    }

    public static function get_card_token_data($reference) {
        $response = self::request('', 'customer-token/' . $reference  , 'GET');
        if ( is_wp_error( $response ) ) {
            WC_Callpay::log( 'Callpay Transaction API Error: '.$response->get_error_message() );
        }
        return $response;
    }

    public static function store_card_token_data($userId, $cardData)
    {
        $tokenData = WC_Payment_Tokens::get_tokens($userId);
        if ($tokenData == null) {
            $pan = substr($cardData->pan, 12, 4);
            $token = new WC_Payment_Token_CC();
            $token->set_token($cardData->guid); // Token comes from payment processor
            $token->set_gateway_id('callpay');
            $token->set_last4($pan);
            $time = explode('-', $cardData->expiry_date);
            $token->set_expiry_year($time[1]);
            $token->set_expiry_month($time[0]);
            $token->set_card_type('savedCard');
            $token->set_user_id($userId);
            $token->save();
            // Set this token as the users new default token
            WC_Payment_Tokens::set_users_default($userId, $token->get_id());
        }
    }

    public static function supports_tokenization()
    {
        $versionValue = false;
        if (version_compare(WC_VERSION, '2.6', '>=')) {
            $versionValue = true;
        }
        return $versionValue;
    }

}


