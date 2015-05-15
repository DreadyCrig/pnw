<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Campaigns AJAX File
 *
 * @package			DevDemon_Campaigns
 * @author			DevDemon <http://www.devdemon.com> - Lead Developer @ Parscale Media
 * @copyright 		Copyright (c) 2007-2014 Parscale Media <http://www.parscale.com>
 * @license 		http://www.devdemon.com/license/
 * @link			http://www.devdemon.com/campaigns/
 */
class Campaigns_AJAX
{

	/**
	 * Constructor
	 *
	 * @access public
	 *
	 * Calls the parent constructor
	 */
	public function __construct()
	{
        $this->siteId = ee()->config->item('site_id');
	}

	// ********************************************************************************* //

    public function testAccount()
    {
        $out = array();
        $out['success'] = false;
        $out['result'] = '';
        $out['html'] = '';
        $out['errorMsg'] = '';

        // -------------------------------------------------------
        //  Get Account & Service OBJ
        // -------------------------------------------------------
        $account = ee()->campaigns_model->getAccount(ee()->input->post('id'));
        $service = ee()->campaigns->getService($account);


        $test = $service->testConnection();
        $out['errorMsg'] = $service->errorMsg;

        if ($test === null) {
            $out['result'] = '<div class="neutral">'.ee()->lang->line('c:test:neutral') . '</div>';
        }
        elseif ($test === false) {
            $out['result'] = '<div class="error">'.ee()->lang->line('c:test:failed') . '</div>';
            return $out;
        } else {
            $out['result'] = '<div class="success">'.ee()->lang->line('c:test:success') . '</div>';
        }

        $out['html'] = $service->getAccountData('html');

        if ($out['html'] === false) {
            $out['html'] = $service->errorMsg;
        }

        $out['success'] = true;

        return $out;
    }

    // ********************************************************************************* //

    public function getChannelSettings()
    {
        $out = array();
        $out['success'] = true;
        $out['html'] = '';

        $channel_id = ee()->input->post('channel_id');
        $channel = ee()->campaigns_model->getChannel($channel_id);
        $channelSettings = ee()->campaigns->getChannelSettings($channel_id);

        $vdata = array();
        $vdata['settings'] = $channelSettings;
        $vdata['channel'] = $channel;
        $vdata['base_url'] = ee()->input->post('base_url');
        $out['html'] = ee()->load->view('mcp/channels_settings', $vdata, true);

        // Get the boxes
        $out = array_merge($out, $this->getAccountsAndSettingsData());

        return $out;
    }

    // ********************************************************************************* //

    public function getAccountsAndSettingsData()
    {
        $out = array();
        $out['success'] = true;

        $channel_id = ee()->input->post('channel_id');
        $field_id = ee()->input->post('field_id');

        // -------------------------------------------------------
        //  Get Boxes
        // -------------------------------------------------------
        $out['boxes'] = ee()->campaigns_model->getBoxes($channel_id, $field_id);

        // -------------------------------------------------------
        //  Global Settings
        // -------------------------------------------------------
        $out['settings']['serviceLogo'] = (ee()->campaigns->settings['service_logo'] == 'yes') ? true : false;
        $out['settings']['themeUrl'] = ee()->campaigns_helper->getThemeUrl();

        // -------------------------------------------------------
        //  Accounts
        // -------------------------------------------------------
        $accounts = ee()->campaigns_model->getAccounts();

        foreach ($accounts as $acc) {
            $out['accounts']['a'.$acc->id] = $acc;
        }

        // -------------------------------------------------------
        //  Get all EE templates
        // -------------------------------------------------------
        ee()->load->model('template_model');
        $templates = ee()->template_model->get_templates($this->siteId);
        $out['templates'] = array('' => 'Select a Template');

        foreach($templates->result() as $template) {
            $out['templates'][$template->group_name][$template->template_id] = $template->template_name;
        }

        // -------------------------------------------------------
        //  Get all Extra Data
        // -------------------------------------------------------
        foreach ($out['accounts'] as $key => &$account) {
            $service = ee()->campaigns->getService($account);
            $account->boxOptions = $service->getBoxOptions();
            $account->defaultBoxSettings = $service->boxSettings;

            if ($account->type == 'email') {
                $account->lists = $service->getLists();
                if (!$account->lists) $account->lists = array();
            }
        }

        unset($account);

        // -------------------------------------------------------
        //  Lets make sure the boxes are updated with the latest settings
        // -------------------------------------------------------
        foreach ($out['boxes'] as $key => &$box) {
            // Get the account
            $account = $out['accounts']['a'.$box->account_id];

            // Assign the latest config settings
            $account->boxSettings = $box->settings;

            // Initialize the service object, this will also merge the
            // boxSettings with the default ones
            $service = ee()->campaigns->getService($account);

            // Grab the "updated" box settings
            $box->settings = $service->boxSettings;
        }

        return $out;
    }

