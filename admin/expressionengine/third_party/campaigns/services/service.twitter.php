<?php if (!defined('BASEPATH')) die('No direct script access allowed');

/**
 * Campaigns TWITTER service
 *
 * @package         DevDemon_Campaigns
 * @author          DevDemon <http://www.devdemon.com> - Lead Developer @ Parscale Media
 * @copyright       Copyright (c) 2007-2014 Parscale Media <http://www.parscale.com>
 * @license         http://www.devdemon.com/license/
 * @link            http://www.devdemon.com/campaigns/
 */
class Service_twitter extends CampaignService implements CampaignServiceInterface
{

    // Service Info
    public $title     =  'Twwitter';
    public $name      =  'twitter';
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
        $this->settings['oauth_token'] = '';
        $this->settings['oauth_token_secret'] = '';

        $this->boxSettings['tweet'] = 'New Post: {title} - {comment_url_title_auto_path}';

        parent::__construct($settings, $boxSettings, $init);

        if ($init) $this->init();
    }

    // ********************************************************************************* //

    public function getAuthorizationUrl()
    {
        $this->settings['AuthUrl'] = 'http://www.devdemon.com/oauth_agent/campaigns_twitter/';
        return $this->settings['AuthUrl'];
    }

    // ********************************************************************************* //

    public function testConnection()
    {
        $http = $this->TW->user_request(array(
            'url' => $this->TW->url('1.1/account/verify_credentials')
        ));

        $res = ee()->campaigns_helper->decodeJson($this->TW->response['response']);

        if (isset($res->errors) === true) {
            //$this->errorMsg .= 'testConnection: ';
            foreach ($res->errors as $key => $error) {
                $this->errorMsg .= $error->message;
            }

            return false;
        }

        return true;
    }

    // ********************************************************************************* //

    public function getAccountData($response)
    {
        $http = $this->TW->user_request(array(
            'url' => $this->TW->url('1.1/account/verify_credentials')
        ));

        $res = ee()->campaigns_helper->decodeJson($this->TW->response['response']);

        //ee()->firephp->log($res);

        $html = '';

        $html .= '<img src="'.$res->profile_image_url_https.'" width="50px" height="50px" style="float:left;margin-right:10px">';
        $html .= '<strong>@' . $res->screen_name . '</strong><br>';
        $html .= '<strong>FR:</strong> '.$res->followers_count.' ';
        $html .= '<strong>FL:</strong> '.$res->friends_count.' ';
        $html .= '<strong>TW:</strong> '.$res->statuses_count.'<br>';
        $html .= '<a href="http://twitter.com/'.$res->screen_name.'" target="_blank">View Profile</a>';
        $html .= '<br clear="all">';
        return $html;
    }

    // ********************************************************************************* //

    public function getBoxAccountVars()
    {
        $vars = array();

        $http = $this->TW->user_request(array(
            'url' => $this->TW->url('1.1/account/verify_credentials')
        ));

        $info = ee()->campaigns_helper->decodeJson($this->TW->response['response']);

        if (isset($info->errors) === true) {
            $this->errorMsg .= 'getBoxAccountVars: ';
            foreach ($info->errors as $key => $error) {
                $this->errorMsg .= $error->message;
            }

            return $vars;
        }

        // In some remote cases, the return is empty.
        if (isset($info->screen_name) === false) {
            return $vars;
        }

        $vars['twitterUsername'] = $info->screen_name;
        $vars['twitterFollowers'] = $info->followers_count;
        $vars['twitterFriends'] = $info->friends_count;
        $vars['twitterTweets'] = $info->statuses_count;
        $vars['twitterImgUrl'] = $info->profile_image_url_https;

        return $vars;
    }

    // ********************************************************************************* //

    public function parseBoxData($settings=array())
    {
        $settings = array_merge($this->boxSettings, $settings);
        $data = array();

        // Parse tweet
        $data['tweet'] = $this->parseEntryTmpl($settings['tweet']);

        return $data;
    }

    // ********************************************************************************* //

    public function send($data)
    {
        $params = array();
        $params['status'] = $data['tweet'];

        // Do we need to shorten URL's?
        if ($this->box->shorten_urls == 1) {
            $res = ee()->campaigns->shortenUrls($params['status']);

            if (!$res['success']) {
                $this->errorMsg = 'shortenUrl(): ' . $res['error'];
                return false;
            } else {
                $params['status'] = $res['text'];
            }
        }

        $http = $this->TW->user_request(array(
            'url' => $this->TW->url('1.1/statuses/update'),
            'method' => 'POST',
            'params' => $params,
        ));

        $status = ee()->campaigns_helper->decodeJson($this->TW->response['response']);

        if (isset($status->errors) === true) {
            $this->errorMsg = 'send(): ';
            foreach ($status->errors as $key => $error) {
                $this->errorMsg .= $error->message;
            }

            return false;
        }

        $this->currentCampaignId = $status->id_str;
        $this->campaignData = $status;
        return true;
    }

    // ********************************************************************************* //

    public function delete($id)
    {

    }

    // ********************************************************************************* //

    public function execGetCampaignStatusHtml($campaign)
    {
        $tweetId = $campaign->service_cid;

        $http = $this->TW->user_request(array(
            'url' => $this->TW->url('1.1/statuses/show'),
            'params' => array('id' => $tweetId)
        ));

        $status = ee()->campaigns_helper->decodeJson($this->TW->response['response']);

        if (isset($status->errors) === true) {
            $this->errorMsg = '';
            foreach ($status->errors as $key => $error) {
                $this->errorMsg .= $error->message;
            }
        }

        $vdata = array();
        $vdata['campaign'] = $campaign;
        $vdata['dbData'] = $campaign->campaign_data;
        $vdata['twData'] = $status;
        $vdata['twError'] = $this->errorMsg;

        $html = ee()->load->view('status/twitter', $vdata, true);

        return $html;
    }

    // ********************************************************************************* //

    private function init()
    {
        if (isset($this->TW)) return true;

        if (class_exists('tmhOAuth') === false) {
            require_once PATH_THIRD.'campaigns/libraries/oauth/tmhOAuth.php';
        }

        $config = array();
        $config['base_uri'] = 'https://api.twitter.com/1.1/';
        $config['consumer_key'] = ee()->campaigns->settings['twitter_keys']['consumer_key'];
        $config['consumer_secret'] = ee()->campaigns->settings['twitter_keys']['consumer_secret'];
        $config['token'] = $this->settings['oauth_token'];
        $config['secret'] = $this->settings['oauth_token_secret'];

        $this->TW = new tmhOAuth($config);

    }

    // ********************************************************************************* //
}

/* End of file service.twitter.php */
/* Location: ./system/expressionengine/third_party/campaigns/services/service.twitter.php */
