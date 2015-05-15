<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Subscriptions Helper File
 *
 * @package         DevDemon_Subscriptions
 * @author          DevDemon <http://www.devdemon.com>
 * @license         http://www.devdemon.com/license/
 * @link            http://www.devdemon.com
 */
class Campaigns_helper
{

    private $package_name = 'campaigns';


    public function __construct()
    {
        // Creat EE Instance
        $this->EE =& get_instance();
        $this->loadApi();
    }

    // ********************************************************************************* //

    public function loadApi()
    {
        // Create our Credits Object
        if (isset($this->EE->campaigns) === false) {
            require_once  dirname(dirname(dirname(__FILE__))).'/campaigns/api.campaigns.php';
            //require_once PATH_THIRD.'campaigns/api.campaigns.php';
            $this->EE->campaigns = new Campaigns_API();
        }
    }

    // ********************************************************************************* //

    public function formatDate($format='', $date=null, $localize=true)
    {
        if (method_exists($this->EE->localize, 'format_date') === true) {
            return $this->EE->localize->format_date($format, $date, $localize);
        } else {
            return $this->EE->localize->decode_date($format, $date, $localize);
        }
    }

    // ********************************************************************************* //

    public function stringToTimestamp($date=null, $localize=true)
    {
        if (method_exists($this->EE->localize, 'string_to_timestamp') === true) {
            return $this->EE->localize->string_to_timestamp($date, $localize);
        } else {
            return $this->EE->localize->convert_human_date_to_gmt($date, $localize);
        }
    }

    // ********************************************************************************* //

    public function generateJson($obj)
    {
        if (function_exists('json_encode') === false) {

            if (class_exists('Services_JSON_CUSTOM') === false) {
                include_once dirname(__FILE__).'/JSON.php';
            }

            $JSON = new Services_JSON_CUSTOM();
            return $JSON->encode($obj);

        } else {
            return json_encode($obj);
        }
    }

    // ********************************************************************************* //

    public function decodeJson($obj, $return_array=false)
    {
        if (function_exists('json_decode') === false) {

            if (class_exists('Services_JSON_CUSTOM') === false) {
                include_once dirname(__FILE__).'/JSON.php';
            }

            $JSON = new Services_JSON_CUSTOM();
            $obj = $return_array ? (array) $JSON->decode($obj) : $JSON->decode($obj);
            return $obj;
        }
        else {
            return json_decode($obj, $return_array);
        }
    }

    // ********************************************************************************* //

    public function getThemeUrl($root=false)
    {
        if (defined('URL_THIRD_THEMES') === true) {
            $theme_url = URL_THIRD_THEMES;
        } else {
            $theme_url = $this->EE->config->item('theme_folder_url').'third_party/';
        }

        $theme_url = str_replace(array('http://','https://'), '//', $theme_url);

        if ($root) return $theme_url;

        $theme_url .= $this->package_name . '/';

        return $theme_url;
    }

    // ********************************************************************************* //

    public function getRouterUrl($type='url', $method='actionGeneralRouter')
    {
        // -----------------------------------------
        // Grab action_id
        // -----------------------------------------
        if (isset($this->EE->session->cache[$this->package_name]['router_url'][$method]['action_id']) === false) {
            $this->EE->db->select('action_id');
            $this->EE->db->where('class', ucfirst($this->package_name));
            $this->EE->db->where('method', $method);
            $query = $this->EE->db->get('exp_actions');

            if ($query->num_rows() == 0) {
                return false;
            }

            $action_id = $query->row('action_id');
        } else {
            $action_id = $this->EE->session->cache[$this->package_name]['router_url'][$method]['action_id'];
        }

        // -----------------------------------------
        // Return FULL action URL
        // -----------------------------------------
        if ($type == 'url') {
            // Grab Site URL
            $url = $this->EE->functions->fetch_site_index(0, 0);

            if (defined('MASKED_CP') == false OR MASKED_CP == false) {
                // Replace site url domain with current working domain
                $server_host = (isset($_SERVER['HTTP_HOST']) == true && $_SERVER['HTTP_HOST'] != false) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
                $url = preg_replace('#http\://(([\w][\w\-\.]*)\.)?([\w][\w\-]+)(\.([\w][\w\.]*))?\/#', "http://{$server_host}/", $url);
            }

             // Create new URL
            $ajax_url = $url.QUERY_MARKER.'ACT=' . $action_id;

            // Config Overrife for action URLs?
            $config = $this->EE->config->item('subs');
            $over = isset($config['action_url']) ? $config['action_url'] : array();

            if (is_array($over) === true && isset($over[$method]) === true) {
                $url = $over[$method];
            }

            // Protocol Relative URL
            $ajax_url = str_replace(array('https://', 'http://'), '//', $ajax_url);

            return $ajax_url;
        }

        return $action_id;
    }

    // ********************************************************************************* //

    /**
     * Array Extend
     * "Extend" recursively array $a with array $b values (no deletion in $a, just added and updated values)
     * @param array $a
     * @param array $b
     */
    public function arrayExtend($a, $b) {
        foreach($b as $k=>$v) {
            if( is_array($v) ) {
                if( !isset($a[$k]) ) {
                    $a[$k] = $v;
                } else {
                    $a[$k] = self::arrayExtend($a[$k], $v);
                }
            } else {
                $a[$k] = $v;
            }
        }
        return $a;
    }

    // ********************************************************************************* //

