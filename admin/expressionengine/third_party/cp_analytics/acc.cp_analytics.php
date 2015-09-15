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

class Cp_analytics_acc {

	var $name			= 'CP Analytics';
	var $id				= 'cp_analytics_acc';
	var $version		= CP_ANALYTICS_VERSION;
	var $description	= 'Display your Google Analytics traffic inside the ExpressonEngine&reg; control panel.';
	var $sections		= array();
	var $theme			= 'default';


	function __construct()
	{
		$this->EE =& get_instance();
		$this->EE->load->add_package_path(PATH_THIRD.PACKAGE_NAME);
		$this->EE->lang->loadfile(PACKAGE_NAME);
		$this->EE->load->helper('cp_analytics');
		$this->EE->load->model(PACKAGE_NAME.'_model');
		$this->EE->cp->load_package_js('spin');
					
		// Backwards-compatibility with pre-2.6 Localize class
		$this->format_date_fn = (version_compare(APP_VER, '2.6', '>=')) ? 'format_date' : 'decode_date';

		$this->theme = $this->EE->session->userdata['cp_theme'];
		
		// Always load the default CSS
		$this->EE->cp->load_package_css('default');
		switch($this->theme)
		{
			// Don't need to add anything if we're using the default theme
			case 'default':
				break;
			// Add tweaks for other themes if needed
			default:
				if(file_exists(PATH_THIRD.PACKAGE_NAME.'/css/'.$this->theme.'.css'))
				{
					$this->EE->cp->load_package_css($this->theme);
				}
		}
	}


	function set_sections()
	{
		$this->name = $this->EE->lang->line('cp_analytics_accessory_tab');
		
		$profile = $this->EE->cp_analytics_model->get_profile();
		$refresh_token = $this->EE->cp_analytics_model->get_refresh_token();
		$settings = $this->EE->cp_analytics_model->get_settings();
		
		if(empty($profile) || empty($refresh_token))
		{
			$this->sections[$this->EE->lang->line('cp_analytics_not_configured')] = 
				'<p><a href="'.BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module='.PACKAGE_NAME.'">'.
				$this->EE->lang->line('cp_analytics_not_configured_message').
				'</a></p>';
		}
		else
		{
			$today = $this->EE->cp_analytics_model->get_hourly_stats();
			$today['hourly_updated'] = $this->EE->localize->{$this->format_date_fn}('%g:%i%a', $today['cache_time']);
			
			$daily = $this->EE->cp_analytics_model->get_daily_stats();
			$daily['daily_updated'] = $daily['cache_date'];
			$daily['profile'] = $profile;
			$daily['site_index'] = $this->EE->functions->fetch_site_index(0,0);
			
			$recently = array_merge($today, $daily);
						
			if($this->_is_cp_home() 
				&& ( (isset($settings['homepage_chart']) && $settings['homepage_chart'] == 'y' && !empty($daily['lastmonth']['daily_stats'])) 
				|| (isset($settings['homepage_realtime']) && $settings['homepage_realtime'] == 'y') )
				)
				{
					$display_sections = array(
						'show_chart' => @$settings['homepage_chart'],
						'show_realtime' => @$settings['homepage_realtime']
					);
					
					$vars = array(
						'chart' => cp_analytics_chart_data($daily['lastmonth']['daily_stats']),
						'realtime_data_url' => $this->EE->cp_analytics_model->get_action_url(),
						'display_sections' => $display_sections,
						'template' => str_replace(array("\r","\n", "\t"), '', $this->EE->load->view('homepage_template', $display_sections, TRUE)),
						'theme' => $this->EE->cp_analytics_model->get_theme()
					);
					$this->EE->cp->add_to_foot(
						$this->EE->load->view('homepage', $vars, TRUE)
					);
				}

			// Hacky: if we're using the Republic theme, and not caching our sparklines, we can change the sparkline color
			if($this->theme == 'republic')
			{
				$sparklines = array('visits','pageviews','pages_per_visit','bounce_rate','avg_visit','new_visits');
				foreach($sparklines as $sparkline)
				{
					$daily['lastmonth'][$sparkline.'_sparkline'] = str_replace('FFFFFF', 'A9C526', $daily['lastmonth'][$sparkline.'_sparkline']);
				}
			}
			
			$this->sections[$this->EE->lang->line('cp_analytics_recently')] = $this->EE->load->view('recent', $recently, TRUE);
			$this->sections[$this->EE->lang->line('cp_analytics_lastmonth')] = $this->EE->load->view('lastmonth', $daily, TRUE);
			$this->sections[$this->EE->lang->line('cp_analytics_top_content')] = $this->EE->load->view('content', $daily, TRUE);
			$this->sections[$this->EE->lang->line('cp_analytics_sources')] = $this->EE->load->view('referrers', $daily, TRUE);
		}		
	}
	
		
	function _is_cp_home()
	{
		if($this->EE->input->get('D') == 'cp' && ( $this->EE->input->get('C') == 'homepage' || $this->EE->input->get('C') == false) )
		{
			return true;
		}
	}

}