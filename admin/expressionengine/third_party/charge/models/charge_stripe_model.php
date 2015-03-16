<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Charge Stripe Model class
 *
 * @package         charge_ee_addon
 * @version         1.9.0
 * @author          Joel Bradbury ~ <joel@squarebit.co.uk>
 * @link            http://squarebit.co.uk/software/expressionengine/charge
 * @copyright       Copyright (c) 2015, Joel Bradbury/Square Bit
 */
class Charge_stripe_model extends Charge_model
{

    public $data;
    private $create_plan_if_not_exists = true;
    public $recurring_plan;
    public $errors;
    public $messages;

    public $api_error_message;


    public $default_currency = 'usd';
    public $supported_currencies = array('usd' => array('name' => 'American Dollar', 'symbol' => '&#36;', 'symbol_long' => 'US&#36;'),
                                         'gbp' => array('name' => 'British Pound Sterling', 'symbol' => '&#163;', 'symbol_long' => '&#163;'),
                                         'eur' => array('name' => 'Euro', 'symbol' => '&#128;', 'symbol_long' => '&#128;'),
                                         'cad' => array('name' => 'Canadian Dollars', 'symbol' => '&#36;', 'symbol_long' => 'CA&#36;'),
                                         'aud' => array('name' => 'Australian Dollar', 'symbol' => '&#36;', 'symbol_long' => 'AU&#36;'),
                                         'hkd' => array('name' => 'Hong Kong Dollar', 'symbol' => '&#36;', 'symbol_long' => 'HK&#36;'),
                                         'sek' => array('name' => 'Swedish Krona', 'symbol' => ':-', 'symbol_long' => 'kr'),
                                         'dkk' => array('name' => 'Danish Krone', 'symbol' => ',-', 'symbol_long' => 'dkr'),
                                         'pen' => array('name' => 'Peruvian Nuevo Sol', 'symbol' => 'S/.', 'symbol_long' => 'S/.'),
                                         'jpy' => array('name' => 'Japanese Yen', 'symbol' => '&#165;', 'symbol_long' => '&#165;'));
    public $supported_actions = array('end'        => '_act_cancel_recurring',
                                      'reactivate' => '_act_reactivate_recurring');
    private $action_charge = array();

    private $_full_metadata = array();

    // --------------------------------------------------------------------
    // METHODS
    // --------------------------------------------------------------------

    /**
     * Constructor
     *
     */
    function __construct()
    {
        require_once(PATH_THIRD . 'charge/libraries/stripe/lib/Stripe.php');

        // Call parent constructor
        parent::__construct();

        // Initialize this model
        $this->initialize(
            'charge_stripe',
            'id',
            array(
                'site_id'               => 'int(4) unsigned NOT NULL default 1',
                'timestamp'             => 'int(10) unsigned NOT NULL default 0',
                'ended_on'              => 'int(10) unsigned NOT NULL default 0',
                'hash'                  => 'varchar(100) NOT NULL default ""',
                'member_id'             => 'int(10) unsigned NOT NULL default 0',
                'source_url'            => 'varchar(255) NOT NULL default ""',
                'type'                  => 'varchar(100) NOT NULL default ""',
                'customer_id'           => 'varchar(100) NOT NULL default ""',
                'payment_id'            => 'varchar(100) NOT NULL default ""',


                'plan_amount'           => 'int(10) unsigned NOT NULL default 0',
                'plan_interval'         => 'varchar(255) NOT NULL default ""',
                'plan_currency'         => 'varchar(255) NOT NULL default ""',
                'plan_interval_count'   => 'int(10) unsigned NOT NULL default 0',
                'plan_trial_days'       => 'int(10) unsigned NOT NULL default 0',
                'plan_trial_end'        => 'int(10) unsigned NOT NULL default 0',
                'plan_length'           => 'int(10) unsigned NOT NULL default 0',
                'plan_length_interval'  => 'varchar(255) NOT NULL default ""',
                'plan_length_expiry'    => 'int(10) unsigned NOT NULL default 0',

                // Coupons!
                'plan_coupon'           => 'varchar(100) NOT NULL default ""',
                'plan_coupon_stripe_id' => 'varchar(100) NOT NULL default ""',
                'plan_discount'         => 'int(10) unsigned NOT NULL default 0',
                'plan_full_amount'      => 'int(10) unsigned NOT NULL default 0',


                'card_name'             => 'varchar(255) NOT NULL default ""',
                'card_address_line1'    => 'varchar(255) NOT NULL default ""',
                'card_address_line2'    => 'varchar(255) NOT NULL default ""',
                'card_address_city'     => 'varchar(255) NOT NULL default ""',
                'card_address_state'    => 'varchar(255) NOT NULL default ""',
                'card_address_zip'      => 'varchar(255) NOT NULL default ""',
                'card_address_country'  => 'varchar(255) NOT NULL default ""',
                'card_last4'            => 'varchar(255) NOT NULL default ""',
                'card_type'             => 'varchar(255) NOT NULL default ""',
                'card_exp_month'        => 'varchar(255) NOT NULL default ""',
                'card_exp_year'         => 'varchar(255) NOT NULL default ""',
                'card_fingerprint'      => 'varchar(255) NOT NULL default ""',

                'customer_name'         => 'varchar(255) NOT NULL default ""',
                'customer_email'        => 'varchar(255) NOT NULL default ""',

                'meta'                  => 'text',
                'stripe'                => 'text',
                'messages'              => 'text',
                'mode'                  => 'varchar(255) NOT NULL default ""',
                'state'                 => 'varchar(255) NOT NULL default ""')
        );

        $this->_setup_stripe();
    }