    // ********************************************************************************* //

	public function getCampaignUi()
	{

        $data = array();
        $data['success'] = false;
        $data['errorMsg'] = '';
        $data['global']['showServiceLogo'] = (ee()->campaigns->settings['service_logo'] == 'yes') ? true : false;
        $data['global']['themeUrl'] = ee()->campaigns_helper->getThemeUrl();

		// -------------------------------------------------------
        //  Get Entry ID & Channel ID
        // -------------------------------------------------------
        $entry_id = ee()->input->get_post('entry_id');
        $channel_id = ee()->input->get_post('channel_id');

        if (!$entry_id) {
            $out['errorMsg'] = 'No Entry ID found';
            return $out;
        }

        $entry_title = '';

        if (!$channel_id) {
            $query = ee()->db->query("SELECT channel_id, title FROM exp_channel_titles WHERE entry_id = {$entry_id}");

            if ($query->num_rows() == 0) {
                $out['errorMsg'] = 'Entry could not be found';
                return $out;
            }

            $channel_id = $query->row('channel_id');
            $entry_title = $query->row('title');
        }

        // -------------------------------------------------------
        //  Get Fields
        // -------------------------------------------------------
        $query = ee()->db->query("SELECT field_group FROM exp_channels WHERE channel_id = {$channel_id}");

        if ($query->num_rows() == 0) {
            $out['errorMsg'] = 'No Field Group found';
            return $out;
        }

        $field_group = $query->row('field_group');

        // get the fields now
        $field_id = 0;
        $query = ee()->db->query("SELECT field_id FROM exp_channel_fields WHERE group_id = {$field_group} AND field_type = 'campaigns' ORDER BY field_order ASC LIMIT 1");

        if ($query->num_rows() > 0) {
            $field_id = $query->row('field_id');
        }

        // -------------------------------------------------------
        //  Get Channel Settings
        // -------------------------------------------------------
        $settings = ee()->campaigns->getChannelSettings($channel_id, $field_id);

        if (!$settings) {
            $out['errorMsg'] = 'No settings could be found';
            return $out;
        }

        if ($settings['enabled'] == 'no') {
            $out['errorMsg'] = 'Campaigns has not been enabled for this channel.';
            return $out;
        }

        // -------------------------------------------------------
        //  Accounts
        // -------------------------------------------------------
        $dbAccounts = ee()->campaigns_model->getAccounts();
        $accounts = array();

        foreach ($dbAccounts as $acc) {
            $accounts[$acc->id] = $acc;
        }


        // -------------------------------------------------------
        //  Get Configs
        // -------------------------------------------------------

        // It's either channel_ifd or field_id not both
        if ($field_id > 0) $channel_id = false;
        $dbBoxes = ee()->campaigns_model->getBoxes($channel_id, $field_id);
        $boxes = array();

        foreach ($dbBoxes as $box) {
            // Enabled?
            if ($box->enabled == 0) continue;

            // Get the account
            $account = $accounts[$box->account_id];

            // Assign the latest config settings
            $account->boxSettings = $box->settings;

            // Initialize the service object, this will also merge the
            // boxSettings with the default ones
            $service = ee()->campaigns->getService($account);
            $service->entryId = $entry_id;

            // Grab the "updated" config settings
            $box->settings = $service->boxSettings;
            $box->service = $account->service;
            $box->serviceType = $account->type;
            $box->accountInfo = $service->getBoxAccountVars();

            // -------------------------------------------------------
            //  Empty Campaign
            // -------------------------------------------------------
            $box->emptyCampaign = new StdClass();
            $box->emptyCampaign->id = false;
            $box->emptyCampaign->scheduled_at = strtotime('+1 hour');
            $box->emptyCampaign->status = 'new';
            $box->emptyCampaign->data = $service->parseBoxData();

            // -------------------------------------------------------
            //  Did we already send the campaign?
            // -------------------------------------------------------
            $campaigns = array();
            $queryCampaign = ee()->db->select('*')->from('exp_campaigns')->where('entry_id', $entry_id)->get();

            if ($queryCampaign->num_rows() > 0) {
                $querySends = ee()->db->select('*')->from('exp_campaigns_sends')->where('campaign_id', $queryCampaign->row('id'))->where('box_id', $box->id)->get();
            }

            if ($queryCampaign->num_rows() > 0 && $querySends->num_rows() > 0) {
                foreach ($querySends->result() as $row) {
                    $row->status = $row->status;
                    $row->currentHtml = ee()->campaigns->getCampaignStatusUi($row);
                    $row->scheduled_at = strtotime($row->scheduled_at);
                    $row->data = $service->parseBoxData(ee()->campaigns_helper->decodeJson($row->box_data, true));

                    // Temp
                    unset($row->box_data, $row->campaign_data);

                    $campaigns[] = $row;
                }
            }

            // -------------------------------------------------------
            //  Email Specific Functions
            // -------------------------------------------------------
            if ($account->type == 'email') {
                $box->emailLists = array();

                // Get lists for this service
                $remoteLists = $service->getLists();

                // loop over all remote lists
                foreach ($remoteLists as $rlist) {
                    if (isset($box->settings['lists'][$rlist['id']]) == false) {
                        continue;
                    }

                    if ($box->settings['lists'][$rlist['id']] == 'no') continue;
                    if ($box->settings['lists'][$rlist['id']] == 'checked') {
                        $rlist['checked'] = true;
                    } else {
                        $rlist['checked'] = false;
                    }

                    $box->emailLists[] = $rlist;
                }

                // Single List?
                $box->singleList = false;

                if ($account->service == 'mailchimp' || $account->service == 'benchmarkemail') {
                    $box->singleList = true;
                }
            }

            // -------------------------------------------------------
            //  Social Specific Functions
            // -------------------------------------------------------
            if ($account->type == 'social') {


            }

            // Add the default campaign so it gets all the vars needed
            array_unshift($campaigns, $box->emptyCampaign);

            // -------------------------------------------------------
            //  Loop over all campaigns
            // -------------------------------------------------------
            foreach ($campaigns as &$campaign) {

                if ($campaign->status == 'new') {
                    $campaign->deliver = ($box->serviceType == 'social') ? 'now' : 'test';
                } else {
                    $campaign->deliver = 'schedule';
                }



                // -------------------------------------------------------
                //  Schedule Data
                // -------------------------------------------------------
                $campaign->schedule = array();
                $campaign->schedule['day'] = ee()->campaigns_helper->formatDate('%d', $campaign->scheduled_at);
                $campaign->schedule['month'] = ee()->campaigns_helper->formatDate('%m', $campaign->scheduled_at);
                $campaign->schedule['hour'] = ee()->campaigns_helper->formatDate('%h', $campaign->scheduled_at);
                $campaign->schedule['min'] = ee()->campaigns_helper->formatDate('%i', $campaign->scheduled_at);
                $campaign->schedule['ampm'] = ee()->campaigns_helper->formatDate('%a', $campaign->scheduled_at);

                $campaign->scheduled_at = ee()->campaigns_helper->formatDate('%D, %F %d, %Y - %g:%i %A', $campaign->scheduled_at);

                if ($account->type == 'email') {

                    // HTML Templates
                    if (isset($campaign->data['html_tmpl']) === false) {
                        $campaign->data['html_tmpl'] = $box->settings['html_tmpl'];
                    }

                    if (isset($campaign->data['text_tmpl']) === false) {
                        $campaign->data['text_tmpl'] = $box->settings['text_tmpl'];
                    }

                    // Preview URLS
                    $service->prepTemplateUrls();
                    $campaign->previewHtml =  $service->tmplUrlHtml;
                    $campaign->previewText =  $service->tmplUrlText;

                    // Default Email Subject
                    if (isset($campaign->data['email_subject']) === false) {
                        $campaign->data['email_subject'] = $entry_title;
                    }
                }

                if ($account->type == 'social') {

                }
            }

            // Get our defailt campaign back
            $box->emptyCampaign = array_shift($campaigns);


            $box->campaigns = $campaigns;
            $boxes['b_'.$box->id] = $box;
        }


        // Last check
        if (empty($boxes) === true) {
            $out['errorMsg'] = '<p style="padding:10px;">No boxes have been enabled/created!</p>';
            return $out;
        }

        $data['success'] = true;
        $data['boxes'] = $boxes;
        $data['schedule'] = $this->getScheduleDropdowns();

        //$out['html'] = ee()->load->view('pbf/campaign', $data, true);

        $data['success'] = true;
        //exit(print_r($data));

        return $data;
	}

