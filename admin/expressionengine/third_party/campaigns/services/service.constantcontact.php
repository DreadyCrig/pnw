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
class Service_constantcontact extends CampaignService implements CampaignServiceInterface, EmailServiceInterface
{

    // Service Info
    public $title     =  'Constant Contact';
    public $name      =  'constantcontact';
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
        $this->settings['access_token'] = '';

        $this->settings['email_from_name'] = function_exists('ee') ? ee()->config->item('site_label') : get_instance()->config->item('site_label');
        $this->settings['email_from'] = function_exists('ee') ? ee()->config->item('webmaster_email') : get_instance()->config->item('webmaster_email');
        $this->settings['reply_to_email'] = function_exists('ee') ? ee()->config->item('webmaster_email') : get_instance()->config->item('webmaster_email');

        parent::__construct($settings, $boxSettings, $init);

        if ($init) $this->init();
    }

    // ********************************************************************************* //

    public function getAuthorizationUrl()
    {
        $this->settings['AuthUrl'] = 'http://www.devdemon.com/oauth_agent/campaigns_constant_contact/';
        return $this->settings['AuthUrl'];
    }

    // ********************************************************************************* //

    public function testConnection()
    {
        $code = $this->API->fetch('GET', 'account/info');
        $res = $this->API->response['response'];

        //ee()->firephp->log($this->API->response);

        if ($code != 200) {
            foreach ($res as $err) $this->errorMsg .= $err->error_message;
            return false;
        }

        return true;
    }

    // ********************************************************************************* //

    public function getAccountData($response)
    {
        $code = $this->API->fetch('GET', 'account/info');
        $res = $this->API->response['response'];

        if ($code != 200) {
            foreach ($res as $err) $this->errorMsg .= $err->error_message;
            return false;
        }

        //ee()->firephp->log($res);

        $html = '';
        $html .= '<strong>' . $res->first_name .' '.$res->last_name.'</strong><br>';
        $html .= 'Company: '. $res->organization_name.'<br>';

        $html .= '<br clear="all">';
        return $html;
    }

    // ********************************************************************************* //

    public function getLists()
    {
        $code = $this->API->fetch('GET', 'lists');
        $res = $this->API->response['response'];

        //ee()->firephp->log($this->API->response);

        if ($code != 200) {
            foreach ($res as $err) $this->errorMsg .= $err->error_message;
            return false;
        }

        $items = array();

        foreach ($res as $list) {

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
        $params = array();
        $params['name'] = $data['campaign_label'];
        $params['subject'] = $data['email_subject'];
        $params['from_name'] = $this->settings['email_from_name'];
        $params['from_email'] = $this->settings['email_from'];
        //$params['greeting_string'] = $params['greeting_string'];
        $params['reply_to_email'] = $this->settings['reply_to_email'];
        //$params['email_content_format'] = 'XHTML';

        $params['sent_to_contact_lists'] = array();
        foreach ($data['lists'] as $listId => $list) {
            $params['sent_to_contact_lists'][] = array('id' => (string)$listId);
        }

        $params['email_content'] = trim(ee()->campaigns_helper->fetchUrl($this->tmplUrlHtml));
        if ($this->tmplUrlText) $params['text_content'] = trim(ee()->campaigns_helper->fetchUrl($this->tmplUrlText));
        else {
            $params['text_content'] = $this->convertHtmlToText($params['email_content']);
        }

        //ee()->firephp->log($params);

        $params = ee()->campaigns_helper->generateJson($params);
        $code = $this->API->fetch('POST', 'emailmarketing/campaigns', $params, array('Content-Type' => 'application/json'));
        $res = $this->API->response['response'];

        //ee()->firephp->log($this->API->response);

        if ($code > 300) {
            $this->errorMsg .= 'makeCampaign: ';
            foreach ($res as $err) $this->errorMsg .= $err->error_message;
            return false;
        }

        $this->currentCampaign = $res;
        $this->currentCampaignId = $res->id;

        return true;
    }

    // ********************************************************************************* //

    public function test()
    {
        $params = array();
        $params['format'] = 'HTML';
        $params['personal_message'] = 'This is a test send of the email campaign message';
        $params['email_addresses'] = $this->testEmails;

        $params = ee()->campaigns_helper->generateJson($params);
        $code = $this->API->fetch('POST', 'emailmarketing/campaigns/'.$this->currentCampaignId.'/tests', $params, array('Content-Type' => 'application/json'));
        $res = $this->API->response['response'];

        //ee()->firephp->log($this->API->response);

        if ($code > 300) {
            $this->errorMsg .= 'test(): ';
            foreach ($res as $err) $this->errorMsg .= $err->error_message;
            return false;
        }

        return true;
    }

    // ********************************************************************************* //

    public function schedule()
    {
        $scheduled_date = ee()->campaigns_helper->formatDate('%Y-%m-%d\T%H:%i:00.000\Z', $this->scheduleDate, false);
        $code = $this->API->fetch('POST', 'emailmarketing/campaigns/'.$this->currentCampaignId.'/schedules', '{"scheduled_date":"'.$scheduled_date.'"}', array('Content-Type' => 'application/json'));
        $res = $this->API->response['response'];

        //ee()->firephp->log($this->API->response);

        if ($code > 300) {
            $this->errorMsg .= 'schedule(): ';
            foreach ($res as $err) $this->errorMsg .= $err->error_message;
            return false;
        }

        return true;
    }

    // ********************************************************************************* //

    public function send($data)
    {
        $code = $this->API->fetch('POST', 'emailmarketing/campaigns/'.$this->currentCampaignId.'/schedules', '{}', array('Content-Type' => 'application/json'));
        $res = $this->API->response['response'];

        //ee()->firephp->log($this->API->response);

        if ($code > 300) {
            $this->errorMsg .= 'send(): ';
            foreach ($res as $err) $this->errorMsg .= $err->error_message;
            return false;
        }

        return true;
    }

    // ********************************************************************************* //

    public function delete($id)
    {
        $code = $this->API->fetch('DELETE', 'emailmarketing/campaigns/'.$this->currentCampaignId);
        $res = $this->API->response['response'];

        //ee()->firephp->log($this->API->response);

        if ($code != 204) {
            $this->errorMsg .= 'removeCampaign: HTTP_ERROR ('.$code.')';
            return false;
        }

        return true;
    }

    // ********************************************************************************* //

    public function execGetCampaignStatusHtml($dbCampaign)
    {
        $res = false;
        $aipError = '';

        /*
        try {
            $res = $this->CC->getEmailCampaign($this->access_token, $dbCampaign->service_cid);
        } catch (Ctct\Exceptions\CtctException $ex) {
            foreach ($ex->getErrors() as $err) {
                $resErrorMsg .= 'GET_CAMPAIGN_STATS: '.$err['error_message'].'<br>';
            }
            return false;
        }
        */

        if ($res !== false) {
            $api = array();
            $api['web_url'] = @$res->permalink_url;
            $api['email_subject'] = $res->subject;
            $api['status'] = $res->status;
            $api['date_created'] = $res->created_date;
            $api['date_scheduled'] = $res->next_run_date ? strtotime($res->next_run_date) : strtotime($res->last_run_date);
            $api['stats'] = array();

            if (isset($res->tracking_summary->sends)) {
                $api['stats']['recipients'] = $res->tracking_summary->sends;
                $api['stats']['opens'] = $res->tracking_summary->opens;
                $api['stats']['clicks'] = $res->tracking_summary->clicks;
                $api['stats']['unsubscribes'] = $res->tracking_summary->unsubscribes;
                $api['stats']['bounces'] = $res->tracking_summary->bounces;
                $api['stats']['abuse_reports'] = $res->tracking_summary->spam_count;
                $api['stats']['forwards'] = $res->tracking_summary->forwards;
            }
        }

        $vdata = array();
        $vdata['campaign'] = $dbCampaign;
        $vdata['dbData'] = $dbCampaign->campaign_data;
        $vdata['apiData'] = $api;
        $vdata['apiError'] = $aipError;

        //ee()->firephp->log($vdata);

        $html = ee()->load->view('status/constantcontact', $vdata, true);

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
        $this->API->config['base_uri'] = 'https://api.constantcontact.com/v2/';
        $this->API->config['global_url_params']['access_token'] = $this->settings['access_token'];
        $this->API->config['global_url_params']['api_key'] = ee()->campaigns->settings['constantcontact_keys']['api_key'];
    }

    // ********************************************************************************* //
}

/* End of file provider.constantcontact.php */
/* Location: ./system/expressionengine/third_party/campaigns/providers/provider.constantcontact.php */
