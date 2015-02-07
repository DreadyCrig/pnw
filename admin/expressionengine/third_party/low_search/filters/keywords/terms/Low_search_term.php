<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Single search term
 *
 * @package        low_search
 * @author         Lodewijk Schutte ~ Low <hi@gotolow.com>
 * @link           http://gotolow.com/addons/low-search
 * @copyright      Copyright (c) 2015, Low
 */
class Low_search_term {

	public $raw;
	public $term;
	public $exact       = FALSE;
	public $exclude     = FALSE;
	public $loose_left  = FALSE;
	public $loose_right = FALSE;

	/**
	 * Return the term for fulltext search
	 */
	public function get_fulltext_term($include = TRUE)
	{
		$term = $this->term;

		if ($this->exact) $term = '"'.$term.'"';
		if ($this->exclude) $term = '-'.$term;
		if ($this->loose_right) $term = $term.'*';
		if ($include && ! $this->exclude) $term = '+'.$term;

		return $term;
	}

	/**
	 * Return the term for fallback search
	 */
	public function get_fallback_term()
	{
		$term = " {$this->term} ";

		if ($this->loose_left) $term = ltrim($term);
		if ($this->loose_right) $term = rtrim($term);

		$like = $this->exclude ? 'NOT LIKE' : 'LIKE';

		return "{$like} '%{$term}%'";
	}
}