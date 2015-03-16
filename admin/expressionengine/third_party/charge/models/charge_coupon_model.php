<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Charge Coupon Model class
 *
 * @package         charge_ee_addon
 * @version         1.9.0
 * @author          Joel Bradbury ~ <joel@squarebit.co.uk>
 * @link            http://squarebit.co.uk/software/expressionengine/charge
 * @copyright       Copyright (c) 2015, Joel Bradbury/Square Bit
 */
class Charge_coupon_model extends Charge_model {


    public $settings = array(
                            'stripe_id'             => '',
                            'type' 				=> array('required' => TRUE),
    						'amount_off'			=> array('required_by' => 'type', 'required_by_val' => 'fixed', 'ignore_else' => TRUE, 'numeric' => TRUE, 'min' => 0),
    						'currency'				=> array('required_by' => 'type', 'required_by_val' => 'fixed', 'ignore_else' => TRUE),
    						'percentage_off'		=> array('required_by' => 'type', 'required_by_val' => 'percentage', 'ignore_else' => TRUE, 'integer' => TRUE, 'min' => 0, 'max' => 100),
    						'payment_type'			=> array('required' => TRUE),
    						'max_redemptions'		=> array('integer' => TRUE, 'min' => -1),
    						'duration'				=> array('required_by' => 'payment_type', 'required_by_val' => 'recurring', 'ignore_else' => TRUE),
    						'duration_in_months' 	=> array('required_by' => 'duration', 'required_by_val' => 'repeating','ignore_else' => TRUE, 'integer' => TRUE, 'min' => -1));

    public $this_settings = array();

    public $errors = array();
    public $actions = array();
    public $protected = array();

	// --------------------------------------------------------------------
	// METHODS
	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @access      public
	 * @return      void
	 */
	function __construct()
	{
		// Call parent constructor
		parent::__construct();

		// Initialize this model
		$this->initialize(
			'charge_coupon',
			'coupon_id',
			array(
				'site_id'       		=> 'int(4) unsigned NOT NULL default 1',
				'timestamp'				=> 'int(10) unsigned NOT NULL default 0',

				'stripe_id'				=> 'varchar(100) NOT NULL default ""',
				'name'					=> 'varchar(100) NOT NULL default ""',
				'code'					=> 'varchar(100) NOT NULL default ""',
				'type'					=> 'varchar(100) NOT NULL default ""',

				'percentage_off'		=> 'varchar(100) NOT NULL default ""',
				'amount_off'			=> 'int(10) unsigned NOT NULL default 0',
				'currency'				=> 'varchar(100) NOT NULL default ""',

				'payment_type'			=> 'varchar(100) NOT NULL default ""',
				'duration'				=> 'varchar(100) NOT NULL default ""',
				'duration_in_months'	=> 'int(10) NOT NULL default 0',
				'max_redemptions'		=> 'int(10) NOT NULL default 0',
				'redeem_by'				=> 'int(10) NOT NULL default 0' )
		);
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
		ee()->db->query("ALTER TABLE {$this->table()} ADD INDEX (`code`)");
	}

    public function get_one($id, $attr = FALSE)
    {
        $row = parent::get_one($id, $attr);

        // Parse out the amount_off
        if(isset($row['amount_off']) AND is_numeric($row['amount_off']) AND $row['amount_off'] > 0){
            $row['amount_off'] = number_format($row['amount_off'] / 100,2, '.', '');
        }

        return $row;
    }


    public function save($coupon_id = 0)
    {
        $this->this_settings = $this->_get_settings();
        $this->this_settings['name'] = ee()->input->post('name');
        $this->this_settings['code'] = ee()->input->post('code');


        if(!isset($this->this_settings['name']) OR $this->this_settings['name'] == '') {
            $this->errors['name'] = lang('charge_action_no_name');
        }

        if(!isset($this->this_settings['code']) OR $this->this_settings['code'] == '') {
            $this->errors['code'] = lang('charge_coupon_no_code');
        }

        // Validate the code is unique
        $this->_check_code_unique($this->this_settings['code'], $coupon_id);

        if(!empty($this->errors)) return FALSE;


        // Ok, looks valid, set it up to save
        $data['name'] = $this->this_settings['name'];
        $data['code'] = $this->this_settings['code'];

        unset($this->this_settings['name']);
        unset($this->this_settings['code']);

        $data['timestamp'] = time();
        $data['site_id'] = ee()->config->item('site_id');

        foreach($this->this_settings as $key => $val)
        {
            $data[$key] = $val;
        }

        // All is valid
        // Create a stripe coupon as requried
        $data = $this->_stripe_update($data);
        // Might return an error
        if(!empty($this->errors)) return FALSE;

        if($coupon_id == 0 OR $coupon_id == '') {
            // New
            self::insert($data);
        } else {
            self::update($coupon_id, $data);
        }


        return TRUE;
    }


