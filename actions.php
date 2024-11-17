<?php

add_meta_box(
    'mailchimp_segments', // ID of the metabox
    'Mailchimp Send', // Title of the metabox
    'display_mailchimp_send_settings', // Callback function
    get_option('mailchimp_post_type'), // Screen(s) on which to show the box
    'side',
    'high'
);

add_action('save_post', 'send_newsletter', 10, 3);
add_filter('update_to_mailchip', 'update_to_mailchip_callback', 1, 1);
add_filter('update_tags_to_mailchip', 'update_tags_to_mailchip_callback', 1, 1);
function display_mailchimp_send_settings($post)
{

    $saved_tags = get_post_meta($post->ID, 'saved_segments', true);
    $send = get_post_meta($post->ID, 'send', true);
    $sent = get_post_meta($post->ID, 'sent', true);

    $mailchimpListSegments = new MailchimpAPI\MailchimpListSegments();
    $listId = get_option('mailchimp_list_id');
    $segments = $mailchimpListSegments->getListSegments($listId);
    $segments->segments = array_reverse($segments->segments);
    echo 'Audience Segments <select name="segments">';
    foreach ($segments->segments as $segment) {
        if ($segment->type != 'saved') {
            continue;
        }
        $selected = "";
        if (isset($saved_tags)) {
            $selected = ($segment->id == $saved_tags) ? 'selected' : '';
        }
        echo '<option value="' . $segment->id . '" ' . $selected . '>' . ucfirst($segment->name) . '</option>';
    }
    echo '</select>';
    echo '<br><br>Confirm Send <input type="checkbox" name="send" value="1"' . checked(1, $send, false) . '/>';
    echo '<br><br>Time Sent <input type="text" name="sent" value="' . esc_attr($sent) . '" readonly/>';
}


function send_newsletter($post_id, $post, $update)
{

    update_post_meta($post_id, 'saved_segments', $_POST['segments']);
    $send = isset($_POST['send']) ? 1 : 0;
    update_post_meta($post_id, 'send', $send);
    if (!wp_is_post_revision($post_id) && $post->post_type == get_option('mailchimp_post_type')) {


        if (class_exists('MailchimpAPI\MailchimpCampaignCreator')) {

            if (!get_post_meta($post_id, 'send', true)) {
                return;
            }

            if (get_post_meta($post_id, 'sent', true) != "") {
                return;
            }
            if (apply_filters('wpml_current_language', NULL) == 'fr') {
                $template_id = intval(get_option('mailchimp_fr_template_id'));
            } else {
                $template_id = intval(get_option('mailchimp_template_id'));
            }

            $campaignCreator = new MailchimpAPI\MailchimpCampaignCreator();
            $listId = get_option('mailchimp_list_id');

            $campaignData = [
                'recipients' => [
                    'list_id' => $listId,
                    'segment_opts' => [
                        'saved_segment_id' => intval(get_post_meta($post_id, 'saved_segments', true)),
                    ],
                ],
                'settings' => [
                    'subject_line' => $post->post_title,
                    'title' => $post->post_title,
                    'from_name' => get_option('mailchimp_from_name'),
                    'reply_to' => get_option('mailchimp_reply_to'),
                    'template_id' => $template_id,
                    'inline_css' => true,
                ],
                'type' => 'regular',
                'content_type' => 'template',
            ];

            $contentData = [
                "plain_text" => $post->post_title,
                "template" => [
                    "id" => $template_id,
                    "sections" => [
                        "content" => $post->post_content,
                    ],
                ],
            ];

            $campaignCreator->createCampaign($campaignData, $contentData);
            update_post_meta($post_id, 'sent', date('Y-m-d H:i:s'));
        }
    }
}


function update_to_mailchip_callback($userData)
{
    $memberList = new MailchimpAPI\MailchimpMemberLists();
    $listId = get_option('mailchimp_list_id');
    if (isset($userData['current_email'])) {
        $current_user = $memberList->getMemberFromList($listId, md5(strtolower($userData['current_email'])));
        unset($userData['current_email']);
    }

    if (!isset($current_user)) {
        $response = $memberList->addMemberToList($listId, $userData);
    } else {
        $response = $memberList->updateMemberToList($listId, md5(strtolower($current_user->email_address)), $userData);
    }

    return $response;
}

function update_tags_to_mailchip_callback($userData)
{

    $memberList = new MailchimpAPI\MailchimpMemberLists();
    $listId = get_option('mailchimp_list_id');
    $response = $memberList->updateTagsForMemberToList($listId, $userData);


    return $response;
}

//