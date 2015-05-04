<?php if ( ! defined('EXT')) exit('No direct script access allowed');


// include config file
include PATH_THIRD.'charge/config'.EXT;

/**
 * Charge Module Class
 *
 * @package         charge_ee_addon
 * @version         1.9.2
 * @author          Joel Bradbury ~ <joel@squarebit.co.uk>
 * @link            http://squarebit.co.uk/software/expressionengine/charge
 * @copyright       Copyright (c) 2015, Joel Bradbury/Square Bit
 */

class Charge {

    public $return_data;
    private $EE;

    private $card_years_show = 5;
    private $recurring_plan = FALSE;
    public $errors;
    public $data;
    private $coupon_data;

    public $hidden;
    private $default_currency = 'usd';
    private $protected_params = array(  'on_success',
                                        'on_success:*',
                                        'required',
                                        'success_action',
                                        'create_member',
                                        'plan_amount',
                                        'plan_interval',
                                        'plan_interval_count',
                                        'plan_currency',
                                        'plan_trial_days',
                                        'plan_balance',
                                        'plan_length',
                                        'plan_length_interval',
                                        'plan_set_name');

    private $protected_input_name = 'P';
    private $protected = array();
    private $rules = array('customer' =>
                        array(  'name' => 'required|alphanumeric',
                                'email' => 'required|email'),
                            'plan' =>
                        array(  'amount' => 'required|numeric',
                                'interval' => 'in:week,month,year',
                                'interval_count' => 'integer',
                                'currency' => 'min:3|max:3',
                                'length'    => 'integer',
                                'length_interval' => 'in:week,month,year',
                                'coupon' => ''),
                            'card' =>
                        array(  'token' => 'required',
                                'last4' => '',
                                'type' => '',
                                'name' => '',
                                'address_line1' => '',
                                'address_line2' => '',
                                'address_city' => '',
                                'address_state' => '',
                                'address_zip' => '',
                                'address_country' => ''),
                            'member' =>
                        array(  'create' => '',
                                'password'  => 'password',
                                'password_confirm' => 'password|repeat:password',
                                'username' => '',
                                'screen_name' => ''));

    /**
     * Constructor: sets EE instance
     *
     * @access      public
     * @return      null
     */
    public function __construct()
    {
        //--------------------------------------------
        //  Alias to get_instance()
        //--------------------------------------------
        if ( ! function_exists('ee') )
        {
            function ee()
            {
                return get_instance();
            }
        }

        // Define the package path
        ee()->load->add_package_path(PATH_THIRD.'charge');

          // Load our helper
        ee()->load->helper('Charge');
        ee()->lang->loadfile('charge');
        // Load base model
        if(!class_exists('Charge_model')) ee()->load->library('Charge_model');
        if(!isset(ee()->charge_stripe)) ee()->charge_model->load_models();

      //  ee()->load->remove_package_path(PATH_THIRD.'charge');

    }

    //-------------------------------------------------------------------
    //-------------------------------------------------------------------
    //  Public Tag Methods
    //-------------------------------------------------------------------
    //-------------------------------------------------------------------


    public function form($action = 'act_charge')
    {
        $data = array();
        $hidden = array();
        $data['errors'] = array();

        $data['card_months'] = $this->_add_card_months();
        $data['card_years'] = $this->_add_card_years();

        //  First things first, set up our raw empty tags

        $tagdata = ee()->TMPL->tagdata;

        $marker = 'meta:([^'.preg_quote('}').'""]+)';
        $matches = ee()->charge_model->match_single( $tagdata, $marker );

        $meta_keys = array();
        if($matches !== FALSE) {
            foreach($matches[1] as $match) {
                $data['meta:'.$match] = '';
                $meta_keys[] = $match;
            }
        }

        // Get any meta data we may have in our flashdata
        // Get it now, so we'll have it ready for picking out validation errors
        foreach($meta_keys as $key) {
            if( ee()->session->flashdata('meta:'.$key) != '') $data['meta:'.$key] = ee()->session->flashdata('meta:'.$key);

            // We could have had extra custom validation added to meta: values
            // use our known meta keys to check if we have any persisted errors
            $data['error_meta:'.$key] = FALSE;
            if( ee()->session->flashdata('error_meta:'.$key) != '' ) {
                $data['errors'][] = array('error_message' => ucwords(str_replace('_', ' ', $key)) . ' - ' . lang(ee()->session->flashdata('error_meta:'.$key)));
                $data['error_meta:'.$key] = TRUE;
                $data['error_meta:'.$key.'_message'] = lang(ee()->session->flashdata('error_meta:'.$key));
            }
        }

        // There is a bug in CI on nginx servers
        // were repeated called to set_flashdata will throw a 502 error
        // We'll have to bundle these up into a single call instead
        $newflash_data = array();

        foreach($this->rules as $set => $set_data) {
            foreach($set_data as $key => $val) {
                $data[$set.'_'.$key] = ee()->session->flashdata($set.'_'.$key);
                //ee()->session->set_flashdata($set.'_'.$key, $data[$set.'_'.$key]); // I'd prefer to use keep_flashdata, but EE's session class has wisely removed that option

                if(trim($data[$set.'_'.$key]) != '') $newflash_data[$set.'_'.$key] = $data[$set.'_'.$key];

                $data['error_'.$set.'_'.$key] = FALSE;

                if( ee()->session->flashdata('error_'.$set.'_'.$key) != '' ) {
                    $data['errors'][] = array('error_message' => ucwords(str_replace('_', ' ', $key)) . ' - ' . lang(ee()->session->flashdata('error_'.$set.'_'.$key)));
                    $data['error_'.$set.'_'.$key] = TRUE;
                    $data['error_'.$set.'_'.$key.'_message'] = lang(ee()->session->flashdata('error_'.$set.'_'.$key));
                }
            }
        }


        if(!empty($newflash_data)) ee()->session->set_flashdata($newflash_data);

        $data['have_card_token'] = FALSE;
        $data['have_errors'] = FALSE;

        if( ee()->session->flashdata('card_token') != '') {
            $data['have_card_token'] = TRUE;
            $hidden['card_token'] = ee()->session->flashdata('card_token');
            $hidden['card_last4'] = ee()->session->flashdata('card_last4');
            $hidden['card_type'] = ee()->session->flashdata('card_type');
        }

        if(count($data['errors']) > 0) $data['have_errors'] = TRUE;
        $data['error_count'] = count($data['errors']);

        // Do we have a plan amount?
        // This will need to be converted back to pounds

        if(isset($data['plan_amount']) AND $data['plan_amount'] != '') {
            $data['plan_amount'] = $data['plan_amount'] / 100;
        }

        // Set up the dotted version of the card
        $data['card_number_dotted'] = ee()->charge_stripe->make_dotted_card($data['card_last4'], $data['card_type']);


        // Do we have any special values on the form?
        $protected = array();
        foreach($this->protected_params as $param) {
            if(strpos($param, ':*') > 0) {

                $matcher = explode(':', $param);

                $matcher = $matcher[0] . ':';

                if(!empty(ee()->TMPL->tagparams)) {
                    foreach(ee()->TMPL->tagparams as $key => $val) {

                        if(strpos($key, $matcher) === 0) {
                            $protected[$key] = $val;
                        }
                    }
                }
            }

            if(ee()->TMPL->fetch_param($param) != '') $protected[$param] = ee()->TMPL->fetch_param($param);
        }
        if(!empty($protected)) {
            // We have to encrypt these
            ee()->load->library('encrypt');
            $hidden[$this->protected_input_name] = ee()->encrypt->encode(base64_encode(serialize($protected)),ee()->config->item('encryption_key'));
        }

        $t = $this->_wrap_form( $action, ee()->TMPL->tagdata, $data, $hidden );
        return $t;
    }