    // --------------------------------------------------------------------

    /**
     * Installs given table
     *
     * @access      public
     * @return      void
     */
    public function install()
    {
        // Call parent install
        parent::install();

        // Add indexes to table
        ee()->db->query("ALTER TABLE {$this->table()} ADD INDEX (`site_id`)");
        ee()->db->query("ALTER TABLE {$this->table()} ADD INDEX (`member_id`)");
        ee()->db->query("ALTER TABLE {$this->table()} ADD INDEX (`hash`)");
    }


    public function get_currencies()
    {
        $ret = array();

        foreach ($this->supported_currencies as $currency_key => $currency_data) {
            $ret[$currency_key]['name'] = $currency_data['name'];
            $ret[$currency_key]['cleaned_name'] = strtoupper($currency_key) . ' - ' . $currency_data['name'] . ' (' . $currency_data['symbol_long'] . ')';
            $ret[$currency_key]['symbol'] = $currency_data['symbol'];
        }

        return $ret;
    }

    public function get_currency_symbol($currency_code = '', $type = 'symbol')
    {
        $symbol = '';

        if (isset($this->supported_currencies[$currency_code])) {
            $symbol = $this->supported_currencies[$currency_code][$type];
        }

        return $symbol;
    }


    public function api_call($call_name = '', $call_val = '', $call_key = '')
    {
        if ($call_name == '') return false;

        $method = explode('::', $call_name);
        if (count($method) != 2) return false;

        $params = array($call_key => $call_val);


        if ($call_name == 'Stripe_Invoice::upcoming') {
            // Stripe_Invoice::upcoming is the only call that requires
            // the params to be a sub array
            $params = array($params);
        }

        try {

            $data = forward_static_call_array($method, $params);

            if (isset($data['data'])) $data = $data['data'];

            $data = $this->_to_array($data);

        } catch (Exception $e) {
            $this->api_error_message = $e->getMessage();

            return false;
        }

        return $data;
    }


    public function get_recurring($member_id = 0)
    {
        if ($member_id == 0) return array();

        ee()->db->where('member_id', $member_id)
            ->where('type', 'recurring')
            ->where('state', 'active');
        $charges = parent::get_all();

        foreach ($charges as $key => $row) {
            $charges[$key] = $this->parse($row, true);
        }

        return $charges;
    }


    public function get_all($limit = 50, $offset = 0, $unset = true)
    {
        ee()->db->limit($limit, $offset);
        ee()->db->order_by('id', 'desc');
        $charges = parent::get_all();

        foreach ($charges as $key => $row) {
            $charges[$key] = $this->parse($row, $unset);
        }

        return $charges;
    }

    public function charge($data)
    {
        $this->data = $data;

        ee()->charge_log->log_donate_attempt_start_one_off($this->data);

        try {

            $description = $this->_create_description();

            $metadata = $this->_collect_metadata();

            $customer = $this->_create_customer(null, 'customer');
            if ($customer == false) return false;

            // Adjust for onetime coupon
            if (isset($this->data['coupon']['plan'])) {
                $this->data['plan']['amount'] = $this->data['coupon']['plan']['amount'];
                $this->data['plan']['full_amount'] = $this->data['coupon']['plan']['full_amount'];
                $this->data['plan']['discount'] = $this->data['coupon']['plan']['discount'];
            }

            $charge = Stripe_Charge::create(array(
                "customer"    => $customer,
                "amount"      => $this->data['plan']['amount'],
                "currency"    => $this->data['plan']['currency'],
                "description" => $description,
                "metadata"    => $metadata
            ));


            // Now wipe the customer card
            $card = $this->_get_customer_card($customer, true);

            ee()->charge_log->log_charge_created(Charge_obj_to_array($charge));

            // Also keep a record in our transaction log
            return $this->_record($charge);

        } catch (Exception $e) {
            $json = $e->getJsonBody();
            ee()->charge_log->log_exception($json);

            $this->errors = $json['error'];
        }

        ee()->charge_log->log_error_creating_charge($this->data);

        return false;
    }


    public function recurring($data)
    {
        $this->data = $data;

        ee()->charge_log->log_donate_attempt_start_recurring($this->data);

        $this->_setup_recurring();
        if ($this->recurring_plan == false) {
            // We don't have a plan_id, so can't do much
            ee()->charge_log->log_error_no_plan($this->data);
        }

        // Now create our customer and attach
        $return = $this->_create_customer($this->recurring_plan);

        if ($return === false) {
            ee()->charge_log->log_error_no_customer($this->data);
        }

        return $return;
    }


    public function fetch($hash)
    {
        $data = self::get_one($hash, 'hash');
        if (empty($data)) return false;

        return $this->parse($data);
    }


