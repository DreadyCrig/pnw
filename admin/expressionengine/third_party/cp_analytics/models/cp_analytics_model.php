<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

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

class Cp_analytics_model {

	var $client_id = '59111117587-fgfsq53kffg2gtb80ocu0msq30trbbsb.apps.googleusercontent.com';
	var $client_secret = 'R7F96dbrCtuKCeO3Fzp6meDa';
	var $redirect_uri = 'urn:ietf:wg:oauth:2.0:oob';
	var $token_endpoint = 'https://accounts.google.com/o/oauth2/token';
	var $data_endpoint = 'https://www.googleapis.com/analytics/v3/data/ga';
	var $realtime_endpoint = 'https://www.googleapis.com/analytics/v3/data/realtime';
	var $access_token = '';
	
	var $site_id = '';
	var $profile_id = '';
	var $current_date = '';
	var $yesterday = '';
	var $last_month = '';

	
	function __construct()
	{
		$this->EE =& get_instance();		
		$this->site_id = (!empty($_GET['site_id'])) ? $this->EE->input->get('site_id') : $this->EE->config->item('site_id');
		
		// Backwards-compatibility with pre-2.6 Localize class
		$this->format_date_fn = (version_compare(APP_VER, '2.6', '>=')) ? 'format_date' : 'decode_date';
		
		$this->current_date = $this->EE->localize->{$this->format_date_fn}('%Y-%m-%d', $this->EE->localize->now);
		$this->yesterday = 'yesterday';
		$this->last_month = '30daysAgo';
	}
	
	function get_action_url()
	{
		if($action_id = ee()->functions->fetch_action_id('Cp_analytics', 'get_realtime_data'))
		{
			$base = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? str_replace('http://', 'https://', ee()->config->item('site_url')) : ee()->config->item('site_url');
			return $base.'?ACT='.ee()->functions->insert_action_ids($action_id);
		}
	}
	
	function get_theme()
	{
		$data = array(
			'accent_color' => '#D91350',
			'background_color' => '#ECF1F4',
			'light_color' => '#96A8B4',
			'medium_color' => '#3E4C54',
			'theme' => $this->EE->session->userdata['cp_theme']
		);
		switch($this->EE->session->userdata['cp_theme'])
		{
			case 'corporate' :
				$data['accent_color'] = '#005EB0'; 
				break;
			case 'nerdery' : 
				$data['accent_color'] = '#2D98DB';
				break;
			case 'sassy_cp' : 
				$data['accent_color'] = '#0393c5';
				break;
			case 'republic' : 
				$data['accent_color'] = '#a9c526';
				$data['background_color'] = '#FFFFFF';
				$data['light_color'] = '#CCCCCC';
				break;
		}
		return $data;
	}

	function get_oauth_url()
	{
		return 'https://accounts.google.com/o/oauth2/auth?response_type=code'.AMP.'client_id='.$this->client_id.AMP.'redirect_uri='.$this->redirect_uri.AMP.'scope=https://www.googleapis.com/auth/analytics.readonly'.AMP.'access_type=offline';
	}
	
	
	function exchange_authorization_for_token($code)
	{
		$r = array(
			'error' => '',
			'access_token' => ''
		);
		$args = array(
			'code' => trim($code),
			'client_id' => $this->client_id,
			'client_secret' => $this->client_secret,
			'redirect_uri' => $this->redirect_uri,
			'grant_type' => 'authorization_code'
		);
		if($response = $this->_curl_request($this->token_endpoint, $args))
		{
			
			if(isset($response['error']))
			{
				$r['error'] = 'cp_analytics_invalid_auth_code';
			}

			// Save our refresh token
			if(isset($response['refresh_token']))
			{
				$this->save_settings(array('refresh_token' => $response['refresh_token']));
			}
			
			// Set the fresh access token
			if(isset($response['access_token']))
			{
				$this->access_token = $response['access_token'];
			}
		}
		return $r;
	}
	