    public function update_card_form()
    {
        return $this->form('act_update_card');
    }

    public function customer()
    {
        $member_id = ee()->session->userdata('member_id');
        if(ee()->TMPL->fetch_param('member_id') != '') $member_id = ee()->TMPL->fetch_param('member_id');

        if($member_id == '' OR $member_id == '0') return ee()->TMPL->no_results;

        // Get the member purchases
        ee()->db->where('member_id', $member_id);
        $charges = ee()->charge_stripe->get_all();
        $data['count'] = count($charges);
        $data['charges'] = $charges;

        // Get any subscriptions they may have
        $subscriptions = ee()->charge_subscription_member->active($member_id);

        if(count($subscriptions) > 0) $data['has_active_subscriptions'] = TRUE;
        else $data['has_active_subscriptions'] = FALSE;
        $data['subscriptions'] = $subscriptions;
        $data['subscriptions_count'] = count($subscriptions);


        // Get any recurring payments they may have
        $recurring = ee()->charge_stripe->get_recurring($member_id);

        if(count($recurring) > 0) $data['has_active_recurring'] = TRUE;
        else $data['has_active_recurring'] = FALSE;
        $data['recurring'] = $recurring;
        $data['count_recurring'] = count($recurring);


        $data['has_active_card'] = TRUE;
/*
        // Get any subscriptions they may have
        ee()->db->where('member_id', $member_id)
                    ->where('status', 'inactive')
                    ->join('charge_subscription', 'charge_subscription_member.subscription_id = charge_subscription.subscription_id');
        $inactive_subscriptions = ee()->charge_subscription_member->get_all();

        if(count($inactive_subscriptions) > 0) $data['has_inactive_subscriptions'] = TRUE;
        else $data['has_inactive_subscriptions'] = FALSE;
        $data['inactive_subscriptions'] = $inactive_subscriptions;

*/


        return ee()->TMPL->parse_variables(ee()->TMPL->tagdata, array($data), FALSE);
    }


    public function cards()
    {
        $customer_id = ee()->TMPL->fetch_param('customer_id');
        if($customer_id == '') {
            $customer_id = ee()->charge_stripe->find_customer_id(ee()->TMPL->fetch_param('member_id'));
        }

        if($customer_id == '' OR $customer_id === FALSE) return ee()->TMPL->no_results;

        // Now pull their card info direct from the api
        $cards = ee()->charge_stripe->cards($customer_id);

        $data['cards_count'] = count($cards);
        // Add some action urls?
        foreach($cards as $key => $card)
        {
            //$cards[$key]['remove_card_uri'] = 'TEST';
        }

        $data['cards'] = $cards;


        return ee()->TMPL->parse_variables(ee()->TMPL->tagdata, array($data), FALSE);
    }


    public function act()
    {
        $type = ee()->TMPL->fetch_param('type');
        $charge_id = ee()->TMPL->fetch_param('charge_id');
        $member_id = ee()->session->userdata('member_id');
        if(ee()->TMPL->fetch_param('member_id') != '') $member_id = ee()->TMPL->fetch_param('member_id');

        // Validate
        $action = ee()->charge_stripe->validate_action($type, $charge_id, $member_id);
        if($action === FALSE) return ee()->TMPL->no_results;

        $extra = ee()->TMPL->tagparams;
        // remove some parts
        unset($extra['type']);
        unset($extra['charge_id']);
        unset($extra['member_id']);

        // Ok. valid
        // Wrap up into a protected url
        $data['action_url'] = $this->_make_act_url($action, $extra);

        return ee()->TMPL->parse_variables(ee()->TMPL->tagdata, array($data), FALSE);
    }

