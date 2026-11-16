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
        } catch (\MailchimpMarketing\ApiException $e) {
            return $this->campaignError('creation', $e);
        } catch (GuzzleHttp\Exception\GuzzleException $e) {
            return $this->campaignError('creation', $e);
        }

        return $this->setContent($campaignId, $contentData);
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
            $this->client->campaigns->setContent($campaignId, $contentData);
        } catch (\MailchimpMarketing\ApiException $e) {
            return $this->campaignError('content update', $e);
        } catch (GuzzleHttp\Exception\GuzzleException $e) {
            return $this->campaignError('content update', $e);
        }

        return $this->sendCampaign($campaignId);
    }

    /**
     * Sends a campaign in Mailchimp.
     *
     * @param string $campaignId The ID of the campaign to send.
     */

    private function sendCampaign($campaignId)
    {
        try {
            $this->client->campaigns->send($campaignId);
            return true;
        } catch (\MailchimpMarketing\ApiException $e) {
            return $this->campaignError('send', $e);
        } catch (GuzzleHttp\Exception\GuzzleException $e) {
            return $this->campaignError('send', $e);
        }
    }

    private function campaignError($operation, \Exception $exception)
    {
        $responseBody = null;
        if ($exception instanceof \MailchimpMarketing\ApiException) {
            $responseBody = $exception->getResponseBody();
        } elseif ($exception instanceof GuzzleHttp\Exception\RequestException && $exception->getResponse()) {
            $responseBody = (string) $exception->getResponse()->getBody();
        }

        if (is_object($responseBody) && method_exists($responseBody, '__toString')) {
            $responseBody = (string) $responseBody;
        }
        if (is_string($responseBody)) {
            $responseBody = json_decode($responseBody);
        }
        $message = $responseBody->detail ?? $responseBody->title
            ?? 'Please check your Mailchimp settings and network connection, then try again.';
        $fieldErrors = [];
        if (isset($responseBody->errors) && is_array($responseBody->errors)) {
            foreach ($responseBody->errors as $error) {
                $field = is_object($error) ? ($error->field ?? '') : ($error['field'] ?? '');
                $detail = is_object($error) ? ($error->message ?? '') : ($error['message'] ?? '');
                if ($detail !== '') {
                    $fieldErrors[] = ($field !== '' ? $field . ': ' : '') . $detail;
                }
            }
        }
        if ($fieldErrors) {
            $message .= ' Field details: ' . implode('; ', $fieldErrors);
        }

        return new \WP_Error(
            'mailchimp_campaign_error',
            sprintf('Mailchimp campaign %s failed: %s', $operation, $message)
        );
    }
}