	function get_access_token()
	{
		if(empty($this->access_token))
		{
			if($token = $this->get_refresh_token())
			{
				$args = array(
					'refresh_token' => $token,
					'client_id' => $this->client_id,
					'client_secret' => $this->client_secret,
					'grant_type' => 'refresh_token'					
				);
				if($response = $this->_curl_request($this->token_endpoint, $args))
				{
					if(isset($response['access_token']))
					{
						$this->access_token = $response['access_token'];
					}
				}				
			}
		}
	}
	
	
	function get_refresh_token()
	{
		$token = $this->EE->db->query('SELECT refresh_token FROM exp_cp_analytics WHERE site_id = '.$this->site_id);
		if($token->num_rows() > 0)
		{
			return $token->row('refresh_token');
		}
	}
	
	
	function get_profile_list()
	{
		$this->get_access_token();
		
		$r = array(
			'error' => '',
			'profiles' => '',
			'profile_segments' => '',
			'profile_names' => ''
		);

		if(!empty($this->access_token))
		{
			$args = array(
				'access_token' => $this->access_token	
			);
			
			if($response = $this->_curl_request(
				'https://www.googleapis.com/analytics/v3/management/accounts/~all/webproperties/~all/profiles'
				, $args, 'get')
			)
			{
				if(isset($response['error']))
				{
					$r['error'] = 'cp_analytics_profile_error';
				}
				
				if(isset($response['items']))
				{
					$r['profiles'] = array('' => '--');
					foreach($response['items'] as $result)
					{
					  $title = (!empty($result['websiteUrl'])) ? $result['websiteUrl'] : $result['name'];
					  $r['profiles'][$result['id']] = $r['names'][$result['id']] = rtrim(str_replace(array('http://','www.'), '', $title), '/');
					  $r['segments'][$result['id']] = 'a'.$result['accountId'].'w'.$result['internalWebPropertyId'].'p'.$result['id'];
					}
					asort($r['profiles']);
				}
			}
		}
		return $r;
	}
	
	
	function get_realtime_stats()
	{
		$this->get_access_token();
		$data = array(
			'active_users' => 0,
			'sources' => array(),
			'countries' => array(),
			'content' => array()
		);
		
		$profile = $this->get_profile();
		$this->profile_id = 'ga:'.$profile['id'];
		
		$args = $this->_default_query_args();
		$args['dimensions'] = 'rt:country';
		$args['metrics'] = 'rt:activeUsers';
		$args['sort'] = '-rt:activeUsers';
		$args['max-results'] = 6;
		$request = $this->_curl_request($this->realtime_endpoint, $args, 'get');
		if(!empty($request['rows']))
		{
			foreach($request['rows'] as $row)
			{
				if($row[0] != '(not set)')
				{
					$data['countries'][] = array(
						'country' => $row[0],
						'users' => $row[1],	
						'percentage' => cp_analytics_percentage($row[1], $request['totalsForAllResults']['rt:activeUsers'], true).'%'
					);	
				}
			}
			if(count($data['countries']) > 5)
			{
				array_pop($data['countries']);
			}
		}
		
		$args = $this->_default_query_args();
		$args['dimensions'] = 'rt:source';
		$args['metrics'] = 'rt:activeUsers';
		$args['sort'] = '-rt:activeUsers';
		$args['max-results'] = 5;
		$request = $this->_curl_request($this->realtime_endpoint, $args, 'get');
		if(!empty($request['rows']))
		{
			foreach($request['rows'] as $row)
			{
				$data['sources'][] = array(
					'source' => ($row[0] == '(not set)') ? 'direct' : $row[0],
					'users' => $row[1]	,
					'percentage' => cp_analytics_percentage($row[1], $request['totalsForAllResults']['rt:activeUsers'], true).'%'
				);
			}
		}
		
		$args = $this->_default_query_args();
		$args['dimensions'] = 'rt:pagePath,rt:pageTitle';
		$args['metrics'] = 'rt:activeUsers';
		$args['sort'] = '-rt:activeUsers';
		$args['max-results'] = 5;
		$request = $this->_curl_request($this->realtime_endpoint, $args, 'get');
		if(!empty($request['rows']))
		{
			foreach($request['rows'] as $row)
			{
				$data['content'][] = array(
					'page_path' => $row[0],
					'page_title' => (empty($row[1]) || $row[1] == '(not set)') ? $row[0] : $row[1],
					'users' => $row[2],
					'percentage' => cp_analytics_percentage($row[2], $request['totalsForAllResults']['rt:activeUsers'], true).'%'
				);
			}
		}

		$args = $this->_default_query_args();
		$args['dimensions'] = 'rt:deviceCategory';
		$args['metrics'] = 'rt:activeUsers';
		$args['sort'] = '-rt:activeUsers';
		$request = $this->_curl_request($this->realtime_endpoint, $args, 'get');
		if(!empty($request['rows']))
		{
			foreach($request['rows'] as $row)
			{
				$data['active_users'] = $request['totalsForAllResults']['rt:activeUsers'];
				$data['devices'][] = array(
					'device' => $row[0],
					'users' => $row[1]	,
					'percentage' => cp_analytics_percentage($row[1], $request['totalsForAllResults']['rt:activeUsers'], true).'%',
					'percentage_numeric' => cp_analytics_percentage($row[1], $request['totalsForAllResults']['rt:activeUsers'], true),
					'percentage_precise' => cp_analytics_percentage($row[1], $request['totalsForAllResults']['rt:activeUsers']).'%'
				);
			}
		}
						
		return $data;		
	}
	

