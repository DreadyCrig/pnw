<?php if (!defined('BASEPATH')) die('No direct script access allowed');

/**
 * Campaigns BITLY services
 *
 * @package         DevDemon_Campaigns
 * @author          DevDemon <http://www.devdemon.com> - Lead Developer @ Parscale Media
 * @copyright       Copyright (c) 2007-2014 Parscale Media <http://www.parscale.com>
 * @license         http://www.devdemon.com/license/
 * @link            http://www.devdemon.com/campaigns/
 */
class Service_lessnmore extends CampaignService implements UrlShortenerInterface
{
    public $title = 'Lessn More';
    public $name = 'lessnmore';
    public $type = 'url_shortener';

    // ********************************************************************************* //

    /**
     * Constructor
     *
     * @access public
     *
     * Calls the parent constructor
     */
    public function __construct($settings=array(), $init=true)
    {
        $this->settings['install_url'] = '';
        $this->settings['signature'] = '';

        parent::__construct($settings);

        if ($init) $this->init();
    }

    // ********************************************************************************* //

    public function shorten($url)
    {
        $params = array();
        $params['url'] = $url;

        $code = $this->API->fetch('GET', 'index.php', $params);
        $res = $this->API->response['response'];

        if (strlen($res) > 200) {
            $this->errorMsg = 'Error creating short url, check install_url';
            return false;
        }

        //ee()->firephp->log($this->API->response);

        return $res;
    }

    // ********************************************************************************* //

    private function init()
    {
        if (isset($this->API)) return true;

        if (class_exists('Campaigns_curl') === false) {
            require_once PATH_THIRD.'campaigns/libraries/campaigns_curl.php';
        }

        $this->API = new Campaigns_curl();
        $this->API->config['base_uri'] = rtrim($this->settings['install_url'], '/') . '/-/';
        $this->API->config['global_url_params']['api'] = $this->settings['api_key'];
        $this->API->config['json_decode_response'] = false;
    }

    // ********************************************************************************* //
}

/* End of file service.bitly.php */
/* Location: ./system/expressionengine/third_party/campaigns/services/service.bitly.php */