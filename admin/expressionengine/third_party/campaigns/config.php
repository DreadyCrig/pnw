<?php

/**
 * Config file for Campaigns
 *
 * @package			DevDemon_Subscriptions
 * @author			DevDemon <http://www.devdemon.com> - Lead Developer @ Parscale Media
 * @copyright 		Copyright (c) 2007-2010 Parscale Media <http://www.parscale.com>
 * @license 		http://www.devdemon.com/license/
 * @link			http://www.devdemon.com/campaigns/
 * @see				http://ee-garage.com/nsm-addon-updater/developers
 */

if ( ! defined('CAMPAIGNS_NAME'))
{
	define('CAMPAIGNS_NAME',         'Campaigns');
	define('CAMPAIGNS_CLASS_NAME',   'campaigns');
	define('CAMPAIGNS_VERSION',      '2.0.4');
}

$config['name'] 	= CAMPAIGNS_NAME;
$config["version"] 	= CAMPAIGNS_VERSION;
$config['nsm_addon_updater']['versions_xml'] = 'http://www.devdemon.com/campaigns/versions_feed/';

/**
 * < EE 2.6.0 backward compat
 */
if (!function_exists('ee'))
{
    function ee()
    {
        static $EE;
        if ( ! $EE) $EE = get_instance();
        return $EE;
    }
}

/* End of file config.php */
/* Location: ./system/expressionengine/third_party/campaigns/config.php */