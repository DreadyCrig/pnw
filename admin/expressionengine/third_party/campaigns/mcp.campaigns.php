<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// include config file
include_once dirname(__FILE__).'/config.php';

/**
 * Subscriptions Module Control Panel Class
 *
 * @package         DevDemon_Subscriptions
 * @author          DevDemon <http://www.devdemon.com>
 * @license         http://www.devdemon.com/license/
 * @link            http://www.devdemon.com/subscriptions/
 * @see             http://expressionengine.com/user_guide/development/module_tutorial.html#control_panel_file
 */
class Campaigns_mcp
{

    /**
     * Constructor
     *
     * @access public
     * @return void
     */
    public function __construct()
    {
        // Creat EE Instance
        $this->EE =& get_instance();
        $this->site_id = $this->EE->config->item('site_id');
        $this->EE->load->library('campaigns_helper');

        // Some Globals
        $this->initGlobals();
    }

    // ********************************************************************************* //

    public function index()
    {
        return $this->home();
    }

    // ********************************************************************************* //

    public function home()
    {
        // Set the page title
        //$this->setCpPageTitle('s:dashboard');

        $this->vdata['campaigns'] = $this->EE->campaigns_model->getCampaigns();

        $vData = array();
        $vData['section'] = 'home';
        $vData['content'] = $this->EE->load->view('mcp/home', $this->vdata, true);
        return $this->EE->load->view('mcp/_layout', $vData, true);
    }

    // ********************************************************************************* //

    public function lists()
    {
        // Set the page title

        $this->vdata['services'] = $this->EE->campaigns_model->getServices();

        foreach ($this->vdata['services'] as $key => &$service) {
            if ($service->type == 'social') {
                unset($this->vdata['services'][$key]);
                continue;
            }
            $provider = $this->EE->campaigns->getProvider($service->provider, $service);
            $service->lists = $provider->getLists();
            if (!$service->lists) $service->lists = array();
        }


        $vData = array();
        $vData['section'] = 'lists';
        $vData['content'] = $this->EE->load->view('mcp/lists', $this->vdata, true);
        return $this->EE->load->view('mcp/_layout', $vData, true);
    }

    // ********************************************************************************* //

    public function lists_add()
    {
        // Set the page title

        $this->vdata['service'] = $this->EE->campaigns_model->getService($this->EE->input->get('service_id'));

        $this->vdata['label'] = '';
        $this->vdata['list_id'] = $this->EE->input->get('list_id');
        $this->vdata['list_raw'] = '{}';

        if ($this->vdata['list_id'] > 0) {
            $item = $this->EE->campaigns_email_services->getRawListDetails($this->vdata['list_id'], $this->vdata['service']);

            if ($this->vdata['service']->provider == 'mailchimp') {
                $this->vdata['label'] = $item['name'];
            } elseif ($this->vdata['service']->provider == 'campaignmonitor') {
                $this->vdata['label'] = $item->Title;
            }

            $this->vdata['list_raw'] = $this->EE->campaigns_helper->generateJson($item);
        }


        $vData = array();
        $vData['section'] = 'lists';
        $vData['content'] = $this->EE->load->view('mcp/lists_add', $this->vdata, true);
        return $this->EE->load->view('mcp/_layout', $vData, true);
    }

    // ********************************************************************************* //

    public function channels()
    {
        // Set the page title

        $channels = array();
        $dbchannels = $this->EE->campaigns_model->getChannels();

        foreach ($dbchannels as $id => $label) {
            $channels[$id] = array();
            $channels[$id]['id'] = $id;
            $channels[$id]['label'] = $label;
            $channels[$id]['enabled'] = 'no';

            $channelSettings = $this->EE->campaigns->getChannelSettings($id);

            if ($channelSettings['enabled'] == 'yes') {
                $channels[$id]['enabled'] = 'yes';
            }
        }

        $this->vdata['channels'] = $channels;

        $vData = array();
        $vData['section'] = 'channels';
        $vData['content'] = $this->EE->load->view('mcp/channels', $this->vdata, true);
        return $this->EE->load->view('mcp/_layout', $vData, true);
    }

    // ********************************************************************************* //

