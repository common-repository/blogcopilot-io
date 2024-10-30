<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
if (!defined('WPINC')) exit; // Exit if accessed directly

// AJAX function to add summarization to the post
add_action('wp_ajax_blogcopilot_io_update_post', 'blogcopilot_io_update_post');
function blogcopilot_io_update_post() {
    if (!isset($_POST['_ajax_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_ajax_nonce'])), 'blogcopilot_io_update_post_nonce')) {
        wp_die('Nonce verification failed, unauthorized request.');
    }   

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
    $content = isset($_POST['content']) ? sanitize_text_field($_POST['content']) : '';

    if ($post_id > 0 && !empty($title) && !empty($content)) {    
        $api_response = blogcopilot_io_call_api_summarize_content($title, $content);
        if ($api_response) {
            $summary = $api_response['summary'] ?? '';  

            if (blogcopilot_io_update_post_seo_parameters($post_id, $title, $summary) == 1) {
                wp_send_json_success("New Post SEO parameters updated");
            }        
        }
    }
    wp_die(); // Terminate AJAX request correctly
}

// AJAX handler for generating more images
add_action('wp_ajax_blogcopilot_io_generate_more_images', 'blogcopilot_io_generate_more_images_callback');
function blogcopilot_io_generate_more_images_callback() {
    if (!isset($_POST['_ajax_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_ajax_nonce'])), 'blogcopilot_io_generate_more_images_nonce')) {
        wp_die('Nonce verification failed, unauthorized request.');
    }   

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';

    // Call the external API again
    $api_response = blogcopilot_io_call_api_generate_images($title);

    if (isset($api_response['error'])) {
        // If there's an error in the response, send it back to the AJAX call
        wp_send_json_error($api_response);
    } else {
        // Otherwise, return the image URLs as before
        wp_send_json_success($api_response['image_urls'] ?? []);
    }
}

// AJAX handler for generating more images in bulk mode (with task id)
add_action('wp_ajax_blogcopilot_io_generate_more_images_2', 'blogcopilot_io_generate_more_images_2_callback');
function blogcopilot_io_generate_more_images_2_callback() {
    if (!isset($_POST['_ajax_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_ajax_nonce'])), 'blogcopilot_io_publish_post_action')) {
        wp_die('Nonce verification failed, unauthorized request.');
    }  

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
    $taskId = isset($_POST['taskId']) ? intval($_POST['taskId']) : 0;

    // Call the external API again
    $api_response = blogcopilot_io_call_api_generate_images($title);

    if (isset($api_response['error'])) {
        // If there's an error in the response, send it back to the AJAX call
        wp_send_json_error($api_response);
    } else {
        // Otherwise, return the image URLs as before
        $mass_response = blogcopilot_io_call_api_mass_add_images($taskId, $api_response['image_urls']);
        wp_send_json_success($api_response['image_urls'] ?? []);
    }
}

// AJAX handler for setting the featured image
add_action('wp_ajax_blogcopilot_io_ai_set_featured_image', 'blogcopilot_io_ai_set_featured_image');
function blogcopilot_io_ai_set_featured_image() {
    if (!isset($_POST['_ajax_nonce']) || (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_ajax_nonce'])), 'blogcopilot_io_publish_post_action') && !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_ajax_nonce'])), 'blogcopilot_io_generate_more_images_nonce'))) {
        wp_die('Nonce verification failed, unauthorized request.');
    }

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $image_url = isset($_POST['image_url']) ? esc_url_raw(sanitize_text_field($_POST['image_url'])) : '';
    $post = $post_id ? get_post($post_id) : null;

    if (!$post) {
        wp_die('Post not found.');
    }

    $post_title = $post->post_title;

    $allow_unsafe_urls = function($args) {
        $args['reject_unsafe_urls'] = false;
        return $args;
    };

    // Temporarily enable the filter
    add_filter('http_request_args', $allow_unsafe_urls);

    // Upload the image and set as featured image
    $attachment_id = media_sideload_image($image_url, $post_id, $post_title, 'id');

    remove_filter('http_request_args', $allow_unsafe_urls);


    if (!is_wp_error($attachment_id)) {
        set_post_thumbnail($post_id, $attachment_id);

        // Set the alt text of the image
        update_post_meta($attachment_id, '_wp_attachment_image_alt', $post_title);
        // When an image is set as featured, check if there is another featured one, and remove from post meta values

        // First, remove any existing featured image meta for this post
        $existing_meta = get_post_meta($post_id);
        foreach ($existing_meta as $meta_key => $meta_values) {
            if (strpos($meta_key, 'blogcopilot_image_usage_') === 0 && in_array('featured', $meta_values)) {
                // Delete the meta key if it's set to 'featured'
                delete_post_meta($post_id, $meta_key);
            }
        }
        // Now, set the new image as featured
        update_post_meta($post_id, 'blogcopilot_image_usage_' . $image_url, 'featured');
    } else {
        wp_die('Error uploading image: ' . esc_html($attachment_id->get_error_message()));
    }

    // Return a response (success or error)
    wp_die();
}

