<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Charge Action Model class
 *
 * @package         charge_ee_addon
 * @version         1.9.6
 * @author          Joel Bradbury ~ <joel@squarebit.co.uk>
 * @link            http://squarebit.co.uk/software/expressionengine/charge
 * @copyright       Copyright (c) 2015, Joel Bradbury/Square Bit
 */
class Charge_action_model extends Charge_model {

    public $data;
    public $action;
    private $fields;
    private $channels;


    public $settings = array(
                            'entry_update'  =>
                                 array('enabled'     => 'bool',
                                       'status'  => 'required'),
                            'entry_create' =>
                                 array('enabled'     => 'bool',
                                       'title'     => 'required',
                                       'status'    => 'required',
                                       'channel'   => 'required'),
                            'member_subscription'  =>
                                 array('enabled'     => 'bool',
                                       'id'  => 'required'),
                            'email_customer' =>
                                 array('enabled'     => 'bool',
                                       'template'  => 'required',
                                       'subject'   => 'required'),
                            'email_admin' =>
                                 array('enabled'     => 'bool',
                                       'addresses' => 'required',
                                       'template'  => 'required',
                                       'subject'   => 'required'),);

    public $this_settings = array();

    public $errors = array();
    public $actions = array();
    public $protected = array();

    // --------------------------------------------------------------------
    // METHODS
    // --------------------------------------------------------------------

    /**
     * Constructor
     *
     * @access      public
     * @return      void
     */
    function __construct()
    {
        // Call parent constructor
        parent::__construct();

        // Initialize this model
        $this->initialize(
            'charge_action',
            'action_id',
            array(
                'name'                  => 'varchar(255) NOT NULL default ""',
                'shortname'             => 'varchar(255) NOT NULL default ""',
                'timestamp'             => 'int(10) unsigned NOT NULL default 0',
                'last_triggered'        => 'int(10) unsigned NOT NULL default 0',
                'triggered_count'       => 'int(10) unsigned NOT NULL default 0',
                'settings'              => 'text')

        );

    }

    // --------------------------------------------------------------------

    /**
     * Installs given table
     *
     * @access      public
     * @return      void
     */
    public function install()
    {
        parent::install();
    }




    public function get_all()
    {
        $subs = parent::get_all();

        foreach($subs as $sub_key => $sub_val) {
            $subs[$sub_key]['settings'] = unserialize($sub_val['settings']);
        }

        return $subs;
    }

    public function get_one( $id, $key = 'action_id')
    {
        $item = parent::get_one($id, $key);

        if(empty($item)) return FALSE;
        // Decode the settings
        $item['settings'] = unserialize($item['settings']);

        return $item;
    }


    public function save($action_id = 0)
    {
        $this->this_settings = $this->_get_settings();
        $this->this_settings['name'] = ee()->input->post('name');
        $this->this_settings['shortname'] = ee()->input->post('shortname');

        if(!isset($this->this_settings['name']) OR $this->this_settings['name'] == '') {
            $this->errors['name'] = lang('charge_action_no_name');
        }

        if(!isset($this->this_settings['shortname']) OR $this->this_settings['shortname'] == '') {
            $this->errors['shortname'] = lang('charge_action_no_shortname');
        }

        // Clean the shortname
        $clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $this->this_settings['shortname']);
        $clean = strtolower(trim($clean, '-'));
        $clean = preg_replace("/[\/|+ -]+/", '-', $clean);

        $this->this_settings['shortname'] = $clean;

        if(!empty($this->errors)) return FALSE;


        // Ok, looks valid, set it up to dave
        $data['name'] = $this->this_settings['name'];
        $data['shortname'] = $this->this_settings['shortname'];
        $data['settings'] = serialize(array()); // Reset to empty just incase

        unset($this->this_settings['name']);
        unset($this->this_settings['shortname']);

        if(!empty($this->this_settings)) $data['settings'] = serialize($this->this_settings);


        $data['timestamp'] = time();
        $data['site_id'] = ee()->config->item('site_id');


        if($action_id == 0 OR $action_id == '') {
            // New
            self::insert($data);
        } else {
            self::update($action_id, $data);
        }