    public function parse($data, $unset = true)
    {

        // Good, parse our the various encoded parts for easier usage later
        foreach (array('meta', 'stripe') as $key) {
            if (!isset($data[$key]) || $data[$key] == '') $data[$key] = array();
            else $data[$key] = unserialize(base64_decode($data[$key]));
        }

        foreach ($data['meta'] as $meta_key => $meta_val) {
            $data['meta:' . $meta_key] = $meta_val;
        }


        // for saftey
        $data['stripe_card_exp_month'] = '';
        $data['stripe_card_exp_year'] = '';

        foreach ($data['stripe'] as $stripe_key => $stripe_val) {
            if (is_array($stripe_val)) continue;
            if (is_object($stripe_val)) {

                if (get_class($stripe_val) == 'Stripe_Object') {
                    // Special subscription handling
                    $sub_obj = $stripe_val->__toArray();

                    foreach ($sub_obj as $sub_key => $sub_val) {

                        if (is_array($sub_val)) continue;

                        if ($sub_key == 'plan') {
                            $plan = $sub_val->__toArray();
                            foreach ($plan as $plan_key => $plan_val) {
                                $data['stripe_' . $sub_key . '_' . $plan_key] = $plan_val;
                            }
                        } else {
                            $data['stripe_' . $stripe_key . '_' . $sub_key] = $sub_val;
                        }
                    }
                }

                if (get_class($stripe_val) == 'Stripe_List' && $stripe_key == 'subscriptions') {
                    $subscriptions_array = $stripe_val->__toArray();

                    // Charge only cares about the first subscription
                    $sub = current($subscriptions_array['data']);

                    if (!empty($sub)) {
                        $sub = $sub->__toArray();

                        foreach ($sub as $sub_key => $sub_val) {
                            if (is_object($sub_val)) continue;
                            $data['stripe_subscription_' . $sub_key] = $sub_val;


                            // Special test for trial states
                            if ($sub_key == 'status' AND $sub_val == 'trialing') {
                                // Test to see if the trial has expired

                                if ($sub['trial_end'] < time()) {
                                    // Trial has expired
                                    // Trigger an update to this status
                                    $ret = $this->trigger_trial_end($data['id']);

                                    $data['plan_trial_end'] = $ret['plan_trial_end'];
                                    $data['state'] = $ret['state'];
                                }

                            }
                        }
                    }
                }


                if (get_class($stripe_val) == 'Stripe_Card') {
                    // Special card handling
                    $card_array = $stripe_val->__toArray();
                    foreach ($card_array as $card_key => $card_val) {
                        $data['stripe_card_' . $card_key] = $card_val;
                    }
                }
            } else {
                $data['stripe_' . $stripe_key] = $stripe_val;
            }
        }

        if ($unset) {
            unset($data['meta']);
            unset($data['stripe']);
        }


        // Handle system messages
        if (!isset($data['message']) || $data['messages'] == '') $data['messages'] = array();
        else {
            $data['messages'] = json_decode($data['messages'], true);
        }

        // Format the ammount
        if (!isset($data['card_last4'])) $data['card_last4'] = '';
        if (!isset($data['plan_interval'])) $data['plan_interval'] = '';
        if (!isset($data['plan_interval_count'])) $data['plan_interval_count'] = '';
        if (!isset($data['plan_currency'])) $data['plan_currency'] = '';
        if (!isset($data['ended_on'])) $data['ended_on'] = '0';


        $data['card_number_dotted'] = $this->make_dotted_card($data['card_last4'], $data['card_type']);
        $data['plan_wordy'] = $this->_construct_plan_description($data['plan_interval'], $data['plan_interval_count']);
        $data['plan_currency_symbol'] = $this->get_currency_symbol($data['plan_currency']);
        $data['amount_currency_formatted'] = $this->get_currency_symbol($data['plan_currency']) . number_format($data['plan_amount'] / 100, 2);
        $data['amount_formatted'] = number_format($data['plan_amount'] / 100, 2);
        $data['amount'] = number_format($data['plan_amount'] / 100);
        $data['time_wordy'] = date('H:i', $data['timestamp']) . ' on ' . date('l jS F', $data['timestamp']);
        $data['ended_on_wordy'] = date('H:i', $data['ended_on']) . ' on ' . date('l jS F', $data['ended_on']);

        return $data;
    }


    public function user_action($data, $extra = array())
    {
        if (!isset($data['action'])) return false;
        if (!isset($data['charge_id'])) return false;

        $member_id = ee()->session->userdata('member_id');
        if ($member_id == '' OR $member_id == '0') return false;

        // validate the action too
        if (!$this->validate_action($data['action'], $data['charge_id'], $member_id, true)) return false;

        // Appears valid
        // Do something
        try {
            $action = $this->supported_actions[$data['action']];

            if (ee()->extensions->active_hook('charge_action_start') === true) {
                ee()->extensions->call('charge_action_start', $data, $data['action'], $member_id, $extra);
                if (ee()->extensions->end_script === true) return;
            }

            $result = $this->$action($extra);

        } catch (Exception $e) {
            return false;
        }

    }


