<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Subscriptions Model File
 *
 * @package         DevDemon_subs
 * @author          DevDemon <http://www.devdemon.com> - Lead Developer @ Parscale Media
 * @copyright       Copyright (c) Parscale Media <http://www.parscale.com>
 * @license         http://www.devdemon.com/license/
 * @link            http://www.devdemon.com/subs/
 */
class Campaigns_model
{
    /**
     * Constructor
     *
     * @access public
     * @return void
     */
    public function __construct()
    {
        $this->EE =& get_instance();
        $this->site_id = $this->EE->config->item('site_id');
    }

    // ********************************************************************************* //

    public function getAccount($id=0)
    {
        $this->EE->db->select('*');
        $this->EE->db->from('exp_campaigns_accounts');
        $this->EE->db->where('id', $id);
        $query = $this->EE->db->get();

        if ($query->num_rows() == 0) {
            return false;
        }

        $item = $query->row();
        $item->settings = @unserialize($item->settings);

        return $item;
    }

    // ********************************************************************************* //

    public function getAccounts()
    {
        $items = array();

        $query = $this->EE->db->select('*')->from('exp_campaigns_accounts')->order_by('label')->where('site_id', $this->site_id)->get();

        foreach ($query->result() as $row) {
            $row->settings = @unserialize($row->settings);
            $items[] = $row;
        }

        return $items;
    }

    // ********************************************************************************* //

    public function getAccountsExtended()
    {
        $items = array();

        $query = $this->EE->db->select('*')->from('exp_campaigns_accounts')->where('site_id', $this->site_id)->order_by('label')->get();

        foreach ($query->result() as $row) {

            $row->settings = @unserialize($row->settings);

            // Get Author
            $q = $this->EE->db->select('screen_name')->from('exp_members')->where('member_id', $row->updated_by)->get();

            if ($query->num_rows() > 0) {
                $row->updated_by = $q->row('screen_name');
            } else {
                $row->updated_by = 'N/A';
            }

            $items[] = $row;
        }

        return $items;
    }

    // ********************************************************************************* //

    public function updateAccount()
    {
        $id = $this->EE->input->post('id');

        // -----------------------------------------
        // Check for fields
        // -----------------------------------------
        $this->EE->db->set('site_id', $this->site_id);
        $this->EE->db->set('updated_at', date('Y-m-d H:i:00', $this->EE->localize->now));
        $this->EE->db->set('updated_by', $this->EE->session->userdata('member_id'));

        if (isset($_POST['type'])) $this->EE->db->set('type', $this->EE->input->post('type'));
        if (isset($_POST['label'])) $this->EE->db->set('label', $this->EE->input->post('label'));
        if (isset($_POST['handle'])) $this->EE->db->set('handle', $this->EE->input->post('handle'));
        if (isset($_POST['service'])) {
            $service = $this->EE->input->post('service');
            $this->EE->db->set('service', $service);

            if (isset($_POST[$service]) === true) {
                $this->EE->db->set('settings', serialize($_POST[$service]));
            } else {
                $this->EE->db->set('settings', serialize(array()));
            }
        }

        // -----------------------------------------
        // Update Or Insert
        // -----------------------------------------
        if ($id > 0)
        {
            $this->EE->db->where('id', $id);
            $this->EE->db->update('exp_campaigns_accounts');
        }
        else
        {
            $this->EE->db->set('created_at', date('Y-m-d H:i:00', $this->EE->localize->now));
            $this->EE->db->set('created_by', $this->EE->session->userdata('member_id'));
            $this->EE->db->insert('exp_campaigns_accounts');
            $id = $this->EE->db->insert_id();
        }

        return $id;
    }

    // ********************************************************************************* //

    public function deleteAccount($id)
    {
        if (!$id) return;

        $query = $this->EE->db->select('id')->from('exp_campaigns_boxes')->where('account_id', $id)->get();

        foreach ($query->result() as $row) {
            $this->deleteConfig($row->id);
        }

        $this->EE->db->where('id', $id);
        $this->EE->db->delete('exp_campaigns_accounts');
    }

    // ********************************************************************************* //

