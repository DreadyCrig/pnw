<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Charge Email Model class
 *
 * @package         charge_ee_addon
 * @version         1.9.0
 * @author          Joel Bradbury ~ <joel@squarebit.co.uk>
 * @link            http://squarebit.co.uk/software/expressionengine/charge
 * @copyright       Copyright (c) 2015, Joel Bradbury/Square Bit
 */
class Charge_email_model extends Charge_model
{

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
        // Nada
    }


    public function send($hash = '', $to = array(), $subject = '', $template = '', $bcc = '')
    {
        ee()->load->library('template', null, 'TMPL');
        ee()->load->library('email');

        // Get the appropriate template
        $template = explode('/', $template);
        if (count($template) != 2) return false;

        $message = ee()->TMPL->fetch_template(trim($template[0]), trim($template[1]), false);
        $variables = ee()->charge_stripe->fetch($hash);

        // Also parse our subject just in case
        $subject = ee()->TMPL->parse_variables($subject, array($variables));
        $message = ee()->TMPL->parse_variables($message, array($variables));
        ee()->TMPL->parse($message);
        $message = ee()->TMPL->final_template;

        // Clean out any comment tags
        $message = $this->_clean_comments($message);

        // Don't send it twice to the same place
        $prefs = $this->get_email_settings();

        $sent_addressess = array();
        foreach ($to as $address) {
            if (in_array($address, $sent_addressess)) continue;
            $sent_addressess[] = $address;

            ee()->email->mailtype = 'html';
            ee()->email->from($prefs['from'], $prefs['name']);
            ee()->email->reply_to($prefs['reply_to'], $prefs['reply_to_name']);
            ee()->email->to($address);

            if ($bcc != '') ee()->email->bcc($bcc);

            ee()->email->subject($subject);
            ee()->email->message($message);

            ee()->email->send();
        }

        return true;
    }

    /*
    * Email
    *
    * A more generic email function, than send().
    * Used where we need to trigger one of the native emails
    * Also later we'll hook this up to postmaster, and refactor our send()
    * to route through here
    */
    public function send_email($from = '', $from_name = '', $to = '', $subject = '', $message = '', $plaintext = false, $variables = array(), $constants = array())
    {
        ee()->load->library('email');

        $mailtype = ($plaintext) ? 'text' : 'html';
        $prefs = $this->get_email_settings();


        if (!$from) {
            $from = $prefs['from'];
        }

        if (!$from_name) {
            $from_name = $prefs['name'];
        }

        $from_reply_to = $prefs['reply_to'];
        $from_reply_to_name = $prefs['reply_to_name'];


        // Gets around a bug where the TMPL class can become confused
        // with repeated headless hits. Kill it, and resurrect a new clone
        unset(ee()->TMPL);
        ee()->load->library('template', null, 'TMPL');

        $from = $this->parse($from, $variables);
        $from_name = $this->parse($from_name, $variables);

        $from_reply_to = $this->parse($from_reply_to, $variables);
        $from_reply_to_name = $this->parse($from_reply_to_name, $variables);

        $to = $this->parse($to, $variables);
        $subject = $this->parse($subject, $variables, $constants);
        $message = $this->parse($message, $variables, $constants);

        ee()->email->clear();
        ee()->email->initialize(array('mailtype' => $mailtype, 'validate' => true));
        ee()->email->from($from, $from_name)
            ->to($to)
            ->reply_to($from_reply_to, $from_reply_to_name)
            ->subject($subject)
            ->message($message);


        ee()->email->send();
        ee()->email->clear();

    }


    /**
     * Parse
     *
     * A quick parser, which can optionally use the full EE tmpl parse engine
     */
    public function parse($template, $variables = array(), $constants = array())
    {
        foreach ($constants as $key => $value) {
            if (!is_array($value)) {
                $template = str_replace($key, $value, $template);
            }
        }

        if ($variables) {
            $template = ee()->TMPL->parse_variables($template, array($variables));
        }

        $this->_clean_comments($template);

        return $template;
    }

    private function _clean_comments($template)
    {
        return preg_replace('/{!-- ra:(\w+) --}/', '', $template);
    }


    public function get_site_defaults()
    {
        $ret = array();
        $ret['from'] = ee()->config->item('webmaster_email');
        $ret['name'] = ee()->config->item('webmaster_name');
        $ret['reply_to'] = '';
        $ret['reply_to_name'] = '';

        return $ret;
    }

    private function get_email_settings()
    {
        $prefs = array();

        $prefs['from'] = ee()->config->item('charge_email_send_from');
        $prefs['name'] = ee()->config->item('charge_email_send_name');
        $prefs['reply_to'] = ee()->config->item('charge_email_send_reply_to');
        $prefs['reply_to_name'] = ee()->config->item('charge_email_send_reply_to_name');
        $default_site_email_settings = $this->get_site_defaults();

        foreach ($prefs as $key => $val) {
            if ($val == '') {
                $prefs[$key] = $default_site_email_settings[$key];
            }
        }

        return $prefs;
    }

} // End class

/* End of file Charge_email_model.php */