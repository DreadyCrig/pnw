<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Charge Email Model class
 *
 * @package         charge_ee_addon
 * @version         1.8.12
 * @author          Joel Bradbury ~ <joel@squarebit.co.uk>
 * @link            http://squarebit.co.uk/addons/charge
 * @copyright       Copyright (c) 2014, Joel Bradbury/Square Bit
 */
class Charge_email_model extends Charge_model {

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
		ee()->load->library('template', NULL, 'TMPL');
		ee()->load->library('email');

		// Get the appropriate template
		$template = explode('/', $template);
		if(count($template) != 2) return FALSE;

		$message = ee()->TMPL->fetch_template(trim($template[0]), trim($template[1]), FALSE);
		$variables = ee()->charge_stripe->fetch($hash);

		// Also parse our subject just in case
		$subject = ee()->TMPL->parse_variables($subject, array($variables));
		$message = ee()->TMPL->parse_variables($message, array($variables));
		ee()->TMPL->parse($message);
		$message = ee()->TMPL->final_template;

		// Clean out any comment tags
		$message = $this->_clean_comments($message);

		// Don't send it twice to the same place

		$sent_addressess = array();
		foreach($to as $address) {

			if(in_array($address, $sent_addressess)) continue;
			$sent_addressess[] = $address;

			ee()->email->mailtype  = 'html';
			ee()->email->from(ee()->config->item('webmaster_email'));
			ee()->email->to($address);

			if($bcc != '') ee()->email->bcc($bcc);

			ee()->email->subject($subject);
			ee()->email->message($message);

			ee()->email->send();
		}

		return TRUE;
	}

	/*
	* Email
	*
	* A more generic email function, than send().
	* Used where we need to trigger one of the native emails
	* Also later we'll hook this up to postmaster, and refactor our send()
	* to route through here
	*/
	public function send_email($from = '', $from_name = '', $to = '', $subject = '', $message = '', $plaintext = FALSE, $variables = array(), $constants = array())
	{
		ee()->load->library('email');

		$mailtype = ($plaintext) ? 'text' : 'html';


		if ( ! $from )
		{
			$from = ee()->config->item('webmaster_email');
		}

		if ( ! $from_name)
		{
			$from_name = ee()->config->item('webmaster_name');
		}

		if (!isset($from_reply_to))
		{
			$from_reply_to = $from;
		}
		if (!isset($from_reply_to_name))
		{
			$from_reply_to_name = $from_name;
		}

		// Gets around a bug where the TMPL class can become confused
		// with repeated headless hits. Kill it, and resurrect a new clone
		unset(ee()->TMPL);
		ee()->load->library('template', NULL, 'TMPL');

		$from = $this->parse($from, $variables);
		$from_name = $this->parse($from_name, $variables);

		$from_reply_to = $this->parse($from_reply_to, $variables);
		$from_reply_to_name = $this->parse($from_reply_to_name, $variables);

		$to = $this->parse($to, $variables);
		$subject = $this->parse($subject, $variables, $constants);
		$message = $this->parse($message, $variables, $constants);

		ee()->email->clear();
		ee()->email->initialize(array('mailtype' => $mailtype, 'validate' => TRUE));
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
		foreach ($constants as $key => $value)
		{
			if (!is_array($value))
			{
				$template = str_replace($key, $value, $template);
			}
		}

		if ($variables)
		{
			$template = ee()->TMPL->parse_variables($template, array($variables));
		}

		$this->_clean_comments($template);

		return $template;
	}

	private function _clean_comments($template)
	{
		return preg_replace('/{!-- ra:(\w+) --}/', '', $template);
	}

} // End class

/* End of file Charge_email_model.php */