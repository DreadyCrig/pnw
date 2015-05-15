<?php if (!defined('BASEPATH')) die('No direct script access allowed');

// include config file
include_once dirname(__FILE__).'/config.php';

/**
 * Channel Images Module FieldType
 *
 * @package         DevDemon_ChannelImages
 * @author          DevDemon <http://www.devdemon.com> - Lead Developer @ Parscale Media
 * @copyright       Copyright (c) 2007-2011 Parscale Media <http://www.parscale.com>
 * @license         http://www.devdemon.com/license/
 * @link            http://www.devdemon.com
 * @see             http://expressionengine.com/user_guide/development/fieldtypes.html
 */
class Campaigns_ft extends EE_Fieldtype
{

    /**
     * Field info - Required
     *
     * @access public
     * @var array
     */
    public $info = array(
        'name'      => CAMPAIGNS_NAME,
        'version'   => CAMPAIGNS_VERSION,
    );

    /**
     * The field settings array
     *
     * @access public
     * @var array
     */
    public $settings = array();

    /**
     * Constructor
     *
     * @access public
     *
     * Calls the parent constructor
     */
    public function __construct()
    {
        if (version_compare(APP_VER, '2.1.4', '>')) { parent::__construct(); } else { parent::EE_Fieldtype(); }

        $this->site_id = $this->EE->config->item('site_id');

        $this->EE->load->add_package_path(PATH_THIRD . 'campaigns/');

        // Only initialize if we are NOT 'installing'
        if ($this->EE->input->get('M') != 'package_settings') {
            $this->EE->load->library('campaigns_helper');
        }
    }

    // ********************************************************************************* //

    /**
     * Display the field in the publish form
     *
     * @access public
     * @param $data String Contains the current field data. Blank for new entries.
     * @return String The custom field HTML
     *
     */
    public function display_field($data)
    {
        /*
        // ADD JS/CSS Assets
        $this->EE->campaigns_helper->addMcpAssets('gjs');
        $this->EE->campaigns_helper->addMcpAssets('css', 'css/pbf.css?v='.CAMPAIGNS_VERSION, 'campaigns', 'pbf');
        $this->EE->campaigns_helper->addMcpAssets('js', 'js/handlebars.runtime.1.0.0.min.js', 'handlebars', 'runtime');
        $this->EE->campaigns_helper->addMcpAssets('js', 'js/hbs-templates.js', 'campaigns', 'hbs-templates');
        $this->EE->campaigns_helper->addMcpAssets('js', 'js/pbf.min.js?v='.CAMPAIGNS_VERSION, 'campaigns', 'pbf');
        */

        $vdata = array();
        $vdata['entry_id'] = ($this->EE->input->get_post('entry_id') != false) ? $this->EE->input->get_post('entry_id') : false;

        return $this->EE->load->view('pbf/pbf', $vdata, true);
    }

    // ********************************************************************************* //

    /**
     * Validates the field input
     *
     * @param $data Contains the submitted field data.
     * @return mixed Must return true or an error message
     */
    public function validate($data)
    {
        // Is this a required field?
        if ($this->settings['field_required'] == 'y') {

        }

        return true;
    }

    // ********************************************************************************* //

    /**
     * Preps the data for saving
     *
     * @param $data Contains the submitted field data.
     * @return string Data to be saved
     */
    public function save($data)
    {

    }

    // ********************************************************************************* //

    /**
     * Handles any custom logic after an entry is saved.
     * Called after an entry is added or updated.
     * Available data is identical to save, but the settings array includes an entry_id.
     *
     * @param $data Contains the submitted field data. (Returned by save())
     * @access public
     * @return void
     */
    public function post_save($data)
    {

    }

    // ********************************************************************************* //

    /**
     * Handles any custom logic after an entry is deleted.
     * Called after one or more entries are deleted.
     *
     * @param $ids array is an array containing the ids of the deleted entries.
     * @access public
     * @return void
     */
    public function delete($ids)
    {
        foreach ($ids as $entry_id) {

        }

    }

    // ********************************************************************************* //

    /**
     * Display the settings page. The default ExpressionEngine rows can be created using built in methods.
     * All of these take the current $data and the fieltype name as parameters:
     *
     * @param $data array
     * @access public
     * @return void
     */
    public function display_settings($data)
    {
        $settings = isset($data['campaigns']) ? $data['campaigns'] : array();
        $settings['field_id'] = $this->field_id ? $this->field_id : 0;

        // ADD JS/CSS Assets
        $this->EE->campaigns_helper->addMcpAssets('gjs');
        $this->EE->campaigns_helper->addMcpAssets('css', 'css/fts.css?v='.CAMPAIGNS_VERSION, 'campaigns', 'fts');
        $this->EE->campaigns_helper->addMcpAssets('js', 'js/handlebars.runtime.1.3.0.min.js', 'handlebars', 'runtime');
        $this->EE->campaigns_helper->addMcpAssets('js', 'js/templates.min.js', 'campaigns', 'templates');
        $this->EE->campaigns_helper->addMcpAssets('js', 'js/twitter-text.min.js', 'twitter', 'text');
        $this->EE->campaigns_helper->addMcpAssets('js', 'js/mcp.min.js?v='.CAMPAIGNS_VERSION, 'campaigns', 'mcp');

        $data = array();
        $data['channel_settings_json'] = $this->EE->campaigns_helper->generateJson($settings);
        $rowhtml = $this->EE->load->view('fieldtype_settings', $data, true);

        $this->EE->table->add_row(array('data' => $rowhtml, 'class' => 'campaigns-settings', 'colspan' => 2));
    }

    // ********************************************************************************* //

    /**
     * Save the fieldtype settings.
     *
     * @param $data array Contains the submitted settings for this field.
     * @access public
     * @return array
     */
    public function save_settings($data)
    {
        if (isset($_POST['campaigns']) === true) {
            $data = $_POST['campaigns'];
        } else {
            $data = array();
        }

        // We don't want that included in our array
        $configs = (isset($data['configs'])) ? $data['configs'] : array();
        unset($data['configs']);

        return array('campaigns' => $data);
    }

    // ********************************************************************************* //

    /**
     * Post Save Settings
     *
     * Called after the settings are saved. Gives you access to the id
     * for this field.
     *
     * @param   array  Full settings array, including the id()
     * @return  void
     */
    public function post_save_settings($fieldData) {

        if (isset($_POST['campaigns']) === true) {
            $data = $_POST['campaigns'];
        } else {
            $data = array();
        }

        // We don't want that included in our array
        $configs = (isset($data['configs'])) ? $data['configs'] : array();
        unset($data['configs']);

        $ids = array();
        foreach ($configs as $order => $config) {
            $configId = $this->EE->campaigns_model->updateConfig(0, $fieldData['field_id'], $order, $config);
            $ids[] = $configId;
        }

        // Grab all Configs
        $configs = $this->EE->campaigns_model->getConfigs(0, $fieldData['field_id']);

        foreach ($configs as $config) {
            if (in_array($config->id, $ids) === false) {
                $this->EE->campaigns_model->deleteConfig($config->id);
            }
        }
    }

    // ********************************************************************************* //
}

/* End of file ft.channel_images.php */
/* Location: ./system/expressionengine/third_party/channel_images/ft.channel_images.php */
