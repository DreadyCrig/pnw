<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Charge helper class
 *
 * @package         charge_ee_addon
 * @version         1.9.5
 * @author          Joel Bradbury ~ <joel@squarebit.co.uk>
 * @link            http://squarebit.co.uk/software/expressionengine/charge
 * @copyright       Copyright (c) 2015, Joel Bradbury/Square Bit
 */

// --------------------------------------------------------------------

/**
 * Object to Array
 *
 * From a multi-dimensional object return a
 * usable multi-dimensional array
 *
 * @param      array
 * @param 	   bool
 * @return     array
 */
if ( ! function_exists('Charge_obj_to_array'))
{
	function Charge_obj_to_array($obj, $clean = FALSE, $convert = array() )
	{

	    if(is_object($obj)) $obj = (array) $obj;

	    if(is_array($obj)) {

	        $new = array();

	        foreach($obj as $key => $val) {

	        	if( $clean )
	        	{
		        	$key = str_replace( '-', '_', $key );

		        	if( isset( $convert[ $key ] ) ) $key = $convert[ $key ];
		        }

	            $new[$key] = Charge_obj_to_array($val, $clean);
	        }
	    }
	    else $new = $obj;

	    return $new;
	}
}

/**
 * Debug
 *
 * @param       mixed
 * @param       bool
 * @return      void
 */
if ( ! function_exists('dumper'))
{
	function dumper($var, $exit = TRUE)
	{
		echo '<pre>'.print_r($var, TRUE).'</pre>';

		if ($exit) exit;
	}
}




// --------------------------------------------------------------------

/**
 * Associate results
 *
 * Given a DB result set, this will return an (associative) array
 * based on the keys given
 *
 * @param      array
 * @param      string    key of array to use as key
 * @param      bool      sort by key or not
 * @return     array
 */
if ( ! function_exists('associate_results'))
{
	function associate_results($resultset, $key, $sort = FALSE)
	{
		$array = array();

		foreach ($resultset AS $row)
		{
			if (array_key_exists($key, $row) && ! array_key_exists($row[$key], $array))
			{
				$array[$row[$key]] = $row;
			}
		}

		if ($sort === TRUE)
		{
			ksort($array);
		}

		return $array;
	}
}


/**
 * Associate results multi dimensional
 *
 * Given a DB result set, this will return an (associative) array
 * based on the keys given
 *
 * @param      array
 * @param      string    key of array to use as key
 * @param      bool      sort by key or not
 * @return     array
 */
if ( ! function_exists('associate_results_multi'))
{
	function associate_results_multi($resultset, $key, $sort = FALSE)
	{
		$array = array();

		foreach ($resultset AS $row)
		{
			$array[$row[$key]][] = $row;
		}

		if ($sort === TRUE)
		{
			ksort($array);
		}

		return $array;
	}
}



if ( ! function_exists('charge_check_yes'))
{
	function charge_check_yes($settings, $key, $output = '', $fail_output = '')
	{

		if(!isset($settings[$key]) OR $settings[$key] != 'yes') {
			if($fail_output == '') return FALSE;
			return $fail_output;
		}

		if($output == '') return TRUE;
		return $output;
	}
}



if ( ! function_exists('charge_check_value'))
{
	function charge_check_value($settings, $key, $val, $output = '', $fail_output = '')
	{
		if(!isset($settings[$key]) OR $settings[$key] != $val) {
			if($fail_output == '') return FALSE;
			return $fail_output;
		}

		if($output == '') return TRUE;
		return $output;
	}
}


if ( ! function_exists('charge_check_value_not'))
{
	function charge_check_value_not($settings, $key, $val, $output = '', $fail_output = '')
	{
		if(!isset($settings[$key]) OR $settings[$key] != $val) {
			if($output == '') return FALSE;
			return $output;
		}

		if($fail_output == '') return TRUE;
		return $fail_output;
	}
}


if ( ! function_exists('charge_get_value'))
{
	function charge_get_value($settings, $key, $pre = '', $post = '')
	{
		if(!isset($settings[$key])) {
			return '';
		}

		return $pre.$settings[$key].$post;
	}
}
// --------------------------------------------------------------

/* End of file Charge_helper.php */