<?php

use MailchimpAPI;

class MailchimpWebhook extends \MailchimpAPI\MailchimpAPI
{

   
    /**
     * Constructor.
     *
     * @since 4.7.0
     * @access public
     */
    public function __construct()
    {
        $this->namespace = get_option('mailchimp_webhook_namespace');
        $this->rest_base = get_option('mailchimp_rest_base');
    }

    /**
     * Registers the routes for the objects of the controller.
     *
     * @since 4.7.0
     * @access public
     *
     * @see register_rest_route()
     */
    public function register_routes()
    {
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            array(
                'methods' => array('POST','GET'),
                'callback' => array($this, 'my_webhook_mailchimp_endpoint'),
                'permission_callback' => "__return_true",
            )
        ));
    }

    /**
     * Checks if a given request has access to update a user profile.
     *
     * @since 4.7.0
     * @access public
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return true|WP_Error True if the request has access to update the item, WP_Error object otherwise.
     */
    public function my_webhook_mailchimp_endpoint($request)
    {
        do_action('handle_mailchimp_webhook', $request);


        return new WP_REST_Response( array( 'message' => 'Works!' ), 200 );
    }

}
