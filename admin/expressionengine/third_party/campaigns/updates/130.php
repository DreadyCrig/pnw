<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class CampaignsUpdate_130
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
        $this->EE =& get_instance();

        // Load dbforge
        $this->EE->load->dbforge();
    }

    // ********************************************************************************* //

    public function do_update()
    {
        // -----------------------------------------
        // exp_campaigns_services to exp_campaigns_accounts
        // -----------------------------------------
        if ($this->EE->db->table_exists('campaigns_accounts') === false) {
            $this->EE->dbforge->rename_table('campaigns_services', 'campaigns_accounts');
        }

        // -----------------------------------------
        // Add sizes_metadata Column
        // -----------------------------------------
        if ($this->EE->db->field_exists('account_data', 'campaigns_accounts') == false)
        {
            $fields = array('service_data' => array('name' => 'account_data', 'type' => 'TEXT'));
            $this->EE->dbforge->modify_column('campaigns_accounts', $fields);
        }

        if ($this->EE->db->field_exists('config_data', 'campaigns') == false)
        {
            $fields = array('scheduled_data' => array('name' => 'config_data', 'type' => 'TEXT'));
            $this->EE->dbforge->modify_column('campaigns', $fields);
        }

        if ($this->EE->db->field_exists('expires_at', 'campaigns_accounts') == false)
        {
            $fields = array('expires_at' => array('type' => 'DATETIME'),);
            $this->EE->dbforge->add_column('campaigns_accounts', $fields);
        }

        if ($this->EE->db->field_exists('config_id', 'campaigns') == false) {
            $fields = array('service_id' => array('name' => 'config_id', 'type' => 'INT', 'unsigned' => true));
            $this->EE->dbforge->modify_column('campaigns', $fields);
        }

        if ($this->EE->db->field_exists('service', 'campaigns') == false) {
            $fields = array('provider' => array('name' => 'service', 'type' => 'VARCHAR',   'constraint' => '250', 'default' => ''));
            $this->EE->dbforge->modify_column('campaigns', $fields);
        }

        if ($this->EE->db->field_exists('service', 'campaigns_accounts') == false) {
            $fields = array('provider' => array('name' => 'service', 'type' => 'VARCHAR',   'constraint' => '250', 'default' => ''));
            $this->EE->dbforge->modify_column('campaigns_accounts', $fields);
        }

        if ($this->EE->db->field_exists('slug', 'campaigns') == false) {
            $fields = array('name' => array('name' => 'slug', 'type' => 'VARCHAR',   'constraint' => '250', 'default' => ''));
            $this->EE->dbforge->modify_column('campaigns', $fields);
        }

        if ($this->EE->db->field_exists('slug', 'campaigns_accounts') == false) {
            $fields = array('name' => array('name' => 'slug', 'type' => 'VARCHAR',   'constraint' => '250', 'default' => ''));
            $this->EE->dbforge->modify_column('campaigns_accounts', $fields);
        }



        //exit();
    }

    // ********************************************************************************* //

}

/* End of file 110.php */
/* Location: ./system/expressionengine/third_party/campaigns/updates/110.php */