    private function _get_settings()
    {
        $this_settings = array();

        foreach($this->settings as $key => $settings)
        {
        	$required = FALSE;
        	if(isset($settings['required']) AND $settings['required'] == TRUE) $required = TRUE;
        	elseif( isset($settings['required_by']) AND isset($settings['required_by_val'])) {
        		// Check another value for required state
        		if(ee()->input->post($settings['required_by']) == $settings['required_by_val']) $required = TRUE;
        	}


            // Further validation for content restrictions
            $error = $this->_extra_validation($required, $this->settings[$key], ee()->input->post($key));
            if($error !== FALSE) $this->errors[$key] = $error;

            $val = ee()->input->post($key);
            if($key == 'amount_off') {
                // Amount off is recorded in cents/pence, but displayed in dollars/pounds
                if(is_numeric($val)) $val = floor($val * 100);
            }

        	$this_settings[$key] = $val;
        }

        return $this_settings;
    }


    private function _extra_validation($required, $rules, $val)
    {
        if($required AND $val == '')
        {
            return lang('charge_required_setting');
        }
        elseif($val != '')
        {
            if(isset($rules['ignore_else']))
            {
                return FALSE;
            }

            if(isset($rules['numeric']) AND !is_numeric($val))
            {
                return lang('charge_error_numeric');
            }

            if(isset($rules['integer']))
            {
                if(!is_numeric($val)) return lang('charge_error_integer');
                $i = (int) $val;
                if(!is_integer($i)) return lang('charge_error_integer');
            }

            if(isset($rules['min']) AND is_numeric($val) AND $val <= $rules['min'])
            {
                return str_replace('{min}', $rules['min'], lang('charge_error_below_min'));
            }

            if(isset($rules['max']) AND is_numeric($val) AND $val > $rules['max'])
            {
                return str_replace('{max}', $rules['max'], lang('charge_error_above_max'));
            }
        }
        return FALSE;
    }

    private function _check_code_unique($code, $coupon_id = 0)
    {
        if($coupon_id != 0) ee()->db->where('coupon_id !=', $coupon_id);

        $res = self::get_one($code, 'code');

        if(!empty($res))  $this->errors['code'] = lang('charge_coupon_code_unique');
    }


    private function _stripe_update($data)
    {
        $create_new = FALSE;
        $remove_old = FALSE;

        // Only need to bother with this for recurring coupons
        if($data['payment_type'] != 'recurring') return $data;

        // Find if we have an existing id
        if($data['stripe_id'] == '') $create_new = TRUE;
        else {
            // Only create a new coupon if we have changes
            $stripe_coupon = ee()->charge_stripe->check_coupon_exists($data['stripe_id']);

            if($stripe_coupon == FALSE)
            {
                $create_new = TRUE;
            } elseif( !$this->_compare_coupons($data, $stripe_coupon)) {
                $remove_old = TRUE;
                $create_new = TRUE;

                $data['max_redemptions'] = $data['max_redemptions'] - $stripe_coupon->times_redeemed;
            }
        }


        if($remove_old === TRUE) {
            // Kill an old code
            ee()->charge_stripe->delete_coupon($data['stripe_id']);
        }

        if($create_new === TRUE) {
            ee()->load->helper('string');
            $data['stripe_id'] = $data['code'] . ' - '. random_string('alnum', 8);
            $stripe_coupon = ee()->charge_stripe->create_coupon($data);

             if($stripe_coupon === FALSE) {
                $this->errors['general'] = lang('charge_coupon_create_error');
                return $data;
            }
        }

        return $data;

    }



    private function _compare_coupons($coupon, Stripe_Coupon &$stripeCoupon)
    {
        // Test the type
        $type = $coupon['type'];

        if($stripeCoupon->percent_off != null) $stripeType = 'percentage';
        else $stripeType = 'amount';

        if($type != $stripeType) return FALSE;

        // The actual amounts may also have changed
        if($type == 'percentage') {
            if($coupon['percentage_off'] != $stripeCoupon->percent_off) return FALSE;
        }


        if($type == 'amount') {
            if($coupon['amount_off'] != $stripeCoupon->amount_off) return FALSE;
            if($coupon['currency'] != $stripeCoupon->currency) return FALSE;
        }

        // The duration may have changed too
        if($coupon['duration'] != $stripeCoupon->duration) return FALSE;

        if($coupon['duration'] == 'repeating') {
            if($coupon['duration_in_months'] != $stripeCoupon->duration_in_months) return FALSE;
        }

        // And Max Durations
        if($coupon['max_redemptions'] != $stripeCoupon->max_redemptions) return FALSE;

        return TRUE;
    }

} // End class

/* End of file Charge_webhook_model.php */