    public function info()
    {
        // We require a hash
        $hash = ee()->TMPL->fetch_param('hash');
        if($hash == '') return ee()->TMPL->no_results;

        $info = ee()->charge_stripe->fetch($hash);
        if(empty($info)) return ee()->TMPL->no_results;

        return ee()->TMPL->parse_variables(ee()->TMPL->tagdata, array($info), FALSE);
    }

    public function public_key()
    {
        $stripe_mode = ee()->config->item('charge_stripe_account_mode');
        if($stripe_mode == '') $stripe_mode = 'test';

        return ee()->config->item('charge_stripe_'.$stripe_mode.'_credentials_pk');
    }

    public function error_info()
    {
        $data['has_error'] = FALSE;
        $data['error_message'] = lang('charge_unknown_error');
        $data['error_type'] = 'general';

        // Do we have flash data?
        if(ee()->session->flashdata('charge_stripe_error') == TRUE) {

            $data['has_error'] = TRUE;

            $data['error_message'] = ee()->session->flashdata('charge_stripe_error_message');
            $data['error_type'] = ee()->session->flashdata('charge_stripe_error_type');

        }


        return ee()->TMPL->parse_variables(ee()->TMPL->tagdata, array($data), FALSE);

    }

    public function api()
    {
        $ret = array();

        // Collect our parts
        $call_name = ee()->TMPL->fetch_param('call');
        $call_val = ee()->TMPL->fetch_param('key');
        $call_param_name = ee()->TMPL->fetch_param('param_name');


        // performs a read-only retrieve to the stripe api
        $data = ee()->charge_stripe->api_call($call_name, $call_val, $call_param_name);

        if($data === FALSE) {
            $ret['has_error'] = TRUE;
            $ret['error_message'] = ee()->charge_stripe->api_error_message;
        } else {
            $ret['data'] = $data;
            $ret['has_error'] = FALSE;
            $ret['error_message'] = '';
        }

        $ret['raw'] = '<pre>'.print_r($data,1).'</pre>';

        return ee()->TMPL->parse_variables(ee()->TMPL->tagdata, array($ret), FALSE);
    }


    public function js()
    {
        return $this->_js();
    }

    public function stripe_js()
    {
        return $this->_js('stripe');
    }

    //-------------------------------------------------------------------
    //-------------------------------------------------------------------
    //  ACT Method Endpoints
    //-------------------------------------------------------------------
    //-------------------------------------------------------------------


    /**
    * Act Charge
    *
    * The end point for our main action. When posted to this act point
    * we expect to have the stripe token already at this point.
    * This will handle the logic of creating a customer, charge, plan
    * and such
    *    1. Validate the inbound post
    *
    *    2. Choose our path, based on the requested plan
    *            if a one-off, we can fire the charge directly
    *            otherwise we need to check/create a plan
    *            then check/create a customer
    *            then fire the card charge
    *
    *        3. Log some details of whats happened to our audit log
    *
    *        4. Wrap up our response, ajax wrapped as nessecary
    *
    *
    * @access      public
    * @return      array()
    */
    public function act_charge()
    {
        // 0. If we have protected params in the data, decrypt and add them to the data
        $this->_handle_protected();

        // 1. Validate
        $this->_validate_request();

        $this->_pull_meta();

        if (ee()->extensions->active_hook('charge_pre_payment') === TRUE)
        {
            ee()->extensions->call('charge_pre_payment', $this);
            if (ee()->extensions->end_script === TRUE) return;
        }

        // Do we need to register a member?
        $this->_handle_member();

        // 1a. If we have error data, do something with it
        $this->_handle_errors();

        // 2. Pick a Path
        $route = $this->_pick_path();

        if($route == 'recurring')  {

            // Now create a subscription to the plan
            $return = ee()->charge_stripe->recurring($this->data);

            if( $return !== FALSE) {
                return $this->_return(TRUE, 'subscription_success', $return);
            }
        } else {

            // Create a one-off charge
            $return = ee()->charge_stripe->charge($this->data);

            if($return !== FALSE) {
                return $this->_return(TRUE, 'charge_success', $return);
            }
        }

        ee()->charge_log->log_error_failed_general($this->data);

        return $this->_return(FALSE,'general_error', $this->data);
    }

    public function act_update_card()
    {
        // Only valid members with a customer record can update a card
        $customer_id = ee()->charge_stripe->find_customer_id();
        if($customer_id == FALSE) return $this->_return(FALSE, 'general_error');

        // 1. Validate
        $this->_validate_request(TRUE);
        // 1a. If we have error data, do something with it
        $this->_handle_errors();

        $return = ee()->charge_stripe->update_card($customer_id, $this->data['card']);

        if( $return !== FALSE) {
            return $this->_return(TRUE, 'update_card_success', $return);
        }

        return $this->_return(FALSE,'general_error', $this->data);
    }

    public function act_webhook()
    {
        // Validate first
        $key = ee()->input->get('key');

        if($key != ee()->config->item('charge_webhook_key')) {
            ee()->charge_log->log_webhook_bad_trigger(array('key' => $key));
            exit();
        }

        $body = @file_get_contents('php://input');
        $body = Charge_obj_to_array(json_decode($body));

        if(empty($body) OR !is_array($body)) {
            ee()->charge_log->log_webhook_empty();
            exit();
        }

        // Good webhook, pull out the data
        ee()->charge_log->log_webhook_good($body);

        // Ok, pass over to the webhook model to all the work
        $response = ee()->charge_webhook->act($body);

        if(!$response) ee()->output->set_status_header(500);
        else ee()->output->set_status_header(200);

        exit();
    }

