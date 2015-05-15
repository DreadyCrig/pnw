<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include_once dirname(__FILE__).'/config.php';

/**
 * Subscriptions API File
 *
 * @package         DevDemon_Updater
 * @author          DevDemon <http://www.devdemon.com> - Lead Developer @ Parscale Media
 * @copyright       Copyright (c) Parscale Media <http://www.parscale.com>
 * @license         http://www.devdemon.com/license/
 * @link            http://www.devdemon.com/subs/
 */
class Campaigns_API
{
	public $debug = false;
    public $siteId = 1;
    public $settings = array(); // Campaigns settings
    public $config = array(); // Campaigns config overrides

    private $servicesList;

    // ********************************************************************************* //

    public function __construct()
    {
        $this->EE =& get_instance();

        $this->EE->lang->loadfile('campaigns');
        $this->EE->load->config('campaigns_config');
        $this->EE->load->library('firephp');
        $this->EE->load->model('campaigns_model');

        $this->stats = new stdClass();
        $this->stats->event = '';
        $this->stats->data = array();

        // Grab our settings!
        $this->getSettings();

        $this->siteId = $this->EE->config->item('site_id');

         // Are we in debug mode?
        $this->debug = ($this->settings['debug'] == 'yes') ? true : false;
    }

    // ********************************************************************************* //

    public function getService($account, $box=null, $init=true)
    {
        if (isset($account->service) === false) {
            return false;
        }

        $service = $account->service;
        $class = 'Service_'.$service;

        // Service Settings
        $settings = array();
        if (isset($account->settings) === true) {
            $settings = $account->settings;
        }

        // Channel Settings
        $boxSettings = array();
        if (isset($account->boxSettings) === true) {
            $boxSettings = $account->boxSettings;
        }

        // Require all files
        $currentDir = dirname(__FILE__);
        require_once $currentDir.'/services/CampaignServiceInterface.php';
        require_once $currentDir.'/services/EmailServiceInterface.php';
        require_once $currentDir.'/services/UrlShortenerInterface.php';
        require_once $currentDir.'/services/CampaignService.php';
        require_once $currentDir."/services/service.{$service}.php";

        $obj = new $class($settings, $boxSettings, $init);
        $obj->account = $account;
        $obj->box = $box;

        return $obj;
    }

    // ********************************************************************************* //

    public function getUrlShortener($serviceName, $settings=false)
    {
        $class = 'Service_'.$serviceName;

        if (!$settings) {
            $settings = (isset($this->settings['shortner'][$serviceName])) ? $this->settings['shortner'][$serviceName] : array();
        }


        // Require all files
        $currentDir = dirname(__FILE__);
        require_once $currentDir.'/services/UrlShortenerInterface.php';
        require_once $currentDir.'/services/CampaignService.php';
        require_once $currentDir."/services/service.{$serviceName}.php";

        $obj = new $class($settings);

        return $obj;
    }

    // ********************************************************************************* //

    public function shortenUrls($text)
    {
        $ret = array('success' => false, 'error' => '', 'text' => $text);

        if ($this->settings['url_shortener'] == false) {
            $ret['error'] = 'No URL shortener has been defined';
            return $ret;
        }

        // Find all URL's
        $urls = array();

        $count = preg_match_all('#\bhttps?://[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/))#', $text, $matches);

        if ($count > 0) {
            $urls = reset($matches);
        }

        // Init the Service
        $serviceName = $this->settings['url_shortener'];
        $settings = (isset($this->settings['shortner'][$serviceName])) ? $this->settings['shortner'][$serviceName] : array();
        $service = $this->getUrlShortener($serviceName);

        // Loop over all URLs
        foreach ($urls as $url) {
            $short_url = $service->shorten($url);

            if ($short_url) {
                $ret['text'] = str_replace($url, $short_url, $ret['text']);
            } else {
                $ret['error'] = $service->errorMsg;
                return $ret;
            }
        }

        $ret['success'] = true;
        //exit($ret['text']);
        return $ret;
    }

    // ********************************************************************************* //

    public function getServicesList()
    {
        if (empty($this->servicesList) === false) return $this->servicesList;

        $this->servicesList = array();

        $files = scandir(dirname(__FILE__).'/services/');
        sort($files);

        foreach ($files as $file) {
            if ($file == '..' || $file == '.') continue;
            if (strpos($file, 'service.') !== 0) continue;
            $service = substr($file, 8);
            $service = substr($service, 0, -4);
            $this->servicesList[] = $service;
        }

        return $this->servicesList;
    }

    // ********************************************************************************* //