	// ********************************************************************************* //

    public function sendCampaign()
    {
        $out = array();
        $out['success'] = false;
        $out['errorMsg'] = '';
        $out['errors'] = array();
        $out['campaignId'] = '';

        if (isset($_POST['campaigns']['boxes']) === false || empty($_POST['campaigns']['boxes']) === true) {
            $out['errorMsg'] = 'No Configurations';
            return $out;
        }

        // -------------------------------------------------------
        //  Get Entry ID & Channel ID
        // -------------------------------------------------------
        $entry_id = ee()->input->get_post('entry_id');
        $channel_id = ee()->input->get_post('channel_id');

        if (!$entry_id) {
            $out['errorMsg'] = 'No Entry ID found';
            return $out;
        }

        $entry_title = '';

        if (!$channel_id) {
            $query = ee()->db->query("SELECT channel_id, title FROM exp_channel_titles WHERE entry_id = {$entry_id}");

            if ($query->num_rows() == 0) {
                $out['errorMsg'] = 'Entry could not be found';
                return $out;
            }

            $channel_id = $query->row('channel_id');
            $entry_title = $query->row('title');
        }

        // -------------------------------------------------------
        //  Get Fields
        // -------------------------------------------------------
        $query = ee()->db->query("SELECT field_group FROM exp_channels WHERE channel_id = {$channel_id}");

        if ($query->num_rows() == 0) {
            $out['errorMsg'] = 'No Field Group found';
            return $out;
        }

        $field_group = $query->row('field_group');

        // get the fields now
        $field_id = 0;
        $query = ee()->db->query("SELECT field_id FROM exp_channel_fields WHERE group_id = {$field_group} AND field_type = 'campaigns' ORDER BY field_order ASC LIMIT 1");

        if ($query->num_rows() > 0) {
            $field_id = $query->row('field_id');
        }

        // Get Channel Settings
        $settings = ee()->campaigns->getChannelSettings($channel_id, $field_id);

        // -------------------------------------------------------
        //  Loop over all posted boxes
        // -------------------------------------------------------
        foreach ($_POST['campaigns']['boxes'] as $box_id => $campaigns) {
            foreach ($campaigns as $order => $campaign) {

                $box = ee()->campaigns_model->getBox($box_id);
                $account = ee()->campaigns_model->getAccount($box->account_id);
                $account->boxSettings = $box->settings;

                $service = ee()->campaigns->getService($account, $box);
                $service->entryId = $entry_id;

                $test = false;
                if ($campaign['deliver'] == 'test') {
                    $test = true;
                }

                $res = $service->createCampaign($campaign, $test);

                if ($res === false) {
                    $out['errors'][] = array('box_id' => $box_id, 'campaign_order' => $order, 'error' => $service->errorMsg);
                    continue;
                }

                switch ($campaign['deliver']) {
                    case 'test':
                        $res = $service->testCampaign($campaign);
                        break;
                    case 'schedule':
                        $res = $service->scheduleCampaign($campaign);
                        break;
                    case 'now':
                        $res = $service->sendCampaign($campaign);
                        break;
                }

                if ($res === false) {
                    $out['errors'][] = array('box_id' => $box_id, 'campaign_order' => $order, 'error' => $service->errorMsg);

                    $res = $service->deleteCampaign();

                    if ($res === false) {
                        $out['errors'][] = array('box_id' => $box_id, 'campaign_order' => $order, 'error' => $service->errorMsg);
                    }

                    continue;
                }

                if ($test) {
                    $res = $service->deleteCampaign();

                    if ($res === false) {
                        $out['errors'][] = array('box_id' => $box_id, 'campaign_order' => $order, 'error' => $service->errorMsg);
                    }
                }

                if ($service->type == 'social') {
                    $query = ee()->db->query("SELECT channel_id, title FROM exp_channel_titles WHERE entry_id = {$service->entryId}");
                    $campaign_label = $query->row('title');
                } else {
                    $campaign_label = $campaign['email_subject'];
                }

                if ($campaign['deliver'] != 'test') {

                    $data = array();
                    $data['label'] = $campaign_label;
                    $data['service'] = $account->service;
                    $data['status'] = '';
                    $data['entry_id'] = $entry_id;
                    $campaign_id = ee()->campaigns_model->updateCampaign($data);

                    $out['sendId'] = ee()->campaigns_model->updateCampaignSend($campaign, $campaign_id, $box_id, $service);
                    $out['boxId'] = $box_id;

                    // Did we send a campaign?
                    $query = ee()->db->select('*')->from('exp_campaigns_sends')->where('id', $out['sendId'])->get();
                    $out['campaign'] = $query->row();
                    $out['campaign']->currentHtml = ee()->campaigns->getCampaignStatusUi($out['campaign']);
                    $out['campaign']->scheduled_at = ee()->campaigns_helper->formatDate('%D, %F %d, %Y - %g:%i %A', strtotime($out['campaign']->scheduled_at));
                }
            }
        }

        $out['success'] = true;
        return $out;
    }