    public function channels_update()
    {
        $channel_id = $this->EE->input->post('channel_id');

        if (isset($_POST['campaigns']) === true) {
            $data = $_POST['campaigns'];
        } else {
            $data = array();
        }

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

        // We don't want that included in our array
        $boxes = (isset($data['boxes'])) ? $data['boxes'] : array();
        unset($data['boxes']);

        $settings['channels'][$channel_id] = $data;

        // Put it Back
        $this->EE->db->set('settings', serialize($settings));
        $this->EE->db->where('module_name', 'Campaigns');
        $this->EE->db->update('exp_modules');

        $ids = array();
        foreach ($boxes as $order => $box) {
            $configId = $this->EE->campaigns_model->updateBox($channel_id, 0, $order, $box);
            $ids[] = $configId;
        }

        // Grab all boxes
        $boxes = $this->EE->campaigns_model->getBoxes($channel_id);

        foreach ($boxes as $box) {
            if (in_array($box->id, $ids) === false) {
                $this->EE->campaigns_model->deleteBox($box->id);
            }
        }

        $this->EE->functions->redirect($this->base . '&method=channels');
    }

    // ********************************************************************************* //

    public function accounts()
    {
        // Set the page title

        $this->vdata['accounts'] = array();
        $accounts = $this->EE->campaigns_model->getAccountsExtended();
        $this->vdata['accounts'] = $accounts;


        $vData = array();
        $vData['section'] = 'accounts';
        $vData['content'] = $this->EE->load->view('mcp/accounts', $this->vdata, true);
        return $this->EE->load->view('mcp/_layout', $vData, true);
    }

    // ********************************************************************************* //

    public function accounts_add()
    {
        // Set the page title
        //$this->setCpPageTitle('s:gateways');

        $fields = $this->EE->db->list_fields('exp_campaigns_accounts');

        foreach ($fields as $name) $this->vdata[$name] = '';

        // Edit?
        if ($this->EE->input->get('id') != false) {
            $item = $this->EE->campaigns_model->getAccount($this->EE->input->get('id'));

            if ($item != false) {
                foreach ($item as $key => $val) {
                    $this->vdata[$key] = $val;
                }
            }
        }

        if (!$this->vdata['settings']) $this->vdata['settings'] = array();

        // Start the JSON array
        $json = array();
        $json['_global'] = array();
        $json['_global']['themeUrl'] = $this->EE->campaigns_helper->getThemeUrl();
        $json['currentSettings'] = $this->vdata['settings'];

        //----------------------------------------
        // Providers
        //----------------------------------------

        // Get the list of Providers
        $services = $this->EE->campaigns->getServicesList();
        $this->vdata['services']['email'] = array();
        $this->vdata['services']['social'] = array();

        // Loop over all Providers
        foreach ($services as $serviceName) {

            if ($this->vdata['service'] == $serviceName) {
                $account = $item;
            } else {
                $account = new stdClass;
                $account->service = $serviceName;
            }

            // Init!
            $service = $this->EE->campaigns->getService($account, null, false);
            $url =  $service->getAuthorizationUrl();

            // Settings
            $json['services'][$serviceName] = $service->settings;

            // Put it in the right section
            if ($service->type == 'email') $this->vdata['services']['email'][] = $serviceName;
            elseif ($service->type == 'social') $this->vdata['services']['social'][] = $serviceName;
        }

        // Create the JSON
        $this->vdata['json'] = $this->EE->campaigns_helper->generateJson($json);


        $vData = array();
        $vData['section'] = 'accounts';
        $vData['content'] = $this->EE->load->view('mcp/accounts_add', $this->vdata, true);
        return $this->EE->load->view('mcp/_layout', $vData, true);
    }

    // ********************************************************************************* //

    public function accounts_update()
    {
        //----------------------------------------
        // Create/Updating? or Delete
        //----------------------------------------
        if ($this->EE->input->get('delete') == 'yes') {
            $this->EE->campaigns_model->deleteAccount($this->EE->input->get('id'));
        } else {
            $this->EE->campaigns_model->updateAccount();
        }

        $this->EE->functions->redirect($this->base . '&method=accounts');
    }

