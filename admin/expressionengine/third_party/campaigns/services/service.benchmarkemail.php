<?php if (!defined('BASEPATH')) die('No direct script access allowed');

/**
 * Campaigns BENCHMARKEMAIL provider
 *
 * @package         DevDemon_Campaigns
 * @author          DevDemon <http://www.devdemon.com> - Lead Developer @ Parscale Media
 * @copyright       Copyright (c) 2007-2014 Parscale Media <http://www.parscale.com>
 * @license         http://www.devdemon.com/license/
 * @link            http://www.devdemon.com/campaigns/
 */
class Service_benchmarkemail extends CampaignService implements CampaignServiceInterface, EmailServiceInterface
{

    // Service Info
    public $title     =  'BenchmarkEmail';
    public $name      =  'benchmarkemail';
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
        $this->settings['username'] = '';
        $this->settings['password'] = '';
        $this->settings['api_key'] = '';

        $this->settings['email_from_name'] = function_exists('ee') ? ee()->config->item('site_label') : get_instance()->config->item('site_label');
        $this->settings['email_from'] = function_exists('ee') ? ee()->config->item('webmaster_email') : get_instance()->config->item('webmaster_email');
        $this->settings['reply_to_email'] = function_exists('ee') ? ee()->config->item('webmaster_email') : get_instance()->config->item('webmaster_email');

        parent::__construct($settings, $boxSettings, $init);