// AJAX handler for adding images to the post
add_action('wp_ajax_blogcopilot_io_ai_use_image_in_content', 'blogcopilot_io_ai_use_image_in_content');
function blogcopilot_io_ai_use_image_in_content() {
    if (!isset($_POST['_ajax_nonce']) || (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_ajax_nonce'])), 'blogcopilot_io_publish_post_action') && !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_ajax_nonce'])), 'blogcopilot_io_generate_more_images_nonce'))) {
        wp_die('Nonce verification failed, unauthorized request.');
    }

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $image_url = isset($_POST['image_url']) ? esc_url_raw(sanitize_text_field($_POST['image_url'])) : '';
    $post = $post_id ? get_post($post_id) : null;

    if ($post) {
        $content = $post->post_content;
        $post_title = esc_attr($post->post_title); // Sanitize the post title for use in HTML

        // Find all <h2> and <p> tags (case-insensitive)
        preg_match_all('/<\/h2>|<\/p>/i', $content, $matches, PREG_OFFSET_CAPTURE);
        $tag_positions = $matches[0];

        // Randomly choose one of the tags to insert after, if any are found
        if (!empty($tag_positions)) {
            $random_tag_position = $tag_positions[wp_rand(0, count($tag_positions) - 1)];
            $position = $random_tag_position[1] + strlen($random_tag_position[0]);
        } else {
            // If no tags found, default to end of content
            $position = strlen($content);
        }

        $allow_unsafe_urls = function($args) {
            $args['reject_unsafe_urls'] = false;
            return $args;
        };

        // Temporarily enable the filter
        add_filter('http_request_args', $allow_unsafe_urls);

        // Upload the image to the WordPress Media Library
        $uploaded_image = media_sideload_image($image_url, $post_id, $post_title, 'id');

        remove_filter('http_request_args', $allow_unsafe_urls);

        if (is_wp_error($uploaded_image)) {
            // Handle error in image uploading
            wp_die('Error uploading image: ' . esc_html($uploaded_image->get_error_message()));
        }
        // Set the alt text of the image
        update_post_meta($uploaded_image, '_wp_attachment_image_alt', $post_title);

        $size = 'large'; // Choose a size that fits your content area ('thumbnail', 'medium', 'large', etc.)
        $image_html = wp_get_attachment_image($uploaded_image, $size, false, array('alt' => $post_title));
        // Wrap the image in a caption shortcode, if option set in settings
        $image_with_caption = get_option('blogcopilot_image_with_caption', '');
        $blog_lang = get_option('blogcopilot_blog_lang', 'English');
        // Language translations for "AI Generated"
        $translations = [
            'English' => 'AI Generated',
            'Spanish' => 'Generado por IA',
            'German' => 'KI-Generiert',
            'French' => 'Généré par IA',
            'Portuguese' => 'Gerado por IA',
            'Russian' => 'Создано ИИ',
            'Italian' => 'Generato da IA',
            'Indonesian' => 'Dibuat oleh AI',
            'Japanese' => 'AI生成',
            'Polish' => 'Wygenerowane przez AI',
            'Dutch' => 'AI gegenereerd'
        ];

        // Default to English if the language is not found
        $caption_text = $translations[$blog_lang] ?? $translations['English'];

        if ($image_with_caption != '') {
            $image_metadata = wp_get_attachment_metadata($uploaded_image);
            $sizes = wp_calculate_image_sizes(array($size), wp_get_attachment_url($uploaded_image), $image_metadata);
            preg_match('/\d+px/', $sizes, $matches);
            $displayed_width = isset($matches[0]) ? intval($matches[0]) : '600';
    
            $caption_html = '<figure style="width: ' . esc_attr($displayed_width) . 'px" class="wp-caption alignnone">';
            $caption_html .= $image_html;
            $caption_html .= '<figcaption class="wp-caption-text"><em>' . esc_html($caption_text) . '</em></figcaption></figure>';           
        } else {
            $caption_html = $image_html;
        }

        // Insert the caption HTML into the content
        $content = substr_replace($content, $caption_html, $position, 0);

        // Update the post content
        wp_update_post(array(
            'ID' => $post_id,
            'post_content' => $content
        ));
        // When an image is set as highlighted or used in content
        update_post_meta($post_id, 'blogcopilot_image_usage_' . $image_url, 'content'); // $usage_type can be 'featured' or 'content'
    }

    wp_die(); 
}

add_action('wp_ajax_blogcopilot_generate_article', 'blogcopilot_generate_article_ajax');
function blogcopilot_generate_article_ajax() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'create-article-' . sanitize_text_field($_POST['keyword']))) {
        wp_send_json_error(['message' => 'Nonce verification failed, unauthorized submission.']);
    }

    $title = isset($_POST['keyword']) ? sanitize_text_field($_POST['keyword']) : '';
    $category_id = 0;
    $language = get_option('blogcopilot_blog_lang', 'English');
    $keywords = '';
    $content_description = '';
    $style = '';
    $premium = 'no';
    $publish_as_draft = 'draft';
    $conspect = 'no';
    $articleLength = 0;
    $live = 'no';

    // Call API
    $api_response = blogcopilot_io_call_api_generate_content($title, $category_id, $language, $keywords, $content_description, $style, $premium, $live, $conspect, $articleLength);

    wp_send_json_success(['message' => 'Article sent for generation!']);

    wp_die(); 
}


?>