    public function getBox($id=0)
    {
        $this->EE->db->select('*');
        $this->EE->db->from('exp_campaigns_boxes');
        $this->EE->db->where('id', $id);
        $query = $this->EE->db->get();

        if ($query->num_rows() == 0) {
            return false;
        }

        $item = $query->row();
        $item->settings = @unserialize($item->settings);

        return $item;
    }

    // ********************************************************************************* //

    public function getBoxes($channel_id=false, $field_id=false, $account_id=false)
    {
        $items = array();

        $this->EE->db->select('*');
        $this->EE->db->from('exp_campaigns_boxes');
        $this->EE->db->order_by('box_order');
        $this->EE->db->where('site_id', $this->site_id);

        if ($channel_id !== false) {
            $this->EE->db->where('channel_id', $channel_id);
        }

        if ($field_id !== false) {
            $this->EE->db->where('field_id', $field_id);
        }

        if ($account_id !== false) {
            $this->EE->db->where('account_id', $account_id);
        }

        $query = $this->EE->db->get();

        foreach ($query->result() as $row) {
            $row->settings = @unserialize($row->settings);
            $items[] = $row;
        }

        return $items;
    }

    // ********************************************************************************* //

    public function updateBox($channel_id=0, $field_id=0, $order=0, $box=array())
    {
        if (isset($box['id']) === false) return false;
        $id = $box['id'];

        // -----------------------------------------
        // Check for fields
        // -----------------------------------------
        $this->EE->db->set('site_id', $this->site_id);
        $this->EE->db->set('updated_at', date('Y-m-d H:i:00', $this->EE->localize->now));
        $this->EE->db->set('updated_by', $this->EE->session->userdata('member_id'));
        $this->EE->db->set('box_order', $order);

        if (isset($box['label'])) {
            $this->EE->db->set('label', $box['label']);
            $this->EE->db->set('slug', $box['label']);
        }

        if (isset($box['account_id'])) $this->EE->db->set('account_id', $box['account_id']);
        if ($channel_id) $this->EE->db->set('channel_id', $channel_id);
        if ($field_id) $this->EE->db->set('field_id', $field_id);

        if (isset($box['enabled'])) {
            $box['enabled'] = ($box['enabled'] == 'yes') ? 1 : 0;
            $this->EE->db->set('enabled', $box['enabled']);
        } else {
            $this->EE->db->set('enabled', 0);
        }

        if (isset($box['shorten_urls'])) {
            $box['shorten_urls'] = ($box['shorten_urls'] == 'yes') ? 1 : 0;
            $this->EE->db->set('shorten_urls', $box['shorten_urls']);
        } else {
            $this->EE->db->set('shorten_urls', 0);
        }

        if (isset($box['settings']) === true) {
            $this->EE->db->set('settings', serialize($box['settings']));
        } else {
            $this->EE->db->set('settings', serialize(array()));
        }

        // -----------------------------------------
        // Update Or Insert
        // -----------------------------------------
        if ($id > 0)
        {
            $this->EE->db->where('id', $id);
            $this->EE->db->update('exp_campaigns_boxes');
        }
        else
        {
            $this->EE->db->set('created_at', date('Y-m-d H:i:00', $this->EE->localize->now));
            $this->EE->db->set('created_by', $this->EE->session->userdata('member_id'));
            $this->EE->db->insert('exp_campaigns_boxes');
            $id = $this->EE->db->insert_id();
        }

        return $id;
    }

    // ********************************************************************************* //

    public function deleteBox($id)
    {
        if (!$id) return;

        $this->EE->db->where('box_id', $id);
        $this->EE->db->delete('exp_campaigns_sends');

        $this->EE->db->where('id', $id);
        $this->EE->db->delete('exp_campaigns_boxes');
    }

    // ********************************************************************************* //

