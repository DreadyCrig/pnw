<?php if (!defined('BASEPATH')) die('No direct script access allowed');

/**
 * Campaigns CAMPAIGNMONITOR provider
 *
 * @package         DevDemon_Campaigns
 * @author          DevDemon <http://www.devdemon.com> - Lead Developer @ Parscale Media
 * @copyright       Copyright (c) 2007-2014 Parscale Media <http://www.parscale.com>
 * @license         http://www.devdemon.com/license/
 * @link            http://www.devdemon.com/campaigns/
 */
class Service_campaignmonitor extends CampaignService implements CampaignServiceInterface, EmailServiceInterface
{

    // Service Info
    public $title     =  'Campaign Monitor';
    public $name      =  'campaignmonitor';
    public $type      =  'email';

	private $clientId;

	/**
	 * Constructor
	 *
	 * @access public
	 *
	 * Calls the parent constructor
	 */
	public function __construct($settings=array(), $boxSettings=array(), $init=true)
	{
        $this->settings['access_token'] = '';
		$this->settings['client_id'] = '';
		$this->settings['client_api_key'] = '';

        $this->settings['email_from_name'] = function_exists('ee') ? ee()->config->item('site_label') : get_instance()->config->item('site_label');
        $this->settings['email_from'] = function_exists('ee') ? ee()->config->item('webmaster_email') : get_instance()->config->item('webmaster_email');
        $this->settings['reply_to_email'] = function_exists('ee') ? ee()->config->item('webmaster_email') : get_instance()->config->item('webmaster_email');
        $this->settings['confirmation_email'] = function_exists('ee') ? ee()->config->item('webmaster_email') : get_instance()->config->item('webmaster_email');

		parent::__construct($settings, $boxSettings, $init);

        if ($init) $this->init();
	}

	// ********************************************************************************* //

    public function getAuthorizationUrl()
    {
        $this->settings['AuthUrl'] = 'http://www.devdemon.com/oauth_agent/campaigns_campaignmonitor/';
        return $this->settings['AuthUrl'];
    }

    // ********************************************************************************* //

    public function testConnection()
    {
        if (!$this->settings['client_id']) {
            $this->errorMsg = 'Missing Client ID';
            return false;
        }

        $code = $this->API->fetch('GET', 'clients.json');
        $res = $this->API->response['response'];

        //ee()->firephp->log($this->API->response);

        if ($code != 200) {
            $this->errorMsg = $res->Message;
            return false;
        }

        return true;
    }

    // ********************************************************************************* //

    public function getAccountData($response)
    {
        $code = $this->API->fetch('GET', 'clients/'.$this->settings['client_id'].'.json');
        $res = $this->API->response['response'];

        //ee()->firephp->log($this->API->response);

        if ($code != 200) {
            $this->errorMsg = $res->Message;
            return false;
        }

        $html = '';
        $html .= '<strong>' . $res->BasicDetails->CompanyName.'</strong><br>';
        $html .= 'Contact: '. $res->BasicDetails->ContactName.'<br>';

        $html .= '<br clear="all">';
        return $html;
    }

    // ********************************************************************************* //

	public function getLists()
	{
		$code = $this->API->fetch('GET', 'clients/'.$this->clientId.'/lists.json');
        $res = $this->API->response['response'];

        //ee()->firephp->log($this->API->response);

        if ($code != 200) {
            $this->errorMsg = $res->Message;
            return false;
        }

        $items = array();

        foreach ($res as $list) {

            $item = array();
            $item['id'] = $list->ListID;
            $item['label'] = $list->Name;
            $items[] = $item;
        }

        return $items;
	}

	// ********************************************************************************* //

    public function create($data)
    {
        $params = array();
        $params['Name'] = $data['campaign_label'];
        $params['Subject'] = $data['email_subject'];

        $params['FromEmail'] = $this->settings['email_from'];
        $params['FromName'] = $this->settings['email_from_name'];
        $params['ReplyTo'] = $this->settings['reply_to_email'];

        $params['HtmlUrl'] = $this->tmplUrlHtml;
        if ($this->tmplUrlText) $params['TextUrl'] = $this->tmplUrlText;

        $params['ListIDs'] = array();

        foreach ($data['lists'] as $id => $val) {
            $params['ListIDs'][] = $id;
        }

        //ee()->firephp->log($params);

        $params = ee()->campaigns_helper->generateJson($params);
        $code = $this->API->fetch('POST', 'campaigns/'.$this->clientId.'.json', $params);
        $res = $this->API->response['response'];

        //ee()->firephp->log($this->API->response);
        //ee()->firephp->log($res);

        if ($code > 300) {
            $this->errorMsg = 'makeCampaign: ' . $res->Message;
            return false;
        }

        $this->currentCampaignId = trim($res);

        return true;
    }

    // ********************************************************************************* //

    public function test()
    {
        $params = ee()->campaigns_helper->generateJson(array('PreviewRecipients' => $this->testEmails, 'Personalize' => 'Random'));
        $code = $this->API->fetch('POST', 'campaigns/'.$this->currentCampaignId.'/sendpreview.json', $params);
        $res = $this->API->response['response'];

        if ($code != 200) {
            $this->errorMsg = 'test(): ' . $res->Message;
            return false;
        }

        return true;
    }

    // ********************************************************************************* //