	function get_hourly_stats()
	{			
		$cache = $this->get_hourly_cache();
		if(!empty($cache) && ($cache['cache_time'] + 3600) >= $this->EE->localize->now)
		{
			$data = $cache;
		}
		else
		{
			$this->get_access_token();
			$data = array(
				'pageviews' => 0,
				'visits' => 0,
				'pages_per_visit' => 0,
				'avg_visit' => '00:00:00'
			);
			$data['cache_time'] = $this->EE->localize->now;
			
			$profile = $this->get_profile();
			$this->profile_id = 'ga:'.$profile['id'];

			$args = $this->_default_query_args();
			$args['start-date'] = 'today';
			$args['end-date'] = 'today';
			$args['metrics'] = 'ga:pageviews,ga:sessions,ga:sessionDuration';
			$request = $this->_curl_request($this->data_endpoint, $args, 'get');
			if(isset($request['rows']))
			{			
				$data['pageviews'] = number_format($request['totalsForAllResults']['ga:pageviews']);
				$data['visits'] = number_format($request['totalsForAllResults']['ga:sessions']);
				$data['pages_per_visit'] = $this->_avg_pages($request['totalsForAllResults']['ga:pageviews'], $request['totalsForAllResults']['ga:sessions']);
				$data['avg_visit'] = $this->_avg_visit($request['totalsForAllResults']['ga:sessionDuration'], $request['totalsForAllResults']['ga:sessions']);
			}

			// Now cache it
			$settings = array(
				'hourly_cache' => $data
			);
			$this->save_settings($settings);
		}
		return $data;
	}


