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
class Service_facebook extends CampaignService implements CampaignServiceInterface
{

    // Service Info
    public $title     =  'Facebook';
    public $name      =  'facebook';
    public $version   =  '1.0';
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
        $this->settings['access_token'] = '';

        $this->boxSettings['post_as'] = '';
        $this->boxSettings['post_to'] = '';
        $this->boxSettings['message'] = "New Post: {title}\nCheck it out: {comment_url_title_auto_path}";
        $this->boxSettings['post_item'] = 'no';
        $this->boxSettings['item_title'] = '{title}';
        $this->boxSettings['item_url'] = '{comment_url_title_auto_path}';
        $this->boxSettings['item_image_url'] = '';
        $this->boxSettings['item_desc'] = '';

        parent::__construct($settings, $boxSettings, $init);

        if ($init) $this->init();
    }

    // ********************************************************************************* //

    public function getAuthorizationUrl()
    {
        $this->settings['AuthUrl'] = 'http://www.devdemon.com/oauth_agent/campaigns_facebook/';
        return $this->settings['AuthUrl'];
    }

    // ********************************************************************************* //

    public function testConnection()
    {
        $code = $this->API->fetch('GET', 'me');
        $res = $this->API->response['response'];

        //ee()->firephp->log($this->API->response);

        if ($code != 200) {
            $this->errorMsg = $res->error->message;
            return false;
        }

        return true;
    }

    // ********************************************************************************* //

    public function getAccountData($response)
    {
        $code = $this->API->fetch('GET', 'me');
        $res = $this->API->response['response'];

        if ($code != 200) {
            $this->errorMsg = $res->error->message;
            return false;
        }

        //ee()->firephp->log($res);

        $html = '';
        $html .= '<img src="https://graph.facebook.com/'.$res->id.'/picture?type=square" width="50px" height="50px" style="float:left;margin-right:10px">';
        $html .= '<strong>' . $res->name . '</strong><br>';
        $html .= 'ID: '.$res->id.'<br>';
        $html .= '<a href="'.$res->link.'" target="_blank">View Profile</a>';


        $html .= '<br clear="all">';
        return $html;
    }

    // ********************************************************************************* //

    public function getBoxOptions()
    {
        $data =& $this->boxOptions;
        $data = array();

        $data['accounts'] = array();
        $data['post_to'] = array();

        try {
            $code = $this->API->fetch('GET', 'me');
            $res = $this->API->response['response'];

            $data['accounts'][$res->id] = $res->name;
            $data['post_to']['me:'.$res->id] = 'My Timeline';
        } catch (Exception $e) {

        }

        try {
            $code = $this->API->fetch('GET', 'me/accounts?limit(1000)');
            $res = $this->API->response['response'];

            if (isset($res->data[0]) === true) {
                foreach ($res->data as $account) {
                    $data['accounts'][$account->id] = $account->name;
                    $data['post_to']['Page']['page:'.$account->id] = $account->name;
                }
            }
        } catch (Exception $e) {

        }

        try {
            $code = $this->API->fetch('GET', 'me/groups?limit(1000)');
            $res = $this->API->response['response'];

            if (isset($res->data[0]) === true) {
                foreach ($res->data as $account) {
                    $data['post_to']['Group']['group:'.$account->id] = $account->name;
                }
            }
        } catch (Exception $e) {

        }

        return $data;
    }

    // ********************************************************************************* //

    public function getBoxAccountVars()
    {
        $vars = array();

        if (!$this->boxSettings['post_as']) {
            $code = $this->API->fetch('GET', 'me');

            if ($code != 200) {
                $this->errorMsg = $this->API->response->error->message;
                return false;
            }
            $infoFrom = $this->API->response['response'];
            $id = $infoFrom->id;

        } else {
            $id = $this->boxSettings['post_as'];
            $code = $this->API->fetch($id, 'me');

            if ($code != 200) {
                $this->errorMsg = $this->API->response->error->message;
                return false;
            }

            $infoFrom = $this->API->response['response'];
        }

        if (!$this->boxSettings['post_to']) {
            $code = $this->API->fetch('GET', 'me');

            if ($code != 200) {
                $this->errorMsg = $this->API->response->error->message;
                return false;
            }

            $infoTo = $this->API->response['response'];
            $postToId = $infoTo->id;
        } else {
            $postToId = $this->boxSettings['post_to'];
            $postToId = end(explode(':', $postToId));

            $code = $this->API->fetch('GET', $postToId);

            if ($code != 200) {
                $this->errorMsg = $this->API->response->error->message;
                return false;
            }

            $infoTo = $this->API->response['response'];
        }

        $vars['facebookName'] = $infoFrom->name;
        $vars['facebookImgUrl'] = 'https://graph.facebook.com/'.$id.'/picture?type=square';
        $vars['facebookTo'] = $infoTo->name;

        return $vars;
    }

    // ********************************************************************************* //

    public function parseBoxData($settings=array())
    {
        $settings = array_merge($this->boxSettings, $settings);
        $data = array();

        $data['post_item'] = ($settings['post_item'] == 'yes') ? 'yes' : 'no';
        $data['message'] = $this->parseEntryTmpl($settings['message']);
        $data['item_title'] = $settings['item_title'] ? $this->parseEntryTmpl($settings['item_title']) : '';
        $data['item_desc'] = $settings['item_desc'] ? $this->parseEntryTmpl($settings['item_desc']) : '';
        $data['item_url'] = $settings['item_url'] ? $this->parseEntryTmpl($settings['item_url']) : '';
        $data['item_image_url'] = $settings['item_image_url'] ? $this->parseEntryTmpl($settings['item_image_url']) : '';


        return $data;
    }

    // ********************************************************************************* //

    public function send($data)
    {
        $postItem = ($data['post_item'] == 'yes') ? true : false;

        // -----------------------------------------
        // Create the post array
        // -----------------------------------------
        $post = array();
        $post['message'] = $data['message'];

        // -----------------------------------------
        // Do we need to shorten URL's?
        // -----------------------------------------
        if ($this->box->shorten_urls == 1) {
            $res = ee()->campaigns->shortenUrls($post['message']);

            if (!$res['success']) {
                $this->errorMsg = 'shortenUrl(): ' . $res['error'];
                return false;
            } else {
                $post['message'] = $res['text'];
            }
        }

        // -----------------------------------------
        // Post Item
        // -----------------------------------------
        if ($postItem === true) {
            $post['name'] = $data['item_title'];
            $post['link'] = $data['item_url'];
            $post['picture'] = $data['item_image_url'];
            $post['description'] = $data['item_desc'];

            //'actions' => json_encode(array('name' => $action_name,'link' => $action_link))
        }

        $post['access_token'] = $this->settings['access_token'];

        // -----------------------------------------
        // Get the special Access Code for this user
        // -----------------------------------------
        $code = $this->API->fetch('GET', "{$this->boxSettings['post_as']}?fields=access_token");

        if ($code == 200) {
            $post['access_token'] = $this->API->response['response']->access_token;
        }

        // -----------------------------------------
        // Prepare
        // -----------------------------------------
        $postTo = explode(':', $this->boxSettings['post_to']);
        $postToId = end($postTo);
        $postToType = reset($postTo);

        // -----------------------------------------
        // Post to FB
        // -----------------------------------------
        if (!$this->boxSettings['post_to']) {
            $code = $this->API->fetch('POST', 'me/feed', $post);
            $postToType = 'me';
        } else {
            $postTo = explode(':', $this->boxSettings['post_to']);
            $postToId = end($postTo);

            $code = $this->API->fetch('POST', $postToId.'/feed', $post);
            $postToType = reset($postTo);
        }

        $res = $this->API->response['response'];

        //ee()->firephp->log($this->API->response);

        if ($code != 200) {
            $this->errorMsg = $res->error->message;
            return false;
        }

        $status = $this->API->response['response'];
        $id = $status->id;

        // -----------------------------------------
        // Grab the post again from FB for storage
        // -----------------------------------------

        // Give facebook a little time
        sleep(1);

        $code = $this->API->fetch('GET', $status->id);

        if ($code == 200) {
            $status = $this->API->response['response'];
        }

        //ee()->firephp->log($status);

        $this->currentCampaignId = $id;
        $this->campaignData = array('post_type' => $postToType, 'post_to_id' => $postToId, 'data' => $status);
        return true;
    }

    // ********************************************************************************* //

    public function delete($id)
    {

    }

    // ********************************************************************************* //

    public function execGetCampaignStatusHtml($campaign)
    {
        $data = $campaign->campaign_data;
        $id = $campaign->service_cid;
        $resErrorMsg = '';
        $fbLikes = 'N/A';
        $fbComments = 'N/A';

        $stats = false;

        $code = $this->API->fetch('GET', $id);
        $res = $this->API->response['response'];

        if ($code != 200) {
            $resErrorMsg = $res->error->message;
        }

        if ($data['post_type'] != 'groups' && !$resErrorMsg) {
            $code = $this->API->fetch('GET', 'fql', array('q' => "SELECT comment_info, like_info, message, post_id, actor_id FROM stream WHERE post_id = '{$id}'"));
            $stats = $this->API->response['response'];

            if (isset($stats->data[0]->message) == true) {
                $stats = $stats->data[0];
            } else {
                $stats = false;
            }
        }

        $vdata = array();
        $vdata['campaign'] = $campaign;
        $vdata['dbData'] = $data;
        $vdata['fbData'] = $res;
        $vdata['fbError'] = $resErrorMsg;
        $vdata['fbStats'] = $stats;

        $html = ee()->load->view('status/facebook', $vdata, true);

        return $html;
    }

    // ********************************************************************************* //

    private function init()
    {
        if (isset($this->API)) return true;

        if (class_exists('Campaigns_curl') === false) {
            require_once PATH_THIRD.'campaigns/libraries/campaigns_curl.php';
        }

        $this->API = new Campaigns_curl();
        $this->API->config['host'] = 'graph.facebook.com';
        $this->API->config['global_url_params']['access_token'] = $this->settings['access_token'];
    }

    // ********************************************************************************* //
}

/* End of file provider.mailchimp.php */
/* Location: ./system/expressionengine/third_party/campaigns/providers/provider.mailchimp.php */
