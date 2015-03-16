<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Charge Log Model class
 *
 * @package         charge_ee_addon
 * @version         1.9.0
 * @author          Joel Bradbury ~ <joel@squarebit.co.uk>
 * @link            http://squarebit.co.uk/software/expressionengine/charge
 * @copyright       Copyright (c) 2015, Joel Bradbury/Square Bit
 */
class Charge_log_model extends Charge_model {

	// --------------------------------------------------------------------
	// METHODS
	// --------------------------------------------------------------------

	// create new : plus info
	// customer created : user info
	// error : remove error
	// exception : asterisk error /or/ warning-sign
	// info : info-sign info
	// action : transfer info


	private $icon_map = array(	'exception' => array('icon' => 'warning-sign', 'type' => 'error'),
								'error'	 	=> array('icon' => 'remove', 'type' => 'error'),
								'success'	=> array('icon' => 'ok', 'type' => 'success'),
								'created'	=> array('icon' => 'ok', 'type' => 'success'),
								'attempt'	=> array('icon' => 'share-alt', 'type' => 'info'),
								'customer' 	=> array('icon' => 'user', 'type' => 'info'),
								'action'	=> array('icon' => 'transfer', 'type' => 'info'),
								'info' 		=> array('icon' => 'info-sign', 'type' => 'info'));
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
			'charge_log',
			'log_id',
			array(
				'site_id'       		=> 'int(4) unsigned NOT NULL default 1',
				'timestamp'				=> 'int(10) unsigned NOT NULL default 0',
				'type'					=> 'varchar(100) NOT NULL default ""',
				'message'				=> 'varchar(255) NOT NULL default ""',
				'member_id'				=> 'int(10) unsigned NOT NULL default 0',
				'extended'				=> 'text')
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
		ee()->db->query("ALTER TABLE {$this->table()} ADD INDEX (`member_id`)");
	}


	// --------------------------------------------------------------

	public function get_all($limit = 50, $offset = 0)
	{
		ee()->db->order_by('log_id', 'desc');
		ee()->db->limit($limit, $offset);
		$data = parent::get_all();

		$ret = array();

		foreach($data as $row) {
			$row['extended'] = unserialize(base64_decode($row['extended']));
			$row['time_wordy'] = date('H:i', $row['timestamp']). ' on '.date('l jS F', $row['timestamp']);

			$row['log_icon'] = $this->_make_icon($row['type']);

			$ret[] = $row;
		}


		return $ret;
	}

	private function _make_icon($row_type = '')
	{
		// Make the icons
		$icon_base = '<span class="gicon gicon-{marker} {type}"></span>';

		$marker = '';
		$type = '';

		if($row_type != '') {
			$parts = explode('_', $row_type);

			foreach($parts as $part) {
				if($part == 'charge') continue;

				if(isset($this->icon_map[$part])) {
					$marker = $this->icon_map[$part]['icon'];
					$type = $this->icon_map[$part]['type'];
					return str_replace(array('{marker}', '{type}'), array($marker, $type), $icon_base);
				}
			}
		}

		return str_replace(array('{marker}', '{type}'), array($marker, $type), $icon_base);
	}
	// --------------------------------------------------------------

	public function log( $data = array() )
	{
		if( empty( $data )) return FALSE;

		if( !isset( $data['member_id'] ) OR $data['member_id'] == 0 ) $data['member_id'] = ee()->session->userdata('member_id');
		if( !isset( $data['site_id'] ) ) $data['site_id'] = ee()->config->item('site_id');
		if( !isset( $data['timestamp'] ) ) $data['timestamp'] = ee()->localize->now;
		if( !isset( $data['type'] ) OR $data['type'] == '' ) $data['type'] = '';

		if( isset( $data['extended'] ) )
		{
			if( !is_array( $data['extended'] ) ) $data['extended'] = array( $data['extended'] );
			$data['extended'] = base64_encode( serialize( $data['extended'] ) );
		}

		self::insert( $data );

		return TRUE;
	}


	// --------------------------------------------------------------

	public function log_action_response($name, $data = array() )
	{
		//if( empty( $data ) ) return FALSE;

		$log_data					= $data;
		$log_data['type'] 			= 'charge_action_'.$name;
		$log_data['extended']		= $data;

		$this->log( $log_data );
		return TRUE;

	}


	// --------------------------------------------------------------

	public function log_action_start( $data = array() )
	{
		//if( empty( $data ) ) return FALSE;

		$log_data					= $data;
		$log_data['type'] 			= 'charge_action_start';
		$log_data['extended']		= $data;

		$this->log( $log_data );
		return TRUE;

	}

	// --------------------------------------------------------------

	public function log_donate_attempt_start( $data = array() )
	{
		//if( empty( $data ) ) return FALSE;

		$log_data					= $data;
		$log_data['type'] 			= 'charge_attempt_start';
		$log_data['extended']		= $data;

		$this->log( $log_data );
		return TRUE;
	}

	// --------------------------------------------------------------

	public function log_donate_attempt_start_one_off( $data = array() )
	{
		//if( empty( $data ) ) return FALSE;

		$log_data					= $data;
		$log_data['type'] 			= 'charge_attempt_start_one_off';
		$log_data['extended']		= $data;

		$this->log( $log_data );
		return TRUE;

	}

	// --------------------------------------------------------------

	public function log_donate_attempt_start_recurring( $data = array() )
	{
		//if( empty( $data ) ) return FALSE;

		$log_data					= $data;
		$log_data['type'] 			= 'charge_attempt_start_recurring';
		$log_data['extended']		= $data;

		$this->log( $log_data );
		return TRUE;

	}

	// --------------------------------------------------------------

	public function log_plan_created( $data = array() )
	{
		//if( empty( $data ) ) return FALSE;

		$log_data					= $data;
		$log_data['type'] 			= 'charge_plan_created';
		$log_data['extended']		= $data;

		$this->log( $log_data );
		return TRUE;

	}

	// --------------------------------------------------------------

	public function log_customer_created( $data = array() )
	{
		//if( empty( $data ) ) return FALSE;

		$log_data					= $data;
		$log_data['type'] 			= 'charge_customer_created';
		$log_data['extended']		= $data;

		$this->log( $log_data );
		return TRUE;

	}


    // --------------------------------------------------------------

    public function log_customer_updated( $data = array() )
    {
        //if( empty( $data ) ) return FALSE;

        $log_data					= $data;
        $log_data['type'] 			= 'charge_customer_updated';
        $log_data['extended']		= $data;

        $this->log( $log_data );
        return TRUE;

    }

	// --------------------------------------------------------------

	public function log_charge_created( $data = array() )
	{
		//if( empty( $data ) ) return FALSE;

		$log_data					= $data;
		$log_data['type'] 			= 'charge_charge_created';
		$log_data['extended']		= $data;

		$this->log( $log_data );
		return TRUE;

	}

	// --------------------------------------------------------------

	public function log_error_creating_customer( $data = array() )
	{
		//if( empty( $data ) ) return FALSE;

		$log_data					= $data;
		$log_data['type'] 			= 'charge_error_creating_customer';
		$log_data['extended']		= $data;

		$this->log( $log_data );
		return TRUE;

	}

	// --------------------------------------------------------------

	public function log_error_creating_charge( $data = array() )
	{
		//if( empty( $data ) ) return FALSE;

		$log_data					= $data;
		$log_data['type'] 			= 'charge_error_creating_charge';
		$log_data['extended']		= $data;

		$this->log( $log_data );
		return TRUE;

	}


	// --------------------------------------------------------------

	public function log_error_failed_general( $data = array() )
	{
		//if( empty( $data ) ) return FALSE;

		$log_data					= $data;
		$log_data['type'] 			= 'charge_error_failed_general';
		$log_data['extended']		= $data;

		$this->log( $log_data );
		return TRUE;

	}


	// --------------------------------------------------------------

	public function log_error_no_plan( $data = array() )
	{
		//if( empty( $data ) ) return FALSE;

		$log_data					= $data;
		$log_data['type'] 			= 'charge_error_no_plan';
		$log_data['extended']		= $data;

		$this->log( $log_data );
		return TRUE;

	}



	// --------------------------------------------------------------

	public function log_error_no_customer( $data = array() )
	{
		//if( empty( $data ) ) return FALSE;

		$log_data					= $data;
		$log_data['type'] 			= 'charge_error_no_customer';
		$log_data['extended']		= $data;

		$this->log( $log_data );
		return TRUE;

	}

	// --------------------------------------------------------------

	public function log_exception( $data = array() )
	{
		//if( empty( $data ) ) return FALSE;

		$log_data					= $data;
		$log_data['type'] 			= 'charge_exception';
		$log_data['extended']		= $data;

		$this->log( $log_data );
		return TRUE;
	}

