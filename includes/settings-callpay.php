<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return apply_filters( 'wc_enterprise_settings',
	array(
		'enabled' => array(
			'title'       => __( 'Enable/Disable', 'woocommerce-gateway-callpay' ),
			'label'       => __( 'Enable Enterprise', 'woocommerce-gateway-callpay' ),
			'type'        => 'checkbox',
			'description' => '',
			'default'     => 'no'
		),
        'checkout_enabled' => array(
            'title'       => __( 'Enable Checkout Widget', 'woocommerce-gateway-callpay' ),
            'label'       => __( 'Enable javascript popup instead of redirect', 'woocommerce-gateway-callpay' ),
            'type'        => 'checkbox',
            'description' => '',
            'default'     => 'no'
        ),
		'title' => array(
			'title'       => __( 'Title', 'woocommerce-gateway-callpay' ),
			'type'        => 'text',
			'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-gateway-callpay' ),
			'default'     => __( 'Enterprise', 'woocommerce-gateway-callpay' ),
			'desc_tip'    => true,
		),
		'description' => array(
			'title'       => __( 'Description', 'woocommerce-gateway-callpay' ),
			'type'        => 'text',
			'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce-gateway-callpay' ),
			'default'     => __( 'Pay using your credit card details.', 'woocommerce-gateway-callpay'),
			'desc_tip'    => true,
		),
		'username' => array(
			'title'       => __( 'API Username', 'woocommerce-gateway-callpay' ),
			'type'        => 'text',
			'description' => __( 'Get your API username from your Enterprise account.', 'woocommerce-gateway-callpay' ),
			'default'     => '',
			'desc_tip'    => true,
		),
        'password' => array(
            'title'       => __( 'API Password', 'woocommerce-gateway-callpay' ),
            'type'        => 'password',
            'description' => __( 'Get your API password from your Enterprise account.', 'woocommerce-gateway-callpay' ),
            'default'     => '',
            'desc_tip'    => true,
        ),
		'logging' => array(
			'title'       => __( 'Logging', 'woocommerce-gateway-callpay' ),
			'label'       => __( 'Log debug messages', 'woocommerce-gateway-callpay' ),
			'type'        => 'checkbox',
			'description' => __( 'Save debug messages to the WooCommerce System Status log.', 'woocommerce-gateway-callpay' ),
			'default'     => 'no',
			'desc_tip'    => true,
		),
	)
);
