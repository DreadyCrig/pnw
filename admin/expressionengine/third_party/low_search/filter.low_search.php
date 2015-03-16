<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Low Search Filter class, for inheritance
 *
 * @package        low_search
 * @author         Lodewijk Schutte ~ Low <hi@gotolow.com>
 * @link           http://gotolow.com/addons/low-search
 * @copyright      Copyright (c) 2015, Low
 */
abstract class Low_search_filter {

	/**
	 * Default priority for this filter
	 */
	protected $priority = 5;

	/**
	 * Shortcut to Low_search_params
	 */
	protected $params;

	// --------------------------------------------------------------------
	// METHODS
	// --------------------------------------------------------------------

	/**
	 * Constructor
	 */
	public function __construct()
	{
		// Set the shortcut
		$this->params =& ee()->low_search_params;
	}

	// --------------------------------------------------------------------

	/**
	 * Return the priority
	 */
	public function priority()
	{
		return $this->priority;
	}

	/**
	 * The filter method
	 */
	public function filter($entry_ids)
	{
		return $entry_ids;
	}

	/**
	 * Fixed order?
	 */
	public function fixed_order()
	{
		return FALSE;
	}

	/**
	 * The results method for manipulating search results
	 */
	public function results($rows)
	{
		return $rows;
	}

	// --------------------------------------------------------------------

	/**
	 * Get field id for given field short name
	 *
	 * @access      protected
	 * @param       string
	 * @param       array
	 * @return      int
	 */
	protected function _get_field_id($str, $fields = array())
	{
		// --------------------------------------
		// Get custom channel fields from cache
		// --------------------------------------

		if (empty($fields) && ! ($fields = low_get_cache('channel', 'custom_channel_fields')))
		{
			// If not present, get them from the API
			// Takes some effort, but its reusable for others this way
			$this->_log('Getting channel field info from API');

			ee()->load->library('api');
			ee()->api->instantiate('channel_fields');

			$fields = ee()->api_channel_fields->fetch_custom_channel_fields();

			foreach ($fields AS $key => $val)
			{
				low_set_cache('channel', $key, $val);
			}

			$fields = $fields['custom_channel_fields'];
		}

		// --------------------------------------
		// To be somewhat compatible with MSM,
		// get the first ID that matches,
		// not just for current site, but all given.
		// --------------------------------------

		// Initiate ID
		$it = 0;

		// Check active site IDs, return first match encountered
		foreach (ee()->low_search_params->site_ids() AS $site_id)
		{
			if (isset($fields[$site_id][$str]))
			{
				$it = $fields[$site_id][$str];
				break;
			}
		}

		// Please
		return $it;
	}

	// --------------------------------------------------------------------

	/**
	 * Get WHERE clause for given field and parameter value, based on search: field rules
	 *
	 * @access     protected
	 * @param      string
	 * @param      string
	 * @return     string
	 */
	protected function _get_where_search($field, $val)
	{
		// Initiate some vars
		$exact = $all = $starts = $ends = $exclude = FALSE;
		$sep = '|';

		// Exact matches
		if (substr($val, 0, 1) == '=')
		{
			$val   = substr($val, 1);
			$exact = TRUE;
		}

		// Starts with matches
		if (substr($val, 0, 1) == '^')
		{
			$val    = substr($val, 1);
			$starts = TRUE;
		}

		// Ends with matches
		if (substr($val, -1) == '$')
		{
			$val  = rtrim($val, '$');
			$ends = TRUE;
		}

		// All items? -> && instead of |
		if (strpos($val, '&&') !== FALSE)
		{
			$all = TRUE;
			$sep = '&&';
		}

		// Excluding?
		if (substr($val, 0, 4) == 'not ')
		{
			$val = substr($val, 4);
			$exclude = TRUE;
		}

		// Explode it
		$items = explode($sep, $val);

		// Init sql for where clause
		$sql = array();

		// Loop through each sub-item of the filter an create sub-clause
		foreach ($items AS $item)
		{
			// Convert IS_EMPTY constant to empty string
			$empty = ($item == 'IS_EMPTY');
			$item  = str_replace('IS_EMPTY', '', $item);

			// whole word? Regexp search
			if (substr($item, -2) == '\W')
			{
				$operand = $exclude ? 'NOT REGEXP' : 'REGEXP';
				$item = preg_quote(substr($item, 0, -2));
				$item = str_replace("'", "\'", $item);
				$item = "'[[:<:]]{$item}[[:>:]]'";
			}
			else
			{
				if (preg_match('/^([<>]=?)([\d\.]+)$/', $item, $match))
				{
					// Numeric operator!
					$operand = $match[1];
					$item    = $match[2];
				}
				elseif ($exact || $empty || ($starts && $ends))
				{
					// Use exact operand if empty or = was the first char in param
					$operand = $exclude ? '!=' : '=';
					$item = "'".ee()->db->escape_str($item)."'";
				}
				else
				{
					// Use like operand in all other cases
					$operand = $exclude ? 'NOT LIKE' : 'LIKE';
					$item = '%'.ee()->db->escape_like_str($item).'%';

					// Allow for starts/ends with matching
					if ($starts) $item = ltrim($item, '%');
					if ($ends)   $item = rtrim($item, '%');

					$item = "'{$item}'";
				}
			}

			$sql[] = sprintf("(%s %s %s)", $field, $operand, $item);
		}

		// Inclusive or exclusive
		$andor = $all ? ' AND ' : ' OR ';

		// Get complete clause, with parenthesis and everything
		$where = (count($sql) == 1) ? $sql[0] : '('.implode($andor, $sql).')';

		return $where;
	}

	// --------------------------------------------------------------------

	/**
	 * Remove vars from tagdata
	 *
	 * @access     protected
	 * @param      mixed
	 * @return     void
	 */
	protected function _remove_rogue_vars($key, $prefix = TRUE)
	{
		// Force array
		if ( ! is_array($key))
		{
			$key = array($key);
		}

		foreach ($key AS $pfx)
		{
			// Append global prefix?
			if ($prefix)
			{
				$pfx = ee()->low_search_settings->prefix . $pfx;
			}

			// Escape
			$pfx = preg_quote($pfx);

			// Strip vars from tagdata
			ee()->TMPL->tagdata = preg_replace(
				"/\{{$pfx}[\w\-:]+?\}/", '',
				ee()->TMPL->tagdata
			);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Log message to Template Logger
	 *
	 * @access     protected
	 * @param      string
	 * @return     void
	 */
	protected function _log($msg)
	{
		ee()->TMPL->log_item("Low Search: {$msg}");
	}
}
// End of file filter.low_search.php