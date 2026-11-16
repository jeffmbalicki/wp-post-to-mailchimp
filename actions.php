<?php

add_action('add_meta_boxes', 'register_mailchimp_send_meta_box');
add_action('add_meta_boxes', 'register_mailchimp_content_sections_meta_box');
add_action('save_post', 'send_newsletter', 10, 3);
add_action('save_post', 'save_mailchimp_content_section_values', 9, 2);
add_action('admin_notices', 'mailchimp_campaign_error_notice');
add_action('enqueue_block_editor_assets', 'mailchimp_enqueue_editor_error_check');
add_action('wp_ajax_mailchimp_get_campaign_error', 'mailchimp_get_campaign_error');
add_filter('update_to_mailchip', 'update_to_mailchip_callback', 1, 1);
add_filter('update_tags_to_mailchip', 'update_tags_to_mailchip_callback', 1, 1);

function mailchimp_enqueue_editor_error_check()
{
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== get_option('mailchimp_post_type')) {
        return;
    }

    wp_enqueue_script('wp-data');
    wp_enqueue_script('wp-util');
    wp_add_inline_script(
        'wp-data',
        'wp.data.subscribe(function () {
            var editor = wp.data.select("core/editor");
            if (!editor) {
                return;
            }

            var saving = editor.isSavingPost();
            if (window.mailchimpEditorWasSaving && !saving && editor.didPostSaveRequestSucceed()) {
                wp.ajax.post("mailchimp_get_campaign_error", {
                    post_id: editor.getCurrentPostId(),
                    nonce: ' . wp_json_encode(wp_create_nonce('mailchimp_get_campaign_error')) . '
                }).done(function (response) {
                    var notices = wp.data.dispatch("core/notices");
                    notices.removeNotice("mailchimp-campaign-error");
                    if (response.error) {
                        notices.createErrorNotice(response.error, {
                            id: "mailchimp-campaign-error",
                            isDismissible: true
                        });
                    }
                }).fail(function () {
                    wp.data.dispatch("core/notices").createErrorNotice(
                        "Could not check the Mailchimp campaign result. Reload the page to check for an error.",
                        { id: "mailchimp-campaign-error" }
                    );
                });
            }
            window.mailchimpEditorWasSaving = saving;
        });',
        'after'
    );
}

function mailchimp_get_campaign_error()
{
    check_ajax_referer('mailchimp_get_campaign_error', 'nonce');

    $post_id = isset($_POST['post_id']) ? absint(wp_unslash($_POST['post_id'])) : 0;
    $post = get_post($post_id);
    if (
        !$post
        || $post->post_type !== get_option('mailchimp_post_type')
        || !current_user_can('edit_post', $post_id)
    ) {
        wp_send_json_error(['message' => 'You are not allowed to view this campaign result.'], 403);
    }

    wp_send_json_success([
        'error' => get_post_meta($post_id, '_mailchimp_campaign_error', true),
    ]);
}

function mailchimp_campaign_error_notice()
{
    $screen = get_current_screen();
    if (!$screen || $screen->base !== 'post') {
        return;
    }

    $post_id = isset($_GET['post'])
        ? absint(wp_unslash($_GET['post']))
        : (isset($GLOBALS['post']->ID) ? absint($GLOBALS['post']->ID) : 0);
    if (!$post_id) {
        return;
    }

    $post = get_post($post_id);
    if (!$post || $post->post_type !== get_option('mailchimp_post_type')) {
        return;
    }

    $error = get_post_meta($post_id, '_mailchimp_campaign_error', true);
    if ($error) {
        printf('<div class="notice notice-error"><p>%s</p></div>', esc_html($error));
    }
}

function register_mailchimp_send_meta_box()
{
    $post_type = get_option('mailchimp_post_type');
    if (!$post_type) {
        return;
    }

    add_meta_box(
        'mailchimp_segments',
        'Mailchimp Send',
        'display_mailchimp_send_settings',
        $post_type,
        'side',
        'high'
    );
}

function register_mailchimp_content_sections_meta_box()
{
    $post_type = get_option('mailchimp_post_type');
    $content_sections = get_option('mailchimp_content_sections', []);
    if (!$post_type || !is_array($content_sections) || !$content_sections) {
        return;
    }

    add_meta_box(
        'mailchimp_content_sections',
        'Mailchimp Content Sections',
        'display_mailchimp_content_sections_meta_box',
        $post_type,
        'normal',
        'high'
    );
}

