<?php
namespace MailchimpAPI;

require_once (dirname(__FILE__) . '/../vendor/autoload.php');
require_once (dirname(__FILE__) . '/MailchimpAPI.php');

use GuzzleHttp;

/**
 * Class MailchimpTagList
 *
 * This class is responsible for managing tags of Mailchimp lists.
 * It uses the MailchimpMarketing API client to interact with the Mailchimp API.
 * It provides a method to get all tags of a list.
 *
 * @package Tz\WordPress\CBV\MailChimp
 */

class MailchimpTagsList extends \MailchimpAPI\MailchimpAPI
{

    protected $client;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Gets all tags of a Mailchimp list.
     *
     * @param string $listId The ID of the list to get the tags of.
     */

    public function getTagsList($listId)
    {

        try {
            $response = $this->client->lists->tagSearch($listId);
            return $response;
        } catch (GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            error_log("getTagsList()" . $responseBodyAsString);
        }
    }
}