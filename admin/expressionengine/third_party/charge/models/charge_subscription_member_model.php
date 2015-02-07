<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Charge Subscription Model class
 *
 * @package         charge_ee_addon
 * @version         1.8.12
 * @author          Joel Bradbury ~ <joel@squarebit.co.uk>
 * @link            http://squarebit.co.uk/addons/charge
 * @copyright       Copyright (c) 2014, Joel Bradbury/Square Bit
 */
class Charge_subscription_member_model extends Charge_model {

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
			'charge_subscription_member',
			'subscription_member_id',
			array(
				'site_id'       		=> 'int(4) unsigned NOT NULL default 0',
				'timestamp'				=> 'int(10) unsigned NOT NULL default 0',
				'member_id'				=> 'int(10) unsigned NOT NULL default 0',
				'subscription_id'		=> 'int(10) unsigned NOT NULL default 0',
				'status'				=> 'varchar(100) NOT NULL default ""',
				'last_contact'			=> 'int(10) unsigned NOT NULL default 0',
				'customer_id'			=> 'varchar(100) NOT NULL default ""',
				'charge_id'				=> 'int(10) unsigned NOT NULL default 0')
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


	public function active($member_id = '0')
	{
		if($member_id == '0') return array();

		ee()->db->where('charge_subscription_member.member_id', $member_id)
                    ->where('charge_subscription_member.status', 'active')
                    ->join('charge_stripe', 'charge_stripe.id = charge_subscription_member.charge_id');
        $all = self::get_all();

        foreach($all as $key => $row) {
        	$all[$key] = ee()->charge_stripe->parse($row);
        }

        return $all;
	}



} // End class

/* End of file Charge_subscription_member_model.php */