    private function _act_reactivate_recurring($extra)
    {
        // Only if we have a charge
        if (empty($this->action_charge)) return false;

        // Must be in a cancelled state
        if ($this->action_charge['type'] != 'recurring') return false;
        if ($this->action_charge['state'] != 'canceled' AND $this->action_charge['state'] != 'canceled_trial') return false;

        // ok. Decode the stripe block to get the stripe id
        $stripe = unserialize(base64_decode($this->action_charge['stripe']));
        if (!is_array($stripe) OR empty($stripe) OR $stripe['object'] != 'customer') return false;

        $state = 'active';
        if ($this->action_charge['state'] != 'canceled_trial') $state = 'trialing';

        try {

            if (isset($stripe['subscription'])) {
                $plan_id = $stripe['subscription']->plan->id; // Legacy support
                $sub_id = $stripe['subscription']->id;
                $sub = $stripe['subscription'];
            } else {
                $sub = $stripe['subscriptions']->data;
                $sub = current($sub);
                $plan_id = $sub->plan->id;
                $sub_id = $sub->id;
            }


            $sub->plan = $plan_id;
            $sub->status = $state;

            // Update our record too
            $this->reactivate_charge($this->action_charge['id'], $state);

        } catch (Exception $e) {
            return false;
        }

        return true;
    }


    private function _act_cancel_recurring($extra)
    {
        // Only if we have a charge
        if (empty($this->action_charge)) return false;

        // Only valid on 'active' 'recurring' payments
        if ($this->action_charge['state'] != 'active' AND $this->action_charge['state'] != 'trialing') return false;
        if ($this->action_charge['type'] != 'recurring') return false;

        // ok. Decode the stripe block to get the stripe id
        $stripe = unserialize(base64_decode($this->action_charge['stripe']));
        if (!is_array($stripe) OR empty($stripe) OR $stripe['object'] != 'customer') return false;

        $at_period_end = false;
        if (isset($extra['at_period_end']) AND $extra['at_period_end'] == 'yes') $at_period_end = true;

        $cancelled_state = 'canceled';
        if ($this->action_charge['state'] == 'trialing') $cancelled_state = 'canceled_trial';

        try {

            $cu = Stripe_Customer::retrieve($stripe['id']);
            $sub_ids = array();
            if (isset($stripe['subscription'])) $sub_ids[] = $stripe['subscription']->id; // Legacy support

            if (isset($stripe['subscriptions'])) {
                $subs = $stripe['subscriptions']->__toArray();
                foreach ($subs['data'] as $sub) {
                    $sub_ids[] = $sub->id;
                }
            }

            foreach ($sub_ids as $sub_id) {
                $this_sub = $cu->subscriptions->retrieve($sub_id)->cancel(array('at_period_end' => $at_period_end));
            }

            $this->end_charge($this->action_charge['id'], $cancelled_state);


        } catch (Exception $e) {
            $message = $e->getMessage();

            if ($message == str_replace('{customer_id}', $stripe['id'], lang('charge_stripe_cancel_no_active'))) {
                // Already cancelled, clear it  (legacy for single sub. support)
                $this->end_charge($this->action_charge['id'], $cancelled_state);
            } else if ($message == str_replace(array('{customer_id}', '{sub_id}'), array($stripe['id'], $sub_id), lang('charge_stripe_cancel_no_match'))) {
                // No matching sub. found
                $this->end_charge($this->action_charge['id'], $cancelled_state);
            } else {

                // Throw a message?

            }

            return false;
        }

        return true;
    }


    public function validate_action($type, $charge_id, $member_id, $remember = false)
    {
        // Get the charge
        ee()->db->where('member_id', $member_id)
            ->where('id', $charge_id);
        $charge = parent::get_all();

        if (empty($charge)) return false;
        $charge = current($charge);

        // We only allow some actions
        if (!isset($this->supported_actions[$type])) return false;

        // Valid, wrap the details up in to a hash
        $data['action'] = $type;
        $data['charge_id'] = $charge_id;

        if ($remember) $this->action_charge = $charge;

        return $data;
    }


    private function reactivate_charge($charge_id, $state = 'active')
    {
        $data = array();
        $data['state'] = $state;
        $data['ended_on'] = '0';
        self::update($charge_id, $data);

        return;
    }


    private function end_charge($charge_id, $state = 'canceled')
    {
        $data = array();
        $data['state'] = $state;
        $data['ended_on'] = ee()->localize->now;
        self::update($charge_id, $data);

        return;
    }

    private function _setup_stripe()
    {
        $stripe_mode = ee()->config->item('charge_stripe_account_mode');
        if ($stripe_mode == '') $stripe_mode = 'test';

        $sk_key = ee()->config->item('charge_stripe_' . $stripe_mode . '_credentials_sk');
        Stripe::setApiKey($sk_key);

        $api_version = CHARGE_API_VERSION;
        Stripe::setApiVersion($api_version);
    }


    private function _setup_recurring()
    {
        // Get the recurring periods, then check stripe to see
        // if we have an existing plan, create if not
        $plan['interval'] = $this->data['plan']['interval'];
        $plan['interval_count'] = $this->data['plan']['interval_count'];
        $plan['amount'] = $this->data['plan']['amount'];
        $plan['currency'] = $this->data['plan']['currency'];
        $plan['trial_days'] = $this->data['plan']['trial_days'];

        // Handle set length subscriptions
        if (isset($this->data['plan']['length']) AND isset($this->data['plan']['length_interval'])) {
            // Set length
            $plan['length'] = $this->data['plan']['length'];
            $plan['length_interval'] = $this->data['plan']['length_interval'];
        }

        $plan_name = $this->_construct_plan_name($plan);
        $this->recurring_plan = $this->_check_plan_exists($plan_name, $plan);

        if ($this->recurring_plan === false AND $this->create_plan_if_not_exists) {
            // Create the plan
            ee()->charge_log->log_plan_create_start(array('plan_name' => $plan_name, 'plan' => $plan));
            $this->recurring_plan = $this->_create_plan($plan_name, $plan);
        }

        if ($this->recurring_plan === false) {
            return false;
        }

        return true;
    }

