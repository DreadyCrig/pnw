<?php if (!defined('BASEPATH')) die('No direct script access allowed');

/**
 * Campaigns MAILCHIMP provider
 *
 * @package         DevDemon_Campaigns
 * @author          DevDemon <http://www.devdemon.com> - Lead Developer @ Parscale Media
 * @copyright       Copyright (c) 2007-2014 Parscale Media <http://www.parscale.com>
 * @license         http://www.devdemon.com/license/
 * @link            http://www.devdemon.com/campaigns/
 */
class Service_googleplus extends CampaignService implements CampaignServiceInterface
{

    // Service Info
    public $title     =  'Google+';
    public $name      =  'googleplus';
    public $type      =  'social';

    /**
     * Constructor
     *
     * @access public
     *
     * Calls the parent constructor
     */
    public function __construct($settings=array(), $boxSettings=array(), $init=true)
    {
        // Default Settings
        $this->settings['email'] = '';
        $this->settings['password'] = '';

        $this->boxSettings['post_to'] = 'me';
        $this->boxSettings['message'] = 'New Post: {title} - {comment_url_title_auto_path}';
        $this->boxSettings['link_url'] = '{comment_url_title_auto_path}';
        $this->boxSettings['image_url'] = '';

        $this->boxSettings['business_page_id'] = '';
        $this->boxSettings['community_page_id'] = '';

        parent::__construct($settings, $boxSettings, $init);

        //if ($init) $this->init();
    }

    // ********************************************************************************* //

    public function testConnection()
    {
        return null;
    }

    // ********************************************************************************* //

    public function getAccountData($response)
    {
        $html = '';
        $html .= $this->settings['email'];
        return $html;
    }

    // ********************************************************************************* //

    public function getBoxOptions()
    {
        $data =& $this->boxOptions;
        $data = array();

        $data['blogs'] = array();

        $data['post_to'] = array();
        $data['post_to']['me'] = 'My Stream';
        $data['post_to']['business'] = 'Business Page Stream';
        $data['post_to']['community'] = 'Community Page Stream';


        return $data;
    }

    // ********************************************************************************* //

    public function getBoxAccountVars()
    {
        $vars = array();

        $post_to = array();
        $post_to['me'] = 'My Stream';
        $post_to['business'] = 'Business Page Stream';
        $post_to['community'] = 'Community Page Stream';

        $settings['googlePlusEmail'] = $this->settings['email'];
        $settings['googlePlusPostTo'] = $post_to[$this->boxSettings['post_to']];

        return $vars;
    }

    // ********************************************************************************* //

    public function parseBoxData($settings=array())
    {
        $settings = array_merge($this->boxSettings, $settings);
        $data = array();

        $data['message'] = $this->parseEntryTmpl($settings['message']);

        if ($settings['image_url']) {
            $data['image_url'] = $this->parseEntryTmpl($settings['image_url']);
        }

        if ($settings['link_url']) {
            $data['link_url'] = $this->parseEntryTmpl($settings['link_url']);
        }

        return $data;
    }

    // ********************************************************************************* //

    public function send($data)
    {
        if (function_exists('doPostToGooglePlus2') === false) {
            require_once PATH_THIRD.'campaigns/libraries/postToGooglePlus.php';
        }

        $loginError = doConnectToGooglePlus2($this->settings['email'], $this->settings['password']);

        if ($loginError) {
            $this->errorMsg = 'send(): '. $loginError;
            return false;
        }

        $message = $data['message'];
        $link = false;
        $pageId = false;
        $commId = false;

        // -----------------------------------------
        // Do we need to shorten URL's?
        // -----------------------------------------
        if ($this->box->shorten_urls == 1) {
            $res = ee()->campaigns->shortenUrls($message);

            if (!$res['success']) {
                $this->errorMsg = 'shortenUrl(): ' . $res['error'];
                return false;
            } else {
                $message = $res['text'];
            }
        }

        if (isset($data['image_url']) === true && $data['image_url'] != false) {
            $link = array('img' => $data['image_url']);
        }

        if (isset($data['link_url']) === true && $data['link_url'] != false) {
            $lnk = doGetGoogleUrlInfo2($data['link_url']);
            $link = $lnk;
        }

        //ee()->firephp->log($link);

        if ($this->boxSettings['post_to'] == 'me') {

        }

        if ($this->boxSettings['post_to'] == 'business') {
            if ($this->boxSettings['business_page_id']) $pageId = $this->boxSettings['business_page_id'];
            else {
                $this->errorMsg = 'execSendCampaign: Missing Business Page ID';
                return false;
            }
        }

        if ($this->boxSettings['post_to'] == 'community') {
            if ($this->boxSettings['community_page_id']) $commId = $this->boxSettings['community_page_id'];
            else {
                $this->errorMsg = 'execSendCampaign: Missing Business Page ID';
                return false;
            }
        }

        $ret = doPostToGooglePlus2($message, $link, $pageId, $commId);

        $response = array();

        if (isset($ret['post_id']) === true) {
            $response = explode('/', $ret['post_id']);
        }

        //['post_id'] => '103259424836461556378/posts/bMUNsZpjWot'

        $this->currentCampaignId = end($response);
        $this->campaignData = array('alpha_response' => $response);
        return true;
    }

    // ********************************************************************************* //

    public function delete($id)
    {

    }

    // ********************************************************************************* //

    public function execGetCampaignStatusHtml($dbCampaign)
    {
        $vdata = array();
        $vdata['campaign'] = $dbCampaign;
        $vdata['dbData'] = $dbCampaign->campaign_data;

        $vdata['url'] = 'https://plus.google.com/';

        if (isset($vdata['dbData']['alpha_response'][2])) {
            $user_id = $vdata['dbData']['alpha_response'][0];
            $type = $vdata['dbData']['alpha_response'][1];
            $post_id = $vdata['dbData']['alpha_response'][2];

            $vdata['url'] = "https://plus.google.com/{$user_id}/{$type}/{$post_id}";
        }

        ee()->firephp->log($vdata);
        $html = ee()->load->view('status/googleplus', $vdata, true);

        return $html;
    }

    // ********************************************************************************* //
}

/* End of file provider.mailchimp.php */
/* Location: ./system/expressionengine/third_party/campaigns/providers/provider.mailchimp.php */