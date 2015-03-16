<?php defined('BASEPATH') or exit('No direct script access allowed');

// include config file
include PATH_THIRD . 'charge/config' . EXT;

class Charge_mcp
{
    public $module_name;
    private $nocache;
    public $cached_vars;

    public $limit = '15';
    private $_theme_url;


    function __construct()
    {

        //--------------------------------------------
        //	Alias to get_instance()
        //--------------------------------------------
        if (!function_exists('ee')) {
            function ee()
            {
                return get_instance();
            }
        }

        $this->module_name = strtolower(str_replace('_mcp', '', get_class($this)));
        $this->base = str_replace('&amp;D=', '&D=', BASE . '&C=addons_modules&M=show_module_cp&module=' . $this->module_name);


        $this->contols[] = $this->base . AMP . 'method=settings';
        $this->_theme_url = $this->_get_package_theme_url();

        $this->cached_vars = array();


        $this->cached_vars['subscriptions_enabled'] = false;
        if (CHARGE_SUBSCRIPTIONS_ENABLED === true) {
            $this->cached_vars['subscriptions_enabled'] = true;
        }

        $controls = array(lang('charge_title')   => $this->base . '&method=index',
                          lang('charge_actions') => $this->base . '&method=actions',
                          lang('charge_coupons') => $this->base . '&method=coupons');

        if ($this->cached_vars['subscriptions_enabled'] == true) {
            $controls[lang('charge_subscriptions')] = $this->base . '&method=subscriptions';
        }

        $controls[lang('charge_logs')] = $this->base . '&method=logs';
        $controls[lang('charge_settings')] = $this->base . '&method=settings';

        ee()->cp->set_right_nav($controls);

        $this->cached_vars['encrypt_key_set'] = false;
        if (ee()->config->item('encryption_key') != '') $this->cached_vars['encrypt_key_set'] = true;

        $this->_add_morphine();
        $this->_check_csrf_exempt();

        // Define the package path
//    	ee()->load->add_package_path(PATH_THIRD.'charge');

        // Load our helper
        ee()->load->helper('Charge');
        ee()->lang->loadfile('charge');
        // Load base model
        if (!class_exists('Charge_model')) ee()->load->library('Charge_model');
        if (!isset(ee()->charge_stripe)) ee()->charge_model->load_models();
    }


    function index()
    {
        ee()->view->cp_page_title = lang('charge_module_name');

        $item_count = ee()->charge_stripe->count_all();

        $offset = $this->_pagination($item_count, 'index');

        $this->cached_vars['charges'] = ee()->charge_stripe->get_all($this->limit, $offset, false);

        $this->_get_overview();

        $this->cached_vars['export_uri'] = $this->base . AMP . 'method=export';

        return ee()->load->view('mcp_index', $this->cached_vars, true);
    }


    public function export()
    {
        $output = $this->_get_export_data();

        $this->_download_send_headers("charge_export_" . date("Y-m-d") . ".csv");
        echo $this->_array_to_csv($output);
        die();
    }


    public function coupons()
    {
        ee()->view->cp_page_title = lang('charge_coupons');

        $this->cached_vars['add_coupon_uri'] = $this->base . '&method=view_coupon&new=yes';
        $this->cached_vars['coupons'] = ee()->charge_coupon->get_all();

        $this->cached_vars['view_single_coupon_uri'] = $this->base . '&method=view_coupon&id=';
        $this->cached_vars['delete_coupon_uri'] = $this->base . '&method=delete_coupon&id=';

        return ee()->load->view('mcp_coupons', $this->cached_vars, true);
    }

