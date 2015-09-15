<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
    This file is part of CP Analytics add-on for ExpressionEngine.

    CP Analytics is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    CP Analytics is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    Read the terms of the GNU General Public License
    at <http://www.gnu.org/licenses/>.
    
    Copyright 2012-2015 Derek Hogue
*/

require(PATH_THIRD.'cp_analytics/config.php');

class Cp_analytics {
	
	public function __construct()
	{
		$this->EE =& get_instance();
		$this->EE->load->add_package_path(PATH_THIRD.PACKAGE_NAME);
		$this->EE->lang->loadfile(PACKAGE_NAME);
		$this->EE->load->helper(PACKAGE_NAME.'_helper');
		$this->EE->load->model(PACKAGE_NAME.'_model');
	}
	
	function get_realtime_data($dummy = false)
	{	
		if($realtime_data = $this->EE->cp_analytics_model->get_realtime_stats())
		{					
			exit($this->EE->load->view('homepage_realtime', $realtime_data, true));
		}
		
		return false;
	}
	
	public function integration_statusboard()
	{
		$this->EE->load->add_package_path(PATH_THIRD.PACKAGE_NAME);
		$this->EE->lang->loadfile(PACKAGE_NAME);
		$this->EE->load->model(PACKAGE_NAME.'_model');
		if(!function_exists('json_decode'))
		{
			$this->EE->load->library('Services_json');
		}
		
		$profile = $this->EE->cp_analytics_model->get_profile();
		$refresh_token = $this->EE->cp_analytics_model->get_refresh_token();
		$settings = $this->EE->cp_analytics_model->get_settings();
		
		
		if(! empty($profile) && ! empty($refresh_token) && $settings['enable_integration_statusboard'] == 'y')
		{
			$today = $this->EE->cp_analytics_model->get_hourly_stats();			
			$daily = $this->EE->cp_analytics_model->get_daily_stats();
			$daily['profile'] = $profile;
			$recently = array_merge($today, $daily);

			$this->EE->load->helper('cp_analytics');
			
			if(!$type = $this->EE->input->get('type'))
			{
				$type = 'graph';
			}
			
			$recently['board_type'] = $type;
			
			return $this->EE->load->view('statusboard', $recently);
		}
		
		return false;
	}
	
}