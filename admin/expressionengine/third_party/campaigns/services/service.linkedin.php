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
class Service_linkedin extends CampaignService implements CampaignServiceInterface
{

    // Service Info
    public $title     =  'LinkedIn';
    public $name      =  'linkedin';
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

        $this->boxSettings['post_to'] = '';
        $this->boxSettings['message_title'] = '{title}';
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
        $this->settings['AuthUrl'] = 'http://www.devdemon.com/oauth_agent/campaigns_linkedin/';
        return $this->settings['AuthUrl'];
    }

    // ********************************************************************************* //

    public function testConnection()
    {
        $code = $this->API->fetch('GET', 'people/~');
        $res = $this->API->response['response'];

        if ($code != 200) {
            $this->errorMsg = $res->message;
            return false;
        }

        return true;
    }

    // ********************************************************************************* //

    public function getAccountData($response)
    {
        $code = $this->API->fetch('GET', 'people/~:(id,first-name,last-name,formatted-name,headline,pictureUrl,siteStandardProfileRequest)', array('secure-urls' => 'true'));
        $res = $this->API->response['response'];

        //ee()->firephp->log($this->API->response);

        if ($code != 200) {
            $this->errorMsg = $res->message;
            return false;
        }

        $html = '';
        $html .= '<img src="'.$res->pictureUrl.'" width="50px" height="50px" style="float:left;margin-right:10px">';
        $html .= '<strong>' . $res->formattedName . '</strong><br>';
        $html .= $res->headline.'<br>';
        $html .= '<a href="'.$res->siteStandardProfileRequest->url.'" target="_blank">View Profile</a>';

        $html .= '<br clear="all">';
        return $html;
    }

    // ********************************************************************************* //

    public function getBoxOptions()
    {
        $data =& $this->boxOptions;
        $data = array();

        $data['post_to'] = array();
        $data['post_to']['my_timeline'] = 'My Timeline';

        try {
            $code = $this->API->fetch('GET', 'people/~/group-memberships?&start=0&count=100');
            $res = $this->API->response['response'];

            if (isset($res->values) === true) {
                foreach ($res->values as $mb) {
                    $data['post_to']['Groups']['group:'.$mb->group->id] = $mb->group->name;
                }
            }

        } catch (Exception $e) {
            //ee()->firephp->log($e->getMessage());
        }

        try {
            $code = $this->API->fetch('GET', 'companies?is-company-admin=true&start=0&count=100');
            $res = $this->API->response['response'];

            if (isset($res->values) === true) {
                foreach ($res->values as $mb) {
                    $data['post_to']['Companies']['company:'.$mb->id] = $mb->name;
                }
            }

        } catch (Exception $e) {
            //ee()->firephp->log($e->getMessage());
        }

        return $data;
    }

    // ********************************************************************************* //

    public function getBoxAccountVars()
    {
        $vars = array();

        $code = $this->API->fetch('GET', 'people/~:(first-name,last-name,formatted-name,picture-url)?&secure-urls=true');
        $info = $this->API->response['response'];

        if ($code != 200) {
            $this->errorMsg = $res->message;
            return false;
        }

        //ee()->firephp->log($info);

        $vars['post_to'] = 'me'; // for handlebars

        if (!$this->boxSettings['post_to']) {
            $code = $this->API->fetch('GET', 'people/~:(first-name,last-name,formatted-name)');
            $infoTo = $this->API->response['response'];
        } else {
            if (strpos($this->boxSettings['post_to'], 'group:') === 0) {
                $id = str_replace('group:', '', $this->boxSettings['post_to']);
                $code = $this->API->fetch('GET', 'groups/'.$id);
                $vars['post_to'] = 'group';
            } elseif (strpos($this->boxSettings['post_to'], 'company:') === 0) {
                $id = str_replace('company:', '', $this->boxSettings['post_to']);
                $code = $this->API->fetch('GET', 'companies/'.$id);
                $vars['post_to'] = 'company';
            } else {
                $code = $this->API->fetch('GET', 'people/~:(first-name,last-name,formatted-name)');
                $this->API->response['response']->name = $this->API->response['response']->formattedName;
            }

            $infoTo = $this->API->response['response'];
        }

        $vars['ln_name'] = $info->formattedName;
        if (isset($info->pictureUrl) === true && $info->pictureUrl) $vars['ln_img_url'] = $info->pictureUrl;
        $vars['ln_to'] = $infoTo->name;

        return $vars;
    }

    // ********************************************************************************* //

    public function parseBoxData($settings=array())
    {
        $settings = array_merge($this->boxSettings, $settings);
        $data = array();

        $data['post_item'] = ($settings['post_item'] == 'yes') ? 'yes' : 'no';
        $data['message'] = $this->parseEntryTmpl($settings['message']);

        if ($settings['message_title']) {
            $data['message_title'] = $this->parseEntryTmpl($settings['message_title']);
        }

        if ($settings['item_desc']) {
            $data['item_desc'] = $this->parseEntryTmpl($settings['item_desc']);
        }

        if ($settings['item_title']) {
            $data['item_title'] = $this->parseEntryTmpl($settings['item_title']);
        }

        if ($settings['item_url']) {
            $data['item_url'] = $this->parseEntryTmpl($settings['item_url']);
        }

        if ($settings['item_image_url']) {
            $data['item_image_url'] = $this->parseEntryTmpl($settings['item_image_url']);
        }

        return $data;
    }

    // ********************************************************************************* //

    public function send($data)
    {
        $postItem = ($data['post_item'] == 'yes') ? true : false;

        // -----------------------------------------
        // Shorten URL's
        // -----------------------------------------
        if ($this->box->shorten_urls == 1) {
            $res = ee()->campaigns->shortenUrls($data['message']);

            if (!$res['success']) {
                $this->errorMsg = 'shortenUrl(): ' . $res['error'];
                return false;
            } else {
                $data['message'] = $res['text'];
            }
        }

        // -----------------------------------------
        // Create the post array
        // -----------------------------------------
        $post = array();
        $post['comment'] = $data['message'];
        $post['visibility']['code'] = 'anyone';

        if ($postItem === true) {
            $post['content']['title'] = $data['item_title'];
            $post['content']['submitted-url'] = $data['item_url'];
            $post['content']['submitted-image-url'] = $data['item_image_url'];
            $post['content']['description'] = $data['item_desc'];
        }

        // -----------------------------------------
        // Prepare
        // -----------------------------------------
        $postTo = explode(':', $this->boxSettings['post_to']);
        $postToId = end($postTo);
        $postToType = reset($postTo);

        // -----------------------------------------
        // Post: Group
        // -----------------------------------------
        if ($postToType == 'group') {

            // Groups have different keys
            unset($post['comment'], $post['visibility']);
            $post['summary'] = $data['message'];
            $post['title'] = $data['message_title'];

            $code = $this->API->fetch('POST', 'groups/'.$postToId.'/posts', ee()->campaigns_helper->generateJson($post), array('Content-Type' => 'application/json'));
            $status = $this->API->response['response'];

            //ee()->firephp->log($this->API->response);

            if ($code != 201) {
                $this->errorMsg = $status->message;
                return false;
            }

            // Status tends to be null! wtf?
            // So we need get the post id from the http header
            $location = $this->API->response['headers']['location'];
            $location = explode('/', $location);
            $id = end($location);
        }

        // -----------------------------------------
        // Post: Company
        // -----------------------------------------
        elseif ($postToType == 'company') {
            $code = $this->API->fetch('POST', 'companies/'.$postToId.'/shares', ee()->campaigns_helper->generateJson($post), array('Content-Type' => 'application/json'));
            $status = $this->API->response['response'];

            //ee()->firephp->log($this->API->response);

            if ($code != 201) {
                $this->errorMsg = $status->message;
                return false;
            }

            $id = $status->updateKey;
        }

        // -----------------------------------------
        // Post: Personal Feed
        // -----------------------------------------
        else {
            $code = $this->API->fetch('POST', 'people/~/shares', ee()->campaigns_helper->generateJson($post), array('Content-Type' => 'application/json'));
            $status = $this->API->response['response'];

            //ee()->firephp->log($this->API->response);

            if ($code != 201) {
                $this->errorMsg = $status->message;
                return false;
            }

            $id = $status->updateKey;
        }

        $this->currentCampaignId = $id;
        $this->campaignData = array('post_type' => $postToType, 'post_to_id' => $postToId, 'data' => $status);
        return true;
    }

    // ********************************************************************************* //

    public function delete($id)
    {

    }

    // ********************************************************************************* //

    public function execGetCampaignStatusHtml($dbCampaign)
    {
        $data = $dbCampaign->campaign_data;
        $id = $dbCampaign->service_cid;
        $html = '';
        $res = false;
        $resErrorMsg = '';

        $item = array();

        try {
            if ($data['post_type'] == 'group') {
                $code = $this->API->fetch('GET', "posts/{$id}:(id,type,category,creator,title,summary,creation-timestamp,relation-to-viewer:(is-following,is-liked,available-actions),likes,comments,attachment,site-group-post-url)");
                $res = $this->API->response['response'];

                if ($code > 299) {
                    $resErrorMsg = $res->message;
                } else {
                    $item['title'] = $res->title;
                    $item['summary'] = $res->summary;
                    $item['link'] = $res->siteGroupPostUrl;
                    $item['creator'] = $res->creator->firstName . ' ' . $res->creator->lastName;
                    $item['creatorUrl'] = 'http://www.linkedin.com/profile/view?id='.$res->creator->id;
                    $item['likes'] = $res->likes->_total;
                    $item['comments'] = $res->comments->_total;
                }
            } elseif ($data['post_type'] == 'company') {
                $code = $this->API->fetch('GET', "companies/{$data['post_to_id']}/updates/key={$id}:(likes,numLikes,updateComments,updateContent)");
                $res = $this->API->response['response'];

                if ($code > 299) {
                    $resErrorMsg = $res->message;
                } else {
                    $item['summary'] = $res->updateContent->companyStatusUpdate->share->comment;
                    $item['link'] = "http://www.linkedin.com/company/{$data['post_to_id']}/comments?topic=5795649663142998016&type=U&scope={$data['post_to_id']}&stype=C&a=cnlr";
                    $item['creator'] = $res->updateContent->company->name;
                    $item['creatorUrl'] = "http://www.linkedin.com/company/{$data['post_to_id']}/";
                    $item['likes'] = $res->numLikes;
                    $item['comments'] = $res->updateComments->_total;
                }
            } else {
                $code = $this->API->fetch('GET', "people/~/network/updates/key={$id}");
                $res = $this->API->response['response'];

                //ee()->firephp->log($this->API->response);

                if ($code > 299) {
                    $resErrorMsg = $res->message;
                } else {
                    $idArr = explode('-', $id);

                    $status = $res->updateContent->person;
                    $item['summary'] = $status->currentShare->comment;
                    $item['link'] = 'http://www.linkedin.com/nhome/updates?topic='.$idArr[2];
                    $item['creator'] = $status->firstName . ' '. $status->lastName;
                    $item['creatorUrl'] = 'http://www.linkedin.com/profile/view?id='.$status->id;
                    $item['likes'] = $res->numLikes;
                    $item['comments'] = $res->updateComments->_total;
                }
            }

        } catch (Exception $e) {
            $resErrorMsg = $e->getMessage();
        }

        $vdata = array();
        $vdata['campaign'] = $dbCampaign;
        $vdata['dbData'] = $data;
        $vdata['lnData'] = $res;
        $vdata['lnError'] = $resErrorMsg;
        $vdata['item'] = $item;

        //ee()->firephp->log($vdata);

        $html = ee()->load->view('status/linkedin', $vdata, true);


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
        $this->API->config['base_uri'] = 'https://api.linkedin.com/v1/';
        $this->API->config['global_url_params']['oauth2_access_token'] = $this->settings['access_token'];
        $this->API->config['global_url_params']['format'] = 'json';
    }

    // ********************************************************************************* //
}

/* End of file provider.linkedin.php */
/* Location: ./system/expressionengine/third_party/campaigns/providers/provider.linkedin.php */