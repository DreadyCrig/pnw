<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// Load CI model if it doesn't exist
if ( ! class_exists('CI_model'))
{
	load_class('Model', 'core');
}

class Charge_model extends CI_Model {

	// --------------------------------------------------------------------
	// PROPERTIES
	// --------------------------------------------------------------------

	private $_table;

	private $_pk;

	private $_attributes = array();

	protected $EE;

	private $_params = array();


	// --------------------------------------------------------------------
	// METHODS
	// --------------------------------------------------------------------

	/**
	 * PHP5 Constructor
	 *
	 * @return     void
	 */
	function __construct( )
	{
		// Call parent constructor
		parent::__construct();

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
	}


	// --------------------------------------------------------------------

	/**
	 * Sets table, PK and attributes
	 *
	 * @access      protected
	 * @param       string    Table name
	 * @param       string    Primary Key name
	 * @param       array     Attributes
	 * @return      void
	 */
	protected function initialize($table, $pk, $attributes)
	{
		// Check table prefix
		$prefix = ee()->db->dbprefix;

		// Add prefix to table name if not there
		if (substr($table, 0, strlen($prefix)) != $prefix)
		{
			$table = $prefix.$table;
		}

		// Set the values
		$this->_table       = $table;
		$this->_pk          = $pk;
		$this->_attributes  = $attributes;
	}

	// --------------------------------------------------------------------

	/**
	 * Load models based on this main model
	 *
	 * @access      public
	 * @return      void
	 */
    public function load_models()
	{
		$EE =& get_instance();

		$EE->load->model('charge_log_model', 'charge_log');
		$EE->load->model('charge_stripe_model', 'charge_stripe');
		$EE->load->model('charge_action_model', 'charge_action');
		$EE->load->model('charge_coupon_model', 'charge_coupon');
		$EE->load->model('charge_webhook_model', 'charge_webhook');
		$EE->load->model('charge_subscription_model', 'charge_subscription');
		$EE->load->model('charge_subscription_member_model', 'charge_subscription_member');
		$EE->load->model('charge_email_model', 'charge_email');
		$EE->load->model('charge_member_model', 'charge_member');
	}

	// --------------------------------------------------------------------

	/**
	 * Return table name
	 *
	 * @access      public
	 * @return      string
	 */
	public function table()
	{
		return $this->_table;
	}

	// --------------------------------------------------------------------

	/**
	 * Return primary key
	 *
	 * @access      public
	 * @return      string
	 */
	public function pk()
	{
		return $this->_pk;
	}

	// --------------------------------------------------------------------

	/**
	 * Return array of attributes, sans PK
	 *
	 * @access      public
	 * @return      array
	 */
	public function attributes()
	{
		return array_keys($this->_attributes);
	}

	// --------------------------------------------------------------------

	/**
	 * Check key is a valid attribute
	 *
	 * @access      public
	 * @return      array
	 */
	public function is_attribute( $key )
	{
		if( array_key_exists( $key, $this->_attributes ) ) return TRUE;

		elseif( $key == $this->_pk ) return TRUE;

		return FALSE;
	}



	// --------------------------------------------------------------------

	/**
	 * Return one record by primary key or attribute
	 *
	 * @access      public
	 * @param       int       id of the record to fetch
	 * @param       string    attribute to check
	 * @return      array
	 */
	public function get_one($id, $attr = FALSE)
	{
		if ($attr === FALSE) $attr = $this->_pk;

		return ee()->db->where($attr, $id)->get($this->_table)->row_array();
	}

	// --------------------------------------------------------------------

	/**
	 * Return multiple records
	 *
	 * @access      public
	 * @return      array
	 */
	public function get_all()
	{
		return ee()->db->get($this->_table)->result_array();
	}

	// --------------------------------------------------------------------

	/**
	 * Return multiple records' primary keys
	 *
	 * @access      public
	 * @return      array
	 */
	public function get_all_pk()
	{
		return ee()->db->select( $this->_pk )->get($this->_table)->result_array();
	}
	// --------------------------------------------------------------------

	/**
	 * Return an empty row for data initialisation
	 *
	 * @access      public
	 * @return      array
	 */
	public function empty_row()
	{
		$row = array_merge(array($this->_pk), $this->attributes());
		$row = array_combine($row, array_fill(0, count($row), ''));
		return $row;
	}

	// --------------------------------------------------------------------

	/**
	 * Insert record into DB
	 *
	 * @access      public
	 * @param       array     data to insert
	 * @return      int
	 */
	public function insert($data = array())
	{
		if (empty($data))
		{
			// loop through attributes to get posted data
			foreach ($this->attributes() AS $attr)
			{
				if (($val = ee()->input->post($attr)) !== FALSE)
				{
					$data[$attr] = $val;
				}
			}
		}
		else
		{
			// Check our passed data, and drop any non attributes
			foreach( $data as $key => $val )
			{
				if( is_array( $val ) ) unset( $data[ $key ] );
				elseif( ! $this->is_attribute( $key ) ) unset( $data[ $key ] );

			}
		}

		// Insert data and return inserted id
		ee()->db->insert($this->_table, $data);
		return ee()->db->insert_id();
	}

	// --------------------------------------------------------------------


	/**
	 * Update record into DB
	 *
	 * @access      public
	 * @param       array     data to insert
	 * @return      int
	 */
	public function update($id, $data = array())
	{
		if (empty($data))
		{
			// loop through attributes to get posted data
			foreach ($this->attributes() AS $attr)
			{
				if (($val = ee()->input->post($attr)) !== FALSE)
				{
					$data[$attr] = $val;
				}
			}
		}
		else
		{
			// Check our passed data, and drop any non attributes
			foreach( $data as $key => $val )
			{
				if( is_array( $val ) ) unset( $data[ $key ] );
				elseif( ! $this->is_attribute( $key ) ) unset( $data[ $key ] );

			}
		}

		// Insert data and return inserted id
		ee()->db->update($this->_table, $data, "{$this->_pk} = '{$id}'");
	}

