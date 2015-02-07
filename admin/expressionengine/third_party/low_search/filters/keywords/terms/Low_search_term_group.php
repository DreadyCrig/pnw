<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Grouped search terms
 *
 * @package        low_search
 * @author         Lodewijk Schutte ~ Low <hi@gotolow.com>
 * @link           http://gotolow.com/addons/low-search
 * @copyright      Copyright (c) 2015, Low
 */
class Low_search_term_group {

	/**
	 * Array of Low_search_term instances
	 */
	public $terms = array();

	/**
	 * Construct and set the terms
	 */
	public function __construct($terms = array())
	{
		$this->terms = $terms;
	}

}