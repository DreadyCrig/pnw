<?php if (!defined('BASEPATH')) die('No direct script access allowed');

/**
 * Campaigns Module
 *
 * @package         DevDemon_Campaigns
 * @author          DevDemon <http://www.devdemon.com>
 * @license         http://www.devdemon.com/license/
 * @link            http://www.devdemon.com
 * @see             http://expressionengine.com/user_guide/development/module_tutorial.html#core_module_file
 */
class Campaigns
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
        $this->EE->load->library('campaigns_helper');
    }

    // ********************************************************************************* //

    public function html2text()
    {
        if (class_exists('html2text') === false) require_once dirname(__FILE__).'/libraries/html2text.php';
        $html2text = new html2text();
        return $html2text->convertHtml($this->EE->TMPL->tagdata);
    }

    // ********************************************************************************* //

    public function actionGeneralRouter($mcp_task=false)
    {
        @header('Access-Control-Allow-Origin: ' . $this->EE->input->server('host'));
        //@header('Access-Control-Allow-Credentials: true');
        @header('Access-Control-Max-Age: 86400');
        @header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        @header('Access-Control-Allow-Headers: Keep-Alive, Content-Type, User-Agent, Cache-Control, Origin, X-Requested-With, X-File-Name, X-File-Size');

        // -----------------------------------------
        // Ajax Request?
        // -----------------------------------------
        if ( $this->EE->input->get_post('ajax_method') != FALSE OR (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') ) {
            // Load Library
            if (class_exists('Campaigns_AJAX') != TRUE) include 'ajax.campaigns.php';

            $AJAX = new Campaigns_AJAX();

            // Shoot the requested method
            $method = $this->EE->input->get_post('ajax_method');
            $out = $AJAX->$method();

            if (is_array($out) === true) {
                exit($this->EE->campaigns_helper->generateJson($out));
            }

            exit($out);
        }

        exit('Campaigns ACT URL');
    }

    // ********************************************************************************* //

    public function processScheduledCampaigns()
    {
        // Load Library
        if (class_exists('Campaigns_AJAX') != TRUE) include 'ajax.campaigns.php';

        $AJAX = new Campaigns_AJAX();

        $out = $AJAX->processScheduledCampaigns();

        if (is_array($out) === true) {
            exit($this->EE->campaigns_helper->generateJson($out));
        }

        exit($out);
    }

    // ********************************************************************************* //


} // END CLASS

/* End of file mod.campaigns.php */
/* Location: ./system/expressionengine/third_party/campaigns/mod.campaigns.php */