function display_mailchimp_content_sections_meta_box($post)
{
    $content_sections = get_option('mailchimp_content_sections', []);
    $section_values = get_post_meta($post->ID, '_mailchimp_content_section_values', true);
    if (!is_array($content_sections) || !is_array($section_values)) {
        $section_values = [];
    }

    wp_nonce_field('save_mailchimp_content_section_values', 'mailchimp_content_sections_nonce');

    foreach ($content_sections as $index => $section) {
        if (!is_array($section) || empty($section['section_name'])) {
            continue;
        }

        $section_name = $section['section_name'];
        $label = !empty($section['label']) ? $section['label'] : $section_name;
        $value = isset($section_values[$section_name]) ? $section_values[$section_name] : $post->post_content;
        ?>
        <p>
            <label for="mailchimp-content-section-<?php echo esc_attr($index); ?>">
                <strong><?php echo esc_html($label); ?></strong>
                <small>(<?php echo esc_html($section_name); ?>)</small>
            </label>
            <textarea class="widefat" rows="5"
                id="mailchimp-content-section-<?php echo esc_attr($index); ?>"
                name="mailchimp_content_section_values[<?php echo esc_attr($index); ?>]"><?php echo esc_textarea($value); ?></textarea>
        </p>
        <?php
    }
}

function save_mailchimp_content_section_values($post_id, $post)
{
    if (
        wp_is_post_revision($post_id)
        || wp_is_post_autosave($post_id)
        || $post->post_type !== get_option('mailchimp_post_type')
        || !current_user_can('edit_post', $post_id)
        || !isset($_POST['mailchimp_content_sections_nonce'])
        || !wp_verify_nonce(
            sanitize_text_field(wp_unslash($_POST['mailchimp_content_sections_nonce'])),
            'save_mailchimp_content_section_values'
        )
    ) {
        return;
    }

    $content_sections = get_option('mailchimp_content_sections', []);
    $submitted_values = isset($_POST['mailchimp_content_section_values']) && is_array($_POST['mailchimp_content_section_values'])
        ? wp_unslash($_POST['mailchimp_content_section_values'])
        : [];
    $section_values = [];

    if (is_array($content_sections)) {
        foreach ($content_sections as $index => $section) {
            if (!is_array($section) || empty($section['section_name'])) {
                continue;
            }

            $section_values[$section['section_name']] = isset($submitted_values[$index])
                ? wp_kses_post($submitted_values[$index])
                : '';
        }
    }

    update_post_meta($post_id, '_mailchimp_content_section_values', $section_values);
}

function display_mailchimp_send_settings($post)
{
    wp_nonce_field('save_mailchimp_send_settings', 'mailchimp_send_settings_nonce');

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
    if (
        wp_is_post_revision($post_id)
        || wp_is_post_autosave($post_id)
        || $post->post_type !== get_option('mailchimp_post_type')
        || !current_user_can('edit_post', $post_id)
        || !isset($_POST['mailchimp_send_settings_nonce'])
        || !wp_verify_nonce(
            sanitize_text_field(wp_unslash($_POST['mailchimp_send_settings_nonce'])),
            'save_mailchimp_send_settings'
        )
    ) {
        return;
    }

    if (isset($_POST['segments'])) {
        update_post_meta($post_id, 'saved_segments', absint(wp_unslash($_POST['segments'])));
    }
    $send = isset($_POST['send']) ? 1 : 0;
    update_post_meta($post_id, 'send', $send);

    if (!$send) {
        delete_post_meta($post_id, '_mailchimp_campaign_error');
        return;
    }

    if (class_exists('MailchimpAPI\MailchimpCampaignCreator')) {

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

        $contentSections = get_option('mailchimp_content_sections', []);
        $sectionValues = get_post_meta($post_id, '_mailchimp_content_section_values', true);
        $templateSections = [];

        if (is_array($contentSections)) {
            foreach ($contentSections as $section) {
                if (!is_array($section) || empty($section['section_name'])) {
                    continue;
                }

                $templateSections[$section['section_name']] = (
                    is_array($sectionValues) && isset($sectionValues[$section['section_name']])
                ) ? $sectionValues[$section['section_name']] : $post->post_content;
            }
        }
        $templateSections['body_content'] = $post->post_content;

        $contentData = [
            "plain_text" => $post->post_title,
            "template" => [
                "id" => $template_id,
                "sections" => $templateSections,
            ],
        ];

        delete_post_meta($post_id, '_mailchimp_campaign_error');
        $result = $campaignCreator->createCampaign($campaignData, $contentData);
        if (is_wp_error($result)) {
            update_post_meta($post_id, '_mailchimp_campaign_error', $result->get_error_message());
            return;
        }

        delete_post_meta($post_id, '_mailchimp_campaign_error');
        update_post_meta($post_id, 'sent', date('Y-m-d H:i:s'));
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