    private function _get_customer_card(Stripe_Customer $stripeCustomer, $wipeCard = false)
    {
        $ret['exp_month'] = '';
        $ret['exp_year'] = '';

        try {
            // Pull out the card id from the Stripe_List on the Stripe_Customer
            $cards = $stripeCustomer->cards->__toArray();
            $card = current($cards['data']);

            if ($wipeCard == true) $stripeCustomer->cards->retrieve($card->id)->delete();

            $card = $card->__toArray();

            return $card;

        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
        }

        return $ret;
    }


    private function _create_plan($plan_name, $plan)
    {
        $response = array();
        $plan_id = $plan_name;

        $amount = $plan['amount'];
        $interval = (isset($plan['interval']) ? $plan['interval'] : 'month');
        $interval_count = (isset($plan['interval_count']) ? $plan['interval_count'] : '1');
        $currency = $plan['currency'];
        $trial_days = (isset($plan['trial_days']) AND is_numeric($plan['trial_days'])) ? $plan['trial_days'] : 0;

        try {
            $p = Stripe_Plan::create(array(
                "amount"            => $amount,
                "interval"          => $interval,
                "interval_count"    => $interval_count,
                "name"              => $plan_name,
                "currency"          => $currency,
                "id"                => $plan_id,
                "trial_period_days" => $trial_days));

            // Log this
            ee()->charge_log->log_plan_created(Charge_obj_to_array($p));

            return $p;
        } catch (Exception $e) {
            $json = $e->getJsonBody();
            ee()->charge_log->log_exception($json);
            $this->errors = $json['error'];
        }

        ee()->charge_log->log_error_plan_creation($plan);

        return false;
    }

    private function _check_plan_exists($plan_name, $plan)
    {
        try {
            $p = Stripe_Plan::retrieve($plan_name);

            return $p;
        } catch (Stripe_Error $e) {
            if ($e->getHttpStatus() == '404') {
                return false;
            }
        }

        return false;
    }


    private function _construct_plan_name($plan)
    {
        // Allow for set names
        if (isset($this->data['plan']['set_name']) AND trim($this->data['plan']['set_name']) != '') {
            return $this->data['plan']['set_name'];
        }

        // 75 Every [x] Month(s)
        $plan_name[] = number_format($plan['amount'] / 100, 2);
        $plan_name[] = strtoupper($plan['currency']);
        // $plan_name[] = $plan['amount'];


        // This was actually a one-time charge,
        // but with a setlength for an associated subscription
        // We've spoofed this into a recurring payment
        if (isset($this->data['plan']['spoofed_recurring'])) {
            $plan_name[] = 'charge';
        } else {
            if ($plan['interval_count'] > 1) {
                // every [x] [period]s
                $plan_name[] = 'Every ' . $plan['interval_count'] . ' ' . ucwords($plan['interval'] . 's');
            } else {
                $plan_name[] = ucwords($plan['interval'] . 'ly');
            }
        }

        if (isset($plan['trial_days']) AND $plan['trial_days'] > 0) {
            $plan_name[] = 'with ' . $plan['trial_days'] . ' day trial';
        }

        if (isset($plan['length']) AND isset($plan['length_interval'])) {
            // Set length
            if ($plan['length'] > 1) {
                $plan_name[] = 'for ' . $plan['length'] . ' ' . ucwords($plan['length_interval'] . 's');
            } else {
                $plan_name[] = 'for ' . $plan['length'] . ' ' . ucwords($plan['length_interval']);
            }
        }


        return implode(' ', $plan_name);
    }


    private function _construct_plan_description($period, $period_count, $trial_days = 0)
    {
        if (!in_array($period, array('week', 'month', 'year'))) return '';

        $plan_name = array();

        if ($period_count > 1) {
            // every [x] [period]s
            $plan_name[] = 'Every ' . $period_count . ' ' . ucwords($period . 's');
        } else {
            $plan_name[] = ucwords($period . 'ly');
        }

        if ($trial_days != 0) {
            $plan_name[] = 'with ' . $trial_days . ' day trial';
        }

        return implode(' ', $plan_name);
    }