    public function updateCampaign($data)
    {
        $this->EE->db->set('site_id', $this->site_id);
        $this->EE->db->set('label', $data['label']);
        //$this->EE->db->set('slug', $data['slug']);
        $this->EE->db->set('service', $data['service']);
        $this->EE->db->set('status', $data['status']);
        $this->EE->db->set('entry_id', $data['entry_id']);
        $this->EE->db->set('updated_by', $this->EE->session->userdata('member_id'));
        $this->EE->db->set('updated_at', date('Y-m-d H:i:00', $this->EE->localize->now));

        $query = $this->EE->db->query("SELECT * FROM exp_campaigns WHERE entry_id = {$data['entry_id']} LIMIT 1");

        if ($query->num_rows() > 0) {
            $id = $query->row('id');

            $this->EE->db->where('id', $id);
            $this->EE->db->update('exp_campaigns');
        } else {
            $this->EE->db->set('created_by', $this->EE->session->userdata('member_id'));
            $this->EE->db->set('created_at', date('Y-m-d H:i:00', $this->EE->localize->now));
            $this->EE->db->insert('exp_campaigns');
            $id = $this->EE->db->insert_id();
        }

        return $id;
    }

    // ********************************************************************************* //

    public function updateCampaignSend($campaign, $campaign_id, $box_id, &$service)
    {
        $status = ($campaign['deliver'] == 'now') ? 'sent' : 'scheduled';

        $this->EE->db->set('campaign_id', $campaign_id);
        $this->EE->db->set('status', $status);
        $this->EE->db->set('box_id', $box_id);
        $this->EE->db->set('service_cid', $service->currentCampaignId);
        $this->EE->db->set('scheduled_at', date('Y-m-d H:i:00', $service->scheduleDate));
        $this->EE->db->set('box_data', ee()->campaigns_helper->generateJson($campaign));
        $this->EE->db->set('campaign_data', serialize($service->campaignData));
        $this->EE->db->set('updated_by', $this->EE->session->userdata('member_id'));
        $this->EE->db->set('updated_at', date('Y-m-d H:i:00', $this->EE->localize->now));

        if ($campaign['id'] > 0) {
            $id = $campaign['id'];

            $this->EE->db->where('id', $id);
            $this->EE->db->update('exp_campaigns_sends');
        } else {
            $this->EE->db->set('created_by', $this->EE->session->userdata('member_id'));
            $this->EE->db->set('created_at', date('Y-m-d H:i:00', $this->EE->localize->now));
            $this->EE->db->insert('exp_campaigns_sends');
            $id = $this->EE->db->insert_id();
        }

        return $id;
    }

    // ********************************************************************************* //

    public function getChannels()
    {
        $channels = array();

        $query = $this->EE->db->select('channel_id, channel_title')->from('exp_channels')->where('site_id', $this->site_id)->order_by('channel_title','asc')->get();

        foreach ($query->result() as $row) {
            $channels[$row->channel_id] = $row->channel_title;
        }

        return $channels;
    }

    // ********************************************************************************* //

    public function getChannel($id=0)
    {
        $query = $this->EE->db->select('channel_id, channel_title')->from('exp_channels')->where('channel_id', $id)->get();

        return $query->row();
    }

    // ********************************************************************************* //

    public function saveSettings($newSettings=array())
    {
        if (is_array($newSettings) === false) $newSettings = array();

        // Get the module settings from the DB
        $this->EE->db->select('settings');
        $this->EE->db->where('module_name', 'Campaigns');
        $query = $this->EE->db->get('exp_modules');

        if ($query->num_rows() > 0) {
            $settings = @unserialize($query->row('settings'));
        }

        // Still falsy?
        if ($settings == false) {
            $settings = array();
        }

        $settings = array_merge($settings, $newSettings);

        // Put it Back
        $this->EE->db->set('settings', serialize($settings));
        $this->EE->db->where('module_name', 'Campaigns');
        $this->EE->db->update('exp_modules');
    }

    // ********************************************************************************* //

    public function getCampaigns()
    {
        $items = array();

        $query = $this->EE->db->select('*')->from('exp_campaigns')->where('site_id', $this->site_id)->order_by('created_at', 'DESC')->get();

        foreach ($query->result() as $row) {
            $items[] = $row;
        }

        return $items;
    }

    // ********************************************************************************* //

    public function getCampaign($id=0)
    {
        $query = $this->EE->db->select('*')->from('exp_campaigns')->where('id', $id)->get();

        if ($query->num_rows() == 0) {
            return false;
        }

        return $query->row();
    }

    // ********************************************************************************* //

} // END CLASS

/* End of file campaigns_model.php  */
/* Location: ./system/expressionengine/third_party/campaigns/models/campaigns_model.php */