    public function act_user()
    {
        $data = ee()->input->get('A');
        if($data == '') return $this->_return(FALSE,'general_error');


        ee()->load->library('encrypt');
        $data = unserialize(base64_decode(ee()->encrypt->decode($data)));
        if(!is_array($data) OR empty($data)) return $this->_return(FALSE,'general_error');

        $extra = ee()->input->get('extra');
        if($extra != '') $extra = unserialize(base64_decode($extra));
        if(!is_array($extra)) $extra = array();

        // Pass over to the stripe model to actually do something with
        $result = ee()->charge_stripe->user_action($data, $extra);

        return $this->_return($result, 'user_action');
    }

    //-------------------------------------------------------------------
    //-------------------------------------------------------------------
    //  Private Methods
    //-------------------------------------------------------------------
    //-------------------------------------------------------------------


    /*
    * Handle Member
    *
    * In 1.4 we added the ability to register a member as part of a
    * successful payment. We'll create the member here, before we
    * actually take the payment. This may fail, so we could return errors.
    * Also if they payment fails, we'll need to clean up the member later
    */
    private function _handle_member()
    {
        // Only relevant when we're not already logged in
        if(ee()->session->userdata('member_id') != '0') return;
        if( !(isset($this->protected['create_member']) AND $this->protected['create_member'] == 'yes')) return;


        // We also allow an option to let the user decide if they want to have an account created for them
        if(isset($this->data['member']['create']) AND $this->data['member']['create'] == 'no') return;

        // if passed, we also need to validate password fields
        if(isset($this->data['member']['password']) AND $this->data['member']['password'] != '') {

            if(isset($this->data['member']['password_confirm'])) {
                if($this->data['member']['password'] != $this->data['member']['password_confirm']) {
                    $this->errors['member_password'] = lang('charge_member_password_must_match');

                    return FALSE;
                }
            } else {
                $this->errors['member_password'] = lang('charge_member_password_confirm');
                return FALSE;
            }
        }

        // Only create the member if there are no errors at this point
        if(!empty($this->errors)) return FALSE;

        // Ok. Create the member.
        // We might have some varying levels of data
        // and we need to validate things too
        $member = ee()->charge_member->create($this->data);
        if($member === FALSE) {
            // Error state
            $this->errors = array_merge($this->errors, ee()->charge_member->errors);
            return FALSE;
        }

        // Complete, make sure we log them in here too
        return;
    }

    private function _handle_errors()
    {

        if(empty($this->errors)) return;
        if(empty($this->data)) return;

         // There is a bug in CI on nginx servers
        // were repeated called to set_flashdata will throw a 502 error
        // We'll have to bundle these up into a single call instead
        $newflash_data = array();


        // There's an error somewhere, we'll need to persist our data for the refresh
        foreach($this->data as $set => $data) {
            if($set == 'meta') {
                foreach($data as $key => $val) {
                    if($val != '') $newflash_data[$set.':'.$key] = $val;
                }
            }
            else {
                foreach($data as $key => $val) {
                    if($val != '') $newflash_data[$set.'_'.$key] = $val;
                }
            }
        }

        foreach($this->errors as $error_key =>$error_val) {
            if($error_val != '') $newflash_data['error_'.$error_key] = lang($error_val);
        }

        if(!empty($newflash_data)) ee()->session->set_flashdata($newflash_data);

        $this->_return(FALSE, 'validation_fail');
    }