    // ********************************************************************************* //

    public function settings()
    {
        $this->vdata['cron_url'] = 'http:'.$this->EE->campaigns_helper->getRouterUrl('url', 'processScheduledCampaigns');
        $this->vdata['settings'] = $this->EE->campaigns->settings;

        $vData = array();
        $vData['section'] = 'settings';
        $vData['content'] = $this->EE->load->view('mcp/settings', $this->vdata, true);
        return $this->EE->load->view('mcp/_layout', $vData, true);
    }

    // ********************************************************************************* //

    public function update_settings()
    {
        $settings = $this->EE->input->post('settings');

        if (isset(ee()->campaigns->settings['channels'])) {
            $settings['channels'] = ee()->campaigns->settings['channels'];
        }

        // Put it Back
        $this->EE->db->set('settings', serialize($settings));
        $this->EE->db->where('module_name', 'Campaigns');
        $this->EE->db->update('exp_modules');

        $this->EE->functions->redirect($this->base . '&method=index');
    }

    // ********************************************************************************* //

    public function ajaxRouter()
    {
        include PATH_THIRD . 'campaigns/mod.campaigns.php';
        $MOD = new Campaigns();
        $MOD->actionGeneralRouter($this->EE->input->get('task'));
    }

    // ********************************************************************************* //

    private function initGlobals()
    {
        // Some Globals
        $this->base = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=campaigns';
        $this->base_short = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=campaigns';
        $this->site_id = $this->EE->config->item('site_id');

        // Page Title & BreadCumbs
        $this->EE->cp->set_breadcrumb($this->base, $this->EE->lang->line('campaigns'));
        $this->setCpPageTitle('campaigns');

        // -------------------------------------------------------
        //  Global Views Data
        // -------------------------------------------------------
        $this->vdata['base_url'] = $this->base;
        $this->vdata['base_url_short'] = $this->base_short;
        $this->vdata['theme_url'] = $this->EE->campaigns_helper->getThemeUrl();
        $this->vdata['csettings'] = $this->EE->campaigns->settings;

        // -------------------------------------------------------
        //  CSS/JS
        // -------------------------------------------------------
        $this->EE->campaigns_helper->addMcpAssets('gjs');
        $this->EE->campaigns_helper->addMcpAssets('css', 'css/bootstrap.modal.css', 'bootstrap', 'modal');
        $this->EE->campaigns_helper->addMcpAssets('css', 'css/mcp.css?v='.CAMPAIGNS_VERSION, 'campaigns', 'mcp');
        //$this->EE->campaigns_helper->addMcpAssets('js', 'js/jquery.isotope.min.js', 'jquery', 'isotope');
        //$this->EE->campaigns_helper->addMcpAssets('js', 'js/bootstrap.modal.js', 'jquery', 'bootstrap.modal');
        $this->EE->campaigns_helper->addMcpAssets('js', 'js/handlebars.runtime.1.3.0.min.js', 'handlebars', 'runtime');
        $this->EE->campaigns_helper->addMcpAssets('js', 'js/templates.min.js', 'campaigns', 'templates');
        $this->EE->campaigns_helper->addMcpAssets('js', 'js/twitter-text.min.js', 'twitter', 'text');
        $this->EE->campaigns_helper->addMcpAssets('js', 'js/bootstrap.modal.js', 'bootstrap', 'modal');
        $this->EE->campaigns_helper->addMcpAssets('js', 'js/jquery.stringtoslug.min.js', 'jquery', 'stringtoslug');
        $this->EE->campaigns_helper->addMcpAssets('js', 'js/mcp.min.js?v='.CAMPAIGNS_VERSION, 'campaigns', 'mcp');

    }

     // ********************************************************************************* //

    private function setCpPageTitle($langkey='')
    {
    	if (version_compare(APP_VER, '2.5.5', '>')) {
			ee()->view->cp_page_title = $this->EE->lang->line($langkey);
		} else {
			$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line($langkey));
		}
    }

    // ********************************************************************************* //


} // END CLASS

/* End of file mcp.campaigns.php */
/* Location: ./system/expressionengine/third_party/campaigns/mcp.campaigns.php */