	function get_daily_stats()
	{		
		$cache = $this->get_daily_cache();
		if(!empty($cache) && $cache['cache_date'] >= $this->current_date)
		{
			$data = $cache;
		}
		else
		{
			$this->get_access_token();
			$data = array(
				'yesterday' => array(
					'pageviews' => 0,
					'visits' => 0,
					'pages_per_visit' => 0,
					'avg_visit' => '00:00:00'
				),
				'lastmonth' => array(
					'pageviews' => 0,
					'visits' => 0,
					'pages_per_visit' => 0,
					'avg_visit' => '00:00:00',
					'bounce_rate' => 0,
					'new_visits' => 0,
					'content' => array(),
					'referrers' => array()
				)
			);
			$data['cache_date'] = $this->current_date;
			
			$profile = $this->get_profile();
			$this->profile_id = 'ga:'.$profile['id'];
			
			// Compile yesterday's stats
			$args = $this->_default_query_args();
			$args['start-date'] = $this->yesterday;
			$args['end-date'] = $this->yesterday;
			$args['metrics'] = 'ga:pageviews,ga:sessions,ga:sessionDuration';
			$request = $this->_curl_request($this->data_endpoint, $args, 'get');
			
			if(isset($request['rows']))
			{	
				$data['yesterday']['pageviews'] = number_format($request['totalsForAllResults']['ga:pageviews']);
				$data['yesterday']['visits'] = number_format($request['totalsForAllResults']['ga:sessions']);
				$data['yesterday']['pages_per_visit'] = $this->_avg_pages($request['totalsForAllResults']['ga:pageviews'], $request['totalsForAllResults']['ga:sessions']);
				$data['yesterday']['avg_visit'] = $this->_avg_visit($request['totalsForAllResults']['ga:sessionDuration'], $request['totalsForAllResults']['ga:sessions']);
			}
			
			
			// Compile last month's stats
			$args = $this->_default_query_args();
			$args['start-date'] = $this->last_month;
			$args['end-date'] = $this->yesterday;
			$args['metrics'] = 'ga:pageviews,ga:sessions,ga:newUsers,ga:sessionDuration,ga:bounces,ga:entrances';
			$args['dimensions'] = 'ga:date';
			$request = $this->_curl_request($this->data_endpoint, $args, 'get');
			
			if(isset($request['rows']))
			{	
				$data['lastmonth']['visits'] = 
				number_format($request['totalsForAllResults']['ga:sessions']);
				$data['lastmonth']['visits_sparkline'] = 
				$this->_cp_analytics_sparkline($request['rows'], 'visits');
				
				$data['lastmonth']['pageviews'] = 
				number_format($request['totalsForAllResults']['ga:pageviews']);
				$data['lastmonth']['pageviews_sparkline'] = 
				$this->_cp_analytics_sparkline($request['rows'], 'pageviews');
				
				$data['lastmonth']['pages_per_visit'] = 
				$this->_avg_pages($request['totalsForAllResults']['ga:pageviews'], $request['totalsForAllResults']['ga:sessions']);
				$data['lastmonth']['pages_per_visit_sparkline'] = 
				$this->_cp_analytics_sparkline($request['rows'], 'avgpages');
				
				$data['lastmonth']['avg_visit'] = 
				$this->_avg_visit($request['totalsForAllResults']['ga:sessionDuration'], $request['totalsForAllResults']['ga:sessions']);
				$data['lastmonth']['avg_visit_sparkline'] = 
				$this->_cp_analytics_sparkline($request['rows'], 'time');
				
				$data['lastmonth']['bounce_rate'] = 
				($request['totalsForAllResults']['ga:bounces'] > 0 && $request['totalsForAllResults']['ga:sessions'] > 0) ? 
				round( ($request['totalsForAllResults']['ga:bounces'] / $request['totalsForAllResults']['ga:entrances']) * 100, 2 ).'%' : '0%';
				$data['lastmonth']['bounce_rate_sparkline'] = 
				$this->_cp_analytics_sparkline($request['rows'], 'bouncerate');
				
				$data['lastmonth']['new_visits'] = 
				($request['totalsForAllResults']['ga:newUsers'] > 0 && $request['totalsForAllResults']['ga:sessions'] > 0) ? 
				round( ($request['totalsForAllResults']['ga:newUsers'] / $request['totalsForAllResults']['ga:sessions']) * 100, 2).'%' : '0%';					
				$data['lastmonth']['new_visits_sparkline'] = 
				$this->_cp_analytics_sparkline($request['rows'], 'newvisits');
				
				$data['lastmonth']['daily_stats'] = array();
				
				foreach($request['rows'] as $row)
				{
					$data['lastmonth']['daily_stats'][$row[0]] = array(
						'visits' => $row[2],
						'pageviews' => $row[1]
					);
				}
			}
		
		
			// Compile last month's top content
			$args = $this->_default_query_args();
			$args['start-date'] = $this->last_month;
			$args['end-date'] = $this->yesterday;
			$args['metrics'] = 'ga:pageviews';
			$args['dimensions'] = 'ga:hostname,ga:pagePath,ga:pageTitle';
			$args['sort'] = '-ga:pageviews';
			$args['max-results'] = 16;
			$request = $this->_curl_request($this->data_endpoint, $args, 'get');
	
			if(isset($request['rows']))
			{	
				$data['lastmonth']['content'] = array();
				$i = 0;
				
				// Make a temporary array to hold page paths
				// (for checking dupes resulting from www vs non-www hostnames)
				$paths = array();
				
				foreach($request['rows'] as $row)
				{
					// Do we already have this page path?
					$dupe_key = array_search($row[1], $paths);
					if($dupe_key !== FALSE)
					{
						// Combine the pageviews of the dupes
						$data['lastmonth']['content'][$dupe_key]['count'] = ( $row[3] + $data['lastmonth']['content'][$dupe_key]['count'] );
					}
					else
					{
						$data['lastmonth']['content'][$i]['title'] = $row[2];
						$data['lastmonth']['content'][$i]['url'] = 'http://'.$row[0].$row[1];
						$data['lastmonth']['content'][$i]['count'] = $row[3];
						// Store the page path at the same position so we can check for dupes
						$paths[$i] = $row[1];
						$i++;
					}				
				}
				
				// Slice down to 8 results
				$data['lastmonth']['content'] = array_slice($data['lastmonth']['content'], 0, 8);
			}
			
			
			// Compile last month's top referrers
			$args = $this->_default_query_args();
			$args['start-date'] = $this->last_month;
			$args['end-date'] = $this->yesterday;
			$args['metrics'] = 'ga:sessions';
			$args['dimensions'] = 'ga:source,ga:referralPath,ga:medium';
			$args['sort'] = '-ga:sessions';
			$args['max-results'] = 8;
			$request = $this->_curl_request($this->data_endpoint, $args, 'get');
	
			if(isset($request['rows']))
			{		
				$data['lastmonth']['referrers'] = array();
				$i = 0;
				foreach($request['rows'] as $row)
				{
					$data['lastmonth']['referrers'][$i]['title'] = ($row[0] == '(direct)' || $row[0] == '(not set') ? 'direct' : $row[0];
					$data['lastmonth']['referrers'][$i]['url'] = 'http://'.$row[0].$row[1];
					$data['lastmonth']['referrers'][$i]['type'] = $row[2];
					$data['lastmonth']['referrers'][$i]['count'] = number_format($row[3]);
					$i++;
				}
			}
						
			// Now cache it
			$settings = array(
				'daily_cache' => $data
			);
			$this->save_settings($settings);
		}

		return $data;
	}


