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
class Service_bitly extends CampaignService implements UrlShortenerInterface
{
    public $title = 'Bitly';
    public $name = 'bitly';
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
        $this->settings['username'] = '';
        $this->settings['api_key'] = '';

        parent::__construct($settings);

        if ($init) $this->init();
    }

    // ********************************************************************************* //

    public function shorten($url)
    {
        $params = array();
        $params['longUrl'] = $url;

        $code = $this->API->fetch('GET', 'shorten', $params);
        $res = $this->API->response['response'];

        if ($res->status_code != '200') {
            $this->errorMsg = $res->status_txt;
            return false;
        }

        return $res->data->url;
    }

    // ********************************************************************************* //

    private function init()
    {
        if (isset($this->API)) return true;

        if (class_exists('Campaigns_curl') === false) {
            require_once PATH_THIRD.'campaigns/libraries/campaigns_curl.php';
        }

        $this->API = new Campaigns_curl();
        $this->API->config['base_uri'] = 'https://api-ssl.bitly.com/v3/';
        $this->API->config['global_url_params']['login'] = $this->settings['username'];
        $this->API->config['global_url_params']['apiKey'] = $this->settings['api_key'];
    }

    // ********************************************************************************* //
}

/* End of file service.bitly.php */
/* Location: ./system/expressionengine/third_party/campaigns/services/service.bitly.php */