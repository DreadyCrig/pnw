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
class Service_mailchimp extends CampaignService implements CampaignServiceInterface, EmailServiceInterface
{

    // Service Info
    public $title     =  'Mailchimp';
    public $name      =  'mailchimp';
    public $type      =  'email';

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
        $this->settings['api_key'] = '';
        $this->settings['access_token'] = '';
        $this->settings['api_dc'] = '';
        $this->settings['api_endpoint'] = '';

        $this->settings['inline_css'] = true;
        $this->settings['auto_footer'] = false;
        $this->settings['generate_text'] = false;

        // Weird conditional, i know. ee() is only available after we initialize the parent construct()
        $this->settings['email_from_name'] = function_exists('ee') ? ee()->config->item('site_label') : get_instance()->config->item('site_label');
        $this->settings['email_from'] = function_exists('ee') ? ee()->config->item('webmaster_email') : get_instance()->config->item('webmaster_email');
        $this->settings['reply_to_email'] = function_exists('ee') ? ee()->config->item('webmaster_email') : get_instance()->config->item('webmaster_email');

        parent::__construct($settings, $boxSettings, $init);

        if ($init) $this->init();
    }

    // ********************************************************************************* //

    public function getAuthorizationUrl()
    {
        $this->settings['AuthUrl'] = 'http://www.devdemon.com/oauth_agent/campaigns_mailchimp/';
        return $this->settings['AuthUrl'];
    }

    // ********************************************************************************* //

    public function testConnection()
    {
        $code = $this->API->fetch('GET', 'helper/ping');
        $res = $this->API->response['response'];

        if ($code != 200) {
            $this->errorMsg = $res->error;
            return false;
        }

        return true;
    }

    // ********************************************************************************* //

    public function getAccountData($response)
    {
        $code = $this->API->fetch('GET', 'helper/account-details');
        $account = $this->API->response['response'];

        //ee()->firephp->log($account);

        $code = $this->API->fetch('GET', 'users/profile');
        $profile = $this->API->response['response'];

        //ee()->firephp->log($profile);

        if ($code != 200) {
            $this->errorMsg = $res->error;
            return false;
        }

        $html = '';
        $html .= '<img src="'.$profile->avatar.'" width="50px" height="50px" style="float:left;margin-right:10px">';
        $html .= '<strong>' . $profile->name . '</strong><br>';
        $html .= $account->contact->company.'<br>';
        $html .= 'Role: '.$profile->role;
        //$html .= '<a href="'.$res->siteStandardProfileRequest->url.'" target="_blank">View Profile</a>';

        $html .= '<br clear="all">';
        return $html;
    }

    // ********************************************************************************* //

    public function getLists()
    {
        $code = $this->API->fetch('GET', 'lists/list');
        $res = $this->API->response['response'];

        //ee()->firephp->log($this->API->response);

        if ($code != 200) {
            $this->errorMsg = $res->error;
            return false;
        }

        $items = array();

        foreach ($res->data as $list) {
            //var_dump($list);
            $item = array();
            $item['id'] = $list->id;
            $item['label'] = $list->name;
            $items[] = $item;
        }

        return $items;
    }

    // ********************************************************************************* //

    public function create($data)
    {
        $options = array();
        $options['list_id'] = key($data['lists']);
        $options['subject'] = str_replace('[TEST]', '', $data['email_subject']);
        $options['from_email'] = $this->settings['email_from'];
        $options['from_name'] = $this->settings['email_from_name'];
        $options['to_name'] = $this->settings['email_to'];
        $options['title'] = $data['campaign_label'];
        $options['auto_footer'] = $this->settings['auto_footer'];
        $options['inline_css'] = $this->settings['inline_css'];
        $options['generate_text'] = $this->settings['generate_text'];

        $params['type'] = 'regular';
        $params['options'] = $options;
        $params['content']['html'] = trim(ee()->campaigns_helper->fetchUrl($this->tmplUrlHtml));
        if ($this->tmplUrlText) $params['content']['text'] = trim(ee()->campaigns_helper->fetchUrl($this->tmplUrlText));

        $code = $this->API->fetch('POST', 'campaigns/create', $params);
        $res = $this->API->response['response'];

        //ee()->firephp->log($this->API->response);

        if ($code != 200) {
            $this->errorMsg = 'create(): ' . $res->error;
            return false;
        }

        $this->currentCampaign = $res;
        $this->currentCampaignId = trim($res->id);
        $this->campaignData = $res;

        return true;
    }

    // ********************************************************************************* //

    public function test()
    {
        $code = $this->API->fetch('POST', 'campaigns/send-test', array('cid' => $this->currentCampaignId, 'test_emails' => $this->testEmails));
        $res = $this->API->response['response'];

        //ee()->firephp->log($this->API->response);

        if ($code != 200) {
            $this->errorMsg = 'test(): ' . $res->error;
            return false;
        }

        return true;
    }

    // ********************************************************************************* //

    public function schedule()
    {
        $code = $this->API->fetch('POST', 'campaigns/schedule', array('cid' => $this->currentCampaignId, 'schedule_time' => ee()->campaigns_helper->formatDate('%Y-%m-%d %H:%i:%s', $this->scheduleDate, false)));
        $res = $this->API->response['response'];

        if ($code != 200) {
            $this->errorMsg = 'schedule(): ' . $res->error;
            return false;
        }

        return true;
    }

    // ********************************************************************************* //

    public function send($data)
    {
        $code = $this->API->fetch('POST', 'campaigns/send', array('cid' => $this->currentCampaignId));
        $res = $this->API->response['response'];

        //ee()->firephp->log($this->API->response);

        if ($code != 200) {
            $this->errorMsg = 'send(): ' . $res->error;
            return false;
        }

        return true;
    }

    // ********************************************************************************* //

    public function delete($id)
    {
        $code = $this->API->fetch('POST', 'campaigns/delete', array('cid' => $id));
        $res = $this->API->response['response'];

        //ee()->firephp->log($this->API->response);

        if ($code != 200) {
            $this->errorMsg = 'delete(): ' . $res->error;
            return false;
        }

        return true;
    }

    // ********************************************************************************* //

    public function execGetCampaignStatusHtml($dbCampaign)
    {
        $res = false;
        $resErrorMsg = '';

        $code = $this->API->fetch('POST', 'campaigns/list', array('filters' =>  array('campaign_id' => $dbCampaign->service_cid) ));
        $request = $this->API->response['response'];

        if ($code != 200) {
            $resErrorMsg = 'execGetCampaignStatusHtml: ' . $request->error;
            #
        }

        if (isset($request->data[0]->id) === true) {
            $res = $request->data[0];
        } else {
            $resErrorMsg = 'MailChimp could not find the campaign (by id)';
        }

        /*
        try {
            $res = $this->MC->campaigns->getList(array('campaign_id' => ));
            if (isset($res['data'][0]['id']) === true) {
                $res = $res['data'][0];
            } else {
                $resErrorMsg = 'MailChimp could not find the campaign (by id)';
            }
        } catch (Exception $e) {
            $resErrorMsg = $e->getMessage();
        }
        */

        if ($res !== false) {
            $mcData = array();
            $mcData['web_url'] = $res->archive_url_long;
            $mcData['status'] = $res->status;
            $mcData['email_subject'] = $res->subject;
            $mcData['date_created'] = strtotime($res->create_time);
            $mcData['date_scheduled'] = strtotime($res->send_time);
            $mcData['stats'] = array();

            if (isset($res->summary->clicks)) {
                $mcData['stats']['recipients'] = $res->summary->emails_sent;
                $mcData['stats']['opens'] = $res->summary->opens;
                $mcData['stats']['clicks'] = $res->summary->clicks;
                $mcData['stats']['unsubscribes'] = $res->summary->unsubscribes;
                $mcData['stats']['bounces'] = $res->summary->hard_bounces + $res->summary->soft_bounces;
                $mcData['stats']['unique_opens'] = $res->summary->unique_opens;
                $mcData['stats']['abuse_reports'] = $res->summary->abuse_reports;

                $mcData['stats']['forwards'] = $res->summary->forwards;
                $mcData['stats']['facebook_likes'] = $res->summary->facebook_likes;
                //$mcData['stats']['twitter_mentions'] = ''; //$res->summary->recipient_likes;
            }
        }

        $vdata = array();
        $vdata['campaign'] = $dbCampaign;
        $vdata['dbData'] = $dbCampaign->campaign_data;
        $vdata['apiData'] = $mcData;
        $vdata['apiError'] = $resErrorMsg;

        //ee()->firephp->log($vdata);

        $html = ee()->load->view('status/mailchimp', $vdata, true);

        return $html;
    }

    // ********************************************************************************* //

    private function init()
    {
        if (isset($this->API)) return true;

        if (class_exists('Campaigns_curl') === false) {
            require_once PATH_THIRD.'campaigns/libraries/campaigns_curl.php';
        }

        // -----------------------------------------
        // API key overrides access key
        // -----------------------------------------
        if ($this->settings['api_key'] != false) {
            $temp = explode('-', $this->settings['api_key']);
            $this->settings['access_token'] = $temp[0];
            $this->settings['api_dc'] = $temp[1];
            $this->settings['api_endpoint'] = 'https://'.$this->settings['api_dc'].'.api.mailchimp.com';
        }

        $this->API = new Campaigns_curl();
        //$this->API->config['host'] = $this->settings['api_dc'].'.api.mailchimp.com';
        $this->API->config['base_uri'] = "{$this->settings['api_endpoint']}/2.0/";
        $this->API->config['global_url_params']['apikey'] = "{$this->settings['access_token']}-{$this->settings['api_dc']}";
    }

    // ********************************************************************************* //
}

/* End of file provider.mailchimp.php */
/* Location: ./system/expressionengine/third_party/campaigns/providers/provider.mailchimp.php */
