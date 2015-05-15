<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Subscriptions Helper File
 *
 * @package         DevDemon_Subscriptions
 * @author          DevDemon <http://www.devdemon.com>
 * @license         http://www.devdemon.com/license/
 * @link            http://www.devdemon.com
 */

defined('__DIR__') or define('__DIR__', dirname(__FILE__));

class Campaigns_curl
{
    public $config = array();
    public $response = array();

    public function __construct($config=array()) {
        $this->reconfigure($config);
    }

    // ********************************************************************************* //

    public function reconfigure($config=array()) {
        // default configuration options
        $this->config = array_merge(
            array(
            // leave 'user_agent' blank for default, otherwise set this to
            // something that clearly identifies your app
            'user_agent'                 => '',
            'host'                       => 'api.twitter.com',
            'base_uri'                   => false, // overrides host

            'global_params'              => array(),
            'global_url_params'          => array(),
            'global_request_headers'     => array(),
            'json_decode_response'       => true,
            'urlencoded_post_params'     => false,

            // you probably don't want to change any of these curl values
            'curl_http_version'          => CURL_HTTP_VERSION_1_1,
            'curl_connecttimeout'        => 30,
            'curl_timeout'               => 10,

            // for security this should always be set to 2.
            'curl_ssl_verifyhost'        => 2,
            // for security this should always be set to true.
            'curl_ssl_verifypeer'        => true,
            // for security this should always be set to true.
            'use_ssl'                    => true,

            // you can get the latest cacert.pem from here http://curl.haxx.se/ca/cacert.pem
            // if you're getting HTTP 0 responses, check cacert.pem exists and is readable
            // without it curl won't be able to create an SSL connection
            'curl_cainfo'                => __DIR__ . DIRECTORY_SEPARATOR . 'oauth/cacert.pem',
            'curl_capath'                => __DIR__. DIRECTORY_SEPARATOR . 'oauth/',

            'curl_followlocation'        => false, // whether to follow redirects or not

            // support for proxy servers
            'curl_proxy'                 => false, // really you don't want to use this if you are using streaming
            'curl_proxyuserpwd'          => false, // format username:password for proxy, if required
            'curl_encoding'              => '',    // leave blank for all supported formats, else use gzip, deflate, identity etc
            ),
            $config
        );
    }

    // ********************************************************************************* //

