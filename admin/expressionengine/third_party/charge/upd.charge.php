<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// include config file
include PATH_THIRD.'charge/config'.EXT;

/**
 * Charge Update Class
 *
 * @package         charge_ee_addon
 * @version         1.9.5
 * @author          Joel Bradbury ~ <joel@squarebit.co.uk>
 * @link            http://squarebit.co.uk/software/expressionengine/charge
 * @copyright       Copyright (c) 2015, Joel Bradbury/Square Bit
 */
class Charge_upd {

	// --------------------------------------------------------------------
	// PROPERTIES
	// --------------------------------------------------------------------

	/**
	 * Version number
	 *
	 * @access      public
	 * @var         string
	 */
	public $version = CHARGE_VERSION;


	private $actions = array(
		'act_charge',
		'act_webhook',
		'act_user',
		'act_update_card'
	);


	private $csrf_exempt = array('act_webhook');


	// --------------------------------------------------------------------
	// METHODS
	// --------------------------------------------------------------------

	/**
	 * Constructor: sets EE instance
	 *
	 * @access      public
	 * @return      null
	 */
	public function __construct()
	{
		//--------------------------------------------
		//	Alias to get_instance()
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

    //	ee()->load->remove_package_path(PATH_THIRD.'charge');
	}

	// --------------------------------------------------------------------

