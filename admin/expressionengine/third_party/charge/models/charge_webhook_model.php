<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Charge Webhook Model class
 *
 * @package         charge_ee_addon
 * @version         1.8.12
 * @author          Joel Bradbury ~ <joel@squarebit.co.uk>
 * @link            http://squarebit.co.uk/addons/charge
 * @copyright       Copyright (c) 2014, Joel Bradbury/Square Bit
 */
class Charge_webhook_model extends Charge_model {

	private $events_map = array(
						'customer.subscription.created' => TRUE,
						'invoice.payment_succeeded' => TRUE,
						'customer.subscription.deleted' => FALSE);

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
			'charge_webhook',
			'call_id',
			array(
				'site_id'       		=> 'int(4) unsigned NOT NULL default 0',
				'timestamp'				=> 'int(10) unsigned NOT NULL default 0',
				'mode'					=> 'varchar(100) NOT NULL default ""',
				'id'					=> 'varchar(100) NOT NULL default ""',
				'type'					=> 'varchar(100) NOT NULL default ""',
				'body'					=> 'text',
				'status'				=> 'varchar(100) NOT NULL default ""')
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
		ee()->db->query("ALTER TABLE {$this->table()} ADD INDEX (`type`)");


		$this->_set_key();
	}


	public function act($body)
	{
		// Pull out the type to start
		if(!isset($body['type']) ) return FALSE;

		$type = $body['type'];
		$mode = 'test';
		if($body['livemode'] === TRUE) $mode = 'live';

		// Extract the customer_id
		$customer_id = $this->extract_customer_id($body);
		$invoice_value = $this->extract_invoice_value($body);

        if (ee()->extensions->active_hook('charge_webhook_received_start') === TRUE)
        {
            ee()->extensions->call('charge_webhook_received_start', $type, $mode, $body);
            if (ee()->extensions->end_script === TRUE) return;
        }


		if(!isset($this->events_map[$type])) {
			// Nothing to do really
			return TRUE;
		}


		// We seem to have something to do
		// We must find the relevant member for this event
		$action_state = $this->events_map[$type];

		if($customer_id === FALSE) return FALSE;


		// Get this customer's subscription
		if($customer_id == 'cus_00000000000000') {
			// Dummy member, coming from test webhook
			return FALSE;

			// Fire anyway?
		//	$customer_id = 'cus_3h5hZZyFqKye44';
		}


		// Get the original charge that started this subscription
		$charge = ee()->charge_stripe->get_one($customer_id, 'customer_id');
		if(empty($charge)) return FALSE;

		// Handle set length plans
		if($type == 'invoice.payment_succeeded' AND $charge['plan_length_expiry'] != '') {

			// This will kill the plan if needed.
			// If it's killed, this will trigger a fresh set of hook events
			// but that's invisible to this action, so we handle it all atomically
			ee()->charge_stripe->handle_plan_expiry($charge);
		}


		// Handle the empty invoice success we recieve when a trial plan is created
		// with a zero balance paid
		if($type == 'invoice.payment_succeeded') {
			if($invoice_value !== FALSE AND $invoice_value <= 0) {
				// We have a zero balance invoice from a trial success
				return TRUE;
			}
		}

		// Now handle any subscriptions
		$sub_member = ee()->charge_subscription_member->get_one($customer_id, 'customer_id');
		if(empty($sub_member)) return FALSE;

		$subscription = ee()->charge_subscription->get_one($sub_member['subscription_id']);
		if(empty($subscription)) return FALSE;

		// Based on the action state we might be removing a member subscription
		// or just sending a thank you note
		if($action_state == TRUE)
		{
			if($type == 'invoice.payment_succeeded') $action_type = 'recurring';
			else $action_type = 'welcome';

			// Thank you note
			return $this->_handle_success($customer_id, $sub_member, $subscription, $action_type, $charge);
		}
		else
		{
			// Remove the member
			return $this->_handle_failure($customer_id, $sub_member, $subscription, $charge);
		}



		return FALSE;
	}


	public function extract_customer_id($body = array())
	{
		if(!isset($body['data']['object']['customer'])) return FALSE;

		return $body['data']['object']['customer'];
	}


	public function extract_invoice_value($body = array())
	{
		if(!isset($body['data']['object']['total'])) return FALSE;

		return $body['data']['object']['total'];
	}



	private function _handle_failure($customer_id, $sub_member, $subscription, $charge)
	{
		// Move the member to the failure group
		ee()->charge_subscription->remove_member($sub_member['subscription_member_id']);

		// Now deal with emails
		$this->_email('failure', $sub_member, $subscription, $charge['hash']);

		return TRUE;
	}

	private function _handle_success($customer_id, $sub_member, $subscription, $type = 'welcome', $charge)
	{
		// Good
		// Touch the record
		$data['last_contact'] = time();
		ee()->charge_subscription_member->update($sub_member['subscription_member_id'], $data);

		// Now deal with success conditions
		$this->_email($type, $sub_member, $subscription, $charge['hash']);

		return TRUE;
	}


	private function _email($type, $sub_member, $subscription, $hash = '')
	{
		if(isset($subscription['settings']['email_member_'.$type.'_enabled']) AND $subscription['settings']['email_member_'.$type.'_enabled'] == 'yes') {
			// Fire email

			$subject = $subscription['settings']['email_member_'.$type.'_subject'];
			$template = $subscription['settings']['email_member_'.$type.'_template'];
			$bcc = array();
			foreach(explode(',', $subscription['settings']['email_member_'.$type.'_bcc']) as $address) {
                $bcc[] = trim($address);
            }

           	$member = ee()->db->select('email')->where('member_id', $sub_member['member_id'])->from('members')->get()->row_array();
           	if(empty($member)) return;

           	$to = array($member['email']);

            $email = ee()->charge_email->send($hash, array($to), $subject, $subscription['settings']['email_member_'.$type.'_template']);
		}
	}

	private function _set_key()
	{
		// Also generate a unique 'charge_webhook_key' on install
		$rand_key = ee()->functions->random('alnum', 32);

		$preferences['charge_webhook_key'] = $rand_key;

		/** --------------------------------------------
        /**	Grab prefs from DB
        /** --------------------------------------------*/
		$site_id = ee()->config->item('site_id');

        $sql	= "SELECT site_system_preferences
					FROM exp_sites
					WHERE site_id = " . ee()->db->escape_str( $site_id );

        $query	= ee()->db->query( $sql );

        if ( $query->num_rows() == 0 ) return FALSE;

        ee()->load->helper('string');

 		$prefs = unserialize( base64_decode( $query->row('site_system_preferences') ) );


 		/** --------------------------------------------
        /**	Add our prefs
        /** --------------------------------------------*/

        foreach ( explode( "|", CHARGE_PREFERENCES ) as $val )
        {
        	if ( isset( $preferences[$val] ) === TRUE )
        	{
        		$prefs[$val]	= $preferences[$val];
        	}
        }


		$prefs = base64_encode( serialize( $prefs ) );


		ee()->db->query( ee()->db->update_string(
					'exp_sites',
					array(
						'site_system_preferences' => $prefs
					),
					array(
						'site_id'	=> ee()->db->escape_str( $site_id )
					)
				)
			);

		return TRUE;

	}




} // End class

/* End of file Charge_webhook_model.php */