    private function _create_customer($plan = null, $return = 'record')
    {
        try {
            $description = $this->_create_description();

            $metadata = $this->_collect_metadata();

            $stripe_arr = array(
                "card"        => $this->data['card']['token'],
                "email"       => $this->data['customer']['email'],
                "description" => $description,
                "metadata"    => $metadata
            );


            if (!is_null($plan)) {
                $stripe_arr['plan'] = $plan;

                if (isset($this->data['coupon']['coupon']['stripe_id'])) {
                    $stripe_arr['coupon'] = $this->data['coupon']['coupon']['stripe_id'];
                    $this->data['plan']['coupon_stripe_id'] = $this->data['coupon']['coupon']['stripe_id'];
                }
            }

            if (isset($this->data['plan']['balance'])) $stripe_arr['account_balance'] = $this->data['plan']['balance'];

            // Do we actually need to create a customer?
            // If this user is logged in have a quick check to see if there's already a customer record for them
            $customer = null;
            if(ee()->session->userdata('member_id') != '0') {
                $customer = $this->_attempt_customer_fetch(ee()->session->userdata('member_id'));
            }

            if($customer == null) {
                $customer = Stripe_Customer::create($stripe_arr);
                ee()->charge_log->log_customer_created(Charge_obj_to_array($customer));
            } else {
                foreach($stripe_arr as $key => $val) {
                    $customer->$key = $val;
                }
                $customer->save();
                ee()->charge_log->log_customer_updated(Charge_obj_to_array($customer));
            }


            // Pull out the card exp month/year from the customer->card object
            // at the same time we wipe their card record from our account
            $card = $this->_get_customer_card($customer);

            $this->data['card']['exp_month'] = $card['exp_month'];
            $this->data['card']['exp_year'] = $card['exp_year'];
            $this->data['card']['fingerprint'] = $card['fingerprint'];

            // If the customer had a coupon applied, the value paid may be different from the value passed.
            // Lets hit the api and pull this customer's payments and see
            if(isset($stripe_arr['coupon']) && $stripe_arr['coupon'] != '') {
                $paymentsList = Stripe_Charge::all(array('customer' => $customer->id, 'limit' => 1));

                // Get the first payment
                $payments = $paymentsList->data;
                if(!empty($payments)) {
                    $payment = current($payments);
                    $amount = $payment->amount;
                    $planAmount = $this->data['plan']['amount'];

                    // Is there a difference between the amount paid and the original plan_amount?
                    if($amount != $planAmount) {
                        $this->data['plan']['amount'] = $amount;
                        $this->data['plan']['discount'] = $planAmount - $amount;
                        $this->data['plan']['full_amount'] = $planAmount;
                    }
                }
            }

            if (ee()->extensions->active_hook('charge_customer_created') === true) {
                ee()->extensions->call('charge_customer_created', $customer, $this);
                if (ee()->extensions->end_script === true) return;
            }


            // If we had a plan_expiry, check it now, compared to the expected next payment date,
            // and kill the sub right now if it's beyond the expiry
            if (isset($this->data['plan']['length_expiry'])) {

                $state = $this->_check_subscription_expiry($customer); // This will kill it if needed

                if ($state !== false) $customer = $state;
                // The same logic fires on recurring webhook triggered payments
            }

            // Also record this in our transaction model
            if ($return == 'record') {
                $record = $this->_record($customer);

                return $record;
            }

            return $customer;

        } catch (Exception $e) {
            $json = $e->getJsonBody();
            ee()->charge_log->log_exception($json);
            $this->errors = $json['error'];
        }

        ee()->charge_log->log_error_creating_customer($this->data);

        return false;
    }


    private function _record($stripe_object)
    {
        ee()->load->helper('String');

        $data = array();

        $clean_stripe = $this->_clean_stripe_obj($stripe_object);


        // Build our array for the record
        $data['hash'] = random_string('unique');
        $data['member_id'] = ee()->session->userdata['member_id'];
        $data['timestamp'] = ee()->localize->now;
        $data['site_id'] = ee()->config->item('site_id');
        $data['source_url'] = (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '');
        $data['stripe'] = base64_encode(serialize($clean_stripe));
        $data['mode'] = ee()->config->item('charge_stripe_account_mode');

        if (!empty($this->messages)) $data['messages'] = json_encode($this->messages);

        $data['state'] = 'active';
        $data['customer_id'] = ''; // Just in case we can't find the customer_id later
        if (isset($clean_stripe['customer'])) $data['customer_id'] = $clean_stripe['customer'];
        else {
            if (isset($clean_stripe['object']) AND $clean_stripe['object'] == 'customer') {
                if (isset($clean_stripe['id'])) $data['customer_id'] = $clean_stripe['id'];
            }
        }
        $data['payment_id'] = ''; // Just in case we can't find this later
        if (isset($clean_stripe['id']) && $clean_stripe['object'] == 'charge') $data['payment_id'] = $clean_stripe['id'];

        if ($data['mode'] == '0' OR $data['mode'] == '') $data['mode'] = 'test';

        // Set the card details if we have them

        $meta = array();

        //  First things first, set up our raw empty tags
        foreach ($this->data as $set => $set_data) {
            foreach ($set_data as $key => $val) {
                if ($set == 'meta') {
                    $meta[$key] = $val;
                } else {
                    $data[$set . '_' . $key] = $val;
                }
            }
        }

        // Plan Trials days get special treatment
        $data['plan_trial_days'] = 0;
        if (isset($this->data['plan']['trial_days'])) $data['plan_trial_days'] = $this->data['plan']['trial_days'];

        // If trialing, we need to change the state, and add an expiry
        if ($data['plan_trial_days'] > 0) {
            $data['state'] = 'trialing';
            $data['plan_trial_end'] = strtotime('+' . $data['plan_trial_days'] . ' days', time());
        }

        if (isset($data['plan_interval_count']) AND $data['plan_interval_count'] != '0') $data['type'] = 'recurring';
        else $data['type'] = 'charge';


        if (!empty($meta)) {
            $data['meta'] = base64_encode(serialize($meta));
        }

        $data['charge_id'] = self::insert($data);

        // We need to return the clean object for later parsing
        $ret = $this->parse($data);

        return $ret;
    }


    private function _clean_stripe_obj($object)
    {
        $json = $object->__toArray();

        return $json;
    }

