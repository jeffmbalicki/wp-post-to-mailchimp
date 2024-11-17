<?php
namespace MailchimpAPI;

require_once (dirname(__FILE__) . '/../vendor/autoload.php');

use \MailchimpMarketing\ApiClient;
use GuzzleHttp;

/**
 * Class MailchimpAPI
 *
 * This class is responsible for setting up the MailchimpMarketing API client.
 * It provides a constructor that takes an API key and a server, and uses them to configure the client.
 *
 * @package Tz\WordPress\CBV\MailChimp
 */


class MailchimpAPI
{
    /**
     * The MailchimpMarketing API client.
     *
     * @var MailchimpMarketing\ApiClient
     */

    protected $client;
    /**
     * MailchimpMemberLists constructor.
     *
     * @param string $apiKey The API key for the Mailchimp account.
     * @param string $server The server for the Mailchimp account.
     */

    public function __construct()
    {
        $this->client = new ApiClient();
        $this->client->setConfig([
            'apiKey' => get_option('mailchimp_api_key'),
            'server' => get_option('mailchimp_server'),
        ]);
    }



    public function verify_mailchimp_api_settings()
    {
        try {
            $response = $this->client->ping->get();
            $return['status'] = true;
            return $return; 
        } catch (GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            $errorResponse = json_decode($responseBodyAsString);
            $return['status'] = false;
            $return['message'] = $errorResponse->detail;
            return $return;
        }
    }

    public function mailchimp_api_get_templates($option_name)
    {
        try {
            $response = $this->client->templates->list();
            if (isset($response->templates)) {

                echo '<select name="' . $option_name . '">';
                foreach ($response->templates as $template) {
                    $template_id = $template->id;
                    $template_name = $template->name;
                    $selected = '';
                    if ($template_id == get_option($option_name)) {
                        $selected = ' selected="selected"';
                    }
                    echo '<option value="' . esc_attr($template_id) . '"' . $selected . '>' . esc_html($template_name) . '</option>';
                }
                echo '</select>';
            } else {
                echo 'Could not retrieve MailChimp templates. Please check your API Key and try again.';
            }
        } catch (GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            error_log("mailchimp_api_get_templates()" . $responseBodyAsString);
        }
    }



    public function mailchimp_api_get_lists()
    {
        try {
            $response = $this->client->lists->getAllLists();

            if (isset($response->lists)) {
                echo '<select name="mailchimp_list_id">';
                foreach ($response->lists as $list) {
                    $list_id = $list->id;
                    $list_name = $list->name;
                    $selected = '';
                    if ($list_id == get_option('mailchimp_list_id')) {
                        $selected = ' selected="selected"';
                    }
                    echo '<option value="' . esc_attr($list_id) . '"' . $selected . '>' . esc_html($list_name) . '</option>';
                }
                echo '</select>';
            } else {
                echo 'Could not retrieve MailChimp lists. Please check your API Key and try again.';
            }
        } catch (GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            error_log("mailchimp_api_get_lists()" . $responseBodyAsString);
        }
    }

}