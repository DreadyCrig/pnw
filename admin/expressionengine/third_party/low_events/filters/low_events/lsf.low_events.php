<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Low Events Filter for Low Search
 *
 * @package        low_events
 * @author         Lodewijk Schutte <hi@gotolow.com>
 * @link           http://gotolow.com/addons/low-events
 * @copyright      Copyright (c) 2012-2015, Low
 */
class Low_search_filter_low_events extends Low_search_filter {

	/**
	 * Prefix for this filter
	 */
	private $_pfx = 'low_events:';

	/**
	 * Fixed order?
	 */
	private $_fixed = FALSE;


	/**
	 * Filter method
	 */
	public function filter($entry_ids)
	{
		// -------------------------------------------
		// Get all low_events: parameters
		// -------------------------------------------

		$params = $this->params->get_prefixed($this->_pfx, TRUE);

		// -------------------------------------------
		// Also look at the orderby parameter
		// -------------------------------------------

		$field_id = $field = $order = FALSE;

		if (preg_match('/^([\w-]+):(start|end)(_date)?\b/i', $this->params->get('orderby'), $m))
		{
			if ($field_id = $this->_get_events_field_id($m[1]))
			{
				$field = $m[1];
				$order = $m[2];

				// Yes, this is a fixed order
				$this->_fixed = TRUE;
			}
		}

		// -------------------------------------------
		// If no events params exist and we're not ordering, bail.
		// -------------------------------------------

		if (empty($params) && empty($field_id)) return $entry_ids;

		// --------------------------------------
		// Log it
		// --------------------------------------

		$this->_log('Applying '.__CLASS__);

		if (empty($params))
		{
			// -------------------------------------------
			// No params, but ordering: just filter existing entry_ids
			// -------------------------------------------

			// Start query to get all events for given field in order
			ee()->db->select('entry_id')
			        ->distinct()
			        ->from('low_events')
			        ->where_in('site_id', $this->params->site_ids())
			        ->where('field_id', $field_id);

			if ( ! empty($entry_ids))
			{
				ee()->db->where_in('entry_id', $entry_ids);
			}

			// Order by date and time
			// Always asc, gets flipped by sort-param
			ee()->db->order_by($order.'_date', 'asc');
			ee()->db->order_by($order.'_time', 'asc');

			$this->_log('Getting all events for field '.$field);

			// Get the lot
			$query = ee()->db->get();

			// Compile to entry IDS
			$entry_ids = low_flatten_results($query->result_array(), 'entry_id');
		}
		else
		{
			// -------------------------------------------
			// We do have parameters; let the LE mod-file handle it
			// -------------------------------------------

			if ( ! class_exists('Low_events'))
			{
				$this->_log('Loading Low Events module file');
				require_once PATH_THIRD.'low_events/mod.low_events.php';
			}

			$Events = new Low_events();

			// Compose params array
			if ( ! empty($entry_ids))
			{
				$params['entry_id'] = implode('|', $entry_ids);
			}

			// Limit by events field?
			if ($field)
			{
				$params['events_field'] = $field;
			}

			// Ordering?
			if ($order)
			{
				$params['orderby'] = 'low_events:'.$order;
			}

			// Save old params for later
			$old_params = ee()->TMPL->tagparams;

			// Overwrite current tagparams so Low_events::entries can work with it
			ee()->TMPL->tagparams = $params;

			$this->_log('Calling low_events::entries()');
			$entry_ids = $Events->entries(TRUE);

			// Restore original params
			ee()->TMPL->tagparams = $old_params;
		}

		return $entry_ids;
	}

	/**
	 * Fixed order?
	 */
	public function fixed_order()
	{
		return $this->_fixed;
	}

	/**
	 * Check whether given field is a Low Events field
	 */
	private function _get_events_field_id($field)
	{
		if ( ! ($id = $this->_get_field_id($field))) return FALSE;

		return (($fields = low_get_cache('channel', 'pair_custom_fields')) &&
				($this->_get_field_id($id, $fields) == LOW_EVENTS_PACKAGE)) ? $id : FALSE;
	}

}
// end of file lsf.low_events.php