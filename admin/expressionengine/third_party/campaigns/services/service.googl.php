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
class Service_googl extends CampaignService implements UrlShortenerInterface
{
    public $title = 'goo.gl';
    public $name = 'googl';
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
        $this->settings['api_key'] = '';

        parent::__construct($settings);

        if ($init) $this->init();
    }

    // ********************************************************************************* //

    public function shorten($url)
    {
        $params = array();
        $params['longUrl'] = $url;
        $params = ee()->campaigns_helper->generateJson($params);

        $code = $this->API->fetch('POST', 'url', $params, array('Content-Type' => 'application/json'));
        $res = $this->API->response['response'];

        if (isset($res->error->code) && $res->error->code > 0) {
            $this->errorMsg = $res->error->message;
            return false;
        }

        return $res->id;
    }

    // ********************************************************************************* //

    private function init()
    {
        if (isset($this->API)) return true;

        if (class_exists('Campaigns_curl') === false) {
            require_once PATH_THIRD.'campaigns/libraries/campaigns_curl.php';
        }

        $this->API = new Campaigns_curl();
        $this->API->config['base_uri'] = 'https://www.googleapis.com/urlshortener/v1/';
        $this->API->config['global_url_params']['api_key'] = $this->settings['api_key'];
    }

    // ********************************************************************************* //
}

/* End of file service.bitly.php */
/* Location: ./system/expressionengine/third_party/campaigns/services/service.bitly.php */