    public function view_coupon()
    {
        // --------------------------------------
        // Load some libraries
        // --------------------------------------
        ee()->load->library('javascript');
        ee()->cp->set_breadcrumb($this->base, lang('charge_module_name'));


        ee()->view->cp_page_title = lang('charge_coupons');
        $this->cached_vars['form_post_url'] = $this->base . '&method=view_coupon';
        $this->cached_vars['errors'] = array();

        $this->cached_vars['supported_currencies'] = ee()->charge_stripe->get_currencies();

        $new_coupon = false;

        $id = ee()->input->get_post('id');
        if ($id != '') {
            $coupon = ee()->charge_coupon->get_one(ee()->input->get_post('id'));

        } else {
            $new_coupon = true;
            $coupon = array('name' => '', 'code' => '', 'settings' => array(), 'stripe_id' => '', 'coupon_id' => '', 'type' => 'fixed');
        }


        if (ee()->input->post('edit_coupon') == 'yes') {
            // This is a post
            $status = ee()->charge_coupon->save($id);
            if ($status == false) {
                // Validation failed
                $this->cached_vars['errors'] = ee()->charge_coupon->errors;
                $this->cached_vars['has_errors'] = true;

                foreach (ee()->charge_coupon->this_settings as $key => $val) {
                    if (in_array($key, array('name', 'code'))) $coupon[$key] = $val;
                    else $coupon[$key] = $val;
                }
            } else {
                // Good
                ee()->functions->redirect($this->base . '&method=coupons');
                exit();
            }
        }

        if (empty($coupon)) {
            ee()->functions->redirect($this->base . '&method=coupons');
            exit();
        }

        $this->cached_vars['coupon'] = $coupon;

        return ee()->load->view('mcp_add_coupon', $this->cached_vars, true);
    }

    public function logs()
    {
        ee()->view->cp_page_title = lang('charge_logs');

        $this->cached_vars['members_cp_uri'] = BASE . '&C=myaccount&id=';

        $item_count = ee()->charge_log->count_all();

        $this->limit = '25';

        $offset = $this->_pagination($item_count, 'logs');

        $this->cached_vars['logs'] = ee()->charge_log->get_all($this->limit, $offset);

        $member_ids = array('0');
        foreach ($this->cached_vars['logs'] as $log) {
            $member_ids[$log['member_id']] = $log['member_id'];
        }


        $this->cached_vars['members'] = $this->_get_members($member_ids);

        return ee()->load->view('mcp_logs', $this->cached_vars, true);

    }


    function actions()
    {
        ee()->view->cp_page_title = lang('charge_actions');

        $this->cached_vars['add_action_uri'] = $this->base . '&method=view_action&new=yes';
        $this->cached_vars['actions'] = ee()->charge_action->get_all();

        $this->cached_vars['view_single_action_uri'] = $this->base . '&method=view_action&id=';
        $this->cached_vars['delete_action_uri'] = $this->base . '&method=delete_action&id=';

        return ee()->load->view('mcp_actions', $this->cached_vars, true);
    }


    function subscriptions()
    {
        ee()->view->cp_page_title = lang('charge_subscriptions');

        $this->cached_vars['view_single_subscription_uri'] = $this->base . '&method=view_subscription&id=';
        $this->cached_vars['subscriptions'] = ee()->charge_subscription->get_all();
        $this->cached_vars['add_subscription_uri'] = $this->base . '&method=view_subscription&new=yes';
        $this->cached_vars['view_sub_members_uri'] = $this->base . '&method=view_sub_members&id=';
        $this->cached_vars['delete_sub_uri'] = $this->base . '&method=delete_sub&id=';
        $this->cached_vars['actions_uri'] = $this->base . '&method=actions';

        return ee()->load->view('mcp_subscriptions', $this->cached_vars, true);
    }


    function view_sub_members()
    {
        ee()->view->cp_page_title = lang('charge_subscription_members');

        $this->cached_vars['subscription'] = ee()->charge_subscription->get_one(ee()->input->get('id'));

        ee()->db->where('subscription_id', ee()->input->get('id'));
        $this->cached_vars['members'] = ee()->charge_subscription_member->get_all();

        return ee()->load->view('mcp_view_sub_members', $this->cached_vars, true);
    }


    public function webhooks()
    {
        ee()->view->cp_page_title = lang('charge_webhooks');

        $item_count = ee()->charge_webhook->count_all();

        $this->limit = '25';

        $offset = $this->_pagination($item_count, 'logs');

        $this->cached_vars['logs'] = ee()->charge_webhook->get_all($this->limit, $offset);

        return ee()->load->view('mcp_webhooks', $this->cached_vars, true);
    }