	function get_profile()
	{
		return $this->_get_settings_col('profile');
	}
	

	function get_settings()
	{
		return $this->_get_settings_col('settings');

	}
	
	
	function get_hourly_cache()
	{
		return $this->_get_settings_col('hourly_cache');
	}


	function get_daily_cache()
	{
		return $this->_get_settings_col('daily_cache');
	}
		
	
	function _get_settings_col($col)
	{
		if(!function_exists('json_decode'))
		{
			$this->EE->load->library('Services_json');
		}
		
		$data = $this->EE->db->query('SELECT '.$col.' FROM exp_cp_analytics WHERE site_id = '.$this->site_id);
		if($data->num_rows() > 0)
		{
			$array = json_decode($data->row($col), true);
			if(empty($array) && $col == 'settings')
			{
				return array(
					'homepage_realtime' => 'y',
					'homepage_chart' => 'y'
				);
			}
			else
			{
				return $array;
			}
		}
		else
		{
			return array();
		}
	}
	
	
	function save_settings($settings)
	{
		if(!function_exists('json_decode'))
		{
			$this->EE->load->library('Services_json');
		}
		
		foreach($settings as $k => $v)
		{
			if($k != 'refresh_token')
			{
				$settings[$k] = json_encode($v);
			}
		}
		
		// Did we just change profiles? If so, clear the cache
		if($new_profile = $this->EE->input->post('profile'))
		{
			$query = $this->EE->db->query('SELECT profile FROM exp_cp_analytics WHERE site_id = '.$this->site_id);
			if($query->num_rows() > 0)
			{
				$existing_profile = json_decode($query->row('profile'), true);
				if($new_profile != $existing_profile['id'])
				{
					$settings['hourly_cache'] = $settings['daily_cache'] = '';
				}
			}
		}
		
		// Do we already have settings?
		$existing = $this->EE->db->query('SELECT site_id FROM exp_cp_analytics WHERE site_id = '.$this->site_id);
		if($existing->num_rows() > 0)
		{
			$this->EE->db->query(
				$this->EE->db->update_string('exp_cp_analytics', $settings, 'site_id = '.$this->site_id)
			);
		}
		else
		{
			// Create a new row
			$settings['site_id'] = $this->site_id;
			$this->EE->db->query(
				$this->EE->db->insert_string('exp_cp_analytics', $settings)
			);		
		}
	}
	
	
	function reset_authentication()
	{
		if($token = $this->get_refresh_token())
		{
			$args = array(
				'token' => $token
			);
			$this->_curl_request('https://accounts.google.com/o/oauth2/revoke', $args, 'get');
		}
		$this->EE->db->query('DELETE FROM exp_cp_analytics WHERE site_id = '.$this->site_id);
	}
	
	
	function _default_query_args()
	{
		return array(
			'access_token' => $this->access_token,
			'ids' => $this->profile_id,
			'userIp' => $this->EE->input->ip_address()
		);
	}
		
	
	function _curl_request($server, $query, $method = 'post')
	{
	    if(!function_exists('json_decode'))
		{
			$this->EE->load->library('Services_json');
		}
		
		$args = '';
		foreach ($query as $key => $value)
		{
			$args .= trim($key).'='.trim($value).'&';
		}
		$args = rtrim($args, '&');
		
		if($method == 'get')
		{
			$server .= '?'.$args;
		}
				
		$ch = curl_init($server);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		if($method == 'post')
		{
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
		}
		/*
			Some issues have been popping-up with IPv6 which I do not understand, as I am faking my way through this whole thing.
			Forcing an IPv4 connection seems to work. It will likely fuck up something else though.
		*/
		if(defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4'))
		{
			curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
		}		
		curl_setopt($ch, CURLOPT_REFERER, 'http://'.$_SERVER['SERVER_NAME']);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);		
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		$response = curl_exec($ch);
		curl_close($ch);
		/*
			We pass a second argument of "true" to tell the native json_decode() function to return an array, not an object.
			This is because EE's bundled Services_JSON is hard-coded to return an array, so we have to be matchy.
		*/
		return json_decode($response, true);
	}


