<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Production config overrides & db credentials
 * 
 * Our database credentials and any environment-specific overrides
 * 
 * @package    Focus Lab Master Config
 * @version    1.1.1
 * @author     Focus Lab, LLC <dev@focuslabllc.com>
 */

$env_db['hostname'] = 'localhost';
$env_db['username'] = 'root';
$env_db['password'] = 'flaver';
$env_db['database'] = 'pnw';

// Sample global variable for Production only
// Can be used in templates like "{global:google_analytics}"
// $env_global['global:google_analytics'] = 'UA-XXXXXXX-XX';
// $env_global['global:mixpanel'] = 'mixpanel-prod';

/* End of file config.prod.php */
/* Location: ./config/config.prod.php */