    public function delete_sub()
    {
        $subscription = ee()->charge_subscription->get_one(ee()->input->get_post('id'));

        if (empty($subscription)) ee()->functions->redirect($this->base . '&method=subscriptions');

        ee()->charge_subscription->delete($subscription['subscription_id']);

        ee()->charge_subscription_member->delete($subscription['subscription_id'], 'subscription_id');

        ee()->functions->redirect($this->base . '&method=subscriptions');
    }


    public function delete_action()
    {
        $action = ee()->charge_action->get_one(ee()->input->get_post('id'));

        if (empty($action)) ee()->functions->redirect($this->base . '&method=actions');

        ee()->charge_action->delete($action['action_id']);

        ee()->functions->redirect($this->base . '&method=actions');
    }


    public function delete_coupon()
    {
        $coupon = ee()->charge_coupon->get_one(ee()->input->get_post('id'));

        if (empty($coupon)) ee()->functions->redirect($this->base . '&method=coupons');

        ee()->charge_coupon->delete($coupon['coupon_id']);

        ee()->functions->redirect($this->base . '&method=coupons');
    }
    // --------------------------------------------------------------------

    /**
     * Module home page
     *
     * @access      public
     * @return      string
     */
    function settings()
    {
        // --------------------------------------
        // Load some libraries
        // --------------------------------------
        ee()->load->library('javascript');

        ee()->view->cp_page_title = lang('charge_settings');
        ee()->cp->set_breadcrumb($this->base, lang('charge_module_name'));
        $this->cached_vars['form_post_url'] = $this->base . '&method=save_settings';
        $this->cached_vars['webhook_log_uri'] = $this->base . '&method=webhooks';
        $this->cached_vars['currencies'] = ee()->charge_stripe->get_currencies();

        $this->_get_callback_urls();

        foreach (explode("|", CHARGE_PREFERENCES) as $val) {
            $this->cached_vars[$val] = ee()->config->item($val);
        }

        $default_site_email_settings = ee()->charge_email->get_site_defaults();
        foreach (array('charge_email_send_from' => 'from', 'charge_email_send_name' => 'name', 'charge_email_send_reply_to' => 'reply_to', 'charge_email_send_reply_to_name' => 'reply_to_name') as $key => $val) {
            if($this->cached_vars[$key] == '') {
                $this->cached_vars[$key] = $default_site_email_settings[$val];
            }
        }


        // Grab the email settings if not already set in preferences
        return ee()->load->view('mcp_settings', $this->cached_vars, true);
    }


    public function save_settings()
    {
        $data = array();

        // Collect our fields
        $this->set_preference($_POST);
        // ----------------------------------
        //  Redirect to Settings page with Message
        // ----------------------------------
        ee()->functions->redirect($this->base . '&method=index&msg=preferences_updated');
        exit;
    }


    public function view_action()
    {
        // --------------------------------------
        // Load some libraries
        // --------------------------------------
        //	ee()->load->library('javascript');
        //	ee()->load->library('table');
        //	ee()->cp->add_js_script('ui', 'sortable');
        ee()->cp->set_breadcrumb($this->base, lang('charge_module_name'));
        $this->cached_vars['templates'] = ee()->charge_model->get_templates();
        $this->cached_vars['statuses'] = ee()->charge_model->get_statuses();
        $this->cached_vars['channels'] = ee()->charge_model->get_channels();


        ee()->view->cp_page_title = lang('charge_actions');
        $this->cached_vars['form_post_url'] = $this->base . '&method=view_action';
        $this->cached_vars['add_subscription_uri'] = $this->base . '&method=view_subscription&new=yes';

        $this->cached_vars['errors'] = array();
        $this->cached_vars['subscriptions'] = ee()->charge_subscription->get_all();

        $new_action = false;

        $id = ee()->input->get_post('id');
        if ($id != '') {
            $action = ee()->charge_action->get_one(ee()->input->get_post('id'));

        } else {
            $new_action = true;
            $action = array('name' => '', 'shortname' => '', 'settings' => array(), 'action_id' => '');
        }


        if (ee()->input->post('edit_action') == 'yes') {
            // This is a post
            $status = ee()->charge_action->save($id);
            if ($status == false) {
                // Validation failed
                $this->cached_vars['errors'] = ee()->charge_action->errors;
                $this->cached_vars['has_errors'] = true;

                foreach (ee()->charge_action->this_settings as $key => $val) {
                    if (in_array($key, array('name', 'shortname'))) $action[$key] = $val;
                    else $action['settings'][$key] = $val;
                }
            } else {
                // Good
                ee()->functions->redirect($this->base . '&method=actions');
                exit();
            }
        }

        if (empty($action)) {
            ee()->functions->redirect($this->base . '&method=actions');
            exit();
        }

        $this->cached_vars['action'] = $action;

        return ee()->load->view('mcp_add_action', $this->cached_vars, true);
    }