	function _avg_pages($pageviews, $visits)
	{
		return ($pageviews > 0 && $visits > 0) ? round($pageviews / $visits, 2) : 0;
	}
	
	
	function _avg_visit($seconds, $visits)
	{
		if($seconds > 0 && $visits > 0)
		{
			$avg_secs = $seconds / $visits;
			// This little snippet by Carson McDonald, from his Analytics Dashboard WP plugin
			$hours = floor($avg_secs / (60 * 60));
			$minutes = floor(($avg_secs - ($hours * 60 * 60)) / 60);
			$seconds = $avg_secs - ($minutes * 60) - ($hours * 60 * 60);
			return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
		}
		else
		{
			return '00:00:00';
		}
	}
	
	
	function _cp_analytics_sparkline($rows, $metric)
	{
		$max = 0; $stats = array();
		
		foreach($rows as $row)
		{
			switch($metric) {
				case "pageviews":
					$datapoint = $row[1];
					break;
				case "visits":	
					$datapoint = $row[2];
					break;
				case "newvisits":
					$datapoint =  ($row[3] > 0 && $row[2] > 0) ? $row[3] / $row[2] : 0;
					break;
				case "time":
					$datapoint = $row[4];
					break;
				case "avgpages":
					$datapoint = ($row[2] > 0 && $row[1] > 0) ? $row[1] / $row[2] : 0;
					break;
				case "bouncerate":
					$datapoint = ($row[6] > 0 && $row[5] > 0) ? $row[5] / $row[6] : 0;
					break;
			}
			$max = ($max < $datapoint) ? $datapoint : $max;
			$stats[] = $datapoint;
		}
		
		// Build Google Chart url
		$base = 'https://chart.googleapis.com/chart?';
		$args = array(
			'cht=ls',
			'chs=480x80',
			'chm=B,FFFFFF66,0,0,0',
			'chco=FFFFFFEE',
			'chf=c,s,FFFFFF00|bg,s,FFFFFF00',
			'chd=t:'.implode(',', $stats),
			'chds=0,'.$max
		);
		
		$curl_url = $base.implode('&', $args);
		$src_url = $base.implode('&amp;', $args);
					
	   	// Are we caching locally?
	   	$settings = $this->get_settings();
	   	if(isset($settings['cache_sparklines']) && $settings['cache_sparklines'] == 'y')
	   	{
			// Determine cache and file destination
			if($path = $this->EE->config->item('cp_analytics_cache_path'))
			{
				$path = rtrim($path, '/');
			}
			elseif(isset($settings['cache_path']) && !empty($settings['cache_path']))
			{
				$path = rtrim($settings['cache_path'], '/');
			}
			else
			{
				$path = PATH_THIRD_THEMES.'cp_analytics';
			}
	
			// Determine cache URL
			if($url = $this->EE->config->item('cp_analytics_cache_url'))
			{
				$url = rtrim($url, '/').'/';
			}
			elseif(isset($settings['cache_url']) && !empty($settings['cache_url']))
			{
				$url = rtrim($settings['cache_url'], '/').'/';
			}
			else
			{
				$url = URL_THIRD_THEMES.'cp_analytics/';
			}
			
			$filename = sha1(implode(',',$stats).','.$max).'.png';
			$fullpath = $path.'/'.$filename;
				    
			// Check for cache directory
			if(!is_dir($path))
			{
				@mkdir($path, DIR_WRITE_MODE);
			}
			
			// If the directory was there, or we were able to create it, fetch the sparklines and save them	   
			if(is_dir($path))
			{	    
			    // Check for an identical file first
			    if(!file_exists($fullpath))
			    {
					// Fetch it
					$ch = curl_init($curl_url);
					curl_setopt($ch, CURLOPT_HEADER, 0);
					curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					if($image = curl_exec($ch))
					{
						$this->EE->load->helper('file');
						write_file($fullpath, $image);
						$sparkline = $url.$filename;
					}
					curl_close($ch);	          
			    }
			    else
			    {
					$sparkline = $url.$filename;
			    }
			}
		}
				
		// Otherwise just call the image externally
		if(!isset($sparkline)) $sparkline = $src_url;
		
		return $sparkline;
	}

}