    // ********************************************************************************* //

    public function getCampaignStatsUi()
    {
        $out = array();
        $out['success'] = false;
        $out['errorMsg'] = '';
        $out['html'] = '';

        $campaign = ee()->campaigns_model->getCampaign(ee()->input->post('campaign_id'));
        if (!$campaign) {
            $out['errorMsg'] = 'Campaign could not be found';
            return $out;
        }

        $service = ee()->campaigns_model->getService($campaign->service_id);
        if (!$service) {
            $out['errorMsg'] = 'Campaign could not be found';
            return $out;
        }

        $provider = ee()->campaigns->getProvider($service->provider, $service);
        $provider->currentCampaignId = $campaign->campaign_id;

        $out['html'] = $provider->execGetCampaignStatusHtml($campaign);

        $out['success'] = true;
        return $out;
    }

    // ********************************************************************************* //

    public function deleteCampaignSend()
    {
        $id = ee()->input->post('id');

        ee()->db->where('id', $id);
        ee()->db->delete('exp_campaigns_sends');
    }

    // ********************************************************************************* //

    public function delCampaign()
    {
        $out = array();
        $out['success'] = false;
        $out['errorMsg'] = '';

        $campaign = ee()->campaigns_model->getCampaign(ee()->input->post('campaign_id'));
        if (!$campaign) {
            $out['errorMsg'] = 'Campaign could not be found';
            return $out;
        }

        // Delete it from the DB
        ee()->db->where('id',$campaign->id)->delete('exp_campaigns');

        $service = ee()->campaigns_model->getService($campaign->service_id);
        if (!$service) {
            $out['errorMsg'] = 'Campaign could not be found';
            return $out;
        }

        $provider = ee()->campaigns->getProvider($service->provider, $service);
        $provider->currentCampaignId = $campaign->campaign_id;

        $res = $provider->deleteCampaign();

        if ($res === false) {
            $out['errorMsg'] = $provider->errorMsg;
        }

        $out['success'] = true;
        return $out;
    }

