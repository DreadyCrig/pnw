<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
//error_reporting(E_ALL);
//ini_set('display_errors', '1');
/*
====================================================================================================
 Author: Aaron Waldon (Causing Effect)
 http://www.causingeffect.com
====================================================================================================
 This file must be placed in the /system/expressionengine/third_party/ce_gravatar folder in your ExpressionEngine installation.
 package		CE Gravatar
 version		Version 1.0
 copyright		Copyright (c) 2010,2011 Causing Effect, Aaron Waldon <aaron@causingeffect.com>
 Last Update	24 July 2012
----------------------------------------------------------------------------------------------------
 Purpose: Facilitate the use of Gravatar images in ExpressionEngine templates.
====================================================================================================

License: CE Gravatar by Aaron Waldon (Causing Effect) is licensed under a Creative Commons Attribution-NoDerivs 3.0 Unported License (http://creativecommons.org/licenses/by-nd/3.0/). Permissions beyond the scope of this license may be available by contacting software@causingeffect.com.
*/

$plugin_info = array(
	'pi_name' => 'CE Gravatar',
	'pi_version' => '1.0',
	'pi_author' => 'Aaron Waldon (Causing Effect)',
	'pi_author_url' => 'http://www.causingeffect.com/software/ee/ce_gravatar',
	'pi_description' => 'Facilitates the use of Gravatar images in ExpressionEngine templates.',
	'pi_usage' => Ce_gravatar::usage()
);

class Ce_gravatar 
{
	private $ratings = array( 'g', 'pg', 'r', 'x' );
	private $defaults = array( '404', 'mm', 'identicon', 'monsterid', 'wavatar', 'retro' );
	private $check_404 = FALSE;

	function __construct()
	{
		//EE super global
		$this->EE =& get_instance();
	}
	
	function Ce_gravatar()
	{
		$this->__construct();
	}

	/**
	 * This method does all of the heavy lifting.
	 *
	 * @return bool|string
	 */
	private function gravatar()
	{
		//---------- get the URL essentials ----------
		//email
		$email = trim($this->EE->TMPL->fetch_param('email'));
		//Use secure protocol?
		$use_https = $this->EE->TMPL->fetch_param('use_https') == 'yes';
		//Add a .jpg extension?
		$add_extension = $this->EE->TMPL->fetch_param('add_extension') == 'yes';
		
		//---------- get the params ----------
		$params = array();
		//default
		$default = trim( $this->EE->TMPL->fetch_param('default') );
		if ( $default != '' )
		{
			//A default was passed in. Add it or encode it as necessary.
			$params[] = ( in_array( strtolower( $default ), $this->defaults ) ) ? 'd=' . strtolower( $default ) : 'd=' . urlencode( $this->parse_ee_paths( $default ) );
			if ( strtolower( $default ) == '404' )
			{
				$this->check_404 = TRUE;
			}
		}
		//force default
		$force_default = $this->EE->TMPL->fetch_param('force_default') == 'yes';
		if ( $force_default )
		{
			$params[] = 'f=y';
		}
		//rating
		$rating = strtolower( trim( $this->EE->TMPL->fetch_param('rating') ) );
		if ( $rating != '' && in_array( $rating, $this->ratings ) )
		{
			$params[] = 'r=' . $rating;
		}
		//size
		$size = $this->EE->TMPL->fetch_param('size');
		if ( $size != '' && is_numeric( $size ) )
		{
			$params[] = 's=' . $size;
		}
		
		//---------- Create the URL ----------
		$url = ( ! $use_https ) ? 'http://www' : 'https://secure';
		$url .= '.gravatar.com/avatar/';

		//check to see if an un-parsed email tag was passed in
		//code donated by pvledoux - http://devot-ee.com/add-ons/support/ce-gravatar/viewthread/1988/
		if ( $email == '{email}' )
		{
			$email = $this->EE->session->userdata[ 'email' ];
		}
		
		//prep the email and add - http://en.gravatar.com/site/implement/hash/
		if ( $email != '' )
		{
			$url .= md5( strtolower( $email ) );
			
			if ( $add_extension )
			{
				$url .= '.jpg';	
			}
		}
		
		//add the parameters
		if ( count( $params ) > 0 )
		{
			$url .= '?' . implode( '&amp;', $params );
		}
		
		if ( $this->check_404 )
		{
			$headers = FALSE;

			//see if we can set the default stream context
			if ( ini_get( 'allow_url_fopen' ) && function_exists( 'stream_context_set_default' ) )
			{
				//set stream context
				stream_context_set_default(
					array (
						'http' => array(
							'method' => 'HEAD'
						)
					)
				);

				//get the headers
				$headers = @get_headers( $url );
			}

			if ( $headers != FALSE ) //get_headers was successful
			{
				//check the response
				if ( strpos( $headers[0], '200' ) === FALSE )
				{
					$url = FALSE;
				}
			}
			else if ( function_exists( 'curl_init' ) ) //get_headers failed, fall back to cURL
			{
				$curl = curl_init();
				curl_setopt_array( $curl,
					array(
						CURLOPT_RETURNTRANSFER => TRUE, //no output
						CURLOPT_URL => $url  //set the URL
					)
				);
				$response = curl_exec( $curl );
				if ( $response != FALSE ) //cURL succeeded
				{
					$response_code = curl_getinfo( $curl, CURLINFO_HTTP_CODE );

					if ( $response_code != '200' )
					{
						$url = FALSE;
					}
				}
				curl_close( $curl );
			}
			else //no cURL or get_headers
			{
				$url = FALSE;
			}
		}
		
		$tagdata = $this->EE->TMPL->tagdata;
		if ( trim( $tagdata ) == '' ) //there is no tagdata
		{
			return $url;
		}
		else //there is tagdata
		{
			//conditionals
			$conditionals = array();
			$conditionals['gravatar'] = ! empty( $url );
			
			//variables
			$tagdata = $this->EE->functions->prep_conditionals( $tagdata, $conditionals );
			
			$parsed = $this->EE->TMPL->parse_variables_row( $tagdata, array( 'gravatar' => $url ) );
			return $parsed;
		}
	}

