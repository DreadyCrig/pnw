<?php

/**
 * Charge Config File *
 * @package         charge_ee_addon
 * @version         1.8.12
 * @author          Joel Bradbury ~ <joel@squarebit.co.uk>
 * @link            http://squarebit.co.uk/addons/charge
 * @copyright       Copyright (c) 2014, Joel Bradbury/Square Bit
 */

if ( ! defined('CHARGE_NAME'))
{
	define('CHARGE_NAME',         'Charge');
	define('CHARGE_CLASS_NAME',   'Charge');
	define('CHARGE_VERSION',      '1.8.12');
	define('CHARGE_DOCS',         'http://squarebit.co.uk/addons/charge');
	define('CHARGE_API_VERSION',  '2014-01-31');

	define('CHARGE_SUBSCRIPTIONS_ENABLED', 		true);

	define('CHARGE_PREFERENCES', 'charge_stripe_account_mode|charge_stripe_test_credentials_sk|charge_stripe_test_credentials_pk|charge_stripe_live_credentials_sk|charge_stripe_live_credentials_pk|charge_stripe_currency|charge_logging_level|charge_webhook_key|charge_force_ssl|charge_metadata_pass' );
}

$config['name']    = CHARGE_NAME;
$config['version'] = CHARGE_VERSION;
