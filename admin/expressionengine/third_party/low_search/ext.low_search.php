<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// include base class
if ( ! class_exists('Low_search_base'))
{
	require_once(PATH_THIRD.'low_search/base.low_search.php');
}

/**
 * Low Search extension class
 *
 * @package        low_search
 * @author         Lodewijk Schutte <hi@gotolow.com>
 * @link           http://gotolow.com/addons/low-search
 * @copyright      Copyright (c) 2015, Low
 */
class Low_search_ext extends Low_search_base {

	// --------------------------------------------------------------------
	// PROPERTIES
	// --------------------------------------------------------------------

	/**
	 * Do settings exist?
	 *
	 * @access      public
	 * @var         bool
	 */
	public $settings_exist = TRUE;

	/**
	 * Settings array
	 *
	 * @var        array
	 * @access     public
	 */
	public $settings = array();

	/**
	 * Extension is required by module
	 *
	 * @access      public
	 * @var         array
	 */
	public $required_by = array('module');

	// --------------------------------------------------------------------
	// PUBLIC METHODS
	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @access      public
	 * @param       array
	 * @return      void
	 */
	public function __construct($settings = array())
	{
		// Add path for CP requests
		if (REQ == 'CP') ee()->load->add_package_path(PATH_THIRD.$this->package);

		// Call Base constructor
		parent::__construct();

		// Set the Settings object
		ee()->low_search_settings->set($settings);

		// Assign current settings
		$this->settings = ee()->low_search_settings->get();
	}

	// --------------------------------------------------------------------

	/**
	 * Settings: redirect to module
	 *
	 * @access      public
	 * @return      void
	 */
	public function settings()
	{
		ee()->functions->redirect($this->mcp_url('settings'));
	}

	// --------------------------------------------------------------------
	// HOOKS
	// --------------------------------------------------------------------

	/**
	 * Add/modify entry in search index
	 *
	 * @access      public
	 * @param       int
	 * @param       array
	 * @param       array
	 * @return      void
	 */
	public function entry_submission_end($entry_id, $meta, $data)
	{
		// Use the index lib to rebuild this entry
		ee()->load->library('Low_search_index');
		ee()->low_search_index->build_by_entry($entry_id);
	}

	/**
	 * Delete entry from search index
	 *
	 * @access      public
	 * @param       int
	 * @param       int
	 * @return      void
	 */
	public function delete_entries_loop($entry_id, $channel_id)
	{
		ee()->low_search_index_model->delete($entry_id, 'entry_id');
	}

	/**
	 * Add search score to channel entries
	 *
	 * @access      public
	 * @param       object
	 * @param       array
	 * @return      array
	 */
	public function channel_entries_query_result($obj, $query)
	{
		// -------------------------------------------
		// Get the latest version of $query
		// -------------------------------------------

		if (ee()->extensions->last_call !== FALSE)
		{
			$query = ee()->extensions->last_call;
		}

		// -------------------------------------------
		// Bail out if we're not Low Searching
		// -------------------------------------------

		if (ee()->TMPL->fetch_param('low_search') != 'yes') return $query;

		// -------------------------------------------
		// Get variables from parameters
		// -------------------------------------------

		$vars = ee()->low_search_params->get_vars(ee()->low_search_settings->prefix);

		// -------------------------------------------
		// Add shortcut data to vars
		// -------------------------------------------

		if ($row = low_get_cache(LOW_SEARCH_PACKAGE, 'shortcut'))
		{
			foreach (ee()->low_search_shortcut_model->get_template_attrs() AS $key)
			{
				$vars[ee()->low_search_settings->prefix.$key] = $row[$key];
			}
		}

		// -------------------------------------------
		// Get pipe-separated list of entries
		// -------------------------------------------

		$entry_ids = ee()->low_search_filters->entry_ids();
		$entry_ids = empty($entry_ids) ? '' : implode('|', $entry_ids);

		// -------------------------------------------
		// Loop through entries and add items
		// -------------------------------------------

		foreach ($query AS &$row)
		{
			// Add the filter's entry IDs to the row
			$row[ee()->low_search_settings->prefix.'entry_ids'] = $entry_ids;

			// Add all search parameters to entry
			$row = array_merge($row, $vars);
		}

		// Check what the filters are doing
		$query = ee()->low_search_filters->results($query);

		return $query;
	}

	// --------------------------------------------------------------------

	/**
	 * Change collections when a custom field is deleted
	 */
	public function custom_field_modify_data($ft, $method, $data)
	{
		// -------------------------------------------
		// Get the latest version of $data
		// -------------------------------------------

		if (ee()->extensions->last_call !== FALSE)
		{
			$data = ee()->extensions->last_call;
		}

		// -------------------------------------------
		// Remove reference to field if found in collection settings
		// -------------------------------------------

		if ($method == 'settings_modify_column')
		{
			$collections = ee()->low_search_collection_model->get_all();

			foreach ($data AS $row)
			{
				// Skip if not deleting
				if ($row['ee_action'] != 'delete') continue;

				foreach ($collections AS $col_id => $col)
				{
					// Init update array
					$update = array();

					// Is the field the excerpt? If so, fall back to title
					if ($row['field_id'] == $col['excerpt'])
					{
						$update['excerpt'] = 0;
					}

					// Is the field part of a collection's settings?
					// If so, remove it
					if (array_key_exists($row['field_id'], $col['settings']))
					{
						unset($col['settings'][$row['field_id']]);
						$update['settings'] = low_search_encode($col['settings'], FALSE);

						// also update edit date to trigger 'rebuild index' message
						$update['edit_date'] = ee()->localize->now;
					}

					// If we need to update, do so
					if ( ! empty($update))
					{
						ee()->low_search_collection_model->update($col_id, $update);
					}
				}
			}
		}

		// Return the data again
		return $data;
	}

	// --------------------------------------------------------------------

	/**
	 * Category hooks
	 */
	public function category_save($cat_id, $data)
	{
		return $this->_update_index_by_category(array($cat_id));
	}

	/**
	 * Category hooks
	 */
	public function category_delete($cat_ids)
	{
		return $this->_update_index_by_category($cat_ids);
	}

	// --------------------------------------------------------------------
	// PRIVATE METHODS
	// --------------------------------------------------------------------

	/**
	 * Update by category
	 */
	private function _update_index_by_category($cat_ids)
	{
		if (empty($cat_ids)) return $cat_ids;

		// Get entries for this category
		$query = ee()->db->select('entry_id')
		       ->from('category_posts')
		       ->where_in('cat_id', $cat_ids)
		       ->get();

		$entry_ids = array_unique(low_flatten_results($query->result_array(), 'entry_id'));

		// Do nothing if we haven't got anything
		if ( ! $entry_ids) return;

		// Only update if we're below the threshold, or we can timeout
		if (count($entry_ids) <= $this->settings['batch_size'])
		{
			ee()->load->library('Low_search_index');
			ee()->low_search_index->build_by_entry($entry_ids);
		}
		else
		{
			// @todo: Touch collections to alert user to update?
		}
	}
}
/* End of file ext.low_search.php */