	// --------------------------------------------------------------------

	/**
	 * Update record into DB
	 *
	 * @access      public
	 * @param       array     data to insert
	 * @return      int
	 */
	public function insert_update($id, $data = array())
	{
		if (empty($data))
		{
			// loop through attributes to get posted data
			foreach ($this->attributes() AS $attr)
			{
				if (($val = ee()->input->post($attr)) !== FALSE)
				{
					$data[$attr] = $val;
				}
			}
		}
		else
		{
			// Check our passed data, and drop any non attributes
			foreach( $data as $key => $val )
			{
				if( is_array( $val ) ) unset( $data[ $key ] );
				elseif( ! $this->is_attribute( $key ) ) unset( $data[ $key ] );
			}
		}

		$sql = ee()->db->insert_string($this->_table, $data);


		$sql .= " ON DUPLICATE KEY UPDATE ";

		$temp = array();

		foreach( $data as $key => $row )
		{
			$temp[] = " `" . $key . "` = '" . ee()->db->escape_str( $row ) . "'";
		}

		$sql .= implode( ", ", $temp );

		// Insert data and return inserted id
		ee()->db->query( $sql );
	}

	// --------------------------------------------------------------------

	/**
	 * Delete record
	 *
	 * @access      public
	 * @param       array     data to insert
	 * @param       string    optional attribute to delete records by
	 * @return      void
	 */
	public function delete($id, $attr = FALSE)
	{
		if ( ! is_array($id))
		{
			$id = array($id);
		}

		if ($attr === FALSE) $attr = $this->_pk;

		ee()->db->where_in($attr, $id)->delete($this->_table);
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
		// Begin composing SQL query
		$sql = "CREATE TABLE IF NOT EXISTS {$this->_table} ( ";

		// Add primary key -- is it an array?
		if (is_array($this->_pk))
		{
			foreach ($this->_pk AS $key)
			{
				$sql .= "{$key} int(10) unsigned NOT NULL, ";
			}
		}
		else
		{
			$sql .= "{$this->_pk} int(10) unsigned NOT NULL AUTO_INCREMENT, ";
		}

		// add other attributes
		foreach ($this->_attributes AS $attr => $props)
		{
			$sql .= "{$attr} {$props}, ";
		}

		// Set PK
		$sql .= "PRIMARY KEY (".implode(',', (array) $this->_pk)."))";

		// Execute query
		ee()->db->query($sql);
	}

	// --------------------------------------------------------------------

	/**
	 * Uninstalls given table
	 *
	 * @access      public
	 * @return      void
	 */
	public function uninstall()
	{
		ee()->db->query("DROP TABLE IF EXISTS {$this->_table}");
	}

	// --------------------------------------------------------------------

	/**
	 *  Matches a variable pair
	 *
	 */
	public function match_pair($string, $variable)
	{
		$l_delim = '{';
		$r_delim = '}';


		if ( ! preg_match("|" . preg_quote($l_delim) . $variable . preg_quote($r_delim) . "(.+?)". preg_quote($l_delim) . '/' . $variable . preg_quote($r_delim) . "|s", $string, $match))
		{
			return FALSE;
		}

		return $match;
	}


	/**
	 *  Matches a variable pair
	 *
	 */
	public function match_single($string, $variable)
	{
		$l_delim = '{';
		$r_delim = '}';


		if( ! preg_match_all("|".preg_quote($l_delim).$variable.preg_quote($r_delim)."|", $string, $match) )
		{
			return FALSE;
		}

		return $match;
	}


    public function count_all()
    {
        ee()->db->select('COUNT(*) c');

        $c = current(self::get_all());

        if(empty($c)) return 0;

        return $c['c'];
    }

    public function get_templates()
	{
		static $templates;

		if (is_null($templates))
		{
			ee()->load->model('template_model');

			$templates = array();

			$query = ee()->template_model->get_templates();

			foreach ($query->result() as $row)
			{
				$templates[$row->group_name.' / '.$row->template_name] = $row->group_name.'/'.$row->template_name;
			}
		}

		return $templates;
	}

	public function get_statuses()
	{
		static $statuses;

		if (is_null($statuses))
		{
			ee()->load->model('status_model');

			$statuses = array();

			$query = ee()->status_model->get_statuses();

			foreach ($query->result() as $row)
			{
				$statuses[$row->status] = $row->status;
			}
		}

		return $statuses;

	}


	public function get_channels()
	{
		static $channels;

		if (is_null($channels))
		{
			ee()->load->library('api');
        	ee()->load->library('api/Api_channel_structure');

			$channels = ee()->api_channel_structure->get_channels();

			if($channels == false) return array();

			$channels = $channels->result_array();

			$return = array();
        	foreach($channels as $channel)
        	{
        		$return[$channel['channel_id']] = $channel['channel_title'];
        	}

        	$channels = $return;
		}

		return $channels;
	}

    public function get_member_groups()
	{
		static $groups;

		if (is_null($groups))
		{
			ee()->load->model('member_model');

			$groups = array();

			$query = ee()->member_model->get_member_groups();

			foreach ($query->result() as $row)
			{
				$groups[$row->group_id] = $row->group_title;
			}
		}

		return $groups;
	}

}
// End of file Charge_model.php