    public function schedule()
    {
        // $this->scheduleDate (comes in as the user inputs it, no timezone conversion has yet been done)
/*
        // Make it UTC
        $this->scheduleDate = ee()->campaigns_helper->formatDate('%U', $this->scheduleDate, false);

        // Get the timezone
        $code = $this->API->fetch('GET', 'clients/'.$this->clientId.'.json');
        $res = $this->API->response['response'];

        $timezone = $res->BasicDetails->TimeZone;
        preg_match('/\(GMT.*\)/', $timezone, $matches);
        $timezone = reset($matches);
        $timezone = str_replace(array('GMT', ')', '('), '', $timezone);

        $offset = 0;

        if ($timezone != false) {
            $symbol = substr($timezone, 0, 1);
            $timezone = explode(':', substr($timezone, 1));

            $offset += ($timezone[1] * 60);
            $offset += ($timezone[0] * 3600);

            if ($symbol == '+') {
                $this->scheduleDate += $offset;
            } else {
                $this->scheduleDate -= $offset;
            }
        }
*/
        $params = array();
        $params['ConfirmationEmail'] = $this->settings['confirmation_email'];
        $params['SendDate'] = date('Y-m-d H:i:s', $this->scheduleDate);
//ee()->firephp->log(date('Y-m-d H:i:s', $this->scheduleDate));
        //ee()->firephp->log($params);

        $params = ee()->campaigns_helper->generateJson($params);
        $code = $this->API->fetch('POST', 'campaigns/'.$this->currentCampaignId.'/send.json', $params);
        $res = $this->API->response['response'];


        //ee()->firephp->log($this->API->response);

        if ($code != 200) {
            $this->errorMsg = 'execScheduleCampaign: ' . $res->Message;
            return false;
        }

        return true;
    }

    // ********************************************************************************* //

    public function send($data)
    {
        $params = array();
        $params['ConfirmationEmail'] = $this->settings['confirmation_email'];
        $params['SendDate'] = 'immediately';

        $params = ee()->campaigns_helper->generateJson($params);
        $code = $this->API->fetch('POST', 'campaigns/'.$this->currentCampaignId.'/send.json', $params);
        $res = $this->API->response['response'];

        if ($code != 200) {
            $this->errorMsg = 'send(): ' . $res->Message;
            return false;
        }

        return true;
    }

    // ********************************************************************************* //

    public function delete($id)
    {
        $code = $this->API->fetch('DELETE', 'campaigns/'.$this->currentCampaignId.'.json');
        $res = $this->API->response['response'];

        //ee()->firephp->log($this->API->response);

        if ($code != 200) {
            $this->errorMsg = 'delete(): ' . $res->Message;
            return false;
        }

        return true;
    }

    // ********************************************************************************* //

    public function execGetCampaignStatusHtml($dbCampaign)
    {
        $res = false;
        $resErrorMsg = '';

        $code = $this->API->fetch('GET', 'campaigns/'.$dbCampaign->service_cid.'/summary.json');
        $request = $this->API->response['response'];

        if ($code != 200) {
            $resErrorMsg = 'execTestCampaign: ' . $request->Message;
        } else {
            $res = $request;
        }

        $api = array();

        if ($res !== false) {
            $api['web_url'] = $res->WebVersionURL;
            $api['web_url_text'] = $res->WebVersionTextURL;
            $api['status'] = false;
            $api['date_created'] = false;
            $api['date_scheduled'] = false;

            $api['stats']['recipients'] = $res->Recipients;
            $api['stats']['opens'] = $res->TotalOpened;
            $api['stats']['clicks'] = $res->Clicks;
            $api['stats']['unsubscribes'] = $res->Unsubscribed;
            $api['stats']['bounces'] = $res->Bounced;
            $api['stats']['unique_opens'] = $res->UniqueOpened;
            $api['stats']['abuse_reports'] = $res->SpamComplaints;
            $api['stats']['forwards'] = $res->Forwards;
            $api['stats']['facebook_likes'] = $res->Likes;
            $api['stats']['twitter_mentions'] = $res->Mentions;
        }

        $vdata = array();
        $vdata['campaign'] = $dbCampaign;
        $vdata['dbData'] = $dbCampaign->campaign_data;
        $vdata['apiData'] = $api;
        $vdata['apiError'] = $resErrorMsg;

        //ee()->firephp->log($vdata);

        $html = ee()->load->view('status/campaignmonitor', $vdata, true);

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
        //$this->API->config['host'] = $this->settings['api_dc'].'.api.mailchimp.com';
        $this->API->config['base_uri'] = "https://api.createsend.com/api/v3.1/";

        if ($this->settings['client_api_key'] == false) {
            $this->API->config['global_request_headers']['Authorization'] = "Bearer {$this->settings['access_token']}";
        } else {
            $this->API->config['global_request_headers']['Authorization'] = 'Basic ' . base64_encode($this->settings['client_api_key']);
        }

        $this->clientId = $this->settings['client_id'];

        if (!$this->clientId) {
            $this->errorMsg = 'INIT: Missing client ID';
            return false;
        }
    }

    // ********************************************************************************* //

}

/* End of file provider.campaignmonitor.php */
/* Location: ./system/expressionengine/third_party/campaigns/providers/provider.campaignmonitor.php */