        if ($init) $this->init();
    }

    // ********************************************************************************* //

    public function testConnection()
    {
        if ($this->settings['api_key'] != false) {
            $this->API->config['global_url_params']['method'] = 'listGet';
            $this->API->fetch('POST', '', array('pageSize' => 99));
        }

        //ee()->firephp->log($this->API->response);

        if (isset($this->API->response['response']->error) === true) {
            $this->errorMsg = "{$this->API->response['response']->error} ({$this->API->response['response']->code})";
            return false;
        }

        return true;
    }

    // ********************************************************************************* //

    public function getAccountData($response)
    {
        $html = '';
        $html .= 'Username: ' . $this->settings['username'];
        $html .= '<br clear="all">';
        return $html;
    }

    // ********************************************************************************* //

    public function getLists()
    {
        $this->API->config['global_url_params']['method'] = 'listGet';
        $this->API->fetch('POST', '', array('pageSize' => 99));

        if (isset($this->API->response['response']->error) === true) {
            $this->errorMsg = "{$this->API->response['response']->error} ({$this->API->response['response']->code})";
            return false;
        }

        $lists = $this->API->response['response'];

        $items = array();

        foreach ($lists as $list) {
            //var_dump($list);
            $item = array();
            $item['id'] = $list->id;
            $item['label'] = $list->listname;
            $items[] = $item;
        }

        return $items;
    }

    // ********************************************************************************* //

    public function create($data)
    {
        $this->API->config['global_url_params']['method'] = 'emailCreate';

        $params = array();
        $params['emailDetails']['toListID'] = key($data['lists']);
        $params['emailDetails']['emailName'] = $data['campaign_label'];
        $params['emailDetails']['subject'] = $data['email_subject'];

        $params['emailDetails']['fromEmail'] = $this->settings['email_from'];
        $params['emailDetails']['fromName'] = $this->settings['email_from_name'];
        $params['emailDetails']['replyEmail'] = $this->settings['reply_to_email'];
        $params['emailDetails']['webpageVersion'] = 'true';

        $params['emailDetails']['templateContent'] = trim(ee()->campaigns_helper->fetchUrl($this->tmplUrlHtml));

        $this->API->fetch('POST', '', $params);

        //ee()->firephp->log($this->API->response);

        if (isset($this->API->response['response']->error) === true) {
            $this->errorMsg = "{$this->API->response['response']->error} ({$this->API->response['response']->code})";
            return false;
        }

        $res = $this->API->response['response'];

        $this->currentCampaignId = trim($res);

        return true;
    }

    // ********************************************************************************* //

    public function test()
    {
        $this->API->config['global_url_params']['method'] = 'emailSendTest';
        $this->API->fetch('POST', '', array('emailID' => $this->currentCampaignId, 'testEmail' => reset($this->testEmails)));

        if (isset($this->API->response['response']->error) === true) {
            $this->errorMsg = "{$this->API->response['response']->error} ({$this->API->response['response']->code})";
            return false;
        }

        return true;
    }

    // ********************************************************************************* //

    public function schedule()
    {
        $scheduled_date = ee()->campaigns_helper->formatDate('%Y-%m-%d %H:%i:00', $this->scheduleDate, true);
        $this->API->config['global_url_params']['method'] = 'emailSchedule';
        $this->API->fetch('POST', '', array('emailID' => $this->currentCampaignId, 'scheduleDate' => $scheduled_date));

        if (isset($this->API->response['response']->error) === true) {
            $this->errorMsg = "{$this->API->response['response']->error} ({$this->API->response['response']->code})";
            return false;
        }

        return true;
    }

    // ********************************************************************************* //

    public function send($data)
    {
        $this->API->config['global_url_params']['method'] = 'emailSendNow';
        $this->API->fetch('POST', '', array('emailID' => $this->currentCampaignId));

        if (isset($this->API->response['response']->error) === true) {
            $this->errorMsg = "{$this->API->response['response']->error} ({$this->API->response['response']->code})";
            return false;
        }

        return true;
    }

    // ********************************************************************************* //

    public function delete($id)
    {
        $this->API->config['global_url_params']['method'] = 'emailDelete';
        $this->API->fetch('POST', '', array('emailID' => $this->currentCampaignId));

        if (isset($this->API->response['response']->error) === true) {
            $this->errorMsg = "{$this->API->response['response']->error} ({$this->API->response['response']->code})";
            return false;
        }

        return true;
    }

    // ********************************************************************************* //

    public function execGetCampaignStatusHtml($dbCampaign)
    {
        $res = false;
        $apiData = false;
        $resErrorMsg = '';

        $this->API->config['global_url_params']['method'] = 'emailGetDetail';
        $this->API->fetch('POST', '', array('emailID' => $dbCampaign->service_cid));

        if (isset($this->API->response['response']->error) === true) {
            $resErrorMsg = "{$this->API->response['response']->error} ({$this->API->response['response']->code})";
        }

        $res = $this->API->response['response'];
        //ee()->firephp->log($res);

        if ($res !== false) {
            $mcData = array();
            $apiData['web_url'] = $res->shareURL;
            $apiData['status'] = $res->status;
            $apiData['email_subject'] = $res->subject;
            $apiData['date_created'] = strtotime($res->createdDate);
            $apiData['date_scheduled'] = strtotime($res->scheduleDate);
            $apiData['to_list'] = $res->toListName;
            $apiData['stats'] = array();

            // Get Stats
            $this->API->config['global_url_params']['method'] = 'reportGetSummary';
            $this->API->fetch('POST', '', array('emailID' => $dbCampaign->service_cid));
            $res = $this->API->response['response'];

            if (isset($res->clicks)) {
                $apiData['stats']['recipients'] = $res->mailSent;
                $apiData['stats']['opens'] = $res->opens;
                $apiData['stats']['clicks'] = $res->clicks;
                $apiData['stats']['unsubscribes'] = $res->unsubscribes;
                $apiData['stats']['bounces'] = $res->bounces;
                $apiData['stats']['abuse_reports'] = $res->abuseReports;

                $apiData['stats']['forwards'] = $res->forwards;
            }
        }

        $vdata = array();
        $vdata['campaign'] = $dbCampaign;
        //$vdata['dbData'] = $dbCampaign->campaign_data;
        $vdata['apiData'] = $apiData;
        $vdata['apiError'] = $resErrorMsg;

        $html = ee()->load->view('status/benchmarkemail', $vdata, true);

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
        $this->API->config['base_uri'] = 'http://www.benchmarkemail.com/api/1.0/';
        $this->API->config['global_url_params']['output'] = 'json';
        $this->API->config['urlencoded_post_params'] = true;

        if ($this->settings['api_key'] != false) {
            $this->API->config['global_params']['token'] = $this->settings['api_key'];
        } else {
            $this->API->config['global_url_params']['method'] = 'login';
            $this->API->fetch('POST', '', array('username' => $this->settings['username'], 'password' => $this->settings['password']));

            if (isset($this->API->response['response']->error) === true) {
                $this->errorMsg = "{$this->API->response['response']->error} ({$this->API->response['response']->code})";
                return false;
            } else {
                $this->tempToken = $this->API->response['response'];
                $this->API->config['global_params']['token'] = $this->tempToken;
            }
        }
    }

    // ********************************************************************************* //
}

/* End of file service.benchmarkemail.php */
/* Location: ./system/expressionengine/third_party/campaigns/services/service.benchmarkemail.php */
