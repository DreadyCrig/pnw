<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Filter by keywords
 *
 * @package        low_search
 * @author         Lodewijk Schutte ~ Low <hi@gotolow.com>
 * @link           http://gotolow.com/addons/low-search
 * @copyright      Copyright (c) 2015, Low
 */
class Low_search_filter_keywords extends Low_search_filter {

	/**
	 * Priority for this filter
	 */
	protected $priority = 10;

	/**
	 * Current collections
	 */
	private $_collections;
	private $_col_order = array();

	/**
	 * Keywords
	 */
	private $_terms = array();
	private $_keywords = array();
	private $_fulltext;

	/**
	 * Search results for this filter
	 */
	private $_results;

	/**
	 * Fixed results?
	 */
	private $_fixed = FALSE;

	/**
	 * Excerpt and url mapping
	 */
	private $_excerpts = array();
	private $_urls = array();

	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @access     public
	 * @return     void
	 */
	public function __construct()
	{
		parent::__construct();

		// Load multibyte lib
		ee()->load->library('low_multibyte');

		// Load term classes
		if ( ! class_exists('Low_search_term'))
		{
			require_once __DIR__.'/terms/Low_search_term.php';
			require_once __DIR__.'/terms/Low_search_term_group.php';
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Allows for keywords="" parameter, along with its associated params
	 *
	 * @access     public
	 * @return     void
	 */
	public function filter($entry_ids)
	{
		// --------------------------------------
		// Log it
		// --------------------------------------

		$this->_log('Applying '.__CLASS__);

		// --------------------------------------
		// Set internal collections array based on param
		// --------------------------------------

		$this->_collections = ee()->low_search_collection_model->get_by_params();

		// --------------------------------------
		// Collection params given, but not valid
		// --------------------------------------

		if (is_array($this->_collections) && empty($this->_collections))
		{
			$this->_log('No valid collections found');
			return array();
		}

		// --------------------------------------
		// Check validity of search mode
		// --------------------------------------

		if ( ! in_array($this->params->get('search_mode'), ee()->low_search_settings->search_modes))
		{
			$this->params->set('search_mode', ee()->low_search_settings->get('default_search_mode'));
		}

		// --------------------------------------
		// Check validity of loose ends
		// --------------------------------------

		switch ($this->params->get('loose_ends'))
		{
			case 'left':
			case 'right':
			case 'both':
				// We're OK!
			break;

			// Backwards compatibility
			case 'yes':
				$this->params->set('loose_ends', 'right');
			break;

			// Other values are equal to "no"
			default:
				$this->params->set('loose_ends', NULL);
			break;
		}

		// --------------------------------------
		// Check keywords and set the search terms
		// --------------------------------------

		$this->_set_terms();

		// --------------------------------------
		// Only perform actual search if keywords are given
		// --------------------------------------

		if (empty($this->_terms))
		{
			$this->_log('No keyword search');
			$this->_no_keywords();
			return $entry_ids;
		}

		// --------------------------------------
		// Reset results
		// --------------------------------------

		$this->_results = array();

		// --------------------------------------
		// Begin composing query
		// --------------------------------------

		ee()->db->select(($this->_fulltext
			? "entry_id, collection_id, MATCH(index_text) AGAINST('{$this->_query()}') AS score"
			: 'entry_id, collection_id, index_text'), FALSE)
			->from(ee()->low_search_index_model->table());

		// --------------------------------------
		// Filters used by both searches
		// --------------------------------------

		// Limit query by collection
		if ($this->_collections)
		{
			ee()->db->where_in('collection_id', low_flatten_results($this->_collections, 'collection_id'));
		}

		// Limit query by site
		if ($site_ids = $this->params->site_ids())
		{
			ee()->db->where_in('site_id', array_values($site_ids));
		}

		// If entry ids were given, limit to those
		if ($entry_ids)
		{
			ee()->db->where_in('entry_id', $entry_ids);
		}

		// Add where clause
		ee()->db->where($this->_sql_where_keywords(), NULL, FALSE);

		if ($this->_fulltext)
		{
			// Actual fulltext search
			ee()->db->order_by('score', 'desc');

			// Limit by min_score
			if ($this->params->get('min_score'))
			{
				list($score, $include) = $this->_min_score();
				$oper = $include ? '>=' : '>';
				ee()->db->having("score {$oper}", $score);
			}
		}

		// --------------------------------------
		// Extra search stuff
		// --------------------------------------

		if ($add_to_query = $this->params->get_prefixed('keywords-query:', TRUE))
		{
			foreach ($add_to_query AS $field => $val)
			{
				if (ee()->db->field_exists($field, ee()->low_search_index_model->table()))
				{
					list($items, $in) = low_explode_param($val);
					ee()->db->{($in ? 'where_in' : 'where_not_in')}($field, $val);
				}
				else
				{
					$this->_log("Field {$field} does not exist in ".ee()->low_search_index_model->table());
				}
			}
		}

		// --------------------------------------
		// Perform the search
		// --------------------------------------

		$this->_log('Starting search '.($this->_fulltext ? '(fulltext)' : '(fallback)'));
		$query = ee()->db->get();

		// --------------------------------------
		// If the search had no results, return no results bit
		// --------------------------------------

		if ($query->num_rows == 0)
		{
			$this->_log('Searched but found nothing. Returning no results.');
			return array();
		}

		// --------------------------------------
		// If we do have results, continue
		// --------------------------------------

		$this->_results = $this->_fulltext
			? low_associate_results($query->result_array(), 'entry_id')
			: $this->_get_fallback_results($query);

		// Bail out if no entry falls above the min_score threshold
		if (empty($this->_results))
		{
			$this->_log('No valid results after scoring');
			return array();
		}

		// --------------------------------------
		// Modify scores for each collection
		// --------------------------------------

		if (is_array($this->_collections) &&
			($modifiers = array_unique(low_flatten_results($this->_collections, 'modifier'))))
		{
			if ( ! (count($modifiers) == 1 && $modifiers[0] == 1.0))
			{
				$this->_log('Applying collection modifier to search results');

				foreach ($this->_results AS &$row)
				{
					if ($mod = (float) $this->_collections[$row['collection_id']]['modifier'])
					{
						$row['score'] = $row['score'] * $mod;
					}
				}
			}
		}

		// -------------------------------------
		// 'low_search_modify_score' hook.
		//  - Modify scoring for keyword searches
		// -------------------------------------

		if (ee()->extensions->active_hook('low_search_modify_score') === TRUE)
		{
			$this->_results = ee()->extensions->call('low_search_modify_score', $this->_results);

			if (empty($this->_results) || ee()->extensions->end_script === TRUE)
			{
				return array();
			}
		}

		// --------------------------------------
		// Orderby what?
		// --------------------------------------

		// Default by score
		$orderby = $this->params->get('orderby', 'low_search_score');

		// Order by collection
		$prefix  = 'low_search_collection:';

		if ($orderby == 'low_search_score')
		{
			// Just order by score
			uasort($this->_results, array($this, '_by_score'));
			$this->_fixed = TRUE;
		}
		elseif (substr($orderby, 0, strlen($prefix)) == $prefix)
		{
			// Order by collection: make sure internal collections are set
			if (empty($this->_collections)) $this->_set_collections_by_results();

			// An array to map collection names to IDs
			$map = low_flatten_results($this->_collections, 'collection_id', 'collection_name');

			// Set the _col_order to the given order
			foreach (explode(',', substr($orderby, strlen($prefix))) AS $col)
			{
				if ( ! array_key_exists($col, $map)) continue;
				$this->_col_order[] = $map[$col];
			}

			// And sort by collection first, score second
			uasort($this->_results, array($this, '_by_col_order'));
			$this->_fixed = TRUE;
		}
		else
		{
			$this->_fixed = FALSE;
		}

		// --------------------------------------
		// Add results to cache, so extension can look this up
		// --------------------------------------

		return array_keys($this->_results);
	}

	// --------------------------------------------------------------------

	/**
	 * Comparison function to order results by score
	 *
	 * @access     private
	 * @param      array     result row
	 * @param      array     result row
	 * @return     int
	 */
	private function _by_score($a, $b)
	{
		if ( ! isset($a['score']) || ! isset($b['score']) || $a['score'] == $b['score']) return 0;
		return ($a['score'] > $b['score']) ? -1 : 1;
	}

	/**
	 * Comparison function to order results by given collectio, then score
	 *
	 * @access     private
	 * @param      array     result row
	 * @param      array     result row
	 * @return     int
	 */
	private function _by_col_order($a, $b)
	{
		$x = array_search($a['collection_id'], $this->_col_order);
		$y = array_search($b['collection_id'], $this->_col_order);
		if ($x === FALSE) $x = count($this->_col_order);
		if ($y === FALSE) $y = count($this->_col_order);

		if ($x === $y)
		{
			return $this->_by_score($a, $b);
		}
		else
		{
			return ($x < $y) ? -1 : 1;
		}
	}

	/**
	 * Fixed order?
	 */
	public function fixed_order()
	{
		return $this->_fixed;
	}

	// --------------------------------------------------------------------

	/**
	 * No Keywords means:
	 * - get excerpt ID from channel prefs, as no collection is relevant
	 * - get {auto_path} URL for search results per channel
	 * - set channel="" param based on Collections given if it isn't set
	 * - remove reference to keywords and collections from orderby param
	 *
	 * @access     private
	 * @return     void
	 */
	private function _no_keywords()
	{
		// --------------------------------------
		// Query Channels table
		// --------------------------------------

		ee()->db->select('channel_id, channel_name, search_excerpt')
		        ->select("IF(search_results_url='', channel_url, search_results_url) AS url", FALSE)
		        ->from('channels')
		        ->where_in('site_id', $this->params->site_ids());

		// Filter by given collections
		if ($this->_collections)
		{
			$channel_ids = array_unique(low_flatten_results($this->_collections, 'channel_id'));
			ee()->db->where_in('channel_id', $channel_ids);
		}

		// Also filter by channel param
		if ($channel_param = $this->params->get('channel'))
		{
			list($channel, $in) = low_explode_param($channel_param);
			ee()->db->{$in ? 'where_in' : 'where_not_in'}('channel_name', $channel);
		}

		// Get the data
		$channels = ee()->db->get()->result_array();

		// --------------------------------------
		// Populate both internal excerpts and URLs
		// --------------------------------------

		foreach ($channels AS $row)
		{
			$this->_excerpts[$row['channel_id']] = $row['search_excerpt'];
			$this->_urls[$row['channel_id']] = $row['url'];
		}

		// --------------------------------------
		// Set channel="" parameter if not set
		// --------------------------------------

		if ($this->_collections && $channels && ! $channel_param)
		{
			$channels = array_unique(low_flatten_results($channels, 'channel_name'));
			$this->params->set('channel', low_implode_param($channels));
		}

		// --------------------------------------
		// Remove reference to keywords and collections from orderby param
		// --------------------------------------

		$orderby = $this->params->get('orderby');

		if (preg_match('/^low_search_(score|collection)/i', $orderby))
		{
			$this->params->set(preg_replace('/^.*?\|?/', '', $orderby));

			if ($sort = $this->params->get('sort'))
			{
				$this->params->set('sort', preg_replace('/^(asc|desc)\|?/i', '', $sort));
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Prep all {auto_path} URLs like the native Search module does
	 *
	 * @access     private
	 * @return     void
	 */
	private function _prep_urls()
	{
		foreach ($this->_urls AS &$url)
		{
			$url = ee()->functions->prep_query_string($url);
			$url = rtrim($url, '/') . '/';
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Populate internal Terms array
	 */
	private function _set_terms()
	{
		// --------------------------------------
		// Local cache of stop words
		// --------------------------------------

		static $stop_words;

		// --------------------------------------
		// Initiate fulltext mode to TRUE, reset results
		// --------------------------------------

		$this->_fulltext = TRUE;
		$this->_results  = NULL;

		// --------------------------------------
		// Shortcuts to these params
		// --------------------------------------

		$mode  = $this->params->get('search_mode');
		$loose = $this->params->get('loose_ends');

		// --------------------------------------
		// Reset terms
		// --------------------------------------

		$this->_terms = $this->_keywords = array();

		// --------------------------------------
		// Get raw keywords from parameters
		// --------------------------------------

		$words = (string) $this->params->get('keywords');

		// --------------------------------------
		// Replace pipes with spaces as if it were separate words
		// --------------------------------------

		$words = str_replace('|', ' ', $words);
		$words = preg_replace('/\s{2,}/', ' ', $words);
		$words = trim($words);

		// --------------------------------------
		// Alter keywords based on mode
		// --------------------------------------

		switch ($mode)
		{
			case 'exact':
				$words = '"' . str_replace('"', '', $words) . '"';
			break;

			case 'any':
				// Thanks http://stackoverflow.com/a/1191598/1769664
				$words = (strpos($words, '"') === FALSE)
					? str_replace(' ', ' OR ', $words)
					: preg_replace('/\s(?=(?:(?:[^"]*+"){2})*+[^"]*+\z)/', ' OR ', $words);
			break;
		}

		// --------------------------------------
		// Use tokens to get to each term,
		// including quoted ones
		// --------------------------------------

		for ($word = strtok($words, ' '); $word !== FALSE; $word = strtok(' '))
		{
			// Create new Term Object
			$term = new Low_search_term();

			// Is term part of an OR group
			$group = FALSE;

			// Check if search term is part of an OR group
			if ($word == 'OR')
			{
				// Grab the next one or bail
				if (($word = strtok(' ')) === FALSE) break;

				if ($total = count($this->_terms))
				{
					// Set the previous token to a term_group, if it's not already
					$prev = $this->_terms[$total - 1];

					if ( ! ($prev instanceof Low_search_term_group))
					{
						$prev = new Low_search_term_group(array($prev));
						$this->_terms[$total - 1] = $prev;
					}

					$group = TRUE;
				}
			}

			// Negation?
			if ($term->exclude = (substr($word, 0, 1) == '-'))
			{
				$word = ltrim($word, '-');
			}

			// Loose ends on the left
			if ($term->loose_left = (substr($word, 0, 1) == '*' || $loose == 'left' || $loose == 'both'))
			{
				$word = ltrim($word, '*');
				$this->_fulltext = FALSE;
			}

			// Loose ends on the right
			if ($term->loose_right = (substr($word, -1) == '*' || $loose == 'right' || $loose == 'both'))
			{
				$word = rtrim($word, '*');
			}

			// Check for quoted terms
			if (substr($word, 0, 1) == '"')
			{
				// 1-word quote or not?
				$word = (substr($word, -1) == '"')
				     ? substr($word, 1, -1)
				     : substr($word, 1).' '.strtok('"');

				// Set exact marker to TRUE
				$term->exact = TRUE;
			}

			// Non-exact match, strip ignore words from term
			$ignore = $term->exact
				? array()
				: ee()->low_search_settings->get('ignore_words');

			// Record the raw term
			$term->raw = $word;

			// Get the cleaned search term
			$term->term = low_clean_string($word, $ignore);

			// Skip the rest if nothing's left
			if ( ! $term->term) continue;

			// Add term object to previous group or general terms
			$group ? ($prev->terms[] = $term) : ($this->_terms[] = $term);

			// Add whole thing to the keywords array if we're not excluding
			if ( ! $term->exclude)
			{
				$this->_keywords[] = $term->term;
			}

			// Check if keywords are fulltext-worthy
			if ($this->_fulltext)
			{
				foreach (explode(' ', $term->term) AS $word)
				{
					// Get word length
					$length = ee()->low_multibyte->strlen($word);

					if ($length < ee()->low_search_settings->get('min_word_length'))
					{
						$this->_fulltext = FALSE;
						continue;
					}

					// Make sure stop words are an array
					if ( ! is_array($stop_words))
					{
						// Clean stopwords and convert into array
						$stop_words = (array) array_unique(preg_split('/\s+/',
							low_clean_string(ee()->low_search_settings->get('stop_words'))
						));
					}

					if (in_array($word, $stop_words))
					{
						$this->_fulltext = FALSE;
						continue;
					}
				}
			}
		}

		// Final ckeck to see if we're dealing with fulltext or not:
		// Empty keywords is sign that there's only negated keywords,
		// which cannot use fulltext...
		if ($this->_fulltext && empty($this->_keywords))
		{
			$this->_fulltext = FALSE;
		}
	}

	/**
	 * Cleaned up search query given
	 */
	private function _query()
	{
		return implode(' ', $this->_keywords);
	}

	/**
	 * Get min score based on param
	 */
	private function _min_score()
	{
		$min_score = $this->params->get('min_score');
		$include   = FALSE;

		// If param starts with =, it's inclusive
		if (substr($min_score, 0, 1) == '=')
		{
			$min_score = substr($min_score, 1);
			$include   = TRUE;
		}

		return array($min_score, $include);
	}

	/**
	 * Get fallback results and calculate score
	 */
	private function _get_fallback_results($query)
	{
		$this->_log('Calculating relevance score');

		// Calculate scores ourselves
		$results       = array();
		$pattern       = $this->_get_pattern();
		$keyword_count = count($this->_keywords);

		// --------------------------------------
		// Loop thru results, calculate score
		// based on total words / word count
		// --------------------------------------

		foreach ($query->result() AS $row)
		{
			// Calculate score
			$score = 0;

			// But only if we have valid keywords
			if ($keyword_count)
			{
				// Check occurrence of each word in index_text
				// Added score is number of occurrences / total words * 10
				if ($found = preg_match_all($pattern, $row->index_text, $matches))
				{
					// Removes weight
					$text = preg_replace('/^\|(.+?)\|.*$/m', '$1', $row->index_text);

					// Safe word count
					$word_count = count(explode(' ', trim($text)));

					// Add score
					$score = ($found / $word_count) * 100 / $keyword_count;
				}

				// Skip entries that fall below the threshold
				if ($this->params->get('min_score'))
				{
					list($min_score, $include) = $this->_min_score();
					if (($include && $score < $min_score) || (!$include && $score <= $min_score)) continue;
				}
			}

			// Add row to results only if the entry doesn't exist yet
			// or if existing score is lower than this one
			if ( ! array_key_exists($row->entry_id, $results) || $results[$row->entry_id]['score'] < $score)
			{
				$results[$row->entry_id] = array(
					'entry_id'      => $row->entry_id,
					'collection_id' => $row->collection_id,
					'score'         => $score
				);
			}
		}

		return $results;
	}

	// --------------------------------------------------------------------

	/**
	 * Get where clausule for keywords based on terms
	 *
	 * @access     private
	 * @return     string
	 */
	private function _sql_where_keywords()
	{
		$where = array();
		$tmpl  = $this->_fulltext
			? "MATCH(index_text) AGAINST('%s' IN BOOLEAN MODE)"
			: '(index_text %s)';

		foreach ($this->_terms AS $obj)
		{
			if ($obj instanceof Low_search_term_group)
			{
				$group = array();

				foreach ($obj->terms AS $term)
				{
					$group[] = $this->_fulltext
						? $term->get_fulltext_term(FALSE)
						: sprintf($tmpl, $term->get_fallback_term());
				}

				$where[] = $this->_fulltext
					? '+('.implode(' ', $group).')'
					: '('.implode(' OR ', $group).')';
			}
			else
			{
				$where[] = $this->_fulltext
					? $obj->get_fulltext_term()
					: sprintf($tmpl, $obj->get_fallback_term());
			}
		}

		$str = $this->_fulltext
			? sprintf($tmpl, implode(' ', $where))
			: implode("\nAND ", $where);

		return $str;
	}

	// --------------------------------------------------------------------

	/**
	 * Set internal collections by results
	 */
	private function _set_collections_by_results()
	{
		$col_ids = low_flatten_results($this->_results, 'collection_id');
		$col_ids = array_unique($col_ids);
		$this->_collections = ee()->low_search_collection_model->get_by_id($col_ids);
	}

	/**
	 * Modify a row for a search result for this filter
	 */
	public function results($rows)
	{
		// -------------------------------------------
		// Shortcut to prefix
		// -------------------------------------------

		$pfx = ee()->low_search_settings->prefix;

		// -------------------------------------------
		// Prep collection info
		// -------------------------------------------

		if ( ! $this->_collections && $this->_results)
		{
			$this->_set_collections_by_results();
		}

		// -------------------------------------------
		// Get auto_path info if not present, and only if we need to
		// -------------------------------------------

		if (empty($this->_urls) && array_key_exists('auto_path', ee()->TMPL->var_single))
		{
			$channel_ids = array_unique(low_flatten_results($rows, 'channel_id'));

			$query = ee()->db->select('channel_id')
			       ->select("IF(search_results_url='', channel_url, search_results_url) AS url", FALSE)
			       ->from('channels')
			       ->where_in('channel_id', $channel_ids)
			       ->get();

			$this->_urls = low_flatten_results($query->result_array(), 'url', 'channel_id');
			$this->_prep_urls();
		}

		// -------------------------------------------
		// Loop through results and do yer thing
		// -------------------------------------------

		foreach ($rows AS &$row)
		{
			// Add {auto_path}
			$row['auto_path'] = (array_key_exists($row['channel_id'], $this->_urls)
				? $this->_urls[$row['channel_id']]
				: '/') . $row['url_title'];

			// Get score for this entry
			$row[$pfx.'score'] = $this->_get_score($row['entry_id']);

			// Add collection info to row
			foreach ($this->_get_collection_info($row['entry_id']) AS $key => $val)
			{
				$row[$pfx.$key] = $val;
			}
		}

		// -------------------------------------------
		// No excerpt var in tagdata? No need to proceed.
		// -------------------------------------------

		if (strpos(ee()->TMPL->tagdata, $pfx.'excerpt') === FALSE)
		{
			return $rows;
		}

		// -------------------------------------------
		// Load typography lib
		// -------------------------------------------

		ee()->load->library('typography');

		// -------------------------------------------
		// Get all entry ids we're working with
		// -------------------------------------------

		$entry_ids = low_flatten_results($rows, 'entry_id');

		// -------------------------------------------
		// Loop through results and add the excerpt
		// -------------------------------------------

		foreach ($rows AS &$row)
		{
			// Get excerpt ID, the field ID to use as excerpt; 0 for title
			$eid = $this->_get_excerpt_id($row);

			// Skip if no valid excerpt ID is found
			if ($eid === FALSE) continue;

			// Get string and format for excerpt
			$str = ($eid == '0' || ! isset($row['field_id_'.$eid]))  ? $row['title'] : $row['field_id_'.$eid];
			$fmt = ($eid == '0' || ! isset($row['field_fmt_'.$eid])) ? 'xhtml'       : $row['field_fmt_'.$eid];

			// -------------------------------------------
			// 'low_search_excerpt' hook
			// - change the excerpt for an entry
			// -------------------------------------------

			if (ee()->extensions->active_hook('low_search_excerpt') === TRUE)
			{
				$str = ee()->extensions->call('low_search_excerpt', $entry_ids, $row, $eid);

				// Check return value
				if (is_array($str) && count($str) == 2)
				{
					// Set excerpt string to first item in array
					list($str, $skip) = $str;

					// If second item in return value, skip native creation of excerpt
					if ($skip === TRUE)
					{
						$row[$pfx.'excerpt'] = $str;
						continue;
					}
				}
			}

			// Overwrite empty excerpt with formatted one
			$row[$pfx.'excerpt'] = $this->_create_excerpt($str, $fmt);

			// Highlight keywords if we have 'em
			if ($this->_keywords)
			{
				$row[$pfx.'excerpt'] = $this->_highlight($row[$pfx.'excerpt']);

				if (ee()->low_search_settings->get('title_hilite') == 'y')
				{
					$row['title'] = $this->_highlight($row['title']);
				}
			}
		}

		return $rows;
	}

	/**
	 * Get regex pattern for current search terms
	 */
	private function _get_pattern()
	{
		// Account for non-exact matching
		$delim = ($this->params->get('search_mode') == 'exact') ? ' ' : '|';
		$keywords = implode($delim, array_map('preg_quote', $this->_keywords));

		// Loose ends?
		switch ($this->params->get('loose_ends'))
		{
			case 'left':
				$pattern = "({$keywords})\b";
			break;

			case 'right':
				$pattern = "\b({$keywords})";
			break;

			case 'both':
				$pattern = "({$keywords})";
			break;

			default:
				$pattern = "\b({$keywords})\b";
			break;
		}

		return "!{$pattern}!iu";
	}

	/**
	 * Get score for entry ID
	 */
	private function _get_score($entry_id)
	{
		return isset($this->_results[$entry_id])
			? number_format(round($this->_results[$entry_id]['score'], 2), 2)
			: 0;
	}

	/**
	 * Get collection info for this search result
	 */
	private function _get_collection_info($entry_id)
	{
		// Prefix
		$pfx = 'collection_';

		// Init info
		$cols = array(
			$pfx.'id'       => '',
			$pfx.'name'     => '',
			$pfx.'label'    => '',
			$pfx.'language' => '',
			'excerpt'       => ''
		);

		if (isset($this->_results[$entry_id]))
		{
			$col_id = $this->_results[$entry_id][$pfx.'id'];

			if (isset($this->_collections[$col_id]))
			{
				$row = $this->_collections[$col_id];

				foreach ($row AS $key => $val)
				{
					// Prefix language with collection_
					if ($key == 'language') $key = $pfx.$key;

					if (array_key_exists($key, $cols))
					{
						$cols[$key] = $val;
					}
				}
			}
		}

		return $cols;
	}

	/**
	 * Get excerpt ID, the field ID to use as excerpt; 0 for title
	 */
	private function _get_excerpt_id($row)
	{
		$id = FALSE;

		if (is_numeric($row['low_search_excerpt']))
		{
			$id = $row['low_search_excerpt'];
		}
		elseif (isset($this->_excerpts[$row['channel_id']]))
		{
			$id = $this->_excerpts[$row['channel_id']];
		}

		return $id;
	}

	/**
	 * Add/modify entry in search index
	 *
	 * @access      private
	 * @param       string
	 * @param       string
	 * @return      string
	 */
	private function _create_excerpt($str = '', $fmt = 'none')
	{
		// Strip tags first
		$str = strip_tags($str);

		// Typography options
		$options = array(
			'text_format'   => $fmt,
			'html_format'   => 'safe',
			'auto_links'    => 'n',
			'allow_img_url' => 'n'
		);

		// Format text
		$str = ee()->typography->parse_type($str, $options);

		// Strip again and trim it
		$str = trim(strip_tags($str));

		// Clean white space
		$str = preg_replace('/(&nbsp;|\s)+/', ' ', $str);

		// Limited string
		if ($length = (int) ee()->low_search_settings->get('excerpt_length'))
		{
			// Multibyte safe checks
			$str_length = ee()->low_multibyte->strlen($str);
			$word_count = ee()->low_multibyte->substr_count($str, ' ');
			$word_count++;

			// Bail out if string is shorter than the amount of words given
			if ($length >= $str_length || $length >= $word_count) return $str;

			if ($this->_keywords)
			{
				// Prep our marker to get the actual position of the first occurrence of the keywords
				$marker = '[[__KEYWORD__]]';

				// Split the excerpt at the first keyword found
				$tmp = preg_replace($this->_get_pattern(), $marker, $str);

				// Get the position
				$pos = ee()->low_multibyte->strpos($tmp, $marker);

				// Overwrite the tmp var where we split the string
				$tmp = array(
					ee()->low_multibyte->substr($str, 0, $pos),
					ee()->low_multibyte->substr($str, $pos, ee()->low_multibyte->strlen($str))
				);

				// If we have a split, check the left part
				if ($tmp && count($tmp) >= 2)
				{
					// Amount of words to put on the left
					$left_count = round($length / 10);

					// Explode left words into an array
					$tmp[0] = explode(' ', $tmp[0]);

					// If there are more words on the left than allowed...
					if (count(array_filter($tmp[0])) > $left_count)
					{
						// ...slice off excess words...
						$tmp[0] = array_slice($tmp[0], -$left_count);

						// ...add horizontal ellipsis to the now first word and...
						$tmp[0][0] = '&#8230;'.$tmp[0][0];
					}

					// ...put it together again.
					$tmp[0] = implode(' ', $tmp[0]);

					// Now bring the whole excerpt together again...
					$str = implode('', $tmp);
				}
			}

			// ...and let EE's word limiter do the rest
			$str = ee()->functions->word_limiter($str, $length);
		}

		return $str;
	}

	/**
	 * Highlight keywords in given string
	 *
	 * @access      private
	 * @param       string
	 * @param       string
	 * @return      string
	 */
	private function _highlight($str)
	{
		if ($tag = ee()->low_search_settings->get('excerpt_hilite'))
		{
			// Case insensitive replace
			$str = preg_replace($this->_get_pattern(), "<{$tag}>$1</{$tag}>", $str);
		}

		return $str;
	}
}
// End of class Low_search_keywords_filter
// End of file lsf.keywords.php