    public function fetchUrl($url, $user=false, $pass=false)
    {
        /** --------------------------------------------
        /**  cURL
        /** --------------------------------------------*/
        if (function_exists('curl_init') === true && ($ch = @curl_init()) !== false) {
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.5) Gecko/2008120122 Firefox/3.0.5 (.NET CLR 3.5.30729)');

            // Channel Images connect Time out
            $ci_config = $this->EE->config->item('campaigns');

            if (isset($ci_config['curl_connect_timeout']) === true) {
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $ci_config['curl_connect_timeout']);
            }

            if (isset($ci_config['curl_timeout']) === true) {
                curl_setopt($ch, CURLOPT_TIMEOUT, $ci_config['curl_timeout']);
            }

            if ($user != false) {
                curl_setopt($ch, CURLOPT_USERPWD, "$user:$pass");
                if (defined('CURLOPT_HTTPAUTH')) curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            }

            $data = curl_exec($ch);
            curl_close($ch);

            if ($data !== false) {
                return $data;
            }
        }

        /** --------------------------------------------
        /**  file_get_contents()
        /** --------------------------------------------*/

        if ((bool) @ini_get('allow_url_fopen') !== false && $user == false) {
            if ($data = @file_get_contents($url)) {
                return $data;
            }
        }

        /** --------------------------------------------
        /**  fsockopen() - Last but only slightly least...
        /** --------------------------------------------*/

        $parts  = parse_url($url);
        $host   = $parts['host'];
        $path   = (!isset($parts['path'])) ? '/' : $parts['path'];
        $port   = ($parts['scheme'] == "https") ? '443' : '80';
        $ssl    = ($parts['scheme'] == "https") ? 'ssl://' : '';

        if (isset($parts['query']) && $parts['query'] != '') {
            $path .= '?'.$parts['query'];
        }

        $fp = @fsockopen($ssl.$host, $port, $error_num, $error_str, 7);

        if (is_resource($fp)) {
            fputs ($fp, "GET ".$path." HTTP/1.0\r\n" );
            fputs ($fp, "Host: ".$host . "\r\n" );
            fputs ($fp, "User-Agent: Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.2.1)\r\n");

            if ($user != false) {
                fputs ($fp, "Authorization: Basic ".base64_encode("$user:$pass")."\r\n");
            }

            fputs ($fp, "Connection: close\r\n\r\n");

            $header = '';
            $body   = '';

            /* ------------------------------
            /*  This error suppression has to do with a PHP bug involving
            /*  SSL connections: http://bugs.php.net/bug.php?id=23220
            /* ------------------------------*/

            $old_level = error_reporting(0);

            /*
            while ( ! feof($fp))
            {
                $data .= trim(fgets($fp, 128));
            }
            */

            // put the header in variable $header
            do // loop until the end of the header
            {
                $header .= fgets ( $fp, 128 );

            } while ( strpos ( $header, "\r\n\r\n" ) === false );

            // now put the body in variable $body
            while ( ! feof ( $fp ) )
            {
                $body .= fgets ( $fp, 128 );
            }

            error_reporting($old_level);

            $data = $body;

            fclose($fp);
        }

        return $data;
    }

    // ********************************************************************************* //

    public function addMcpAssets($type='', $path='', $package='', $name='')
    {
        $theme_url = $this->getThemeUrl();
        $url = $this->getThemeUrl() . $path;

        // CSS
        if ($type == 'css') {
            if (isset($this->EE->session->cache['css'][$package][$name]) === false) {
                $this->EE->cp->add_to_head('<link rel="stylesheet" href="' . $url . '" type="text/css" media="print, projection, screen" />');
                $this->EE->session->cache['css'][$package][$name] = true;
            }
        }

        // JS
        if ($type == 'js') {
            if (isset($this->EE->session->cache['javascript'][$package][$name]) === false) {
                $this->EE->cp->add_to_foot('<script src="' . $url . '" type="text/javascript"></script>');
                $this->EE->session->cache['javascript'][$package][$name] = true;
            }
        }

        // Custom
        if ($type == 'custom') {
            $path = str_replace('{theme_url}', $theme_url, $path);
            $this->EE->cp->add_to_foot($path);
        }

        // Global Inline Javascript
        if ($type == 'gjs') {
            if ( isset($this->EE->session->cache['inline_js'][$this->package_name]) == false ) {

                $ACT_url = $this->getRouterUrl('url');
                $MCP_AJAX_url = BASE.'&C=addons_modules&M=show_module_cp&module='.$this->package_name.'&method=ajaxRouter';
                $MCP_BASE_url = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=campaigns';

                if (isset($this->EE->updater->settings['action_url']['actionGeneralRouter']) === true && $this->EE->updater->settings['action_url']['actionGeneralRouter'] != false) {
                    $ACT_url = $this->EE->updater->settings['action_url']['actionGeneralRouter'];
                }

                // Remove those AMP!!!
                $ACT_url = str_replace('&amp;', '&', $ACT_url);
                $MCP_AJAX_url = str_replace('&amp;', '&', $MCP_AJAX_url);
                $theme_url = str_replace('&amp;', '&', $theme_url);

                $js = " var Campaigns = Campaigns ? Campaigns : {};
                        Campaigns.ACT_URL = '{$ACT_url}';
                        Campaigns.MCP_AJAX_URL = '{$MCP_AJAX_url}';
                        Campaigns.MCP_BASE_URL = '{$MCP_BASE_url}';
                        Campaigns.THEME_URL = '{$theme_url}';
                ";

                $this->EE->cp->add_to_foot('<script type="text/javascript">' . $js . '</script>');
                $this->EE->session->cache['inline_js'][$this->package_name] = true;
            }
        }
    }

    // ********************************************************************************* //

} // END CLASS

/* End of file campaigns_helper.php  */
/* Location: ./system/expressionengine/third_party/campaigns/libraries/campaigns_helper.php */