	/**
	 * Install the module
	 *
	 * @access      public
	 * @return      bool
	 */
	public function install()
	{
		// --------------------------------------
		// Install tables
		// --------------------------------------

		ee()->charge_log->install();
		ee()->charge_stripe->install();
		ee()->charge_coupon->install();
		ee()->charge_webhook->install();
		ee()->charge_subscription->install();
		ee()->charge_subscription_member->install();
		ee()->charge_action->install();


		// --------------------------------------
		// Register our actions
		// --------------------------------------

		foreach ($this->actions AS $action)
		{
			$csrf_exempt = 0;
			if(isset($this->csrf_exempt[$action])) $csrf_exempt = 1;


			if(APP_VER >= '2.7')
			{
				ee()->db->insert('actions', array(
					'class'  => CHARGE_CLASS_NAME,
					'method' => $action,
					'csrf_exempt' => $csrf_exempt
				));
			}
			else
			{
				// No csrf on < 2.7
				ee()->db->insert('actions', array(
					'class'  => CHARGE_CLASS_NAME,
					'method' => $action
				));
			}

		}


		// --------------------------------------
		// Add row to modules table
		// --------------------------------------

		ee()->db->insert('modules', array(
			'module_name'    => CHARGE_CLASS_NAME,
			'module_version' => CHARGE_VERSION,
			'has_cp_backend' => 'y'
		));

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Uninstall the module
	 *
	 * @return	bool
	 */
	function uninstall()
	{
		// --------------------------------------
		// get module id
		// --------------------------------------

		$query = ee()->db->select('module_id')
		       ->from('modules')
		       ->where('module_name', CHARGE_CLASS_NAME)
		       ->get();


		// --------------------------------------
		// remove references from modules
		// --------------------------------------

		ee()->db->where('module_name', CHARGE_CLASS_NAME);
		ee()->db->delete('modules');

		// --------------------------------------
		// Uninstall tables
		// --------------------------------------

		ee()->charge_log->uninstall();
		ee()->charge_stripe->uninstall();
		ee()->charge_coupon->uninstall();
		ee()->charge_webhook->uninstall();
		ee()->charge_subscription->uninstall();
		ee()->charge_subscription_member->uninstall();
		ee()->charge_action->uninstall();

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Update the module
	 *
	 * @return	bool
	 */
	function update($current = '')
	{

		// --------------------------------------
		// Same Version - nothing to do
		// --------------------------------------

		if ($current == '' OR version_compare($current, CHARGE_VERSION) === 0)
		{
			return FALSE;
		}


		if( version_compare( $current, '0.5') < 1)
		{
			$this->_update_from_050();
		}

		if( version_compare( $current, '1.0' ) < 1 )
		{
			$this->_update_from_100();
		}

		if( version_compare( $current, '1.1.1' ) < 1 )
		{
			$this->_update_from_111();
		}

		if( version_compare( $current, '1.4.0' ) < 1 )
		{
			$this->_update_from_140();
		}


		if( version_compare( $current, '1.5.0' ) < 1 )
		{
			$this->_update_from_150();
		}

		if( version_compare( $current, '1.6.2' ) < 1 )
		{
			$this->_update_from_162();
		}


		if( version_compare( $current, '1.7.0' ) < 1 )
		{
			$this->_update_from_170();
		}


		if( version_compare( $current, '1.7.4' ) < 1 )
		{
			$this->_update_from_174();
		}



		if( version_compare( $current, '1.7.5' ) < 1 )
		{
			$this->_update_from_175();
		}


		if( version_compare( $current, '1.8.12.b3' ) < 1 )
		{
			$this->_update_from_1811();
		}

        if( version_compare( $current, '1.8.14' ) < 1 )
        {
            $this->_update_from_1814();
        }


        if( version_compare( $current, '1.9.3' ) < 1 )
        {
            $this->_update_from_193();
        }





        // Get the current actions list and compare to the actions list up top.
		$current_actions = ee()->db->where('class', CHARGE_CLASS_NAME)->get('actions')->result_array();

		$actions = $this->actions;

		foreach( $current_actions as $act )
		{
			if( in_array( $act['method'], $actions ) )
			{
				foreach( $actions as $key => $action )
				{
					if( $action == $act['method'] )
					{
						unset( $actions[ $key ] );
					}
				}
			}
		}

		// Now just add the ones that aren't there
		foreach ($actions AS $action)
		{
			$csrf_exempt = 0;
			if(isset($this->csrf_exempt[$action])) $csrf_exempt = 1;

			ee()->db->insert('actions', array(
				'class'  => CHARGE_CLASS_NAME,
				'method' => $action,
				'csrf_exempt' => $csrf_exempt
			));
		}

		// Just as a safety catch, verify all the actions are correctly csrf_exempt
		$this->_verify_csrf();

		// Returning TRUE updates db version number
		return TRUE;
	}

	private function _verify_csrf()
	{
		$sql = array();
		if(APP_VER >= '2.7') $sql[] = "UPDATE exp_actions SET csrf_exempt = 1 WHERE method = 'act_webhook'";

		foreach($sql as $s) {
			ee()->db->query($s);
		}
	}


	private function _update_from_193()
	{
		$sql = array();
        $sql[] = "ALTER TABLE  `exp_charge_stripe` ADD  `connected_entry_id` varchar(100) NOT NULL DEFAULT ''";

        foreach($sql as $s) {
            ee()->db->query($s);
        }
	}
    private function _update_from_1814()
    {
        $sql = array();
        $sql[] = "ALTER TABLE  `exp_charge_stripe` ADD  `payment_id` varchar(100) NOT NULL DEFAULT ''";

        foreach($sql as $s) {
            ee()->db->query($s);
        }
    }


	private function _update_from_1811()
	{
		$sql = array();
		$sql[] = "ALTER TABLE  `exp_charge_stripe` ADD  `plan_trial_end` int(10) unsigned NOT NULL default 0";

		foreach($sql as $s) {
			ee()->db->query($s);
		}
	}

	private function _update_from_175()
	{
		$sql = array();
		if(APP_VER >= '2.7') $sql[] = "UPDATE exp_actions SET csrf_exempt = 1 WHERE method = 'act_webhook'";

		foreach($sql as $s) {
			ee()->db->query($s);
		}
	}

	private function _update_from_174()
	{
		$sql[] = "ALTER TABLE  `exp_charge_stripe` ADD  `plan_coupon` varchar(100) NOT NULL";
		$sql[] = "ALTER TABLE  `exp_charge_stripe` ADD  `plan_coupon_stripe_id` varchar(100) NOT NULL";
		$sql[] = "ALTER TABLE  `exp_charge_stripe` ADD  `plan_discount` int(10) unsigned NOT NULL";
		$sql[] = "ALTER TABLE  `exp_charge_stripe` ADD  `plan_full_amount` int(10) unsigned NOT NULL";

		ee()->charge_coupon->install();

		foreach($sql as $s) {
			ee()->db->query($s);
		}
	}

	private function _update_from_170()
	{
		$sql[] = "ALTER TABLE  `exp_charge_stripe` ADD  `messages` text NOT NULL";
		$sql[] = "ALTER TABLE  `exp_charge_stripe` ADD  `card_fingerprint` varchar(255) NOT NULL";

		foreach($sql as $s) {
			ee()->db->query($s);
		}
	}

	// --------------------------------------------------------------------

	private function _update_from_162()
	{
		$sql[] = "ALTER TABLE  `exp_charge_stripe` ADD  `plan_length` int(10) NOT NULL";
		$sql[] = "ALTER TABLE  `exp_charge_stripe` ADD  `plan_length_interval` varchar(255) NOT NULL";
		$sql[] = "ALTER TABLE  `exp_charge_stripe` ADD  `plan_length_expiry` int(10) NOT NULL";

		foreach($sql as $s) {
			ee()->db->query($s);
		}

	}

	// --------------------------------------------------------------------


	private function _update_from_150()
	{
		$sql[] = "ALTER TABLE  `exp_charge_stripe` ADD  `customer_id` varchar(255) NOT NULL";

		foreach($sql as $s) {
			ee()->db->query($s);
		}

		// We also need to try to extract the customer_id from the stripe blob for all the users
        $charges = ee()->charge_stripe->get_all();
        $customers = array();

        foreach($charges as $charge)
        {
        	if(isset($charge['customer_id']) AND $charge['customer_id'] != '') continue;

            $charge_id = $charge['id'];
            $customer_id = '';

            if($charge['stripe_object'] == 'customer'){
                $customer_id = $charge['stripe_id'];
            } elseif(isset($charge['stripe_card_customer'])) {
                $customer_id = $charge['stripe_card_customer'];
            }

            if($customer_id != '') $customers[$charge['id']] = $customer_id;
        }

        foreach($customers as $cus => $cus_id)
        {
        	ee()->charge_stripe->update($cus, array('customer_id' => $cus_id));
        }
	}





	// --------------------------------------------------------------------

	private function _update_from_140()
	{
		$sql[] = "ALTER TABLE  `exp_charge_subscription_member` ADD  `charge_id` int(10) unsigned NOT NULL";
		$sql[] = "ALTER TABLE  `exp_charge_stripe` ADD  `plan_trial_days` int(10) unsigned NOT NULL";
		$sql[] = "ALTER TABLE  `exp_charge_stripe` ADD  `state` varchar(255) NOT NULL";
		$sql[] = "ALTER TABLE  `exp_charge_stripe` ADD  `ended_on` int(10) unsigned NOT NULL";

		// Set all the states to be active too
		$sql[] = "UPDATE `exp_charge_stripe` SET `state` = \"active\"";

		foreach($sql as $s) {
			ee()->db->query($s);
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	private function _update_from_111()
	{
		$sql[] = "ALTER TABLE  `exp_charge_stripe` ADD  `card_exp_month` varchar(255) NOT NULL";
		$sql[] = "ALTER TABLE  `exp_charge_stripe` ADD  `card_exp_year` varchar(255) NOT NULL";

		foreach($sql as $s) {
			ee()->db->query($s);
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	private function _update_from_100()
	{
		ee()->charge_webhook->install();
		ee()->charge_subscription->install();
		ee()->charge_subscription_member->install();
		ee()->charge_action->install();

		return TRUE;
	}

	// --------------------------------------------------------------------

	private function _update_from_050()
	{
		$sql = "ALTER TABLE  `exp_charge_stripe` ADD  `mode` varchar(255) NOT NULL";

		ee()->db->query($sql);
	}



} // End class

/* End of file upd.Charge.php */