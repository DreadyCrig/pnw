<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Campaigns Service File
 *
 * @package         DevDemon_Campaigns
 * @author          DevDemon <http://www.devdemon.com> - Lead Developer @ Parscale Media
 * @copyright       Copyright (c) 2007-2014 Parscale Media <http://www.parscale.com>
 * @license         http://www.devdemon.com/license/
 * @link            http://www.devdemon.com/campaigns/
 */
abstract class CampaignService
{
    // Service Info
    public $title     =  '';
    public $name      =  '';
    public $version   =  '1.0';
    public $type      =  ''; // email, social, url_shortener
    public $enabled   =  true;

    public $account   = null; // The Account ROW in the DB

    public $errorMsg = '';
    public $settings = array();

    // Box Specific
    public $box       = null;
    public $boxSettings = array();
    public $boxOptions = array();

    public $entryId = null;
    public $campaignData = '';
    public $currentCampaignId = '';

    /**
     * Constructor
     *
     * @access public
     */
    public function __construct($settings=array(), $boxSettings=array(), $init=true)
    {
        // Creat EE Instance
        $this->settings = array_merge($this->settings, $settings);
        $this->boxSettings = array_merge($this->boxSettings, $boxSettings);
    }

    // ********************************************************************************* //

    public function getAuthorizationUrl()
    {
        return '';
    }

    // ********************************************************************************* //

    public function getAccountData($output='html')
    {
        return;
    }

    // ********************************************************************************* //

    public function getBoxOptions()
    {
        $data =& $this->boxOptions;
        $data = array();

        return $data;
    }

    // ********************************************************************************* //

    public function getBoxAccountVars()
    {
        return array();
    }

    // ********************************************************************************* //

    public function getLists()
    {
        return false;
    }

    // ********************************************************************************* //

    public function save_settings($settings)
    {
        return $settings;
    }



    // ********************************************************************************* //

    public function parseBoxData($settings=array())
    {
        return $settings;
    }

    // ********************************************************************************* //

    public function createCampaign($data, $test=false)
    {
        if ($this->type == 'email') {
            $this->prepTemplateUrls();

            if (!$this->tmplUrlHtml) {
                $this->errorMsg = 'createCampaign: Failed to generate a HTML Template URL';
                return false;
            }

            if (isset($data['lists']) === false) {
                $this->errorMsg = 'createCampaign: No Lists Selected';
                return false;
            }

            if ($test) {
                $data['campaign_label'] = '[TEST] ' . $data['email_subject'];
                $data['email_subject'] = '[TEST] ' . $data['email_subject'];
            } else {
                $data['campaign_label'] = $data['email_subject'];
            }

            $data = $this->parseBoxData($data);

            return $this->create($data);
        }

        return true;
    }

    // ********************************************************************************* //

    public function testCampaign($data)
    {
        //ee()->firephp->log($this->currentCampaignId);

        if (isset($this->currentCampaignId) === true) {
            $cid = $this->currentCampaignId;
        }

        if (!$cid) {
            $this->errorMsg = 'testCampaign: No campaign!';
            return false;
        }

        $this->currentCampaignId = $cid;

        $emails = $data['test_emails'];
        $emails = trim($emails);
        $emails = explode(',', $emails);
        $this->testEmails = array();

        foreach ($emails as $email) {
            $email = trim($email);
            if (!$email) continue;
            $this->testEmails[] = $email;
        }

        if (empty($this->testEmails) === true) {
            $this->errorMsg = 'testCampaign: No Valid Test Emails..';
            return false;
        }

        return $this->test($data);
    }

    // ********************************************************************************* //

    public function scheduleCampaign($data)
    {
        //ee()->firephp->log($this->currentCampaignId);

        if ($this->type == 'email') {
            $cid = $this->currentCampaignId;

            if (!$cid) {
                $this->errorMsg = 'scheduleCampaign: No campaign!';
                return false;
            }
        }

        // Get schedule datetime
        $this->scheduleDate = false;
        $c = $data['schedule'];
        $t = "{$c['year']}-{$c['month']}-{$c['day']} {$c['hour']}:{$c['minute']} {$c['ampm']}";
        $this->scheduleDate = ee()->campaigns_helper->stringToTimestamp($t);

        if ($this->type == 'email') {
            return $this->schedule($data);
        } else {
            return true;
        }
    }

    // ********************************************************************************* //

    public function sendCampaign($data=array())
    {
        //ee()->firephp->log($this->currentCampaignId);

        if ($this->type == 'email') {
            if (isset($this->currentCampaignId) === true) {
                $cid = $this->currentCampaignId;
            }

            if (!$cid) {
                $this->errorMsg = 'sendCampaign: No campaign!';
                return false;
            }
        } else {
            $cid = 0;
        }

        $this->currentCampaignId = $cid;
        $this->scheduleDate = ee()->localize->now;

        $data = $this->parseBoxData($data);
        return $this->send($data);
    }

