<?php
namespace MailchimpAPI;

require_once (dirname(__FILE__) . '/../vendor/autoload.php');
require_once (dirname(__FILE__) . '/MailchimpAPI.php');

use GuzzleHttp;

/**
 * Class MailchimpListSegments
 *
 * This class is responsible for managing segments of Mailchimp lists.
 * It uses the MailchimpMarketing API client to interact with the Mailchimp API.
 * It provides a method to get all segments of a list.
 *
 * @package Tz\WordPress\CBV\MailChimp
 */

class MailchimpListSegments extends \MailchimpAPI\MailchimpAPI
{

    protected $client;

    public function __construct()
    {
        parent::__construct();
    }

    public function getListSegments($listId)
    {

        try {
            $response = $this->client->lists->listSegments($listId, null, null, '100');
            return $response;
        } catch (GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            error_log("getListSegments()" . $responseBodyAsString);
        }
    }

}