    public function fetch($method='GET', $path='', $params=array(), $headers=array(), $options=array())
    {
        $method = strtolower($method);

        $this->response = array(
          'raw' => ''
        );

        // -----------------------------------------
        // URL
        // -----------------------------------------
        $url = 'https://'.$this->config['host'].'/'.$path;

        if ($this->config['base_uri'] != false) {
             $url = $this->config['base_uri'].$path;
        }

        if (strpos($url, '?') === false) $url .= '?';

        // configure curl
        $c = curl_init();

        // -----------------------------------------
        // Global Params
        // -----------------------------------------
        if (! empty($this->config['global_params'])) {
            foreach ($this->config['global_params'] as $key => $val) {
                $params[$key] = $val;
            }
        }

        // -----------------------------------------
        // Method: GET
        // -----------------------------------------
        if ($method == 'get' || $method == 'delete') {
            $pairs = array();

            foreach ($params as $key => $val) {
                $val = $this->safeEncode($val);
                $pairs[] = "{$key}={$val}";
            }

            $url .= implode('&', $pairs);
        }

        // -----------------------------------------
        // Method: POST
        // -----------------------------------------
        if ($method == 'post') {

            // For debugging
            $post_params = $params;

            // Force form-urlencoded?
            if ($this->config['urlencoded_post_params'] === true) {
                $params = http_build_query($params);

                $headers['Content-Type'] = 'application/x-www-form-urlencoded';
            }

            // Multidimensional arrays are tricky
            if (is_array($params) === true) {
                $old_params = $params;
                $params = array();
                $this->http_build_query_for_curl($old_params, $params);

                // Since it's an array, we also do multipart
                $headers['Content-Type'] = 'multipart/form-data';
            }

            curl_setopt($c, CURLOPT_POSTFIELDS, $params);
            curl_setopt($c, CURLOPT_CUSTOMREQUEST, 'POST');
        }

        // Method: DELETE
        if ($method == 'delete') {
            curl_setopt($c, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        // -----------------------------------------
        // Global URL Params
        // -----------------------------------------
        if (!empty($this->config['global_url_params'])) {
            $pairs = array();

            foreach ($this->config['global_url_params'] as $key => $val) {
                $pairs[] = "{$key}={$val}";
            }

            if (substr($url, -1) != '&' && substr($url, -1) != '?') {
                $url .= '&';
            }

            $url .= implode('&', $pairs);
        }

        //var_dump($url);

        // -----------------------------------------
        // Setup CURL
        // -----------------------------------------
        curl_setopt_array($c, array(
            CURLOPT_HTTP_VERSION   => $this->config['curl_http_version'],
            CURLOPT_USERAGENT      => $this->config['user_agent'],
            CURLOPT_CONNECTTIMEOUT => $this->config['curl_connecttimeout'],
            CURLOPT_TIMEOUT        => $this->config['curl_timeout'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => $this->config['curl_ssl_verifypeer'],
            CURLOPT_SSL_VERIFYHOST => $this->config['curl_ssl_verifyhost'],

            CURLOPT_PROXY          => $this->config['curl_proxy'],
            CURLOPT_ENCODING       => $this->config['curl_encoding'],
            CURLOPT_URL            => $url,
            // process the headers
            CURLOPT_HEADERFUNCTION => array($this, 'curlHeader'),
            CURLOPT_HEADER         => false,
            CURLINFO_HEADER_OUT    => true,
        ));

        if (!ini_get('safe_mode') && !ini_get('open_basedir')) {
            curl_setopt($c, CURLOPT_FOLLOWLOCATION, $this->config['curl_followlocation']);
        }

        if ($this->config['curl_cainfo'] !== false) {
            curl_setopt($c, CURLOPT_CAINFO, $this->config['curl_cainfo']);
        }

        if ($this->config['curl_capath'] !== false) {
            curl_setopt($c, CURLOPT_CAPATH, $this->config['curl_capath']);
        }

        if ($this->config['curl_proxyuserpwd'] !== false) {
            curl_setopt($c, CURLOPT_PROXYUSERPWD, $this->config['curl_proxyuserpwd']);
        }

        // -----------------------------------------
        // Request Header
        // -----------------------------------------
        if (! empty($this->config['global_request_headers'])) {
            foreach ($this->config['global_request_headers'] as $key => $val) {
                $headers[$key] = $val;
            }
        }

        if (! empty($headers)) {
            $temp = array();

            foreach ($headers as $k => $v) {
                $temp[] = trim($k . ': ' . $v);
            }

            curl_setopt($c, CURLOPT_HTTPHEADER, $temp);
        }

        // -----------------------------------------
        // Execute!
        // -----------------------------------------
        $response = curl_exec($c);
        $code = curl_getinfo($c, CURLINFO_HTTP_CODE);
        $info = curl_getinfo($c);
        $error = curl_error($c);
        $errno = curl_errno($c);
        curl_close($c);

        // -----------------------------------------
        // Store the response
        // -----------------------------------------
        $this->response['code'] = $code;
        $this->response['response'] = $response;
        $this->response['info'] = $info;
        $this->response['error'] = $error;
        $this->response['errno'] = $errno;

        if (!isset($this->response['raw'])) {
          $this->response['raw'] = '';
        }
        $this->response['raw'] .= $response;
        if (isset($post_params)) $this->response['post_params'] = $post_params;

        if ($this->config['json_decode_response']) {
            $this->response['response'] = function_exists('json_decode') ? json_decode($this->response['response']) : get_instance()->campaigns_helper->decodeJson($this->response['response']);
        }

        return $code;
    }

    // ********************************************************************************* //

    private function safeEncode($data) {
        if (is_array($data)) {
          return array_map(array($this, 'safeEncode'), $data);
        } else if (is_scalar($data)) {
          return str_ireplace(
            array('+', '%7E'),
            array(' ', '~'),
            rawurlencode($data)
          );
        } else {
          return '';
        }
    }

    // ********************************************************************************* //

    /**
    * Utility function to parse the returned curl headers and store them in the
    * class array variable.
    *
    * @param object $ch curl handle
    * @param string $header the response headers
    * @return string the length of the header
    */
    private function curlHeader($ch, $header) {
        $this->response['raw'] .= $header;

        list($key, $value) = array_pad(explode(':', $header, 2), 2, null);

        $key = trim(strtolower($key));
        $value = trim($value);

        if ( ! isset($this->response['headers'][$key])) {
          $this->response['headers'][$key] = $value;
        } else {
          if (!is_array($this->response['headers'][$key])) {
            $this->response['headers'][$key] = array($this->response['headers'][$key]);
          }
          $this->response['headers'][$key][] = $value;
        }

        return strlen($header);
    }

    // ********************************************************************************* //

    private function http_build_query_for_curl( $arrays, &$new = array(), $prefix = null ) {

        if ( is_object( $arrays ) ) {
            $arrays = get_object_vars( $arrays );
        }

        foreach ( $arrays AS $key => $value ) {
            $k = isset( $prefix ) ? $prefix . '[' . $key . ']' : $key;
            if ( is_array( $value ) OR is_object( $value )  ) {
                $this->http_build_query_for_curl( $value, $new, $k );
            } else {
                $new[$k] = $value;
            }
        }
    }

    // ********************************************************************************* //

} // END CLASS

/* End of file campaigns_helper.php  */
/* Location: ./system/expressionengine/third_party/campaigns/libraries/campaigns_helper.php */
