<?php
/**
 * Plugin Name: WP Post to Mailchimp
 * Description: This plugin provides a set of classes to interact with the Mailchimp API and send posts to a Mailchimp list.
 * Version: 1.0.0
 * Author: Tenzing Communications Inc.
 * Author URI: https://gotenzing.com
 */

require_once (dirname(__FILE__) . '/vendor/autoload.php');

use \MailchimpMarketing\ApiClient;

require_once (dirname(__FILE__) . '/class/MailchimpAPI.php');
require_once (dirname(__FILE__) . '/class/MailchimpTagsList.php');
require_once (dirname(__FILE__) . '/class/MailchimpMemberLists.php');
require_once (dirname(__FILE__) . '/class/MailchimpListSegments.php');
require_once (dirname(__FILE__) . '/class/MailchimpCampaignCreator.php');
require_once (dirname(__FILE__) . '/actions.php');

use MailchimpAPI\MailchimpAPI;


// Add options page
add_action('admin_menu', 'mailchimp_api_plugin_menu');

function mailchimp_api_plugin_menu()
{
    add_options_page(
        'WP Post To Mailchimp Settings',
        'Post To Mailchimp',
        'manage_options',
        'mailchimp-api-settings',
        'mailchimp_api_plugin_options_page'
    );
}


// Register settings
add_action('admin_init', 'mailchimp_api_plugin_register_settings');

function mailchimp_api_plugin_register_settings()
{
    register_setting('mailchimp-api-settings-group', 'mailchimp_api_key');
    register_setting('mailchimp-api-settings-group', 'mailchimp_server');
    register_setting('mailchimp-api-settings-group', 'mailchimp_list_id');
    register_setting('mailchimp-api-settings-group', 'mailchimp_from_name');
    register_setting('mailchimp-api-settings-group', 'mailchimp_reply_to');
    register_setting('mailchimp-api-settings-group', 'mailchimp_template_id');
    register_setting('mailchimp-api-settings-group', 'mailchimp_fr_template_id');
    register_setting('mailchimp-api-settings-group', 'mailchimp_post_type');

}

// Function to verify Mailchimp API settings
// Display options page
function mailchimp_api_check($api_key, $server)
{

    if (empty($api_key) || empty($server)) {
        echo '<div class="error"><p>Both fields are required.</p></div>';
    } else {
        $client = new MailchimpAPI();
        $data = $client->verify_mailchimp_api_settings();
        if ($data['status'] === true) {
            echo '<div class="updated"><h3 style="color:#fff;">Successfully verified Mailchimp API key.</h3><p>Please fill in settings below</div>';
        } else {
            echo '<div class="error notice notice-error" ><h3 style="color:#fff;">Failed to verify Mailchimp API key.<h3><p style="color:#fff;">' . $data['message'] . '</p></div>';
        }
    }

}


// Display options page
function mailchimp_api_plugin_options_page()
{
    if (isset($_POST['submit']) && !empty($_POST['mailchimp_api_key'])) {
        $api_key = sanitize_text_field($_POST['mailchimp_api_key']);
        $server = sanitize_text_field($_POST['mailchimp_server']);

        update_option('mailchimp_api_key', $api_key);
        update_option('mailchimp_server', $server);

        mailchimp_api_check($api_key, $server);

    } else if (isset($_POST['submit']) && empty($_POST['mailchimp_api_key'])) {

        $list_id = sanitize_text_field($_POST['mailchimp_list_id']);
        $from_name = sanitize_text_field($_POST['mailchimp_from_name']);
        $reply_to = sanitize_text_field($_POST['mailchimp_reply_to']);
        $template_id = sanitize_text_field($_POST['mailchimp_template_id']);
        $fr_template_id = sanitize_text_field($_POST['mailchimp_fr_template_id']);
        $mailchimp_post_type = sanitize_text_field($_POST['mailchimp_post_type']);

        update_option('mailchimp_list_id', $list_id);
        update_option('mailchimp_from_name', $from_name);
        update_option('mailchimp_reply_to', $reply_to);
        update_option('mailchimp_template_id', $template_id);
        update_option('mailchimp_fr_template_id', $fr_template_id);
        update_option('mailchimp_post_type', $mailchimp_post_type);
    }
    ?>

    <div class="wrap">
        <h1>WP Post To Mailchimp Settings</h1>
    </div>
    <div class="stuffbox">
    <div class="wrap" style="padding:20px;">
        <h2>Mailchimp API Key</h2>
        <form method="post" action="">
            <?php settings_fields('mailchimp-api-settings-group'); ?>
            <?php do_settings_sections('mailchimp-api-settings-group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">API Key</th>
                    <td><input type="text" name="mailchimp_api_key"
                            value="<?php echo esc_attr(get_option('mailchimp_api_key')); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Server Prefix</th>
                    <td><input type="text" name="mailchimp_server"
                            value="<?php echo esc_attr(get_option('mailchimp_server')); ?>" /></td>
                </tr>
            </table>
            <?php submit_button('Verify API', 'primary', 'submit'); ?>
        </form>
    </div>
</div>
    <?php
    $client = new MailchimpAPI();
    $valid = $client->verify_mailchimp_api_settings(); ?>
    <?php if ($valid['status']) { ?>
        <div class="stuffbox">
        <div class="wrap" style="padding:20px;">
            <h2>Mailchimp API Settings</h2>
            <form method="post" action="">
                <?php settings_fields('mailchimp-api-settings-group'); ?>
                <?php do_settings_sections('mailchimp-api-settings-group'); ?>
                <table class="form-table">

                    <tr valign="top">
                        <th scope="row">Audience List</th>
                        <td><?php $client->mailchimp_api_get_lists(); ?></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">English Template</th>
                        <td><?php $client->mailchimp_api_get_templates('mailchimp_template_id'); ?></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">French Template</th>
                        <td><?php $client->mailchimp_api_get_templates('mailchimp_fr_template_id'); ?></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">From Name</th>
                        <td><input type="text" name="mailchimp_from_name"
                                value="<?php echo esc_attr(get_option('mailchimp_from_name')); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Reply To</th>
                        <td><input type="text" name="mailchimp_reply_to"
                                value="<?php echo esc_attr(get_option('mailchimp_reply_to')); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Post Type To Send</th>
                        <td><input type="text" name="mailchimp_post_type"
                                value="<?php echo esc_attr(get_option('mailchimp_post_type')); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button('Save Settings', 'primary', 'submit'); ?>
            </form>
        </div>
        </div>
        <?php
    }
}
