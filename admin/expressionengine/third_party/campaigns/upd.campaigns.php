<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// include config file
include_once dirname(__FILE__).'/config.php';

/**
 * Install / Uninstall and updates the modules
 *
 * @package         DevDemon_campaigns
 * @author          DevDemon <http://www.devdemon.com>
 * @license         http://www.devdemon.com/license/
 * @link            http://www.devdemon.com/campaigns/
 * @see             http://expressionengine.com/user_guide/development/module_tutorial.html#update_file
 */
class Campaigns_upd
{
    /**
     * Module version
     *
     * @var string
     * @access public
     */
    public $version = CAMPAIGNS_VERSION;

    /**
     * Module Short Name
     *
     * @var string
     * @access private
     */
    private $module_name = CAMPAIGNS_CLASS_NAME;

    /**
     * Has Control Panel Backend?
     *
     * @var string
     * @access private
     */
    private $has_cp_backend = 'y';

    /**
     * Has Publish Fields?
     *
     * @var string
     * @access private
     */
    private $has_publish_fields = 'n';


    /**
     * Constructor
     *
     * @access public
     * @return void
     */
    public function __construct()
    {
        $this->EE =& get_instance();
    }

    // ********************************************************************************* //

    /**
     * Installs the module
     *
     * Installs the module, adding a record to the exp_modules table,
     * creates and populates and necessary database tables,
     * adds any necessary records to the exp_actions table,
     * and if custom tabs are to be used, adds those fields to any saved publish layouts
     *
     * @access public
     * @return boolean
     **/
    public function install()
    {
        // Load dbforge
        $this->EE->load->dbforge();

        //----------------------------------------
        // EXP_MODULES
        //----------------------------------------
        $this->EE->db->set('module_name', ucfirst($this->module_name));
        $this->EE->db->set('module_version', $this->version);
        $this->EE->db->set('has_cp_backend', $this->has_cp_backend);
        $this->EE->db->set('has_publish_fields', $this->has_publish_fields);
        $this->EE->db->insert('modules');

        //----------------------------------------
        // Actions
        //----------------------------------------
        $this->EE->db->set('class', ucfirst($this->module_name));
        $this->EE->db->set('method', 'actionGeneralRouter');
        $this->EE->db->insert('actions');

        $this->EE->db->set('class', ucfirst($this->module_name));
        $this->EE->db->set('method', 'processScheduledCampaigns');
        $this->EE->db->insert('actions');

        //----------------------------------------
        // EXP_MODULES
        // The settings column, Ellislab should have put this one in long ago.
        // No need for a seperate preferences table for each module.
        //----------------------------------------
        if (!$this->EE->db->field_exists('settings', 'modules')) {
            $this->EE->dbforge->add_column('modules', array('settings' => array('type' => 'TEXT') ) );
        }

        //----------------------------------------
        // EXP_CAMPAIGNS
        //----------------------------------------
        $ci = array(
            'id'            => array('type' => 'INT',       'unsigned' => true, 'auto_increment' => true),
            'site_id'       => array('type' => 'SMALLINT',  'unsigned' => true),
            'label'         => array('type' => 'VARCHAR',   'constraint' => '250', 'default' => ''),
            'slug'          => array('type' => 'VARCHAR',   'constraint' => '250', 'default' => ''),
            'service'       => array('type' => 'VARCHAR',   'constraint' => '250', 'default' => ''),
            'status'        => array('type' => 'VARCHAR',   'constraint' => '250', 'default' => ''),
            'entry_id'      => array('type' => 'INT',       'unsigned' => true),
            'created_by'    => array('type' => 'INT',       'unsigned' => true),
            'updated_by'    => array('type' => 'INT',       'unsigned' => true),
            'created_at'    => array('type' => 'DATETIME'),
            'updated_at'    => array('type' => 'DATETIME'),
        );

        $this->EE->dbforge->add_field($ci);
        $this->EE->dbforge->add_key('id', true);
        $this->EE->dbforge->create_table('campaigns', true);

        //----------------------------------------
        // EXP_CAMPAIGNS_SENDS
        //----------------------------------------
        $ci = array(
            'id'            => array('type' => 'INT',       'unsigned' => true, 'auto_increment' => true),
            'campaign_id'   => array('type' => 'INT',       'unsigned' => true),
            'status'        => array('type' => 'VARCHAR',   'constraint' => '250', 'default' => ''),
            'box_id'        => array('type' => 'INT',       'unsigned' => true),
            'service_cid'   => array('type' => 'VARCHAR',   'constraint' => '250', 'default' => ''), // Campaign ID at the service
            'scheduled_at'  => array('type' => 'DATETIME'),
            'box_data'      => array('type' => 'TEXT'),
            'campaign_data' => array('type' => 'TEXT'),
            'created_by'    => array('type' => 'INT',       'unsigned' => true),
            'updated_by'    => array('type' => 'INT',       'unsigned' => true),
            'created_at'    => array('type' => 'DATETIME'),
            'updated_at'    => array('type' => 'DATETIME'),
        );

        $this->EE->dbforge->add_field($ci);
        $this->EE->dbforge->add_key('id', true);
        $this->EE->dbforge->create_table('campaigns_sends', true);

        //----------------------------------------
        // EXP_CAMPAIGNS_ACCOUNTS
        //----------------------------------------
        $ci = array(
            'id'            => array('type' => 'INT',       'unsigned' => true, 'auto_increment' => true),
            'site_id'       => array('type' => 'SMALLINT',  'unsigned' => true),
            'label'         => array('type' => 'VARCHAR',   'constraint' => '250', 'default' => ''),
            'handle'        => array('type' => 'VARCHAR',   'constraint' => '250', 'default' => ''),
            'service'       => array('type' => 'VARCHAR',   'constraint' => '250', 'default' => ''),
            'type'          => array('type' => 'VARCHAR',   'constraint' => '250', 'default' => ''),
            'settings'      => array('type' => 'TEXT'),
            'account_data'  => array('type' => 'TEXT'),
            'expires_at'    => array('type' => 'DATETIME'),
            'created_by'    => array('type' => 'INT',       'unsigned' => true),
            'updated_by'    => array('type' => 'INT',       'unsigned' => true),
            'created_at'    => array('type' => 'DATETIME'),
            'updated_at'    => array('type' => 'DATETIME'),
        );

        $this->EE->dbforge->add_field($ci);
        $this->EE->dbforge->add_key('id', true);
        $this->EE->dbforge->add_key('service');
        $this->EE->dbforge->create_table('campaigns_accounts', true);

        //----------------------------------------
        // EXP_CAMPAIGNS_BOXES
        //----------------------------------------
        $ci = array(
            'id'            => array('type' => 'INT',       'unsigned' => true, 'auto_increment' => true),
            'site_id'       => array('type' => 'SMALLINT',  'unsigned' => true),
            'label'         => array('type' => 'VARCHAR',   'constraint' => '250', 'default' => ''),
            'slug'          => array('type' => 'VARCHAR',   'constraint' => '250', 'default' => ''),
            'account_id'    => array('type' => 'INT',       'unsigned' => true, 'default' => '0'),
            'channel_id'    => array('type' => 'INT',       'unsigned' => true, 'default' => '0'),
            'field_id'      => array('type' => 'INT',       'unsigned' => true, 'default' => '0'),
            'box_order'     => array('type' => 'INT',       'unsigned' => true, 'default' => 0),
            'enabled'       => array('type' => 'TINYINT',   'unsigned' => true, 'default' => 1),
            'shorten_urls'  => array('type' => 'TINYINT',   'unsigned' => true, 'default' => 1),
            'settings'      => array('type' => 'TEXT'),
            'created_by'    => array('type' => 'INT',       'unsigned' => true),
            'updated_by'    => array('type' => 'INT',       'unsigned' => true),
            'created_at'    => array('type' => 'DATETIME'),
            'updated_at'    => array('type' => 'DATETIME'),
        );

        $this->EE->dbforge->add_field($ci);
        $this->EE->dbforge->add_key('id', true);
        $this->EE->dbforge->add_key('channel_id');
        $this->EE->dbforge->add_key('field_id');
        $this->EE->dbforge->create_table('campaigns_boxes', true);

        return true;
    }