    public function view_subscription()
    {
        $this->_add_morphine(true);
        // --------------------------------------
        // Load some libraries
        // --------------------------------------
        ee()->load->library('javascript');
        ee()->load->library('table');
        ee()->cp->add_js_script('ui', 'sortable');
        ee()->cp->set_breadcrumb($this->base, lang('charge_module_name'));
        ee()->view->cp_page_title = lang('charge_subscriptions');

        $this->cached_vars['errors'] = array();
        $this->cached_vars['form_post_url'] = $this->base . '&method=view_subscription';
        $this->cached_vars['templates'] = ee()->charge_model->get_templates();
        $this->cached_vars['member_groups'] = ee()->charge_model->get_member_groups();
        $this->cached_vars['actions_uri'] = $this->base . '&method=actions';


        $new_action = false;

        $id = ee()->input->get_post('id');
        if ($id != '') {
            $action = ee()->charge_subscription->get_one(ee()->input->get_post('id'));

        } else {
            $new_action = true;
            $action = array('name' => '', 'settings' => array(), 'subscription_id' => '');
        }


        if (ee()->input->post('edit_subscription') == 'yes') {
            // This is a post

            $status = ee()->charge_subscription->save($id);
            if ($status == false) {
                // Validation failed
                $this->cached_vars['errors'] = ee()->charge_subscription->errors;
                $this->cached_vars['has_errors'] = true;

                foreach (ee()->charge_subscription->this_settings as $key => $val) {

                    if (in_array($key, array('name', 'member_group_valid', 'member_group_invalid'))) $action[$key] = $val;
                    else $action['settings'][$key] = $val;
                }
            } else {
                // Good
                ee()->functions->redirect($this->base . '&method=subscriptions');
                exit();
            }
        }


        if (empty($action)) {
            ee()->functions->redirect($this->base . '&method=actions');
            exit();
        }

        $this->cached_vars['action'] = $action;

        return ee()->load->view('mcp_add_subscription', $this->cached_vars, true);
    }

    /**
     * Set preference
     *
     * @access    public
     * @return    array
     */

    private function set_preference($preferences = array(), $site_id = '')
    {
        /** --------------------------------------------
         * /**  Prep Cache, Return if Set
         * /** --------------------------------------------*/

        $cache_name = __FUNCTION__;
        $cache_hash = $this->_imploder(func_get_args());

        if (isset($this->cached[$cache_name][$cache_hash])) {
            return $this->cached[$cache_name][$cache_hash];
        }

        $this->cached[$cache_name][$cache_hash] = array();

        /** --------------------------------------------
         * /**    Grab prefs from DB
         * /** --------------------------------------------*/

        if ($site_id == '') $site_id = ee()->config->item('site_id');

        $sql = "SELECT site_system_preferences
					FROM exp_sites
					WHERE site_id = " . ee()->db->escape_str($site_id);

        $query = ee()->db->query($sql);

        if ($query->num_rows() == 0) return false;

        ee()->load->helper('string');

        $this->cached[$cache_name][$cache_hash] = unserialize(base64_decode($query->row('site_system_preferences')));


        /** --------------------------------------------
         * /**    Add our prefs
         * /** --------------------------------------------*/

        $prefs = array();

        foreach (explode("|", CHARGE_PREFERENCES) as $val) {
            if (isset($preferences[$val]) === true) {
                $this->cached[$cache_name][$cache_hash][$val] = $preferences[$val];
            }
        }


        $prefs = base64_encode(serialize($this->cached[$cache_name][$cache_hash]));


        ee()->db->query(ee()->db->update_string(
            'exp_sites',
            array(
                'site_system_preferences' => $prefs
            ),
            array(
                'site_id' => ee()->db->escape_str($site_id)
            )
        )
        );

        return true;
    }

