<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Charge Subscription Model class
 *
 * @package         charge_ee_addon
 * @version         1.9.2
 * @author          Joel Bradbury ~ <joel@squarebit.co.uk>
 * @link            http://squarebit.co.uk/software/expressionengine/charge
 * @copyright       Copyright (c) 2015, Joel Bradbury/Square Bit
 */
class Charge_subscription_model extends Charge_model {

	public $settings = array('email_member_welcome' =>
								array('enabled' 	=> 'bool',
										'template' 	=> 'required',
										'subject'	=> 'required',
										'bcc'		=> 'optional'),
							'email_member_recurring' =>
								array('enabled'		=> 'bool',
										'template'	=> 'required',
										'subject'	=> 'required',
										'bcc'		=> 'optional'),
							'email_member_failure'	=>
								array('enabled'		=> 'bool',
										'template'	=> 'required',
										'subject'	=> 'required',
										'bcc'		=> 'optional'));
	public $this_settings = array();

	public $errors = array();
	private $groups = array();


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
			'charge_subscription',
			'subscription_id',
			array(
				'site_id'       		=> 'int(4) unsigned NOT NULL default 0',
				'timestamp'				=> 'int(10) unsigned NOT NULL default 0',
				'member_group_valid'	=> 'int(10) unsigned NOT NULL default 0',
				'member_group_invalid'	=> 'int(10) unsigned NOT NULL default 0',
				'settings'				=> 'text',
				'name'					=> 'varchar(255) NOT NULL default ""')
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
	}


	// --------------------------------------------------------------------

	public function add_member($subscription_id, $customer_id, $charge_id)
	{
		$sub = self::get_one($subscription_id);
		if(empty($sub)) return FALSE;

		$member_id = ee()->session->userdata('member_id');

		$sub_data = array(	'member_id' => $member_id,
							'subscription_id' => $subscription_id,
							'timestamp' => ee()->localize->now,
							'site_id' => ee()->config->item('site_id'),
							'status' => 'active',
							'customer_id' => $customer_id,
							'charge_id'	=> $charge_id);

		$group_id = $this->_get_member_group($sub['member_group_valid']);
		if($group_id == FALSE) return FALSE;

        if (ee()->extensions->active_hook('charge_subscription_start_add_member') === TRUE)
        {
            ee()->extensions->call('charge_subscription_start_add_member', $member_id, $group_id, $sub_data, $sub);
            if (ee()->extensions->end_script === TRUE) return FALSE;
        }

		$this->_move_member($member_id, $group_id);
		ee()->charge_subscription_member->insert($sub_data);

        if (ee()->extensions->active_hook('charge_subscription_end_add_member') === TRUE)
        {
            ee()->extensions->call('charge_subscription_end_add_member', $member_id, $group_id, $sub_data, $sub);
            if (ee()->extensions->end_script === TRUE) return FALSE;
        }

		return TRUE;
	}


	public function remove_member($subscription_member_id)
	{
		// Get the submember details
		$sub_member = ee()->charge_subscription_member->get_one($subscription_member_id, 'subscription_member_id');
		if(empty($sub_member)) return FALSE;

		// Get the subscription itself
		$sub = self::get_one($sub_member['subscription_id']);
		if(empty($sub)) return FALSE;


		// Move the member
		$group_id = $this->_get_member_group($sub['member_group_invalid']);
		if($group_id == FALSE) return FALSE;

		if (ee()->extensions->active_hook('charge_subscription_start_remove_member') === TRUE)
        {
            ee()->extensions->call('charge_subscription_start_remove_member', $sub_member['member_id'], $group_id, $sub_member, $sub);
            if (ee()->extensions->end_script === TRUE) return FALSE;
        }

		$this->_move_member($sub_member['member_id'], $group_id);
		$sub_data = array(	'timestamp' => ee()->localize->now,
							'status' => 'inactive');

		ee()->charge_subscription_member->update($subscription_member_id, $sub_data);

        if (ee()->extensions->active_hook('charge_subscription_end_remove_member') === TRUE)
        {
            ee()->extensions->call('charge_subscription_end_remove_member', $sub_member['member_id'], $group_id, $sub_member, $sub);
            if (ee()->extensions->end_script === TRUE) return FALSE;
        }

		return TRUE;
	}

	public function get_all()
	{
		$subs = parent::get_all();

		$this->groups = ee()->charge_model->get_member_groups();

		foreach($subs as $sub_key => $sub_val) {
			$subs[$sub_key]['settings'] = unserialize($sub_val['settings']);

			$subs[$sub_key]['auto_description'] = $this->_build_description($subs[$sub_key]);
		}

		return $subs;
	}

	public function get_one( $id )
	{
		$item = parent::get_one($id);

		// Decode the settings
		$item['settings'] = unserialize($item['settings']);

		return $item;
	}



	public function save($subscription_id = 0)
	{
		$this->this_settings = $this->_get_settings();

		$this->this_settings['name'] = ee()->input->post('name');
		$this->this_settings['member_group_valid'] = ee()->input->post('member_group_valid');
		$this->this_settings['member_group_invalid'] = ee()->input->post('member_group_invalid');


		if(!isset($this->this_settings['name']) OR $this->this_settings['name'] == '') {
			$this->errors['name'] = lang('charge_subscription_no_name');
		}
		if(!isset($this->this_settings['member_group_valid']) OR $this->this_settings['member_group_valid'] == '') {
			$this->errors['member_group_valid'] = lang('charge_subscription_no_valid_group');
		}
		if(!isset($this->this_settings['member_group_invalid']) OR $this->this_settings['member_group_invalid'] == '') {
			$this->errors['member_group_invalid'] = lang('charge_subscription_no_invalid_group');
		}

		if(!empty($this->errors)) return FALSE;


		// Ok, looks valid, set it up to dave
		$data['name'] = $this->this_settings['name'];
		$data['member_group_valid'] = $this->this_settings['member_group_valid'];
		$data['member_group_invalid'] = $this->this_settings['member_group_invalid'];

		unset($this->this_settings['name']);
		unset($this->this_settings['member_group_valid']);
		unset($this->this_settings['member_group_invalid']);

		if(!empty($this->this_settings)) $data['settings'] = serialize($this->this_settings);

		$data['timestamp'] = time();
		$data['site_id'] = ee()->config->item('site_id');

		if($subscription_id == 0 OR $subscription_id == '') {
			// New
			self::insert($data);
		} else {
			self::update($subscription_id, $data);
		}

		return TRUE;
	}


	private function _get_member_group($group_id)
	{
		$group = array();

		if(APP_VER > 2.5)
		{
			ee()->load->model('Member_group_model');

			$group = ee()->Member_group_model->get(array('group_id' => $group_id))->row_array();
		}
		else
		{
			$group = ee()->db->where('group_id', $group_id)
						->from('member_groups')
						->get()
						->row_array();

		}
		if(empty($group)) return FALSE;
		return $group['group_id'];
	}

	private function _move_member($member_id, $group_id)
	{
		ee()->db->where('member_id', $member_id)
				->update('members', array('group_id' => $group_id));
	}


	private function _get_settings()
	{
		$settings = array();


		foreach($this->settings as $setting_group_key => $setting_group) {
			$this_bool = FALSE;

			foreach($setting_group as $key => $validation) {

				if(ee()->input->post($setting_group_key.'_'.$key) != '') {
					$settings[$setting_group_key.'_'.$key] = ee()->input->post($setting_group_key.'_'.$key);
				}

				if($validation == 'bool') {
					$this_bool = (ee()->input->post($setting_group_key.'_'.$key) == 'yes' ? TRUE : FALSE );
				}

				if($this_bool AND $validation == 'required' AND ee()->input->post($setting_group_key.'_'.$key) == '') {
					$this->errors[$setting_group_key.'_'.$key] = lang('charge_required_setting');
				}
			}
		}


		return $settings;
	}

	private function _build_description($sub)
	{
		$ret = 'Moves member to <strong>'.$this->groups[$sub['member_group_valid']].'</strong>';

		$emails = array();
		foreach(array('email_member_welcome_enabled' => 'Welcome', 'email_member_recurring_enabled' => 'Recurring', 'email_member_failure_enabled' => 'Failure') as $email => $key) {
			if( isset($sub['settings'][$email]) AND $sub['settings'][$email] == 'yes') {
				$emails[] = $key;
			}
		}


		if(!empty($emails)) {

			$ret .= ' and sends <strong>';

			$ret .= implode('</strong>, <strong>', $emails);

			$ret .= '</strong> emails';

		}


		return $ret;

	}

} // End class

/* End of file Charge_webhook_model.php */