    private function _collect_metadata()
    {
        $meta = array();

        if (ee()->config->item('charge_metadata_pass') == 'no') return array();

        $meta['Name'] = $this->data['customer']['name'];
        $meta['Email'] = $this->data['customer']['email'];

        if (ee()->session->userdata('member_id') != '0') {
            $meta['Site Member Id'] = ee()->session->userdata('member_id');
        }

        if (isset($this->data['meta']) AND !empty($this->data['meta'])) {
            foreach ($this->data['meta'] as $meta_key => $meta_vals) {
                $meta[ucwords(str_replace('_', ' ', $meta_key))] = $meta_vals;
            }
        }

        $this->_full_metadata = $meta;

        if (count($meta) > 20) {
            // We have an issue, Stripe only allows 20 keys per item
            // Break this down. return the first 20, but keep the rest for local records
            $meta = array_slice($meta, 0, 20);
        }

        return $meta;
    }

    private function _create_description()
    {
        $ret = 'Payment by ' . $this->data['customer']['name'] . ' (' . $this->data['customer']['email'] . ')';

        return $ret;
    }

    private function _to_array($obj)
    {
        if (is_object($obj)) $obj = $obj->__toArray();

        if (is_array($obj)) {

            $new = array();

            foreach ($obj as $key => $val) {
                $new[$key] = $this->_to_array($val);
            }
        } else $new = $obj;

        return $new;
    }


    private function _clean_array($arr)
    {

        foreach ($arr as $key => $val) {

            if (is_array($val)) {

                $new = array();

                foreach ($val as $sub_key => $sub_val) {
                    if (!is_numeric($sub_key)) {
                        // Nope, needs to be a multi-dimensional array
                        $new[0][$sub_key] = $sub_val;
                    }
                }

                $arr[$key] = $new;
            }
        }

        return $arr;
    }


    public function make_dotted_card($last_4 = '', $type = '')
    {

        switch ($type) {
            case 'American Express' :
                return '&#183;&#183;&#183;&#183; &#183;&#183;&#183;&#183;&#183;&#183; &#183;&#183;' . $last_4;
                break;
            case 'Diners Club' :
                return '&#183;&#183;&#183;&#183; &#183;&#183;&#183;&#183; &#183;&#183;' . $last_4;
                break;
            case 'Visa' :
            case 'MasterCard' :
            case 'Discover' :
            case 'JCB' :
                return '&#183;&#183;&#183;&#183; &#183;&#183;&#183;&#183; &#183;&#183;&#183;&#183; ' . $last_4;
                break;
            default :
                return '&#183;&#183;&#183;&#183; &#183;&#183;&#183;&#183; &#183;&#183;&#183;&#183; &#183;&#183;&#183;&#183';
                break;
        }
    }


    public function find_customer_id($member_id = '', $fallback_to_user = true)
    {
        if ($member_id == '' AND $fallback_to_user == false) return false;

        // Fallback to the currently logged in user
        if ($member_id == '') $member_id = ee()->session->userdata['member_id'];
        if ($member_id == '0') return false;


        // Try to pull the customer_id from the member_id
        ee()->db->where('member_id', $member_id);
        $charges = ee()->charge_stripe->get_all();

        // It's possible (although unlikely) that we could have multiple customer_ids
        // for the same user. In this case we'll only care about the most recent
        $customer_id = '';
        foreach ($charges as $charge) {
            if ($charge['customer_id'] != '') $customer_id = $charge['customer_id'];
        }

        if ($customer_id == '') return false;

        return $customer_id;
    }

    public function retrieve_stripe_customer($customer_id)
    {
        try{
            $customer = Stripe_Customer::retrieve($customer_id);

            return $customer;

        } catch(Exception $e) {
            $this->api_error_message = $e->getMessage();
            return FALSE;
        }

        return FALSE;
    }


    public function update_card($customer_id, $data)
    {
        // Get the customer_id
        if (!isset($data['token'])) return false;

        try {
            $customer = Stripe_Customer::retrieve($customer_id);
            // Add this new card
            $new_card = $customer->cards->create(array('card' => $data['token']));

            // Only remove the _other_ cards, once we've successfully added a new card
            $current_cards = $customer->cards->all()->__toArray();

            foreach ($current_cards['data'] as $card) {
                if ($new_card->id != $card->id) $customer->cards->retrieve($card->id)->delete();
            }

            // Now update all thise relevant db records with this new card
            $record = array();
            $record['card_last4'] = $new_card->last4;
            $record['card_type'] = $new_card->type;
            $record['card_exp_month'] = $new_card->exp_month;
            $record['card_exp_year'] = $new_card->exp_year;
            $record['card_fingerprint'] = $new_card->fingerprint;
            $record['card_customer'] = $new_card->customer;
            $record['card_country'] = $new_card->country;
            $record['card_name'] = $new_card->name;
            $record['card_address_line1'] = $new_card->address_line1;
            $record['card_address_line2'] = $new_card->address_line2;
            $record['card_address_city'] = $new_card->address_city;
            $record['card_address_state'] = $new_card->address_state;
            $record['card_address_zip'] = $new_card->address_zip;
            $record['card_address_country'] = $new_card->address_country;

            ee()->db->where('customer_id', $customer_id)
                ->where('type', 'recurring')
                ->where('state', 'active');
            $customer_charges = self::get_all();

            foreach ($customer_charges as $charge) {
                self::update($charge['id'], $record);
            }

            return true;

        } catch (Exception $e) {
            $this->api_error_message = $e->getMessage();

            return false;
        }
    }