// --------------------------------------------------------------

	public function log_stripe_exception( $data = array() )
	{
		//if( empty( $data ) ) return FALSE;

		$log_data					= $data;
		$log_data['type'] 			= 'charge_stripe_exception';
		$log_data['extended']		= $data;

		$this->log( $log_data );
		return TRUE;
	}


	// --------------------------------------------------------------

	public function log_action_trigger_start( $data = array() )
	{
		//if( empty( $data ) ) return FALSE;

		$log_data					= $data;
		$log_data['type'] 			= 'charge_trigger_start';
		$log_data['extended']		= $data;

		$this->log( $log_data );
		return TRUE;
	}

	// --------------------------------------------------------------

	public function log_action_failed_to_decode( $data = array() )
	{
		//if( empty( $data ) ) return FALSE;

		$log_data					= $data;
		$log_data['type'] 			= 'charge_trigger_failed_to_decode_action';
		$log_data['extended']		= $data;

		$this->log( $log_data );
		return TRUE;
	}

	// --------------------------------------------------------------

	public function log_action_failed_to_run( $data = array() )
	{
		//if( empty( $data ) ) return FALSE;

		$log_data					= $data;
		$log_data['type'] 			= 'charge_trigger_failed_to_run';
		$log_data['extended']		= $data;

		$this->log( $log_data );
		return TRUE;
	}

	// --------------------------------------------------------------

	public function log_error_plan_creation( $data = array() )
	{
		//if( empty( $data ) ) return FALSE;

		$log_data					= $data;
		$log_data['type'] 			= 'charge_error_on_plan_creation';
		$log_data['extended']		= $data;

		$this->log( $log_data );
		return TRUE;
	}

	// --------------------------------------------------------------

	public function log_action_ran( $data = array() )
	{
		//if( empty( $data ) ) return FALSE;

		$log_data					= $data;
		$log_data['type'] 			= 'charge_trigger_ran_successfully';
		$log_data['extended']		= $data;

		$this->log( $log_data );
		return TRUE;
	}


	public function log_plan_create_start($data = array())
	{
		$log_data					= $data;
		$log_data['type'] 			= 'charge_plan_create_start';
		$log_data['extended']		= $data;

		$this->log( $log_data );
		return TRUE;
	}


	public function log_webhook_bad_trigger($data = array())
	{
		$log_data					= $data;
		$log_data['type'] 			= 'charge_webhook_bad_trigger';
		$log_data['extended']		= $data;

		$this->log( $log_data );
		return TRUE;
	}


	public function log_webhook_empty($data = array())
	{
		$log_data					= $data;
		$log_data['type'] 			= 'charge_webhook_empty';
		$log_data['extended']		= $data;

		$this->log( $log_data );
		return TRUE;
	}


	public function log_webhook_good($data = array())
	{
		$log_data					= $data;
		$log_data['type'] 			= 'charge_webhook_good';
		$log_data['extended']		= $data;

		$this->log( $log_data );
		return TRUE;
	}






} // End class

/* End of file Charge_log_model.php */