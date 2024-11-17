<?php 
namespace MailchimpAPI;

require_once(dirname(__FILE__) . '/../vendor/autoload.php');
require_once(dirname(__FILE__) . '/MailchimpAPI.php');

use GuzzleHttp;
/**
 * Class MailchimpMemberLists
 *
 * This class is responsible for managing members of Mailchimp lists.
 * It uses the MailchimpMarketing API client to interact with the Mailchimp API.
 * It provides methods to add a member to a list, update a member on a list, and update tags for a member on a list.
 *
 * @package Tz\WordPress\CBV\MailChimp
 */

class MailchimpMemberLists extends \MailchimpAPI\MailchimpAPI
{

    protected $client;

    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * get a member from a Mailchimp list.
     *
     * @param string $listId The ID of the list to add the member to.
     * @param string $current_email The data for the member to add.
     */

     public function getMemberFromList($listId, $current_email)
     {
         
         try {
             $response = $this->client->lists->getListMember($listId, $current_email);
             return $response;
         } catch (GuzzleHttp\Exception\ClientException $e) {
             $response = $e->getResponse();
             $responseBodyAsString = $response->getBody()->getContents();
             error_log("getMemberFromList()" . $responseBodyAsString);
             $return = json_decode($responseBodyAsString, true);
             return $response = ['success' => 'false', 'detail' => $return['detail'].'<br/>'.$return['errors']['message']];
         }
     }

    /**
     * Adds a member to a Mailchimp list.
     *
     * @param string $listId The ID of the list to add the member to.
     * @param array $userData The data for the member to add.
     */

    public function addMemberToList($listId, $userData)
    {
        
        try {
            $response = $this->client->lists->addListMember($listId, $userData);
            return $response;
        } catch (GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            error_log("addMemberToList()" . $responseBodyAsString);
            $return = json_decode($responseBodyAsString, true);
            return $response = ['success' => 'false', 'detail' => $return['detail'].'<br/>'.$return['errors'][0]['message']];
        }
    } 

     /**
     * Updates a member on a Mailchimp list.
     *
     * @param string $listId The ID of the list the member is on.
     * @param array $userData The updated data for the member.
     */

    public function updateMemberToList($listId, $current_email, $userData)
    {
        try {
            $response = $this->client->lists->setListMember($listId,  $current_email, $userData);
            return $response;
        } catch (GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            error_log("updateMemberToList()" . $responseBodyAsString);
            $return = json_decode($responseBodyAsString, true);
            return $response = ['success' => 'false', 'detail' => $return['detail'].'<br/>'.$return['errors'][0]['message']];
        }
    }

     /**
     * Updates the tags for a member on a Mailchimp list.
     *
     * @param string $listId The ID of the list the member is on.
     * @param array $userData The updated data for the member, including the new tags.
     */

    public function updateTagsForMemberToList($listId, $userData)
    {
        try {
            $response = $this->client->lists->updateListMemberTags($listId, $userData[ "email_address"], $userData);
            return $response;
        } catch (GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            error_log("updateTagsForMemberToList()" . $responseBodyAsString);
            $return = json_decode($responseBodyAsString, true);
            return $response = ['success' => 'false', 'detail' => $return['detail'].'<br/>'.$return['errors'][0]['message']];
        }
    }

}
