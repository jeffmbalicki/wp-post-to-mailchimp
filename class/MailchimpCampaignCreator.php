<?php
namespace MailchimpAPI;

require_once (dirname(__FILE__) . '/../vendor/autoload.php');
require_once (dirname(__FILE__) . '/MailchimpAPI.php');

use GuzzleHttp;

/**
 * Class MailchimpCampaignCreator
 *
 * This class is responsible for creating and managing campaigns in Mailchimp.
 * It uses the MailchimpMarketing API client to interact with the Mailchimp API.
 * It provides methods to create a campaign, set content for a campaign, and send a campaign.
 *
 * @package Tz\WordPress\CBV\MailChimp
 */

class MailchimpCampaignCreator extends \MailchimpAPI\MailchimpAPI
{

    protected $client;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Creates a campaign in Mailchimp.
     *
     * @param array $campaignData The data for the campaign to create.
     * @param array $contentData The content for the campaign to create.
     */


    public function createCampaign($campaignData, $contentData)
    {
        try {
            $response = $this->client->campaigns->create($campaignData);
            $campaignId = $response->id;
            $this->setContent($campaignId, $contentData);
        } catch (GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            error_log("createCampaign()" . $responseBodyAsString);
        }
    }

    /**
     * Sets the content for a campaign in Mailchimp.
     *
     * @param string $campaignId The ID of the campaign to set the content for.
     * @param array $contentData The content for the campaign.
     */

    private function setContent($campaignId, $contentData)
    {
        try {
            $setContent = $this->client->campaigns->setContent($campaignId, $contentData);
            $this->sendCampaign($campaignId);
        } catch (GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            error_log("setContent()" . $responseBodyAsString);
        }
    }

    /**
     * Sends a campaign in Mailchimp.
     *
     * @param string $campaignId The ID of the campaign to send.
     */

    private function sendCampaign($campaignId)
    {
        try {
            $sendCampaign = $this->client->campaigns->send($campaignId);
            return $sendCampaign;
        } catch (GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            error_log("sendCampaign()" . $responseBodyAsString);
        }
    }
}