    // ********************************************************************************* //

    /**
     * Uninstalls the module
     *
     * @access public
     * @return Boolean false if uninstall failed, true if it was successful
     **/
    public function uninstall()
    {
        // Load dbforge
        $this->EE->load->dbforge();

        $this->EE->dbforge->drop_table('campaigns');
        $this->EE->dbforge->drop_table('campaigns_sends');
        $this->EE->dbforge->drop_table('campaigns_accounts');
        $this->EE->dbforge->drop_table('campaigns_boxes');

        $this->EE->db->where('module_name', ucfirst($this->module_name));
        $this->EE->db->delete('modules');
        $this->EE->db->where('class', ucfirst($this->module_name));
        $this->EE->db->delete('actions');

        return true;
    }

    // ********************************************************************************* //

    /**
     * Updates the module
     *
     * This function is checked on any visit to the module's control panel,
     * and compares the current version number in the file to
     * the recorded version in the database.
     * This allows you to easily make database or
     * other changes as new versions of the module come out.
     *
     * @access public
     * @return Boolean false if no update is necessary, true if it is.
     **/
    public function update($current = '')
    {
        // Are they the same?
        if ($current >= $this->version) {
            return false;
        }

        // Load dbforge
        $this->EE->load->dbforge();

        $current = str_replace('.', '', $current);

        // Two Digits? (needs to be 3)
        if (strlen($current) == 2) {
            $current .= '0';
        }

        $update_dir = PATH_THIRD.strtolower($this->module_name).'/updates/';

        // Does our folder exist?
        if (@is_dir($update_dir) === true) {
            // Loop over all files
            $files = @scandir($update_dir);

            if (is_array($files) == true) {

                foreach ($files as $file) {

                    if ($file == '.' OR $file == '..' OR strtolower($file) == '.ds_store') continue;

                    // Get the version number
                    $ver = substr($file, 0, -4);

                    // We only want greater ones
                    if ($current >= $ver) continue;

                    require $update_dir . $file;
                    $class = 'CampaignsUpdate_' . $ver;
                    $UPD = new $class();
                    $UPD->do_update();
                }
            }
        }

        // Upgrade The Module
        $this->EE->db->set('module_version', $this->version);
        $this->EE->db->where('module_name', ucfirst($this->module_name));
        $this->EE->db->update('exp_modules');

        return true;
    }

    // ********************************************************************************* //

} // END CLASS

/* End of file upd.campaigns.php */
/* Location: ./system/expressionengine/third_party/campaigns/upd.campaigns.php */