    private function _is_ajax_request()
    {
        if (ee()->input->server('HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest')
        {
            return TRUE;
        }

        return FALSE;
    }
    // END is_ajax_request()





    /*
    * Generates the return url for an ACT link
    * based on params and variables
    */
    private function _make_act_url($data, $extra = array())
    {
        // Wrap up the params into an encoded wrapper
        ee()->load->library('encrypt');
        $str = ee()->encrypt->encode(base64_encode(serialize($data)));

        $url = $this->_get_action_url('act_user');

        $url .= '&A='.urlencode($str);
        $url .= '&ret='.$this->_get_ret_url();

        if(!empty($extra)) $url .= '&extra='.urlencode(base64_encode(serialize($extra)));

        return $url;
    }


    /*
    * Generates the return url for an ACT link
    * based on params and variables
    */
    private function _get_ret_url( $params = array(), $name = 'return' )
    {
        $ret = ee()->functions->fetch_current_uri();

        // Directly use the tagparams array as we're calling this dynamically
        // later on for toggle and this bypasses a logic hold
        if( isset( ee()->TMPL->tagparams[$name] ) )
        {
            $ret = urldecode(ee()->TMPL->tagparams[$name]);
        }

        return $ret;
    }



    private function _return($state, $type, $extra = array())
    {
        if(!is_array($extra) ) $extra = Charge_obj_to_array($extra);

        if($state == FALSE) {
            // We may have errors that should be availble to the next request
            if(isset(ee()->charge_stripe->errors) AND !empty(ee()->charge_stripe->errors)){
                $flashdata = array();
                $flashdata['charge_stripe_error'] = TRUE;

                // Add all the errors
                foreach(ee()->charge_stripe->errors as $error_key => $error_val) {
                    $flashdata['charge_stripe_error_'.$error_key] = $error_val;
                }
                ee()->session->set_flashdata($flashdata);
            }
        }


        if($state == TRUE AND $type != 'update_card_success') {

            if (ee()->extensions->active_hook('charge_post_payment') === TRUE)
            {
                $hook_data = $extra;

                unset($hook_data['card_token']);

                ee()->extensions->call('charge_post_payment', $type, $hook_data);
                if (ee()->extensions->end_script === TRUE) return;
            }

            $this->_trigger_success($extra);
        }

        ee()->load->helper('string');

        if( $this->_is_ajax_request()) {

          if($state === TRUE) {
                ee()->output->send_ajax_response(array(
                    'verb'          => $type,
                    'data'          => $extra,
                    'success'       => $state
                ));
            } else {

              ee()->output->send_ajax_response(array(
                    'verb'          => $type,
                    'data'          => $extra,
                    'success'       => $state
                ));

            }
        }


        $return = urldecode(ee()->input->get_post('ret'));

        if($state === TRUE AND isset($extra['hash'])) {
            $return .= '/'.$extra['hash'];
        }

        if($state === FALSE AND $type == 'validation_fail') $return = ( isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '');
        elseif($state === FALSE) {
            $return = urldecode(ee()->input->get_post('ret_error'));
        }

        if($return == '' OR $return === FALSE) $return = ee()->functions->fetch_site_index();

        ee()->functions->redirect( $return );
        exit();
    }



    private function _pick_path()
    {
        // Is there a plan_length defined? (and also valid)

        if(isset($this->data['plan']['length']) AND is_numeric($this->data['plan']['length'])) {
            // Even if this is a one-time charge, we handle it as a simple recurring payment, but
            // immediately cancel it after the first payment

            // Add a marker to show this is a spoofed recurring plan
            if(!isset($this->data['plan']['interval_count']) OR $this->data['plan']['interval_count'] == '0') {
                $this->data['plan']['spoofed_recurring'] = TRUE;

                // Set the recurring details to match the length
                $this->data['plan']['interval_count'] = $this->data['plan']['length'];
                $this->data['plan']['interval'] = $this->data['plan']['length_interval'];
            }

            return 'recurring';
        }

        // if plan_interval_count isn't set or set to 0, do a one off charge,
        // otherwise, recurring
        if(!isset($this->data['plan']['interval_count']) OR $this->data['plan']['interval_count'] == '0') {
            // One off
            return 'charge';
        }

        // In this case, we need to have a valid interval value also
        if(!isset($this->data['plan']['interval']) OR $this->data['plan']['interval'] == '') {
            $this->data['plan']['interval'] = 'month';
        }

        return 'recurring';
    }


    private function _handle_protected()
    {
        if(ee()->input->get_post($this->protected_input_name) != '') {
            $protected = ee()->input->get_post($this->protected_input_name);

            ee()->load->library('encrypt');
            $protected = unserialize(base64_decode(ee()->encrypt->decode($protected)));

            if(!empty($protected)) $this->protected = $protected;
        }

        $this->_handle_required_extra();
        $this->_handle_trial_days();
        $this->_handle_account_balance();
        $this->_handle_set_plan_name();
    }

    private function _handle_required_extra()
    {
        // If we have a 'required' param in our protected array
        // we let devs specify extra required field inputs
        // beyond the standard
        if(!isset($this->protected['required'])) return;

        $req = explode('|', $this->protected['required']);

        // All rules are subgrouped, by '_', deal with that
        foreach($req as $req_key)
        {
            if(strpos($req_key, 'meta:') === 0) {
                // We have a validation on a meta: key
                $req_parts = explode(':', $req_key);
            } else {
                $req_parts = explode('_', $req_key);
            }

            if(count($req_parts) < 2) continue;

            $set = array_shift($req_parts);
            $sub = implode('_', $req_parts);

            if($set == 'meta') $this->rules['meta'][$sub] = '';
            else if(!isset($this->rules[$set][$sub])) continue;

            $current_rule = $this->rules[$set][$sub];
            if($current_rule == '') $current_rule = 'required';
            else $current_rule = 'required|'.$current_rule;
            $this->rules[$set][$sub] = $current_rule;
        }

        return;
    }

    private function _handle_set_plan_name()
    {
        if(isset($this->protected['plan_set_name'])) {
            $this->data['plan']['set_name'] = $this->protected['plan_set_name'];
        }
    }

    private function _handle_trial_days()
    {
        // Default to no trail
        $this->data['plan']['trial_days'] = 0;

        if(isset($this->protected['plan_trial_days'])) {
            // We need to add extra validation here
            if(is_numeric($this->protected['plan_trial_days']) AND $this->protected['plan_trial_days'] > 0) {
                $this->data['plan']['trial_days'] = ceil($this->protected['plan_trial_days']);
            }
        }
    }


    private function _handle_account_balance()
    {
        if(isset($this->protected['plan_balance'])) {
            // We need to add extra validation here
            if(is_numeric($this->protected['plan_balance']) AND $this->protected['plan_balance'] > 0 OR $this->protected['plan_balance'] < 0) {
                $this->data['plan']['balance'] = floor($this->protected['plan_balance'] * 100);
            }
        }
    }

    /**
    * Validate Request
    * We need :
    *            a) Stripe Token (via stripe.js)
    *            b) Customer Details (name/email)
    *            c) Charge Details (amount)
    *          We may also have :
    *            d) Plan Details (one off/monthly etc.
    *                if not passed, default to one-off)
    */
    private function _validate_request($card_only = FALSE)
    {
        if($card_only === TRUE) {
            foreach($this->rules as $set => $set_rules){
                if($set != 'card') unset($this->rules[$set]);
            }
        }

        foreach($this->rules as $set_name => $set_rules) {
            foreach($set_rules as $item_key => $item_rule) {

                $breaker = '_';
                if($set_name == 'meta') $breaker = ':';

                $item_val = $this->_validate_item($set_name.$breaker.$item_key, $item_rule);
                if($item_val != '') $this->data[$set_name][$item_key] = $item_val;
            }
        }

        if($card_only !== TRUE) {

            // Make sure we have a valid currency value
            if(!isset($this->data['plan']['currency']) ) $this->data['plan']['currency'] = ee()->config->item('charge_stripe_currency');
            elseif(!in_array($this->data['plan']['currency'], array_keys(ee()->charge_stripe->supported_currencies))) {
                // Throw an error
                $this->errors['plan_currency'] = 'charge_error_invalid_currency';
            }

            if($this->data['plan']['currency'] == '' OR $this->data['plan']['currency'] == FALSE) $this->data['plan']['currency']= ee()->charge_stripe->default_currency;


            // Make sure our amount is in cents/pennies
            if(!isset($this->data['plan']['amount']) ) {
                $this->errors['plan_amount'] = 'charge_error_missing_amount';
            } else $this->data['plan']['amount'] = round($this->data['plan']['amount'] * 100);

            if(isset($this->data['plan']['length']) AND is_numeric($this->data['plan']['length'])) {
                $this->_validate_set_length();
            }

            // Validate any supplied coupon is valide
            if(isset($this->data['plan']['coupon']) AND $this->data['plan']['coupon'] != '') {
                $this->_validate_coupon();
            }
        }

    }




    /*
    * Quick helper function that wraps tagdata
    * in an opening and closing form tags
    * and also adds some hidden fields while it's at it
    */
    private function _wrap_form( $act, $tagdata, $data = array(), $hidden_extra = array())
    {
        $form_class = ee()->TMPL->fetch_param('form_class');
        $form_id    = ee()->TMPL->fetch_param('form_id');
        $form_class = $form_class != '' ? ' class="'.$form_class.'"' : '';
        $form_id    = $form_id != '' ? ' id="'.$form_id.'"' : '';
        $form_method = 'POST';

        $ret = $this->_get_ret_url();
        $ret_error = $this->_get_ret_url(array(), 'return_error');

        // Get the action_id
        $action_url = $this->_get_action_url( $act );

        if(APP_VER < '2.7') {
            $hidden[] = '<input type="hidden" name="csrf_token" value="{csrf_token}"/>';
        } else {
            $hidden[] = '<input type="hidden" name="XID" value="{XID_HASH}"/>';
        }

        $hidden[] = '<input type="hidden" name="ret" value="'.urlencode($ret).'"/>';
        $hidden[] = '<input type="hidden" name="ret_error" value="'.urlencode($ret_error).'"/>';

        foreach($hidden_extra as $hidden_key => $hidden_val) {
            $hidden[] = '<input type="hidden" name="'.$hidden_key.'" value="'.$hidden_val.'"/>';
        }

        $bare = "<form name='charge_form'". $form_class . $form_id ." method='".$form_method."' action='".$action_url."'>";

        $bare .= implode(' ',$hidden);

        $bare .= $tagdata;
        $bare .= "</form>";

        $str = ee()->functions->add_form_security_hash($bare);


        // Parse tagdata
        $t = ee()->TMPL->parse_variables(
                $bare,
                array($data),
                TRUE
            );

        return $t;
    }



    private function _get_action_url($method_name)
    {
        // Cache the action urls for repeated use
        if( !isset( ee()->session->cache['charge']['action_urls'][ $method_name ] ) )
        {
            $action_id  = ee()->db->where(
                array(
                    'class'     => CHARGE_CLASS_NAME,
                    'method'    => $method_name
                )
            )->get('actions')->row('action_id');

            $path = str_replace('??', '?', ee()->functions->fetch_site_index(0, 0) . '?ACT=' . $action_id);

            // On some server setups the https path doesn't naturally flow from the built in url
            if(ee()->config->item('charge_force_ssl') == 'yes') {
                $path = str_replace('http://', 'https://', $path);
            }

            ee()->session->cache['charge']['action_urls'][ $method_name ] = $path;
        }

        return ee()->session->cache['charge']['action_urls'][ $method_name ];

    }
    //END get_action_url

    private function _pull_meta()
    {
        $meta = array();

        foreach($_REQUEST as $key => $val) {
            if(strpos($key, 'meta:') !== FALSE) {

                $key = str_replace('meta:','', $key);
                $val = ee()->security->xss_clean($val);

                $meta[$key] = $val;
            }
        }

        if(empty($meta)) return;

        $this->data['meta'] = $meta;

        return;
    }

    private function _validate_item( $post_name, $rules = '')
    {
        $valid = TRUE;
        $required = FALSE;

        // Set in protected?
        if(isset($this->protected[$post_name])) $val = $this->protected[$post_name];
        else $val = ee()->input->get_post($post_name);


        foreach(explode('|', $rules) as $rule) {
            switch($rule) {
                case 'required' :
                    if(trim($val) == '') {
                        $valid = FALSE;
                        $required = TRUE;
                        $this->errors[$post_name] = lang('charge_error_required');
                    }
                break;
                case 'integer' :
                    $val = (int) $val;
                    if(!is_int($val)) {
                        $valid = FALSE;
                        $this->errors[$post_name] = lang('charge_error_integer');
                    }
                break;
                case 'numeric' :
                    if(!is_numeric($val)) {
                        $valid = FALSE;
                        $this->errors[$post_name] = lang('charge_error_numeric');
                    }
                break;
                case 'email' :
                    ee()->load->helper('email');
                    if(!valid_email($val)) {
                        $valid = FALSE;
                        $this->errors[$post_name] = lang('charge_error_email');
                    }
                break;
                case 'alphanumeric'  :
                    preg_match("/[^a-zA-Z\d\s]/", $val, $matches);
                    if(!empty($matches)) {
                        $valid = FALSE;
                        $this->errors[$post_name] = lang('charge_error_not_alphanumeric');
                    }
                default :
                    $rule = explode(':', $rule);
                    $rule_type = current($rule);
                    next($rule);
                    $next_type = current($rule);

                    switch($rule_type) {
                        case 'in' :
                            if(!in_array($val, explode(',',$next_type))) {
                                $valid = FALSE;
                                $this->errors[$post_name] = lang('charge_error_not_valid_value');
                            }
                        break;
                        case 'min' :
                            if( strlen($val) < $next_type) {
                                $valid = FALSE;
                                $this->errors[$post_name] = lang('charge_error_too_short');
                            }
                        break;
                        case 'max' :
                            if( strlen($val) > $next_type) {
                                $valid = FALSE;
                                $this->errors[$post_name] = lang('charge_error_too_long');
                            }
                        break;
                    }
                break;
            }
        }

        // Token gets special handling
        if($post_name == 'card_token' && !$valid) {
            $this->errors[$post_name] = lang('charge_error_card_token_missing');
        }

        if(!$required AND trim($val) == '') unset($this->errors[$post_name]);

        return $val;
    }


    private function _trigger_success($data)
    {
        if(isset($this->protected['on_success']) AND $this->protected['on_success'] != '') {
            ee()->charge_log->log_action_trigger_start(array('data' => $data, 'action' => $this->protected['on_success']));

            $actions = explode('|', $this->protected['on_success']);
            foreach($actions as $action) {
                ee()->charge_action->run($action, $data, $this->protected);
            }
        }

        if(isset($this->protected['success_action']) AND $this->protected['success_action'] != '') {
            // Woot. We have something to do.
            ee()->charge_log->log_action_trigger_start(array('data' => $data, 'action' => $this->protected['success_action']));

            // Throw this over to our action model
            ee()->charge_action->trigger($this->protected['success_action'], $data);
        }
    }

    private function _js($type = '')
    {
        $name = '';

        if($type == 'stripe') $name = 'stripe_v2.min.js';
        else $name = 'jquery.charge.js';

        if($name == '') return;

        $base = '';
        if(defined(URL_THIRD_THEMES))
        {
            $base = URL_THIRD_THEMES;
        }
        else
        {
            $base = ee()->config->item('theme_folder_url');

            if (substr($base, -1) != '/') {
               $base .= '/';
            }

            $base .= "third_party/";
        }

        $theme_folder_url = $base . 'charge/';

        $path = $theme_folder_url . "scripts/".$name;

        if(ee()->config->item('charge_force_ssl') == 'yes') {
            $path = str_replace('http://', 'https://', $path);
        }

        return $path;

    }


    private function _add_card_months()
    {
        $card_months = array();

        $card_months[] = array('month_digit' => '01', 'month_long' => ucwords(ee()->lang->line('january')), 'month_short' => ee()->lang->line('jan'), 'current_month' => ( date('m') == '01' ? TRUE : FALSE ) );
        $card_months[] = array('month_digit' => '02', 'month_long' => ucwords(ee()->lang->line('february')), 'month_short' => ee()->lang->line('feb'), 'current_month' => ( date('m') == '02' ? TRUE : FALSE ) );
        $card_months[] = array('month_digit' => '03', 'month_long' => ucwords(ee()->lang->line('march')), 'month_short' => ee()->lang->line('mar'), 'current_month' => ( date('m') == '03' ? TRUE : FALSE ) );
        $card_months[] = array('month_digit' => '04', 'month_long' => ucwords(ee()->lang->line('april')), 'month_short' => ee()->lang->line('apr'), 'current_month' => ( date('m') == '04' ? TRUE : FALSE ) );
        $card_months[] = array('month_digit' => '05', 'month_long' => ucwords(ee()->lang->line('may')), 'month_short' => ee()->lang->line('may'), 'current_month' => ( date('m') == '05' ? TRUE : FALSE ) );
        $card_months[] = array('month_digit' => '06', 'month_long' => ucwords(ee()->lang->line('june')), 'month_short' => ee()->lang->line('jun'), 'current_month' => ( date('m') == '06' ? TRUE : FALSE ) );
        $card_months[] = array('month_digit' => '07', 'month_long' => ucwords(ee()->lang->line('july')), 'month_short' => ee()->lang->line('jul'), 'current_month' => ( date('m') == '07' ? TRUE : FALSE ) );
        $card_months[] = array('month_digit' => '08', 'month_long' => ucwords(ee()->lang->line('august')), 'month_short' => ee()->lang->line('aug'), 'current_month' => ( date('m') == '08' ? TRUE : FALSE ) );
        $card_months[] = array('month_digit' => '09', 'month_long' => ucwords(ee()->lang->line('september')), 'month_short' => ee()->lang->line('sep'), 'current_month' => ( date('m') == '09' ? TRUE : FALSE ) );
        $card_months[] = array('month_digit' => '10', 'month_long' => ucwords(ee()->lang->line('october')), 'month_short' => ee()->lang->line('oct'), 'current_month' => ( date('m') == '10' ? TRUE : FALSE ) );
        $card_months[] = array('month_digit' => '11', 'month_long' => ucwords(ee()->lang->line('november')), 'month_short' => ee()->lang->line('nov'), 'current_month' => ( date('m') == '11' ? TRUE : FALSE ) );
        $card_months[] = array('month_digit' => '12', 'month_long' => ucwords(ee()->lang->line('december')), 'month_short' => ee()->lang->line('dec'), 'current_month' => ( date('m') == '12' ? TRUE : FALSE ) );

        return $card_months;
    }

    private function _add_card_years()
    {
        $card_years = array();

        $this->card_years_show = ( ee()->TMPL->fetch_param('show_card_years_count') != '' ? ee()->TMPL->fetch_param('show_card_years_count')  : $this->card_years_show );

        $base_year = date('Y');
        for ($i=0; $i < $this->card_years_show ; $i++) {
            $current_year = $base_year + $i;
            $card_years[] = array('year' => $current_year, 'year_long' => $current_year, 'year_short' => substr($current_year, 2));
        }

        return $card_years;
    }


    private function _validate_set_length()
    {

        // If they've passed a plan_length, we must also validate it's a workable length

        if($this->data['plan']['length'] < 1) unset($this->data['plan']['length']); // Ignore it
        else {
            $this->data['plan']['length'] = floor($this->data['plan']['length']);
            // We let the dev specify the plan_length_interval multiple ways
            // 1. Directly by plan_length_interval
            // 2. Implied by plan_interval
            // 3. Default to 'month' otherwise
            $length_interval = 'month';

            // These are validated to be 'week', 'month' or 'year' by our global rules
            if(isset($this->data['plan']['length_interval']) AND $this->data['plan']['length_interval'] != '') $length_interval = $this->data['plan']['length_interval'];
            elseif (isset($this->data['plan']['interval']) AND $this->data['plan']['interval'] != '') $length_interval = $this->data['plan']['interval'];

            $this->data['plan']['length_interval'] = $length_interval;
            // Now check we're within our limits
            switch($length_interval) {
                case 'week' :
                    if($this->data['plan']['length'] > 52) $this->errors['plan_length'] = 'charge_error_plan_length_too_long';
                break;
                case 'month' :
                    if($this->data['plan']['length'] > 12) $this->errors['plan_length'] = 'charge_error_plan_length_too_long';
                break;
                case 'year' :
                    if($this->data['plan']['length'] > 1) $this->errors['plan_length'] = 'charge_error_plan_length_too_long';
                break;
                default :
                    $this->errors['plan_length'] = 'charge_error_plan_length_invalid';
                break;
            }

            // Now figure out our expiry timestamp
            $expiry = strtotime('+'.$this->data['plan']['length']. ' ' . $this->data['plan']['length_interval']);
            $this->data['plan']['length_expiry'] = $expiry;

        }
    }

    private function _validate_coupon()
    {
        $valid_base = FALSE;

        if(trim($this->data['plan']['coupon']) == '')
        {
            unset($this->data['plan']['coupon']);
            return; // Ignore it
        }

        $plan_type = 'one-off';
        if(isset($this->data['plan']['interval_count']) AND $this->data['plan']['interval_count'] > 0) $plan_type = 'recurring';

        // Find the coupon in question (if it exists)
        $coupon = ee()->charge_coupon->get_one($this->data['plan']['coupon'], 'code');

        if(empty($coupon))
        {
            // Not a valid code
            $this->errors['plan_coupon'] = 'charge_coupon_error_invalid_code';
            return;
        }
        else
        {
            // Check the code is valid for this payment type
            if($plan_type == 'recurring' AND $coupon['payment_type'] == 'one-off')
            {
                $this->errors['plan_coupon'] = 'charge_coupon_error_invalid_recurring';
                return;
            }
            elseif($plan_type == 'one-off' AND $coupon['payment_type'] == 'recurring')
            {
                $this->errors['plan_coupon'] = 'charge_coupon_error_invalid_charge';
                return;
            }
            else
            {
                $valid_base = TRUE;
            }
        }

        if($plan_type == 'recurring')
        {
            // Nothing more we do now. Recurring coupons are validated by stripe directly
            $this->data['coupon']['coupon'] = $coupon;
            return;
        }


        // Apply our validation for one-time coupons
        // Only dealing with one-time coupons now.
        $base_amount = $this->data['plan']['amount'];
        $base_currency = $this->data['plan']['currency'];

        $discount_amount = 0;
        $final_amount = $base_amount;

        if($coupon['type'] == 'percentage') {

            $percentage_off = $coupon['percentage_off'];

            $discount_amount = (double) $base_amount * ( $percentage_off / 100 );
            $final_amount = (double) $base_amount - $discount_amount;
        }


        if($coupon['type'] == 'fixed') {

            $amount_off = $coupon['amount_off'];

            $discount_amount = ceil($amount_off * 100);
            $final_amount = (double) $base_amount - $discount_amount;
        }


        // Sanity Check
        if($discount_amount <= 0) {
            // nope, discount is zero.
            $this->errors['plan_coupon'] = lang('charge_craft_coupon_invalid');
            return;
        }

        if($final_amount >= $base_amount) {
            // nope, somehow this 'discount' has increased the price
            $this->errors['plan_coupon'] = lang('charge_craft_coupon_invalid');
            return;
        }

        // Check we're still above the min transaction price
        if($final_amount <= 0.5) {
            $this->errors['plan_coupon'] = lang('charge_craft_coupon_below_min');
            $model->addError('planCoupon', Craft::t('Sorry, applying this coupon brings your total below the minimum we can charge'));
            return;
        }

        $final_amount = floor(strval($final_amount));
        $discount_amount = strval($discount_amount);

        $this->data['coupon']['coupon'] = $coupon;
        $this->data['coupon']['plan']['amount'] = $final_amount;
        $this->data['coupon']['plan']['discount'] = $discount_amount;
        $this->data['coupon']['plan']['full_amount'] = $base_amount;

        return;
    }
}

?>