    public function cards($customer_id)
    {

        // @todo add caching on this call
        try {
            $cards = Stripe_Customer::retrieve($customer_id)
                ->cards->all();
            $cards = $cards->__toArray();

            if (empty($cards['data'])) return array();

            $data = array();
            foreach ($cards['data'] as $c) {
                $temp = array();
                foreach ($c->__toArray() as $key => $val) {
                    $temp['card_' . $key] = $val;
                }

                $temp['card_number_dotted'] = $this->make_dotted_card($temp['card_last4'], $temp['card_type']);

                $data[] = $temp;
            }

            return $data;

        } catch (Exception $e) {
            $this->api_error_message = $e->getMessage();

            return false;
        }
    }


    private function _check_subscription_expiry($customer, $charge = array())
    {
        if (empty($charge)) {
            $charge = array('plan_interval'       => $this->data['plan']['interval'],
                            'plan_interval_count' => $this->data['plan']['interval_count'],
                            'plan_length_expiry'  => $this->data['plan']['length_expiry']);
        }

        if ($charge['plan_length_expiry'] == 0) return false;

        // Figure out the next expected bill date based on the interval values
        $next_payment_due = strtotime('+' . $charge['plan_interval_count'] . $charge['plan_interval']);


        // These are to precise to be realiable, so we'll fudge them slightly
        // to catch the case of immediate cancellation. Give it a 10 minute window
        if ($charge['plan_length_expiry'] > ($next_payment_due + 600)) return false; // Nothing to do right now

        // Pull out the sub_id
        $subscription = $customer->subscriptions->data;
        $subscription = current($subscription);

        // Cancel it
        $cancelled = $subscription->cancel(array('at_period_end' => true));

        // Pull the updated cusomter details so the db record is consistent
        $customer = Stripe_Customer::retrieve($customer->id);

        return $customer;
    }


    public function handle_plan_expiry($charge)
    {
        if (isset($charge['plan_length_expiry']) AND is_numeric($charge['plan_length_expiry'])) {

            // Get the customer
            $customer = Stripe_Customer::retrieve($charge['customer_id']);

            $customer = $this->_check_subscription_expiry($customer, $charge);

            if ($customer === false) {
                // Plan not expired
                return false;
            }

            // Otherwise we'll update our state just to be sure
            $data['state'] = 'expired';
            $data['ended_on'] = time();

            self::update($charge['id'], $data);

            return true;
        }
    }


    public function trigger_trial_end($charge_id)
    {
        $update['plan_trial_end'] = '';
        $update['state'] = 'active';

        self::update($charge_id, $update);


        if (ee()->extensions->active_hook('charge_subscription_trial_end') === true) {
            // Get the charge object so we can pass it to the hook
            $charge = self::get_one($charge_id);

            ee()->extensions->call('charge_subscription_trial_end', $charge);
            if (ee()->extensions->end_script === true) return;
        }

        return $update;
    }


    public function check_coupon_exists($coupon_code)
    {
        try {
            $c = Stripe_Coupon::retrieve($coupon_code);

            return $c;

        } catch (Stripe_InvalidRequestError $e) {
            if ($e->getHttpStatus() == '404') {
                return false;
            }
        } catch (Exception $ex) {
            return false;
        }

        return false;
    }

    public function delete_coupon($stripe_id)
    {
        $response = array();

        try {

            $c = Stripe_Coupon::retrieve($stripe_id);
            $c->delete();

            return true;

        } catch (Exception $e) {
            $this->errors[] = 'Failed to delete coupon - ' . $e->getMessage();

            return false;
        }

        return false;
    }

    public function create_coupon($data)
    {
        $response = array();

        try {
            // Collect our neat array of attributes
            $coupon = array();
            $coupon['id'] = $data['stripe_id'];

            if ($data['type'] == 'fixed') {
                $coupon['amount_off'] = $data['amount_off'];
                $coupon['currency'] = $data['currency'];
            } elseif ($data['type'] == 'percentage') {
                $coupon['percent_off'] = $data['percentage_off'];
            }

            $coupon['duration'] = $data['duration'];
            if ($data['duration'] == 'repeating') {
                $coupon['duration_in_months'] = $data['duration_in_months'];
            }

            if ($data['max_redemptions'] != '' AND $data['max_redemptions'] != '0') $coupon['max_redemptions'] = $data['max_redemptions'];
            if (isset($data['redeem_by']) AND $data['redeem_by'] != '' AND $data['redeem_by'] != 0) $coupon['redeem_by'] = $data['redeem_by'];


            $c = Stripe_Coupon::create($coupon);

            return $c;

        } catch (Exception $e) {
            $this->errors[] = 'Coupon - ' . $e->getMessage();

            return false;
        }

        return false;
    }


    private function _attempt_customer_fetch($member_id)
    {
        ee()->db->where('member_id', $member_id)
            ->order_by('id', 'desc');
        $res = self::get_all(1);

        if(empty($res)) return null;

        $row = current($res);

        $customer_id = $row['customer_id'];
        if($customer_id == '') return null;


        try {
            $customer = Stripe_Customer::retrieve($customer_id);

            return $customer;
        } catch(Exception $e) {}

        return null;
    }

} // End class
/* End of file Charge_stripe_model.php */