    // ********************************************************************************* //

    public function deleteCampaign($id=false)
    {
        if (isset($this->currentCampaignId) === true && $this->currentCampaignId != false) {
            $id = $this->currentCampaignId;
        }

        if (!$id) {
            return false;
        }

        return $this->delete($id);
    }

    // ********************************************************************************* //

    public function getCampaignStats($id=false){
        if (isset($this->currentCampaignId) === true && $this->currentCampaignId != false) {
            $id = $this->currentCampaignId;
        }

        if (!$id) {
            return false;
        }

        return $this->execGetCampaignStats($id);
    }

    // ********************************************************************************* //

    protected function execGetCampaignStats($id){}

    // ********************************************************************************* //

    public function getCampaignStatusHtml($campaign)
    {
        if (is_string($campaign->campaign_data)) {
            $campaign->campaign_data = unserialize($campaign->campaign_data);
        }

        return $this->execGetCampaignStatusHtml($campaign);
    }

    // ********************************************************************************* //

    protected function execGetCampaignStatusHtml($campaign){}

    // ********************************************************************************* //

    public function convertHtmlToText($str, $width=65)
    {
        if (class_exists('Html2Text') === false) require_once PATH_THIRD.'campaigns/libraries/html2text.php';
        $obj = new Html2Text($str, false, array('width' => $width));
        return $obj->get_text();
    }

    // ********************************************************************************* //

    public function prepTemplateUrls()
    {
        $this->tmplUrlHtml = '';
        $this->tmplUrlText = '';

        ee()->load->helper('string');

        // Site URL
        $siteUrl = ee()->functions->fetch_site_index(0,0);
        //$siteUrl = 'http://657f7ae8.ngrok.com/';

        if (isset($this->boxSettings['html_tmpl']) !== false && $this->boxSettings['html_tmpl'] != false) {
            $query = ee()->db->query("SELECT t.template_name, g.group_name FROM exp_templates AS t LEFT JOIN exp_template_groups AS `g` ON t.group_id = g.group_id WHERE t.template_id = {$this->boxSettings['html_tmpl']}");

            if ($query->num_rows() > 0) {
                $row = $query->row();
                $this->tmplUrlHtml = reduce_double_slashes("{$siteUrl}/{$row->group_name}/{$row->template_name}/{$this->entryId}/");
            }
        }

        if (isset($this->boxSettings['text_tmpl']) !== false && $this->boxSettings['text_tmpl'] != false) {
            $query = ee()->db->query("SELECT t.template_name, g.group_name FROM exp_templates AS t LEFT JOIN exp_template_groups AS `g` ON t.group_id = g.group_id WHERE t.template_id = {$this->boxSettings['text_tmpl']}");

            if ($query->num_rows() > 0) {
                $row = $query->row();
                $this->tmplUrlText = reduce_double_slashes("{$siteUrl}/{$row->group_name}/{$row->template_name}/{$this->entryId}/");
            }
        }
    }

    // ********************************************************************************* //

    protected function parseEntryTmpl($text='')
    {
        if (!$this->entryId) return $text;

        $text = '{exp:channel:entries entry_id="'.$this->entryId.'" dynamic="no" status="not DevDemon" show_future_entries="yes"}'.$text.'{/exp:channel:entries}';
        if (class_exists('EE_Template') == false) require_once APPPATH.'libraries/Template.php';

        //$tag_vars = array(array('name' => 'adrian')); // simple variables to parse directly
        $tag_vars = array();

        // back up existing TMPL class
        ee()->load->library('template');
        $OLD_TMPL = isset(ee()->TMPL) ? ee()->TMPL : NULL;
        ee()->TMPL = new EE_Template();

        // parse simple variables
        $text = ee()->template->parse_variables($text, $tag_vars);

        // pretty lame that we need to manually load snippets
        $result = ee()->db->select('snippet_name, snippet_contents')
            ->where('site_id', ee()->campaigns->siteId)
            ->or_where('site_id', 0)
            ->get('snippets')->result_array();

        $snippets = array();
        foreach ($result as $row) {
            $snippets[$row['snippet_name']] = $row['snippet_contents'];
        }

        // merge snippets into global variables
        ee()->config->_global_vars = array_merge(ee()->config->_global_vars, $snippets);

        // parse email contents as complete template
        ee()->TMPL->parse($text);
        $text = ee()->TMPL->parse_globals(ee()->TMPL->final_template);

        // restore old TMPL class
        ee()->TMPL = $OLD_TMPL;

        return trim($text);
    }

    // ********************************************************************************* //

} // END CLASS

/* End of file campaign_provider.php  */
/* Location: ./system/expressionengine/third_party/campaigns/providers/campaign_provider.php */