    // ********************************************************************************* //

    public function processScheduledCampaigns()
    {
        echo '<h2>CAMPAIGNS SCHEDULE SCRIPT</h2>';

        // Get all scheduled!
        echo '<p>';
        $query = ee()->db->where('status', 'scheduled')->count_all_results('exp_campaigns_sends');
        echo '<strong>Total Scheduled Campaigns: </strong>' . $query.'<br>';
        $query = ee()->db->where('status', 'scheduled')->where('scheduled_at <', date('Y-m-d H:i', ee()->localize->now))->get('exp_campaigns_sends');
        echo '<strong>Campaigns To Execute: </strong>' . $query->num_rows();
        echo '</p>';
        echo '<hr>';

        // Loop over all scheduled campaigns
        foreach ($query->result() as $row) {
            $campaign = ee()->campaigns_model->getCampaign($row->campaign_id);
            $box = ee()->campaigns_model->getBox($row->box_id);
            $account = ee()->campaigns_model->getAccount($box->account_id);
            $account->boxSettings = $box->settings;

            $service = ee()->campaigns->getService($account);
            $service->box = $box;

            echo '<p>';
            echo "<strong>Campaign:</strong> {$campaign->label} <br>";
            echo "<strong>Service:</strong> {$box->label} ({$account->service})<br>";


            if ($service->type == 'email') {
                echo "<strong>Sent:</strong> Yes (skipped, email provider) <br>";
                echo '</p>';

                ee()->db->set('status', 'sent');
                ee()->db->where('id', $row->id);
                ee()->db->update('exp_campaigns_sends');
                continue;
            }

            $campaignData = ee()->campaigns_helper->decodeJson($row->box_data, true);

            // -------------------------------------------------------
            //  Get Entry ID & Channel ID
            // -------------------------------------------------------
            $query = ee()->db->query("SELECT channel_id, title FROM exp_channel_titles WHERE entry_id = {$campaign->entry_id}");

            if ($query->num_rows() == 0) {
                echo '<strong>ERROR:</strong> Entry could not be found';
                echo '</p>';

                ee()->db->set('status', 'error');
                ee()->db->where('id', $row->id);
                ee()->db->update('exp_campaigns_sends');
                continue;
            }

            $channel_id = $query->row('channel_id');
            $entry_title = $query->row('title');

            // -------------------------------------------------------
            //  Get Fields
            // -------------------------------------------------------
            $query = ee()->db->query("SELECT field_group FROM exp_channels WHERE channel_id = {$channel_id}");

            if ($query->num_rows() == 0) {
                echo '<strong>ERROR:</strong> Field Group could not be found';
                echo '</p>';

                ee()->db->set('status', 'error');
                ee()->db->where('id', $row->id);
                ee()->db->update('exp_campaigns_sends');
                continue;
            }

            $field_group = $query->row('field_group');

            // get the fields now
            $field_id = 0;
            $query = ee()->db->query("SELECT field_id FROM exp_channel_fields WHERE group_id = {$field_group} AND field_type = 'campaigns' ORDER BY field_order ASC LIMIT 1");

            if ($query->num_rows() > 0) {
                $field_id = $query->row('field_id');
            }

            // -------------------------------------------------------
            //  Get Settings
            // -------------------------------------------------------
            //$settings = ee()->campaigns->getChannelSettings($channel_id, $field_id);
            //$service->channelSettings = $settings['services'][$row->service_id];

            // -------------------------------------------------------
            //  Init Provider
            // -------------------------------------------------------
            $service->currentCampaignId = $row->service_cid;

            $res = $service->sendCampaign($campaignData);

            if ($res === false) {
                echo "<strong>ERROR:</strong> {$service->errorMsg}";
                echo '</p>';

                ee()->db->set('status', 'error');
                ee()->db->where('id', $row->id);
                ee()->db->update('exp_campaigns_sends');
                continue;
            }

            echo "<strong>Sent:</strong> Success";
            echo '</p>';

            ee()->db->set('service_cid', $service->currentCampaignId);
            ee()->db->set('campaign_data', serialize($service->campaignData));
            ee()->db->set('status', 'sent');
            ee()->db->set('updated_at', date('Y-m-d H:i', ee()->localize->now));
            ee()->db->where('id', $row->id);
            ee()->db->update('exp_campaigns_sends');
        }
    }