    /* End set preference */

    // --------------------------------------------------------------------

    /**
     * Repload Preferences
     *
     * @access    public
     * @return    array
     */

    private function reload_preferences()
    {
        /** --------------------------------------------
         * /**  Prep Cache, Return if Set
         * /** --------------------------------------------*/

        $cache_name = __FUNCTION__;
        $cache_hash = $this->_imploder(func_get_args());

        if (isset($this->cached[$cache_name][$cache_hash])) {
            return $this->cached[$cache_name][$cache_hash];
        }

        $this->cached[$cache_name][$cache_hash] = array();

        /** --------------------------------------------
         * /**    Grab prefs from DB
         * /** --------------------------------------------*/

        $sql = "SELECT site_system_preferences
					FROM exp_sites
					WHERE site_id = " . ee()->db->escape_str(ee()->config->item('site_id'));

        $query = ee()->db->query($sql);

        if ($query->num_rows() == 0) return false;

        ee()->load->helper('string');

        $this->cached[$cache_name][$cache_hash] = unserialize(base64_decode($query->row('site_system_preferences')));


        return $this->cached[$cache_name][$cache_hash];
    }

    /* End set preference */

    // --------------------------------------------------------------------


    /**
     * Implodes an Array and Hashes It
     *
     * @access    public
     * @return    string
     */

    public function _imploder($arguments)
    {
        return md5(serialize($arguments));
    }

    // END


    private function _add_morphine($add_rowland = false)
    {

        $theme_folder_url = $this->_get_package_theme_url();

        ee()->cp->add_to_head('<link rel="stylesheet" type="text/css" href="' . $theme_folder_url . 'styles/screen.css" />');

        ee()->cp->add_to_foot('<script type="text/javascript" charset="utf-8" src="' . $theme_folder_url . 'scripts/compressed.js"></script>');

        ee()->cp->add_to_foot('<script type="text/javascript" src="' . $theme_folder_url . 'scripts/cp.js"></script>');

        if ($add_rowland) {
            // add our shizzle
            ee()->cp->add_to_foot('<script type="text/javascript" src="' . $theme_folder_url . 'scripts/jquery.roland.js"></script>');
        }
    }


    private function _get_overview()
    {
        $this->cached_vars['have_overview'] = false;
        $this->cached_vars['have_payment_overview'] = false;
        $this->cached_vars['have_recurring_overview'] = false;

        if (count($this->cached_vars['charges'])) return;

        return;
    }

    private function _pagination($total, $method)
    {

        $offset = 0;
        $page = 1;
        $this->cached_vars['prev_link'] = '';
        $this->cached_vars['next_link'] = '';

        $this->cached_vars['current_page'] = $page;
        $this->cached_vars['total_pages'] = $page;
        $this->cached_vars['has_pagination'] = false;


        if ($total > $this->limit) {

            $max_pages = ceil($total / $this->limit);


            if (ee()->input->get('page') != '') {
                $page = ee()->input->get('page');

                $offset = ($page - 1) * $this->limit;
            }
            $next_page = $page + 1;
            $prev_page = $page - 1;

            if ($prev_page > 0) $this->cached_vars['prev_link'] = $this->base . '&method=' . $method . '&page=' . $prev_page;
            if ($next_page <= $max_pages) $this->cached_vars['next_link'] = $this->base . '&method=' . $method . '&page=' . $next_page;

            $this->cached_vars['current_page'] = $page;
            $this->cached_vars['total_pages'] = $max_pages;

            $this->cached_vars['has_pagination'] = true;
        }

        return $offset;
    }