    public function getChannelSettings($channel_id=0, $field_id=false, $entry_id=false)
    {
        if ($entry_id > 0) {
            // -------------------------------------------------------
            //  Get Channel ID
            // -------------------------------------------------------
            $query = $this->EE->db->query("SELECT channel_id, title FROM exp_channel_titles WHERE entry_id = {$entry_id}");
            $channel_id = $query->row('channel_id');

            // -------------------------------------------------------
            //  Get Fields
            // -------------------------------------------------------
            $query = $this->EE->db->query("SELECT field_group FROM exp_channels WHERE channel_id = {$channel_id}");

            if ($query->num_rows() > 0) {
                $field_group = $query->row('field_group');

                // get the fields now
                $field_id = 0;
                $query = $this->EE->db->query("SELECT field_id FROM exp_channel_fields WHERE group_id = {$field_group} AND field_type = 'campaigns' ORDER BY field_order ASC LIMIT 1");

                if ($query->num_rows() > 0) {
                    $field_id = $query->row('field_id');
                }
            }
        }

        $settings = $this->settings['default_channel_settings'];

        if (isset($this->settings['channels'][$channel_id]) === true) {
            $settings = array_merge($settings, $this->settings['channels'][$channel_id]);
        }

        if ($field_id > 0) {
            $query = $this->EE->db->query("SELECT field_settings FROM exp_channel_fields WHERE field_id = {$field_id} ");

            if ($query->num_rows() > 0) {
                $field_settings = @unserialize(base64_decode($query->row('field_settings')));
                //$this->EE->firephp->log($field_settings);

                if (empty($field_settings) === false && isset($field_settings['campaigns'])) {
                    $settings = array_merge($settings, $field_settings['campaigns']);
                }
            }
        }

        return $settings;
    }

    // ********************************************************************************* //

    public function getCampaignStatusUi($campaign)
    {
        $box = $this->EE->campaigns_model->getBox($campaign->box_id);
        $account = $this->EE->campaigns_model->getAccount($box->account_id);
        $account->boxSettings = $box->settings;

        $service = $this->EE->campaigns->getService($account);

        $scheduledTime = strtotime($campaign->scheduled_at);

        if ($campaign->status == 'scheduled') {
            $data = array();
            $data['scheduledTime'] = $scheduledTime;
            return $this->EE->load->view('status/scheduled', $data, true);
        } else {
            return $service->getCampaignStatusHtml($campaign);
        }
    }

    // ********************************************************************************* //

    public function getSettings()
    {
        $settings = array();

        if (isset($this->EE->session->cache['campaigns']['module_settings']) == true) {
            $settings = $this->EE->session->cache['campaigns']['module_settings'];
        } else {

            // Get the module settings from the DB
            $this->EE->db->select('settings');
            $this->EE->db->where('module_name', 'Campaigns');
            $query = $this->EE->db->get('exp_modules');

            if ($query->num_rows() > 0) {
                $settings = @unserialize($query->row('settings'));
            }

            // Still falsy?
            if ($settings == false) {
                $settings = array();
            }
        }

        // Module Defaults & Config Overrides
        $default = $this->EE->config->item('campaigns_default');
        $this->config = $this->EE->config->item('campaigns');

        // Just to be sure
        if (is_array($this->config) == false) {
            $this->config = array();
        }

        // Merge them
        $settings = Campaigns_helper::arrayExtend($default, $settings);

        // And the config overrides!
        if (!empty($this->config)) {
            $settings = Campaigns_helper::arrayExtend($settings, $this->config);
        }

        // Save so we don't have to do this again.
        $this->EE->session->cache['campaigns']['module_settings'] = $settings;
        $this->settings = $this->EE->session->cache['campaigns']['module_settings'];

        return $settings;
    }

    // ********************************************************************************* //

    public function sendStats()
    {
        if (!function_exists('curl_init')) return;

        // Are we allowed to track stats?
        if (isset($this->settings['track_stats']) === true && $this->settings['track_stats'] == 'no') {
            return;
        }

        try {
            $host = 'http://api.mixpanel.com/track/?data=';
            $token = '8258f45c0b24fafe438deaec812be1a0';

            $params = array();
            $params['event'] = $this->stats->event;
            $params['properties'] = $this->stats->data;

            if (!isset($params['properties']['token'])){
                $params['properties']['token'] = $token;
            }

            // During EE Update, this is not available
            if (defined('APP_BUILD'))
            {
                $params['properties']['app_build'] = APP_BUILD;
                $params['properties']['app_version'] = APP_VER;
            }

            $params['properties']['server_os'] = (DIRECTORY_SEPARATOR == '/') ? 'unix' : 'windows';
            $params['properties']['updater_version'] = UPDATER_VERSION;
            $params['properties']['transfer_method'] = $this->settings['file_transfer_method'];

            $php_version = explode('.', PHP_VERSION);
            $params['properties']['php_version'] = @$php_version[0].'.'.@$php_version[1].'.'.@$php_version[2];

            if (isset($this->settings['mixpanel_token']) === true)
            {
                $params['properties']['token'] = $this->settings['mixpanel_token'];
            }

            if (isset($this->settings['mixpanel_firephp']) === true && $this->settings['mixpanel_firephp'] == 'yes') {
                $this->EE->firephp->log($params);
            }

            $data = base64_encode($this->EE->updater_helper->generateJson($params));

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $host.$data);
            curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            $resp = curl_exec($ch);
            curl_close($ch);

            $this->stats->event = '';
            $this->stats->data = array();

        } catch (Exception $e) {

        }
    }

    // ********************************************************************************* //



} // END CLASS

/* End of file api.campaigns.php  */
/* Location: ./system/expressionengine/third_party/campaigns/api.campaigns.php */