        return TRUE;
    }


    private function _get_settings()
    {
        $settings = array();


        foreach($this->settings as $setting_group_key => $setting_group) {
            $this_bool = FALSE;

            foreach($setting_group as $key => $validation) {

                if(ee()->input->post($setting_group_key.'_'.$key) != '') {
                    $settings[$setting_group_key.'_'.$key] = ee()->input->post($setting_group_key.'_'.$key);
                }

                if($validation == 'bool') {
                    $this_bool = (ee()->input->post($setting_group_key.'_'.$key) == 'yes' ? TRUE : FALSE );
                }

                if($this_bool AND $validation == 'required' AND ee()->input->post($setting_group_key.'_'.$key) == '') {
                    $this->errors[$setting_group_key.'_'.$key] = lang('charge_required_setting');
                }
            }
        }


        return $settings;
    }

    public function touch($action_id, $triggered_count)
    {
        $data['last_triggered'] = time();
        $data['triggered_count'] = $triggered_count;

        parent::update($action_id, $data);

        return;
    }


    public function run($action_name, $data, $protected = array())
    {
        $this->_load();

        $action = $this->get_one($action_name, 'shortname');

        $this->protected = $protected;

        if(empty($action)) return FALSE; // Log it?

        //Bump the triggered count and last_triggered
        $this->touch($action['action_id'], $action['triggered_count']+1);

        // Actually run them
        foreach($this->settings as $setting_group_key => $setting_group) {

            foreach($setting_group as $key => $validation) {

                if($validation == 'bool' AND isset($action['settings'][$setting_group_key.'_'.$key]) AND $action['settings'][$setting_group_key.'_'.$key] == 'yes') {

                    ee()->charge_log->log_action_start(array('action_type' => $setting_group_key, 'action' => $action, 'data' => $data));
                    // Trigger this action
                    $this->_run($setting_group_key, $action, $data);
                }
            }
        }


        return TRUE;
    }


    private function _run($group_key, $action, $data)
    {
        switch($group_key) {
            case 'email_admin' :
            case 'email_customer' :
                $this->_run_action_email($group_key, $action, $data);
                break;
            case 'entry_update' :
            case 'entry_create' :
                $this->_run_action_entry($group_key, $action, $data);
                break;
            case 'member_subscription' :
                $this->_run_action_member($group_key, $action, $data);
                break;
        }

    }


    private function _run_action_member($group_key, $action, $data)
    {
        // If we don't have a member (or the member is an SA, ignore)
        if(ee()->session->userdata('member_id') == '0' OR ee()->session->userdata('group_id') == '1') return FALSE;

        // Get the subscription information
        if(!isset($action['settings']['member_subscription_id'])) return FALSE;

        $customer_id = '';

        if(isset($data['customer_id']) && $data['customer_id'] != '') $customer_id = $data['customer_id'];
        elseif(isset($data['stripe_id']) && isset($data['stripe_object']) && $data['stipe_object'] == 'customer' && $data['stripe_id'] != '') $customer_id = $data['stripe_id'];
        elseif(isset($data['stripe_customer']) && $data['stripe_customer'] != '') $customer_id = $data['stripe_customer'];
        elseif(isset($data['stripe_subscription_customer'])) $customer_id = $data['stripe_subscription_customer'];
        else {
            // Legacy handling
            $stripe = array();
            if(isset($data['stripe']) && $data['stripe'] != '') $stripe = unserialize(base64_decode($data['stripe']));
            if((is_array($stripe) AND isset($stripe['object']) AND $stripe['object'] == 'customer')) $customer_id = $stripe['id'];
            if(is_array($stripe)) {
                if(isset($stripe['customer'])) $customer_id = $stripe['customer'];
                else if(isset($stripe['object']) == 'customer') $customer_id = $stripe['id'];
            }
        }



        // Move member to group, and keep a marker so we can move them back again later
        $status = ee()->charge_subscription->add_member($action['settings']['member_subscription_id'], $customer_id, $data['charge_id']);

        if($status == FALSE) return FALSE;

        return TRUE;
    }


    private function _run_action_entry($group_key, $action, &$data)
    {
        // Do we have protected data
        if(empty($this->protected)) return FALSE;

        $state = 'update';
        if($group_key == 'entry_create') $state = 'create';


        if(isset($data['charge_id'])) $data['id'] = $data['charge_id'];

        $actors['status'] = $action['settings']['entry_'.$state.'_status'];
        if($state == 'create') {
            $actors['title'] = $action['settings']['entry_create_title'];
        }

        foreach($this->protected as $pkey => $pval) {
            if(strpos($pkey, 'on_success:') !== FALSE) {

                $pkey = substr($pkey, strlen('on_success:'));

                if(!in_array($pkey, array('entry_id', 'url_title'))) {
                    $actors[$pkey] = $pval;
                }
            }
        }

        $cdata = array();

        if($state == 'update')
        {
            // Pull an exsiting entry

            // We need either an entry_id, or url_title to continue
            if(!isset($this->protected['on_success:entry_id']) AND !isset($this->protected['on_success:url_title'])) return FALSE;

            // Try to pull our SINGLE entry
            ee()->db->select('t.entry_id, t.title, t.author_id, t.channel_id');
            ee()->db->from('channel_titles t');


            if(isset($this->protected['on_success:entry_id']) AND is_numeric($this->protected['on_success:entry_id'])) {
                ee()->db->where('t.entry_id', $this->protected['on_success:entry_id']);
            } elseif(isset($this->protected['on_success:url_title']) AND $this->protected['on_success:url_title'] != '') {
                ee()->db->where('t.url_title', $this->protected['on_success:url_title']);
            }
            else {
                return FALSE;
            }

            $r = ee()->db->get()
                ->row_array();


            if(empty($r)) return FALSE;

            $cdata = $this->_setup($r['channel_id'], $r['entry_id'], $actors, $data);

        } else {

            $cdata = $this->_setup($action['settings']['entry_create_channel'], '', $actors, $data);

            // Create a new entry
            // This only creates a stub of an entry with the barest of data.
            // We'll later use the same logic as for update_entry to update all the fields
            // and dynamic values
            $r = $this->_create_entry($action['settings']['entry_create_channel'], $cdata['titles']['title']);
            if($r == FALSE OR empty($r)) return FALSE;
        }

        if(!ee()->api_channel_entries->entry_exists($r['entry_id'])) {
            return FALSE;
        }

        // We can't use the channel entries api to update here
        // because it'll wipe out any custom fields we aren't touching
        // use our own query instead, we'll also bypass all the hooks
        $ret = $this->_update_entry($r['entry_id'], $cdata);

        if($ret === FALSE) return FALSE;

        $data['connected_entry_id'] = $r['entry_id'];
        // Mark this as connected on the original item too
        $this->_mark_connected_entry($data['charge_id'], $r['entry_id']);

        return TRUE;
    }

    private function _run_action_email($group_key, $action, $data)
    {
        $addresses = array();
        if($group_key == 'email_customer') $addresses[] = $data['customer_email'];
        elseif(isset($action['settings'][$group_key.'_addresses'])) {
            foreach(explode(',', $action['settings'][$group_key.'_addresses']) as $address) {
                $addresses[] = trim($address);
            }
        }
        if(empty($addresses)) {
            $this->_log_action('email', FALSE, array('reason' => 'No valid email addresses found to send to', 'action_type' => $group_key, 'action' => $action, 'data' => $data));
            return FALSE;
        }

        $this->_load();
        $subject = $action['settings'][$group_key.'_subject'];

        $email = ee()->charge_email->send($data['hash'], $addresses, $subject, $action['settings'][$group_key.'_template']);

        if($email === FALSE) {
            $this->_log_action('email', FALSE, array('reason' => 'Failed when passing to email class','addresses' => $addresses, 'subject' => $subject, 'action' => $action));
        }

        $this->_log_action('email', TRUE, array('addresses' => $addresses, 'subject' => $subject, 'action' => $action));

        return $email;
    }



    /**
     * LEGACY ACTION
     **/
    // --------------------------------------------------------------------
    // --------------------------------------------------------------------

    public function trigger($action_encoded, $data)
    {
        $this->data = $data;

        $action = $this->_decode_action($action_encoded);

        if($action === FALSE OR !is_array($action)) {
            ee()->charge_log->log_action_failed_to_decode(array('encoded_action' => $action_encoded, 'data' => $this->data));
            return FALSE;
        }

        // Ok, appears a valid action. Fire it off
        $state = $this->_run_action($action);
        if($action === FALSE) {
            ee()->charge_log->log_action_failed_to_run(array('action' => $action, 'data' => $this->data));
            return FALSE;
        }

        ee()->charge_log->log_action_ran(array('entry' => $state, 'action' => $action, 'data' => $this->data));
    }


    private function _decode_action($action)
    {
        // Our encoded actions are made of a few things.
        // A. A '>' marker, denoting the selector, and action
        // B. Individual parts are split between key and value with ':'
        // Just deal with a single action right now
        // eg. "entry_id:12>status:open"
        // eg. "current_member>group:Paid,expiry=30d"

        $actions = explode('>', $action);
        if(count($actions) != 2) return FALSE;

        // Gather our selectors
        $data['selectors'] = explode(',',$actions[0]);
        $data['actors'] = explode(',',$actions[1]);

        $data = $this->_validate_and_clean_acts($data);

        if($data === FALSE) return FALSE;

        // Ok, Appears valid. Try to do something useful with it
        return $data;
    }

    private function _validate_and_clean_acts($data)
    {
        $has_error = FALSE;
        $ret = array();

        foreach($data as $set => $values) {

            foreach($values as $key => $selector) {
                $selector = $this->_reparse($selector);

                $temp = explode(':', $selector);

                if(count($temp) != 2) {
                    $has_error = TRUE;
                } elseif ($temp[0] == '' OR $temp[1] == '') {
                    $has_error = TRUE;
                }

                $ret[$set][$temp[0]] = $temp[1];
            }
        }

        if($has_error == TRUE) return FALSE;

        return $ret;
    }

    private function _reparse($value)
    {
        $value = str_replace( array('{member_id}'), ee()->session->userdata['member_id'], $value);

        foreach($this->data as $key => $val) {
            $value = str_replace('{'.$key.'}', $val, $value);
        }


        return $value;
    }


    private function _run_action($action)
    {
        $this->_load();

        // Try to pull our SINGLE entry
        ee()->db->select('t.entry_id, t.title, t.author_id, t.channel_id');
        ee()->db->from('channel_titles t');

        foreach($action['selectors'] as $key => $val) {
            ee()->db->where('t.'.$key, $val);
        }

        $r = ee()->db->get()
            ->row_array();

        if(empty($r)) return FALSE;


        if(!ee()->api_channel_entries->entry_exists($r['entry_id'])) {
            return FALSE;
        }

        $data = $this->_setup($r['channel_id'], $r['entry_id'], $action['actors']);

        // We can't use the channel entries api to update here
        // because it'll wipe out any custom fields we aren't touching
        // use our own query instead, we'll also bypass all the hooks
        $ret = $this->_update_entry($r['entry_id'], $data);

        if($ret === FALSE) return FALSE;
        return $r;
    }


    private function _load()
    {
        ee()->load->library('template', null, 'TMPL');
        ee()->load->library('api');
        ee()->load->library('api/Api_channel_entries');
        ee()->load->library('api/Api_channel_fields');
        ee()->load->library('api/Api_channel_structure');
        ee()->load->library('email');
        ee()->load->model('channel_entries_model');

    }

    private function _setup($channel_id, $entry_id = 0, $actors, $variables = array())
    {
        $this->channels = ee()->api_channel_structure->get_channels()->result_array();
        $this->channels = associate_results($this->channels, 'channel_id');

        $field_group = $this->channels[$channel_id]['field_group'];

        if($field_group == '') $this->fields = array();
        else {

            ee()->db->where('group_id', $field_group);
            $this->fields = ee()->api_channel_fields->fetch_custom_channel_fields();
            $this->fields = current($this->fields['custom_channel_fields']);
        }


        $ret = array();


        foreach($actors as $act_name => $act_val) {

            if(!empty($variables)) {
                $act_val = ee()->TMPL->parse_variables($act_val, array($variables));
            }

            if($act_name == 'status' OR $act_name == 'title') {
                $ret['titles'][$act_name] = $act_val;
            } else {
                if(isset($this->fields[$act_name])) $ret['data']['field_id_'.$this->fields[$act_name]] = $act_val;
            }
        }

        return $ret;
    }

    private function _update_entry($entry_id, $data)
    {
        if(empty($data)) return TRUE;

        if (ee()->extensions->active_hook('charge_action_update_entry_start') === TRUE)
        {
            ee()->extensions->call('charge_action_update_entry_start', $entry_id, $data, $this->data);
            if (ee()->extensions->end_script === TRUE) return;
        }

        if(isset($data['titles'])) {
            ee()->db->where('entry_id', $entry_id)
                ->update('channel_titles', $data['titles']);
        }

        if(isset($data['data'])) {
            ee()->db->where('entry_id', $entry_id)
                ->update('channel_data', $data['data']);
        }

        if (ee()->extensions->active_hook('charge_action_update_entry_end') === TRUE)
        {
            ee()->extensions->call('charge_action_update_entry_end', $entry_id, $data, $this->data);
            if (ee()->extensions->end_script === TRUE) return;
        }

        return TRUE;
    }

    private function _create_entry($channel_id, $title)
    {
        $data['author_id']          = 1;
        $data['entry_date']         = ee()->localize->now;
        $data['title']              = $title;

        // We need to temporarily change the user_group for this user, so the
        // channel entries api will let us create this entry
        $current_user_group = ee()->session->userdata('group_id');
        $current_member_id = ee()->session->userdata('member_id');

        ee()->session->userdata['group_id'] = 1;
        ee()->session->userdata['member_id'] = 1;

        ee()->api_channel_fields->setup_entry_settings($channel_id, array());


        if (ee()->extensions->active_hook('charge_action_create_entry_start') === TRUE)
        {
            ee()->extensions->call('charge_action_create_entry_start', $channel_id, $data, $this->data);
            if (ee()->extensions->end_script === TRUE) return;
        }


        if( ee()->api_channel_entries->submit_new_entry( $channel_id, $data ) === FALSE )
        {
            $this->errors = ee()->api_channel_entries->get_errors();

            ee()->session->userdata['group_id'] = $current_user_group;
            ee()->session->userdata['member_id'] = $current_member_id;

            return FALSE;
        }

        $entry_id = ee()->api_channel_entries->entry_id;

        // reset their group_id
        ee()->session->userdata['group_id'] = $current_user_group;
        ee()->session->userdata['member_id'] = $current_member_id;

        // ok - now directly update the entry record with the correct author
        $this->_change_entry_author($entry_id, $current_member_id);

        $row['entry_id'] = $entry_id;
        $row['channel_id'] = $channel_id;
        $row['title'] = $title;


        if (ee()->extensions->active_hook('charge_action_create_entry_end') === TRUE)
        {
            ee()->extensions->call('charge_action_create_entry_end', $entry_id, $data, $this->data);
            if (ee()->extensions->end_script === TRUE) return;
        }

        return $row;
    }


    private function _change_entry_author($entry_id, $author_id)
    {
        if($author_id <= 1 ) return;

        $data = array('author_id' => $author_id);

        ee()->db->where('entry_id', $entry_id)
                    ->update('channel_titles', $data);

        return;
    }

    private function _log_action($type, $state, $data = array())
    {
        $name = $type.'_'.($state?'success':'failed');

        ee()->charge_log->log_action_response($name, $data);
    }

    private function _mark_connected_entry($charge_id, $entry_id)
    {
        ee()->charge_stripe->update($charge_id, array('connected_entry_id' => $entry_id));
        return;
    }

    // --------------------------------------------------------------------
    // --------------------------------------------------------------------

} // End class

/* End of file Charge_action_model.php */