    private function _get_package_theme_url()
    {
        $theme_url = '';

        if (defined(URL_THIRD_THEMES)) {
            $theme_url = URL_THIRD_THEMES;
        } else {
            $theme_url = ee()->config->item('theme_folder_url');

            $theme_url .= substr($theme_url, -1) == '/'
                ? 'third_party/'
                : '/third_party/';
        }


        return $theme_url . 'charge/';
    }

    private function _get_callback_urls()
    {
        $webhook = $this->_get_action_url('act_webhook');

        $webhook_key = ee()->config->item('charge_webhook_key');

        $this->cached_vars['callback_url'] = $webhook . '&key=' . $webhook_key;
    }


    private function _get_action_url($method_name)
    {
        // Cache the action urls for repeated use
        if (!isset(ee()->session->cache['charge']['action_urls'][$method_name])) {

            $action_id = ee()->db->where(
                array(
                    'class'  => CHARGE_CLASS_NAME,
                    'method' => $method_name
                )
            )->get('actions')->row('action_id');

            ee()->session->cache['charge']['action_urls'][$method_name] = ee()->functions->fetch_site_index(0, 0) . '?ACT=' . $action_id;
        }

        return ee()->session->cache['charge']['action_urls'][$method_name];

    }

    private function _get_members($member_ids = array())
    {
        if (!isset(ee()->session->cache['charge']['members'])) {
            ee()->session->cache['charge']['members'] = $member_ids;

            $member_ids = array_keys(ee()->session->cache['charge']['members']);

            // Now pull the member details from the db
            $members = ee()->db->select('member_id, group_id, username, screen_name, email')
                ->where_in('member_id', $member_ids)
                ->get('members')
                ->result_array();

            foreach ($members as $member) {
                if (isset(ee()->session->cache['charge']['members'][$member['member_id']])) {
                    ee()->session->cache['charge']['members'][$member['member_id']] = $member;
                }
            }
        }

        return ee()->session->cache['charge']['members'];

    }


    private function _get_export_data()
    {
        $output = array();

        // Get everything
        $charges = ee()->charge_stripe->get_all();

        // Clean up

        $allowed_stripe = array('stripe_id', 'stripe_description', 'stripe_plan_name');
        $ignore = array('card_number_dotted', 'plan_wordy', 'plan_currency_symbol', 'plan_currency_formatted', 'amount_currency_formatted', 'amount_formatted', 'plan_amount', 'time_wordy', 'ended_on_wordy');

        foreach ($charges as $key => $charge) {
            foreach ($charge as $ckey => $cval) {
                if (is_array($cval)) unset($charges[$key][$ckey]);
                if (in_array($ckey, $ignore)) unset($charges[$key][$ckey]);

                // If the name is 'stripe_', and not in the allowed array, unset
                if (strpos($ckey, 'stripe_') === 0 AND !in_array($ckey, $allowed_stripe)) unset($charges[$key][$ckey]);


                // format timestamps
                if (in_array($ckey, array('timestamp', 'ended_on'))) {
                    if ($cval == '0') $charges[$key][$ckey] = '';
                    else $charges[$key][$ckey] = date('c', $cval);
                }
            }
        }

        return $charges;
    }


    private function _array_to_csv(array &$array)
    {
        if (count($array) == 0) return null;

        ob_start();
        $df = fopen("php://output", 'w');

        fputcsv($df, array_keys(reset($array)));

        foreach ($array as $row) {
            fputcsv($df, $row);
        }
        fclose($df);

        return ob_get_clean();
    }

    private function _check_csrf_exempt()
    {
        if (APP_VER >= '2.7') {
            $sql = "UPDATE exp_actions SET csrf_exempt = 1 WHERE class = 'Charge' AND method = 'act_webhook'";
            ee()->db->query($sql);
        }
    }


    private function _download_send_headers($filename)
    {
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header("Content-Disposition: attachment;filename={$filename}");
        header("Content-Transfer-Encoding: binary");
    }


}
