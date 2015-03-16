<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Charge Member Model class
 *
 * @package         charge_ee_addon
 * @version         1.9.0
 * @author          Joel Bradbury ~ <joel@squarebit.co.uk>
 * @link            http://squarebit.co.uk/software/expressionengine/charge
 * @copyright       Copyright (c) 2015, Joel Bradbury/Square Bit
 */
class Charge_member_model extends Charge_model {

	public $errors = array();
	private $default_group_id = '4';

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
		/*parent::install();*/
	}


	// --------------------------------------------------------------------

	public function create($data, $login = TRUE)
	{
		$member = array();
		$member['email'] = '';
		$member['screen_name'] = '';
		$member['username'] = '';
		$member['group_id'] = (ee()->config->item('default_member_group')) ? ee()->config->item('default_member_group') : $this->default_group_id;

		if(isset($data['member']['password']) AND $data['member']['password'] != '') $member['password'] = $data['member']['password'];
		else $member['password'] = ee()->functions->random('alpha', 20); // @todo we need to give a way to activate this

		if(isset($data['customer']['email'])) $member['email'] = $data['customer']['email'];
		$member['screen_name'] = $data['customer']['name'] . ', '. $member['email'];

		if(isset($data['member']['username']) AND $data['member']['username'] != '') $member['username'] = $data['member']['username'];
		if(isset($data['member']['screen_name']) AND $data['member']['screen_name'] != '') $member['screen_name'] = $data['member']['screen_name'];


		// Ok, register the member
		$member = $this->_validate($member);
		if($member === FALSE) {

			foreach($this->errors as $key => $val)
			{
				if($val == lang('email_taken')) {
					$this->errors[$key] = 'charge_member_account_exists';

					unset($this->errors[$key]);
					$this->errors['customer_email'] = 'charge_member_account_exists';
				}

				if($val == lang('screen_name_taken')) {}
			}

			return FALSE;
		}

		$member_id = $this->_create($member);


		if($login) $this->_login($member_id);

		return $member_id;
	}


	private function _validate($member = array())
	{
 		if ($member['group_id'] < 4)
		{
			$this->errors[] = 'charge_member_group_low';
			return FALSE;
		}

		ee()->load->helper('security');

		$member['username'] = ($member['username'] != '') ? $member['username'] : $member['email'];
		$member['language'] = ee()->config->item('deft_lang');


		/** -------------------------------------
		/**  Instantiate validation class
		/** -------------------------------------*/
		if ( ! class_exists('EE_Validate'))
		{
			require APPPATH.'libraries/Validate'.EXT;
		}

		$VAL = new EE_Validate(array(
			'member_id' => '',
			'val_type' => 'new', // new or update
			'fetch_lang' => TRUE,
			'require_cpw' => FALSE,
			'enable_log' => FALSE,
			'username' => $member['username'],
			'cur_username' => '',
			'screen_name' => $member['screen_name'],
			'cur_screen_name' => '',
			'password' => $member['password'],
			'password_confirm' => $member['password'],
			'cur_password' => '',
			'email' => $member['email'],
			'cur_email' => ''
		));

		$has_error = FALSE;

		// Email should already be valid, but check again to be sure
		$VAL->validate_email();
		if (count($VAL->errors) > 0)
		{
			$this->errors['customer_email'] = current($VAL->errors);
			$VAL->errors = array();
			$has_error = TRUE;
		}

		$VAL->validate_username();

		if (count($VAL->errors) > 0){
			$this->errors['member_username'] = current($VAL->errors);
			$VAL->errors = array();
			$has_error = TRUE;
		}
		$VAL->validate_screen_name();

		if (count($VAL->errors) > 0){
			$this->errors['member_screen_name'] = current($VAL->errors);
			$VAL->errors = array();
			$has_error = TRUE;
		}
		$VAL->validate_password();
		if (count($VAL->errors) > 0){
			$this->errors['member_password'] = current($VAL->errors);
			$VAL->errors = array();
			$has_error = TRUE;
		}

		if($has_error === TRUE) return FALSE;

		return $member;
	}

	/**
	 * create_member
	 *
	 * @param array $data must contain: username, email, screen_name, password (hashed!), group_id, language
	 * @return int|false If successful will return member_id, if unsuccessful, will return FALSE
	 */
	private function _create($data)
	{
		ee()->load->helper(array('security', 'string', 'text'));


		// just to be safe
		if ( !empty($data['group_id']) && $data['group_id'] < 4)
		{
			$data['group_id'] = $this->default_group_id;
		}
		else
		{
			ee()->db->select('group_id')
					->from('member_groups')
					->where('site_id', ee()->config->item('site_id'))
					->where('group_id', $data['group_id']);

			$data['group_id'] = ( ! ee()->db->count_all_results()) ? $this->default_group_id : $data['group_id'];
		}

/*
		if (ee()->config->item('req_mbr_activation') === 'manual' || ee()->config->item('req_mbr_activation') === 'email')
		{
			$data['group_id'] = $this->default_group_id;
		}
*/
		if (ee()->config->item('req_mbr_activation') === 'email')
		{
			$data['authcode'] = ee()->functions->random('alnum', 10);
		}

		ee()->load->library('auth');
		$hashed_password = ee()->auth->hash_password($data['password']);


 		$data['password']	= $hashed_password['password'];
		$data['salt']		= $hashed_password['salt'];
		$data['unique_id']	= random_string('encrypt');
		$data['crypt_key']	= ee()->functions->random('encrypt', 16);

		$data['ip_address'] = ee()->input->ip_address();
		$data['join_date'] = ee()->localize->now;
		if (!isset($data['language']))
		{
			$data['language'] 	= ee()->config->item('deft_lang');
		}
		$data['timezone'] 	= (ee()->config->item('default_site_timezone') && ee()->config->item('default_site_timezone') != '') ? ee()->config->item('default_site_timezone') : ee()->config->item('server_timezone');
		$data['time_format'] = (ee()->config->item('time_format') && ee()->config->item('time_format') != '') ? ee()->config->item('time_format') : 'us';

		ee()->load->model('member_model');

		if (ee()->config->item('req_mbr_activation') == 'email')
		{
			$data['authcode'] = ee()->functions->random('alnum', 10);
		}

		// Actually do it
	 	$member_id = ee()->member_model->create_member($data, array());

		// Admin email
		if (ee()->config->item('new_member_notification') == 'y' && ee()->config->item('mbr_notification_emails') != '')
		{
			$vars = array(
							'name'					=> $data['screen_name'],
							'site_name'				=> stripslashes(ee()->config->item('site_name')),
							'control_panel_url'		=> ee()->config->item('cp_url'),
							'username'				=> $data['username'],
							'email'					=> $data['email']
						 );

			$template = ee()->functions->fetch_email_template('admin_notify_reg');

			foreach ($vars as $key => $val)
			{
				$template['title'] = str_replace('{'.$key.'}', $val, $template['title']);
				$template['data'] = str_replace('{'.$key.'}', $val, $template['data']);
			}
			$email_to = reduce_multiples(ee()->config->item('mbr_notification_emails'), ',', TRUE);


			ee()->charge_email->send_email(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'), $email_to, $template['title'], $template['data'], $plaintext = FALSE);
		}


		// Welcome/activiation emails
		if (ee()->config->item('req_mbr_activation') == 'none')
		{
			ee()->stats->update_member_stats();
		}
		elseif (ee()->config->item('req_mbr_activation') == 'email')
		{
			$action_id  = ee()->functions->fetch_action_id('Member', 'activate_member');

			$vars = array(
				'activation_url'	=> ee()->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$action_id.'&id='.$data['authcode'],
				'site_name'			=> stripslashes(ee()->config->item('site_name')),
				'site_url'			=> ee()->config->item('site_url'),
				'username'			=> $data['username'],
				'email'				=> $data['email']
 			 );

			$template = ee()->functions->fetch_email_template('mbr_activation_instructions');

			foreach ($vars as $key => $val)
			{
				$template['title'] = str_replace('{'.$key.'}', $val, $template['title']);
				$template['data'] = str_replace('{'.$key.'}', $val, $template['data']);
			}

			// plaintext was changed from False to TRUE because as far as I can tell, activation instructions are always sent plain text by the system.
			ee()->charge_email->send_email(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'), $data['email'], $template['title'], $template['data'], $plaintext = TRUE);

 		}

		// -------------------------------------------
		// 'charge_create_member' hook.
		// -------------------------------------------
		if (ee()->extensions->active_hook('charge_create_member') === TRUE)
		{
			$edata = ee()->extensions->call('charge_create_member', array_merge($data, array('member_id' => $member_id)), $this);
			if ($this->extensions->end_script === TRUE) return;
		}

		return $member_id;
	}


	/**
	 * Login Member
	 *
	 */
	private function _login($member_id)
	{
		$query = ee()->db->from('members')
				  ->select('password, unique_id')
				  ->where('member_id', $member_id)
				  ->get();

		if ($query->num_rows() === 0)
		{
			$this->errors['general'] = lang('charge_member_error');

			return FALSE;
		}

		ee()->lang->loadfile('login');

		if (ee()->config->item('user_session_type') != 's')
		{
			ee()->functions->set_cookie(ee()->session->c_expire, time(), 0);
			ee()->functions->set_cookie(ee()->session->c_anon, 1, 0);

			if (version_compare(APP_VER, '2.1.5', '<'))
			{
				ee()->functions->set_cookie(ee()->session->c_uniqueid, $query->row('unique_id'), 0);
				ee()->functions->set_cookie(ee()->session->c_password, $query->row('password') , 0);
			}
		}

		ee()->session->create_new_session($member_id, TRUE);
		ee()->session->delete_password_lockout();

		//we have to do this because the XID hash was already cleared by generating a new session with the new member id and needs to be restored,
		//XID should have already kicked in, in the case of a new member registration, so we should be good arbitrarily setting it here to get around secure forms.
		if (ee()->config->item('secure_forms') === 'y' && ee()->input->post('XID'))
		{
			if (version_compare(APP_VER, '2.5.5', '<'))
			{
				ee()->db->insert('security_hashes', array('date' => time() - 60, 'ip_address' => ee()->input->ip_address(), 'hash' => ee()->input->post('XID')));
			}
			else
			{
				ee()->db->insert('security_hashes', array('date' => time() - 60, 'session_id' => ee()->session->userdata('session_id'), 'hash' => ee()->input->post('XID')));
			}
		}


	}
	// END
} // End class

/* End of file Charge_member_model.php */