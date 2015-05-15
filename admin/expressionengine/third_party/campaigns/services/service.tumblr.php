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
class Service_tumblr extends CampaignService implements CampaignServiceInterface
{

    // Service Info
    public $title     =  'Tumblr';
    public $name      =  'tumblr';
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

        $this->boxSettings['post_to'] = '';

        $this->boxSettings['post_type'] = 'text';
        $this->boxSettings['post_state'] = 'published';
        $this->boxSettings['post_format'] = 'html';

        $this->boxSettings['text_title'] = '{title}';
        $this->boxSettings['text_body'] = "New Post: {title}\nCheck it out: {comment_url_title_auto_path}";

        $this->boxSettings['photo_caption'] = '{title}';
        $this->boxSettings['photo_link'] = '{comment_url_title_auto_path}';
        $this->boxSettings['photo_source'] = '';

        $this->boxSettings['link_title'] = '{title}';
        $this->boxSettings['link_url'] = '{comment_url_title_auto_path}';
        $this->boxSettings['link_desc'] = "New Post: {title}\nCheck it out";

        parent::__construct($settings, $boxSettings, $init);

        if ($init) $this->init();
    }

    // ********************************************************************************* //

    public function getAuthorizationUrl()
    {
        $this->settings['AuthUrl'] = 'http://www.devdemon.com/oauth_agent/campaigns_tumblr/';
        return $this->settings['AuthUrl'];
    }

    // ********************************************************************************* //

    public function testConnection()
    {
        $http = $this->API->user_request(array(
            'url' => $this->API->url('v2/user/info', '')
        ));

        $res = ee()->campaigns_helper->decodeJson($this->API->response['response']);
        //ee()->firephp->log($res);

        if ($http != 200) {
            $this->errorMsg = $res->meta->msg;
            return false;
        }

        return true;
    }

    // ********************************************************************************* //

    public function getAccountData($response)
    {
        $http = $this->API->user_request(array(
            'url' => $this->API->url('v2/user/info', '')
        ));

        $res = ee()->campaigns_helper->decodeJson($this->API->response['response']);

        if ($http != 200) {
            $this->errorMsg = $res->meta->msg;
            return false;
        }

        //ee()->firephp->log($res);

        $html = '';
        $html .= '<strong>Username:</strong> '.$res->response->user->name.'<br>';
        $html .= '<strong>Likes:</strong> '.$res->response->user->likes.'<br>';
        $html .= '<strong>Following:</strong> '.$res->response->user->following;
        $html .= '<br clear="all">';
        return $html;
    }

    // ********************************************************************************* //

    public function getBoxOptions()
    {
        $data =& $this->boxOptions;
        $data = array();

        $data['blogs'] = array();

        $data['post_types'] = array();
        $data['post_types']['text'] = 'Text Post';
        $data['post_types']['photo'] = 'Photo Post';
        $data['post_types']['link'] = 'Link Post';

        $http = $this->API->user_request(array(
            'url' => $this->API->url('v2/user/info', '')
        ));

        $res = ee()->campaigns_helper->decodeJson($this->API->response['response']);

        if (isset($res->response->user->blogs) === true) {
            foreach ($res->response->user->blogs as $blog) {
                $data['blogs'][$blog->name] = $blog->title;
            }

        }

        return $data;
    }

    // ********************************************************************************* //

    public function getBoxAccountVars()
    {
        $vars = array();

        $http = $this->API->user_request(array(
            'url' => $this->API->url("v2/blog/{$this->boxSettings['post_to']}.tumblr.com/info", ''),
            'params' => array('api_key' => ee()->campaigns->settings['tumblr_keys']['consumer_key']),
        ));

        $res = ee()->campaigns_helper->decodeJson($this->API->response['response']);

        if ($http != 200) {
            $this->errorMsg = $res->meta->msg;
            return false;
        }

        $vars['tumblrBlogTitle'] = $res->response->blog->title;;

        return $vars;
    }

    // ********************************************************************************* //

    public function parseBoxData($settings=array())
    {
        $settings = array_merge($this->boxSettings, $settings);
        $data = array();

        $data['post_type'] = $settings['post_type'];

        if ($data['post_type'] == 'text') {
            $data['text_title'] = $this->parseEntryTmpl($settings['text_title']);
            $data['text_body'] = $this->parseEntryTmpl($settings['text_body']);
        }

        if ($data['post_type'] == 'photo') {
            $data['photo_caption'] = $this->parseEntryTmpl($settings['photo_caption']);
            $data['photo_link'] = $this->parseEntryTmpl($settings['photo_link']);
            $data['photo_source'] = $this->parseEntryTmpl($settings['photo_source']);
        }

        if ($data['post_type'] == 'link') {
            $data['link_title'] = $this->parseEntryTmpl($settings['link_title']);
            $data['link_url'] = $this->parseEntryTmpl($settings['link_url']);
            $data['link_desc'] = $this->parseEntryTmpl($settings['link_desc']);
        }

        return $data;
    }

    // ********************************************************************************* //

    public function send($data)
    {
        $params = array();
        $params['type'] = $data['post_type'];
        $params['state'] = 'published';
        $params['format'] = 'html';

        if ($data['post_type'] == 'text') {
            $params['title'] = $data['text_title'];
            $params['body'] = $data['text_body'];
        }

        if ($data['post_type'] == 'photo') {
            $params['caption'] = $data['photo_caption'];
            $params['link'] = $data['photo_link'];
            $params['source'] = $data['photo_source'];
        }

        if ($data['post_type'] == 'link') {
            $params['title'] = $data['link_title'];
            $params['url'] = $data['link_url'];
            $params['description'] = $data['link_desc'];
        }

        // -----------------------------------------
        // Do we need to shorten URL's?
        // -----------------------------------------
        if ($this->box->shorten_urls == 1) {
            $text = '';

            switch ($data['post_type']) {
                case 'text':
                    $text = $params['body'];
                    break;
                case 'photo':
                    $text = $params['link'];
                    break;
                case 'link':
                    $text = $params['url'];
                    break;
            }

            $res = ee()->campaigns->shortenUrls($text);

            if (!$res['success']) {
                $this->errorMsg = 'shortenUrl(): ' . $res['error'];
                return false;
            }

            switch ($data['post_type']) {
                case 'text':
                    $params['body'] = $res['text'];
                    break;
                case 'photo':
                    $params['link'] = $res['text'];
                    break;
                case 'link':
                    $params['url'] = $res['text'];
                    break;
            }
        }

        $http = $this->API->user_request(array(
            'url' => $this->API->url("v2/blog/{$this->boxSettings['post_to']}.tumblr.com/post", ''),
            'method' => 'POST',
            'params' => $params,
        ));

        //ee()->firephp->log($params);

        $res = ee()->campaigns_helper->decodeJson($this->API->response['response']);

        //ee()->firephp->log($this->API->response);

        if ($http != 201) {
            if (isset($res->response->errors) === true) {
                $this->errorMsg = 'execSendCampaign: ';
                foreach ($res->response->errors as $key => $error) {
                    $this->errorMsg .= $error;
                }
            }

            return false;
        }

        // Grab the new post
        $http = $this->API->user_request(array(
            'url' => $this->API->url("v2/blog/{$this->boxSettings['post_to']}.tumblr.com/posts", ''),
            'params' => array(
                'api_key' => ee()->campaigns->settings['tumblr_keys']['consumer_key'],
                'id' => $res->response->id,
            ),
        ));

        if ($http == 200) {
            $post = ee()->campaigns_helper->decodeJson($this->API->response['response']);

            if (isset($post->response->posts) === true && empty($post->response->posts) === false) {
                $post = reset($post->response->posts);
            }
        } else {
            $post = array();
        }

        $this->currentCampaignId = $res->response->id;
        $this->campaignData = $post;
        return true;
    }

    // ********************************************************************************* //

    public function delete($id)
    {

    }

    // ********************************************************************************* //

    public function execGetCampaignStatusHtml($dbCampaign)
    {
        /*
        $http = $this->API->user_request(array(
            'url' => $this->API->url("v2/blog/{$this->boxSettings['post_to']}.tumblr.com/posts", ''),
            'method' => 'GET',
            'params' => array('id' => $campaign->service_cid),
        ));
        */

        $vdata = array();
        $vdata['campaign'] = $dbCampaign;
        $vdata['dbData'] = $dbCampaign->campaign_data;
//ee()->firephp->log($vdata);
        $html = ee()->load->view('status/tumblr', $vdata, true);

        return $html;
    }

    // ********************************************************************************* //

    private function init()
    {
        if (isset($this->API)) return true;

        if (class_exists('tmhOAuth') === false) {
            require_once PATH_THIRD.'campaigns/libraries/oauth/tmhOAuth.php';
        }

        $config = array();
        $config['host'] = 'api.tumblr.com';
        $config['consumer_key'] = ee()->campaigns->settings['tumblr_keys']['consumer_key'];
        $config['consumer_secret'] = ee()->campaigns->settings['tumblr_keys']['consumer_secret'];
        $config['token'] = $this->settings['oauth_token'];
        $config['secret'] = $this->settings['oauth_token_secret'];

        $this->API = new tmhOAuth($config);

    }

    // ********************************************************************************* //
}

/* End of file service.mailchimp.php */
/* Location: ./system/expressionengine/third_party/campaigns/services/service.mailchimp.php */