    // ********************************************************************************* //

    private function getScheduleDropdowns()
    {
        $drop = array();
        $drop['day'] = '';
        $drop['month'] = '';
        $drop['year'] = '';
        $drop['hour'] = '';
        $drop['minute'] = '';
        $drop['ampm'] = '';

        $cday = ee()->campaigns_helper->formatDate('%d');
        $cmonth = ee()->campaigns_helper->formatDate('%m');
        $chour = ee()->campaigns_helper->formatDate('%h')+1;
        $cmin = date('i');

        // Day
        for ($i=1; $i <= 31; $i++) {
            $ii = sprintf("%02d", $i);
            $drop['day'][] = array('value' => $ii, 'label' => "&nbsp;&nbsp;{$ii}&nbsp;&nbsp;");
            //$drop['day'] .= '<option value="'.$ii.'"" '.(($cday == $ii) ? 'selected' : '').'>&nbsp;&nbsp;'.$ii.'&nbsp;&nbsp;</option>';
        }

        // Month
        for ($i=1; $i <= 12; $i++) {
            $ii = sprintf("%02d", $i);
            $drop['month'][] = array('value' => $ii, 'label' => '&nbsp;&nbsp;'.date('M', mktime(0, 0, 0, $i, 1, 2013)).'&nbsp;&nbsp;');
            //$drop['month'] .= '<option value="'.$ii.'"" '.(($cmonth == $ii) ? 'selected' : '').'>&nbsp;&nbsp;'.date('M', mktime(0, 0, 0, $i, 1, 2013)).'&nbsp;&nbsp;</option>';
        }

        // Year
        for ($i=date('Y'); $i < (date('Y')+3); $i++) {
            $drop['year'][] = array('value' => $i, 'label' => $i);
            //$drop['year'] .= '<option value="'.$i.'"" >'.$i.'</option>';
        }

        // Hour
        for ($i=0; $i < 13; $i++) {
            $ii = sprintf("%02d", $i);
            $drop['hour'][] = array('value' => $ii, 'label' => '&nbsp;&nbsp;'.$ii.'&nbsp;&nbsp;');
            //$drop['hour'] .= '<option value="'.$ii.'"" '.(($chour == $ii) ? 'selected' : '').'>&nbsp;&nbsp;'.$ii.'&nbsp;&nbsp;</option>';
        }

        // Minute
        for ($i=0; $i < 60; $i++) {
            $ii = sprintf("%02d", $i);
            $drop['minute'][] = array('value' => $ii, 'label' => '&nbsp;&nbsp;'.$ii.'&nbsp;&nbsp;');
            //$drop['minute'] .= '<option value="'.$ii.'"" '.(($cmin == $ii) ? 'selected' : '').'>&nbsp;&nbsp;'.$ii.'&nbsp;&nbsp;</option>';
        }

        // AM PM
        $meridiem = date('a');

        /*
        $ampm = '';
        $ampm .= '<option value="am" '.(($meridiem == 'am') ? 'selected' : '').'>&nbsp;&nbsp;AM&nbsp;&nbsp;</option>';
        $ampm .= '<option value="pm" '.(($meridiem == 'pm') ? 'selected' : '').'>&nbsp;&nbsp;PM&nbsp;&nbsp;</option>';
        $drop['ampm'] = $ampm;
        */

        $drop['ampm'][] = array('value' => 'am', 'label' => '&nbsp;&nbsp;AM&nbsp;&nbsp;');
        $drop['ampm'][] = array('value' => 'pm', 'label' => '&nbsp;&nbsp;PM&nbsp;&nbsp;');

        return $drop;
    }

    // ********************************************************************************* //

} // END CLASS

/* End of file ajax.campaigns.php  */
/* Location: ./system/expressionengine/third_party/campaigns/ajax.campaigns.php */