	/**
	 * Parses the EE paths.
	 *
	 * @param string $string
	 * @return string
	 */
	private function parse_ee_paths( $string )
	{
		//trim the string
		$string = trim( $string );

		//if no string, bail
		if ( $string == '' )
		{
			return $string;
		}

		//replace the site_url variable if applicable
		if ( strpos( $string, '{site_url}' ) !== FALSE )
		{
			$string = str_replace( '{site_url}', stripslashes( $this->EE->config->item( 'site_url' ) ), $string );
		}

		//replace the path= variables if applicable
		if ( strpos( $string, 'path=' ) !== FALSE )
		{
			$string = preg_replace_callback( '@{\s*path=(.*?)}@', array( &$this->EE->functions, 'create_url' ), $string );
		}

		//replace the file directory paths if applicable
		if ( strpos( $string, 'filedir_' ) !== FALSE)
		{
			$filedirs = $this->EE->functions->fetch_file_paths();
			foreach ( $filedirs as $id => $path )
			{
				$string = str_replace( array( '{filedir_'.$id.'}', '&#123;filedir_'.$id.'&#125;' ), $path, $string );
			}
		}

		return $string;
	}

	/**
	 * An alias for the gravatar method for single tag syntax. Useful to help the EE parser out.
	 *
	 * @return bool|string
	 */
	public function single()
	{
		return $this->gravatar();
	}

	/**
	 * An alias for the gravatar method for pair tag syntax. Useful to help the EE parser out.
	 *
	 * @return bool|string
	 */
	public function pair()
	{
		return $this->gravatar();
	}

	// This function describes how the plugin is used.
	public function usage() 
	{
		ob_start();
?>
Full documentation and examples can be found at: http://www.causingeffect.com/software/ee/ce_gravatar

Single Tag:
<img src="{exp:ce_gravatar:single email="aaron@causingeffect.com"}" alt="" />

Pair Tag:
{exp:ce_gravatar:pair email="aaron@causingeffect.com" default="404"}
        {if gravatar}
                {! -- a Gravatar image exists for the provided email address --}
                <img src="{gravatar}" alt="" />
        {if:else}
                {! -- no Gravatar, so let's create our own default image --}
                {exp:ce_img:single src="/images/example/cow_square.jpg" max="80"}
        {/if}
{/exp:ce_gravatar:pair}

<?php
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	} /* End of usage() function */
	
} /* End of class */
/* End of file pi.ce_gravatar.php */ 
/* Location: ./system/expressionengine/third_party/ce_gravatar/pi.ce_gravatar.php */ 