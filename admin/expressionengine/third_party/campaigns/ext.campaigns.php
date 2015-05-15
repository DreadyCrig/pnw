<?php if (!defined('BASEPATH')) die('No direct script access allowed');

// include config file
include_once dirname(__FILE__).'/config.php';

/**
 * Updater Module Extension File
 *
 * @package         DevDemon_Updater
 * @author          DevDemon <http://www.devdemon.com> - Lead Developer @ Parscale Media
 * @copyright       Copyright (c) 2007-2012 Parscale Media <http://www.parscale.com>
 * @license         http://www.devdemon.com/license/
 * @link            http://www.devdemon.com
 * @see             http://expressionengine.com/user_guide/development/extensions.html
 */
class Campaigns_ext
{
    public $version         = CAMPAIGNS_VERSION;
    public $name            = 'Campaigns Extension';
    public $description     = 'Supports the Campaigns Module in various functions.';
    public $docs_url        = 'http://www.devdemon.com';
    public $settings_exist  = false;
    public $settings        = array();
    public $hooks           = array('cp_menu_array');

    // ********************************************************************************* //

    /**
     * Constructor
     *
     * @access public
     * @return void
     */
    public function __construct()
    {
        $this->site_id = ee()->config->item('site_id');
    }

    // ********************************************************************************* //

    /**
     * cp_menu_array
     *
     * @param array $menu
     * @access public
     * @see N/A
     * @return array
     */
    public function cp_menu_array($menu)
    {
        if (ee()->extensions->last_call !== false) {
            $menu = ee()->extensions->last_call;
        }

        if (ee()->input->get('M') != 'view_entry') {
            return $menu;
        }

        // -------------------------------------------------------
        //  Get Entry ID & Channel ID
        // -------------------------------------------------------
        $entry_id = ee()->input->get('entry_id');
        $channel_id = ee()->input->get('channel_id');

        if (!$entry_id) {
            return $menu;
        }

        if (!$channel_id) {
            $query = ee()->db->query("SELECT channel_id FROM exp_channel_titles WHERE entry_id = {$entry_id}");
            if ($query->num_rows() == 0) return $menu;
            else $channel_id = $query->row('channel_id');
        }

        ee()->load->add_package_path(PATH_THIRD . 'campaigns/');
        ee()->load->library('campaigns_helper');

        // -------------------------------------------------------
        //  Get Fields
        // -------------------------------------------------------
        $query = ee()->db->query("SELECT field_group FROM exp_channels WHERE channel_id = {$channel_id}");
        if ($query->num_rows() == 0) return $menu;
        $field_group = $query->row('field_group');

        // get the fields now
        $field_id = 0;

        if ($field_group) {
            $query = ee()->db->query("SELECT field_id FROM exp_channel_fields WHERE group_id = {$field_group} AND field_type = 'campaigns' ORDER BY field_order ASC LIMIT 1");


            if ($query->num_rows() > 0) {
                $field_id = $query->row('field_id');
            }
        }

        $settings = ee()->campaigns->getChannelSettings($channel_id, $field_id);

        if ($settings['enabled'] == 'no') return $menu;

        $group_id = ee()->session->userdata('group_id');

        // -------------------------------------------------------
        //  ADD CSS/JS
        // -------------------------------------------------------
        ee()->campaigns_helper->addMcpAssets('gjs');
        ee()->campaigns_helper->addMcpAssets('css', 'css/field.css?v='.CAMPAIGNS_VERSION, 'campaigns', 'field');
        ee()->campaigns_helper->addMcpAssets('js', 'js/handlebars.runtime.1.3.0.min.js', 'handlebars', 'runtime');
        ee()->campaigns_helper->addMcpAssets('js', 'js/templates.min.js', 'campaigns', 'templates');
        ee()->campaigns_helper->addMcpAssets('js', 'js/twitter-text.min.js', 'twitter', 'text');


        ee()->campaigns_helper->addMcpAssets('custom', "
                <script type='text/javascript'>
                if (!Campaigns) var Campaigns = {};
                Campaigns.ENTRY_ID = {$entry_id};
                Campaigns.GROUP_ID = {$group_id};
                </script>
        ");

        ee()->campaigns_helper->addMcpAssets('js', 'js/field.min.js?v='.CAMPAIGNS_VERSION, 'campaigns', 'field');

        return $menu;
    }

    // ********************************************************************************* //

    /**
     * Called by ExpressionEngine when the user activates the extension.
     *
     * @access      public
     * @return      void
     **/
    public function activate_extension()
    {
        foreach ($this->hooks as $hook) {

            $data = array(
                'class'     =>  __CLASS__,
                'method'    =>  $hook,
                'hook'      =>  $hook,
                'settings'  =>  serialize($this->settings),
                'priority'  =>  100,
                'version'   =>  $this->version,
                'enabled'   =>  'y'
            );

            // insert in database
            ee()->db->insert('exp_extensions', $data);
        }
    }

    // ********************************************************************************* //

    /**
     * Called by ExpressionEngine when the user disables the extension.
     *
     * @access      public
     * @return      void
     **/
    public function disable_extension()
    {
        ee()->db->where('class', __CLASS__);
        ee()->db->delete('exp_extensions');
    }

    // ********************************************************************************* //

    /**
     * Called by ExpressionEngine updates the extension
     *
     * @access public
     * @return void
     **/
    public function update_extension($current=false)
    {
        if($current == $this->version) return false;

        // Update the extension
        ee()->db
            ->where('class', __CLASS__)
            ->update('extensions', array('version' => $this->version));

    }

    // ********************************************************************************* //

} // END CLASS

/* End of file ext.campaigns.php */
/* Location: ./system/expressionengine/third_party/campaigns/ext.campaigns.php */