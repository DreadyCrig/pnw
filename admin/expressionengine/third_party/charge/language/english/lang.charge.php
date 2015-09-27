<?php defined('BASEPATH') or exit('No direct script access allowed');

$lang = array(

    'charge_module_name'                => 'Charge',
    'charge_module_description'         => 'One-off and recurring payments, membership subscriptions, donations and more',

    'charge_title'                      => 'Charge',
    'charge_logs'                       => 'Logs',
    'charge_coupons'                    => 'Coupons',
    'charge_settings'                   => 'Settings',
    'charge_actions'                    => 'Actions',
    'charge_subscriptions'              => 'Subscriptions',
    'add_action'                        => 'Add Action',
    'view_action'                       => 'View Action',
    'add_subscription'                  => 'Add Subscription',
    'view_subscription'                 => 'View Subscription',
    'charge_subscription_members'       => 'Subscription Members',
    'charge_webhooks'                   => 'Webhooks',


    'charge_error_email'                => 'Must be a valid email',
    'charge_error_numeric'              => 'Must be a number',
    'charge_error_required'             => 'Required field',
    'charge_error_invalid_currency'     => 'Not a valid currency',
    'charge_error_missing_amount'       => 'Amount required',
    'charge_error_integer'              => 'Must be an integer',
    'charge_error_numeric'              => 'Must be numeric',
    'charge_error_not_valid_value'      => 'Not a valid value',
    'charge_error_too_short'            => 'Insufficient characters',
    'charge_error_too_long'             => 'Too many characters',
    'charge_error_plan_length_too_long' => 'Subscription lengths must be shorter than 1 year',
    'charge_error_plan_length_invalid'  => 'Invalid subscription length',
    'charge_error_below_min'            => 'Must be a value more than {min}',
    'charge_error_above_max'            => 'Must be a value less than {max}',
    'charge_error_card_token_missing'   => 'Card token missing - javascript is required for this payment form',
    'charge_error_not_alphanumeric'     => 'Must be a valid string',

	'charge_unknown_error'				=> 'Something went wrong with your payment',
	'charge_stripe_cancel_no_active'	=> 'No active subscriptions for customer: {customer_id}',
	'charge_stripe_cancel_no_match'		=> 'Customer {customer_id} does not have a subscription with ID {sub_id}',


	// Subscriptions
	'charge_subscription_no_settings'		=> 'You need at least one setting for a subscription',
	'charge_subscription_no_name'			=> 'A name is required for the subscription',
	'charge_required_setting'				=> 'Setting required',
	'charge_subscription_no_valid_group'	=> 'Member group required',
	'charge_subscription_no_invalid_group'	=> 'Member group required',
	'charge_action_no_name'					=> 'A name is required for this action',
	'charge_action_no_shortname'			=> 'A shortname is required for this action',

	// Coupons
	'charge_coupon_no_name'					=> 'A name is required for this coupon',
	'charge_coupon_no_code'					=> 'A code is required for this coupon',
	'charge_coupon_code_unique'				=> 'Coupon codes must be unique',
	'charge_coupon_create_error'			=> 'There was a problem creating the coupon with Stripe',
	'charge_coupon_error_invalid_code'		=> 'This coupon code is invalid. Check you\'ve entered it correctly',
	'charge_coupon_error_invalid_recurring'	=> 'This coupon can only be used with recurring payments',
	'charge_coupon_error_invalid_charge'	=> 'This coupon can only be used with one-off payments',
	'charge_coupon_error_invalid' 			=> 'Sorry, this coupon is invalid',
	'charge_coupon_error_below_min'			=> 'Sorry, applying this coupon brings your total below the minimum we can charge',



	// Log items
	'charge_attempt_start'					=> 'Attempting Payment Start',
	'charge_attempt_start_one_off'			=> 'Attempting One-Off Payment',
	'charge_attempt_start_recurring'		=> 'Attempting Recurring Payment',
	'charge_plan_created'					=> 'Created a new Stripe Plan',
	'charge_customer_created'				=> 'Created a new Stripe Customer',
	'charge_charge_created'					=> 'One-Off Payment Success',
	'charge_error_creating_customer'		=> 'Error while creating new customer',
	'charge_error_creating_charge'			=> 'Error while creating a new one-off payment',
	'charge_error_failed_general'			=> 'Error, failed with a general error',
	'charge_error_no_plan'					=> 'Error couldn\'t find a matching recurring plan',
	'charge_error_no_customer'				=> 'Error couldn\'t find a matching customer',
	'charge_exception'						=> 'Charge Exception',
	'charge_stripe_exception'				=> 'Stripe Exception',
	'charge_trigger_start'					=> 'Action Trigger started',
	'charge_trigger_failed_to_decode_action'=> 'Action Trigger failed to decode action',
	'charge_trigger_failed_to_run'			=> 'Action Trigger failed to run',
	'charge_error_on_plan_creation'			=> 'Error couldn\'t create a new recurring plan',
	'charge_trigger_ran_successfully'		=> 'Action Trigger success',
	'charge_plan_create_start'				=> 'Attempting Create new recurring plan',
	'charge_action_start'					=> 'Action Triggered',
	'charge_action_email_success'			=> 'Action : Email Success',
	'charge_action_email_failed'			=> 'Action : Email Failed',

	'charge_webhook_bad_trigger'			=> 'Webhook triggered with bad key or mode',
	'charge_webhook_empty'					=> 'Webhook triggered but body was empty',
	'charge_webhook_good'					=> 'Webhook triggered with good data',

	// Members
	'charge_member_group_low' 				=> 'Registered member can\'t be added to secure member group',
	'charge_member_account_exists'			=> 'An account is already registered with this email',
	'charge_member_error'					=> 'Sorry, there was an error creating a new member account',
	'charge_member_password_must_match'		=> 'Passwords must match',
	'charge_member_password_confirm'		=> 'You must confirm your password',


	'' => ''
);
