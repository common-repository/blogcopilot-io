<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
if (!defined('WPINC')) exit; // Exit if accessed directly

function blogcopilot_io_call_api_generate_content($title, $category_id, $language = '', $keywords = '', $content_description = '', $style = '', $premium = 'no', $live = 'no', $conspect = 'no', $articleLength = 2500) {
    // Retrieve the current values from settings
    $license_number = get_option('blogcopilot_license_number', '');
    $blog_title = get_option('blogcopilot_blog_title', '');
    $blog_description = get_option('blogcopilot_blog_description', '');
    $api_url = get_option('blogcopilot_api_url', '');
    $blog_domain = get_option('blogcopilot_blog_domain', '');

    $api_url = $api_url.'api-endpoint-generate-content.php';
    $api_params = array(
        'body' => wp_json_encode(array(
            'title' => $title,
            'category_id' => $category_id,
            'language' => $language,
            'keywords' => $keywords,
            'content_description' => $content_description,            
            'style' => $style,
            'premiumCheck' => $premium,
            'liveCheck' => $live,            
            'articleLength' => $articleLength,
            'licenseKey' => $license_number,
            'domain' => $blog_domain,
            'conspect' => $conspect,
            'description' => $blog_description,
        ), JSON_UNESCAPED_UNICODE),
        'headers' => array('Content-Type' => 'application/json'),
          'timeout' => 120
    );

    $response = wp_remote_post($api_url, $api_params);
    if (is_wp_error($response)) {
        return ['error' => true, 'message' => 'An error occurred while contacting the API.'];
    }

    $body = wp_remote_retrieve_body($response);
    $api_response = json_decode($body, true);

    // Check the API response for errors or quota exceedances
    if (isset($api_response['error']) && $api_response['error']) {
        return $api_response; // Pass the error response through
    }

    return $api_response; // Proceed as normal if no errors
}

function blogcopilot_io_call_api_regenerate_content($title, $article, $language, $keywords, $content_description, $style) {
    // Retrieve the current values from settings
    $license_number = get_option('blogcopilot_license_number', '');
    $blog_title = get_option('blogcopilot_blog_title', '');
    $blog_description = get_option('blogcopilot_blog_description', '');
    $api_url = get_option('blogcopilot_api_url', '');
    $blog_domain = get_option('blogcopilot_blog_domain', '');

    $api_url = $api_url.'api-endpoint-regenerate-content.php';
    $api_params = array(
        'body' => wp_json_encode(array(
            'title' => $title,
            'description' => $blog_description,
            'article' => $article,
            'licenseKey' => $license_number,
            'domain' => $blog_domain,
            'language' => $language,
            'keywords' => $keywords,
            'content_description' => $content_description,            
            'style' => $style                        
        ), JSON_UNESCAPED_UNICODE),
        'headers' => array('Content-Type' => 'application/json'),
          'timeout' => 180
    );

    $response = wp_remote_post($api_url, $api_params);
    if (is_wp_error($response)) {
    // Handle error
    return null;
    }

    $body = wp_remote_retrieve_body($response);
    return json_decode($body, true);
}

function blogcopilot_io_call_api_summarize_content($title, $article) {
    // Retrieve the current values from settings
    $license_number = get_option('blogcopilot_license_number', '');
    $blog_title = get_option('blogcopilot_blog_title', '');
    $blog_description = get_option('blogcopilot_blog_description', '');
    $api_url = get_option('blogcopilot_api_url', '');
    $blog_domain = get_option('blogcopilot_blog_domain', '');

    $api_url = $api_url.'api-endpoint-generate-summarization.php';
    $api_params = array(
        'body' => wp_json_encode(array(
            'article' => $article,
            'title' => $title,          
            'licenseKey' => $license_number,
            'domain' => $blog_domain
        ), JSON_UNESCAPED_UNICODE),
        'headers' => array('Content-Type' => 'application/json'),
          'timeout' => 120
    );
   
    $response = wp_remote_post($api_url, $api_params);

	if (is_wp_error($response)) {
		// Handle the error...
        return null;
	}

    $body = wp_remote_retrieve_body($response);
    return json_decode($body, true);
}

function blogcopilot_io_call_api_generate_images($title) {
    // Retrieve the current values from settings
    $license_number = get_option('blogcopilot_license_number', '');
    $api_url = get_option('blogcopilot_api_url', '');
    $blog_domain = get_option('blogcopilot_blog_domain', '');

    $api_url = $api_url.'api-endpoint-generate-image.php';
    $api_params = array(
        'body' => wp_json_encode(array(
            'title' => $title,          
            'licenseKey' => $license_number,
            'domain' => $blog_domain
        ), JSON_UNESCAPED_UNICODE),
        'headers' => array('Content-Type' => 'application/json'),
        'timeout' => 120
    );
   
    $response = wp_remote_post($api_url, $api_params);

	if (is_wp_error($response)) {
		// Handle the error...
        return null;
	}

    $body = wp_remote_retrieve_body($response);
    return json_decode($body, true);
}

function blogcopilot_io_call_api_mass_add_images($taskId, $imageUrls) {
    // Retrieve the current values from settings
    $license_number = get_option('blogcopilot_license_number', '');
    $api_url = get_option('blogcopilot_api_url', '');
    $blog_domain = get_option('blogcopilot_blog_domain', '');

    $api_url = $api_url.'api-endpoint-mass-add-images.php';
    $api_params = array(
        'body' => wp_json_encode(array(
            'licenseKey' => $license_number,
            'domain' => $blog_domain,
            'taskId' => $taskId,
            'newImageUrls' => $imageUrls
        ), JSON_UNESCAPED_UNICODE),
        'headers' => array('Content-Type' => 'application/json'),
        'timeout' => 120
    );
   
    $response = wp_remote_post($api_url, $api_params);

	if (is_wp_error($response)) {
		// Handle the error...
        return null;
	}

    $body = wp_remote_retrieve_body($response);
    return json_decode($body, true);
}

// The function to be executed on the scheduled autopublish event
function blogcopilot_io_cron_autopublish_function() {
    // Step 1: Read and remove the jobGroupId from the option
    $jobGroupIds = get_option('blogcopilot_job_group_ids_to_publish', array());

    $apiUrl = get_option('blogcopilot_api_url', '') . '/api-endpoint-mass-get-jobs.php';
    $licenseKey = get_option('blogcopilot_license_number', '');
    $domain = get_option('blogcopilot_blog_domain', '');

    $payload = [
        'licenseKey' => $licenseKey,
        'domain' => $domain,
        'scope' => 'articles'
    ];

    $response = wp_remote_post($apiUrl, [
        'body' => wp_json_encode($payload, JSON_UNESCAPED_UNICODE),
        'headers' => ['Content-Type' => 'application/json'],
    ]);

    $body = wp_remote_retrieve_body($response); // Get the response body
    $jobGroupIds = json_decode($body, true); // Decode JSON to array    

    // Assuming you process one jobGroupId at a time
    $jobGroupId = array_shift($jobGroupIds); // Remove the first jobGroupId for processing
    update_option('blogcopilot_job_group_ids_to_publish', $jobGroupIds); // Update the option with the remaining IDs

    // Retrieve articles using the jobGroupId
    $apiUrl = get_option('blogcopilot_api_url', '');
    $licenseKey = get_option('blogcopilot_license_number', '');
    $domain = get_option('blogcopilot_blog_domain', '');

    $response = wp_remote_post($apiUrl.'/api-endpoint-mass-get-results.php', [
        'body' => wp_json_encode(['jobId' => $jobGroupId, 'licenseKey' => $licenseKey, 'domain' => $domain], JSON_UNESCAPED_UNICODE),
        'headers' => ['Content-Type' => 'application/json']
    ]);

    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) != 200) {
        return; // End the cron job if there is an error fetching articles
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    if (isset($data['articles']) && is_array($data['articles'])) {
        $articles = $data['articles'];
    }

    if (empty($articles)) {
        return; // If there are no articles, end the cron job
    }

    // Process and publish each article
    foreach ($articles as $article) {
        $title = $article['title'];
        $categoryId = $article['category_id'];
        $summary = $article['summary'];
        $content = $article['content'];
    
        // Create the post
        $post_id = blogcopilot_io_create_new_post($categoryId, $title, $content, 'publish', $jobGroupId);
        // Update SEO parameters
        blogcopilot_io_update_post_seo_parameters($post_id, $title, $summary);        
        // Assuming you have an array of image URLs from the article's data
        $image_urls = $article['image_urls'];
        if (!empty($image_urls)) {
            // Select a random image to set as the featured image
            $random_key = array_rand($image_urls);
            $featured_image_url = $apiUrl . $image_urls[$random_key];
            // Use the function directly without AJAX
            blogcopilot_io_process_set_featured_image($post_id, $featured_image_url);

            // Add the rest of the images to the content
            foreach ($image_urls as $key => $image_url) {
                if ($key != $random_key) { // Skip the featured image
                    blogcopilot_io_process_use_image_in_content($post_id, $apiUrl . $image_url);
                }
            }
        }
    }
}

function blogcopilot_publish_and_update_phrases() {
    // Step 1: Fetch Pending Articles
    $apiUrl = get_option('blogcopilot_api_url', '');
    $licenseKey = get_option('blogcopilot_license_number', '');
    $domain = get_option('blogcopilot_blog_domain', '');

    $payload = [
        'licenseKey' => $licenseKey,
        'domain' => $domain,
        'scope' => 'phrases'
    ];

    $response = wp_remote_post($apiUrl.'/api-endpoint-mass-get-jobs.php', [
        'body' => wp_json_encode($payload, JSON_UNESCAPED_UNICODE),
        'headers' => ['Content-Type' => 'application/json'],
    ]);

    $body = wp_remote_retrieve_body($response);
    $jobGroupIds = json_decode($body, true);
    foreach ($jobGroupIds['jobs']['jobGroups'] as $jobGroup) {   
        if ($jobGroup['TasksStatus'] == 'completed') {
            // Get PhraseId
            $payload = [
                'action' => 'getPhraseIdByJobGroupId',
                'jobGroupId' => $jobGroup['JobGroupID'],
                'licenseKey' => $licenseKey,
                'domain' => $domain,
            ];
            
            $response = wp_remote_post($apiUrl.'/api-endpoint-phrases.php', [
                'body' => wp_json_encode($payload, JSON_UNESCAPED_UNICODE),
                'headers' => ['Content-Type' => 'application/json'],
            ]);
          
            if (is_wp_error($response)) {
                echo 'Error fetching phrase id: ' . esc_html($response->get_error_message());
                return;
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            $phrases = $data['phraseIds']; 
            $phrases_data = array_map(function($phrase) {
                return ['id' => $phrase['PhraseID'], 'name' => $phrase['Phrase']];
            }, $phrases);

            // Get articles
            $response = wp_remote_post($apiUrl.'/api-endpoint-mass-get-results.php', [
                'body' => wp_json_encode(['jobId' => $jobGroup['JobGroupID'], 'licenseKey' => $licenseKey, 'domain' => $domain], JSON_UNESCAPED_UNICODE),
                'headers' => ['Content-Type' => 'application/json']
            ]);
        
            if (is_wp_error($response)) {
                echo 'Error fetching articles: ' . esc_html($response->get_error_message());
                return;
            }
            
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            $articles = isset($data['articles']) ? $data['articles'] : [];
            foreach ($articles as $article) {       
                // Step 2: Publish Article
                $title = $article['title'];
                $categoryId = $article['category_id'];
                $summary = $article['summary'];
                $content = $article['content'];

                $post_id = blogcopilot_io_create_new_post($categoryId, $title, $content, 'publish');
                blogcopilot_io_update_post_seo_parameters($post_id, $title, $summary);

                // Step 3: Update Post Meta
                update_post_meta($post_id, 'blogcopilot_phrases', wp_json_encode($phrases_data, JSON_UNESCAPED_UNICODE));

                // Step 4: Handle Images (If applicable)
                $image_urls = $article['image_urls'];
                if (!empty($image_urls)) {
                    $random_key = array_rand($image_urls);
                    $featured_image_url = $apiUrl . $image_urls[$random_key];
                    blogcopilot_io_process_set_featured_image($post_id, $featured_image_url);
                    
                    foreach ($image_urls as $key => $image_url) {
                        if ($key != $random_key) {
                            blogcopilot_io_process_use_image_in_content($post_id, $apiUrl . $image_url);
                        }
                    }
                }

                // Step 5: Update Phrase Status via API
                $updateUrl = $apiUrl . '/api-endpoint-phrases.php';
                foreach ($phrases as $phrase) {
                    $payload = [
                        'action' => 'update',
                        'phraseId' => $phrase['PhraseID'],
                        'phrase' => $phrase['Phrase'],
                        'WordPressPostId' => $post_id,
                        'licenseKey' => $licenseKey,
                        'domain' => $domain,
                        'status' => 'AI Published'
                    ];
            
                    $response = wp_remote_post($updateUrl, [
                        'body' => wp_json_encode($payload, JSON_UNESCAPED_UNICODE),
                        'headers' => ['Content-Type' => 'application/json'],
                    ]);
                }

                // Step 6: Update JobGroupId status from completed into published
                $updateUrl = $apiUrl . '/api-endpoint-jobs.php'; // Assuming a separate endpoint for updating
                $payload = [
                    'action' => 'setAsPublished',
                    'jobId' => $article['taskId'],
                    'licenseKey' => $licenseKey,
                    'domain' => $domain,
                ];
                
                $response = wp_remote_post($updateUrl, [
                    'body' => wp_json_encode($payload, JSON_UNESCAPED_UNICODE),
                    'headers' => ['Content-Type' => 'application/json'],
                ]);                
            }
        }
    }
}


function blogcopilot_io_process_set_featured_image($post_id, $image_url) {
    // needed by CRON context
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $post = get_post($post_id);
  
    if (!$post) return;

    $post_title = $post->post_title;
    // Define the filter function
    $allow_unsafe_urls = function($args) {
        $args['reject_unsafe_urls'] = false;
        return $args;
    };

    // Temporarily enable the filter
    add_filter('http_request_args', $allow_unsafe_urls);

    $attachment_id = media_sideload_image($image_url, $post_id, $post_title, 'id');

    remove_filter('http_request_args', $allow_unsafe_urls);

    if (!is_wp_error($attachment_id)) {
        set_post_thumbnail($post_id, $attachment_id);
        update_post_meta($attachment_id, '_wp_attachment_image_alt', $post_title);
        update_post_meta($post_id, 'blogcopilot_image_usage_' . $image_url, 'featured');        
    }
}

function blogcopilot_io_process_use_image_in_content($post_id, $image_url) {
    // needed by CRON context    
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $post = get_post($post_id);
    if (!$post) return;

    $content = $post->post_content;
    $post_title = $post->post_title;

    // Define the filter function
    $allow_unsafe_urls = function($args) {
        $args['reject_unsafe_urls'] = false;
        return $args;
    };

    // Temporarily enable the filter
    add_filter('http_request_args', $allow_unsafe_urls);

    $uploaded_image = media_sideload_image($image_url, $post_id, $post_title, 'id');
    remove_filter('http_request_args', $allow_unsafe_urls);

    if (!is_wp_error($uploaded_image)) {
        update_post_meta($uploaded_image, '_wp_attachment_image_alt', $post_title);
        $image_html = wp_get_attachment_image($uploaded_image, 'large', false, array('alt' => $post_title));
        blogcopilot_io_insert_image_html_into_content($post_id, $content, $image_html);
        update_post_meta($post_id, 'blogcopilot_image_usage_' . $image_url, 'content');
    }
}

function blogcopilot_io_insert_image_html_into_content($post_id, $content, $image_html) {
    // Find all <h2> and <p> tags (case-insensitive) to potentially insert the image after
    preg_match_all('/<\/h2>|<\/p>/i', $content, $matches, PREG_OFFSET_CAPTURE);
    $tag_positions = $matches[0];

    // If there are suitable tags, choose one randomly to insert the image after
    if (!empty($tag_positions)) {
        $random_tag_position = $tag_positions[wp_rand(0, count($tag_positions) - 1)];
        $position = $random_tag_position[1] + strlen($random_tag_position[0]);
    } else {
        // If no tags are found, the image will be inserted at the end of the content
        $position = strlen($content);
    }

    // Insert the image HTML into the content at the determined position
    $new_content = substr_replace($content, $image_html, $position, 0);

    // Update the post content with the new content that includes the image
    wp_update_post(array(
        'ID' => $post_id,
        'post_content' => $new_content
    ));
}

function  blogcopilot_io_display_categories($categories, $parent_id = 0, $depth = 0) {
    foreach ($categories as $category) {
        if ($category->parent == $parent_id) {
            echo '<option value="' . esc_attr($category->term_id) . '">' . esc_html(str_repeat('- ', $depth)) . esc_html($category->name) . '</option>';
            blogcopilot_io_display_categories($categories, $category->term_id, $depth + 1);
        }
    }
}

function  blogcopilot_io_phrase_get($phrase_id) {
    $apiUrl = get_option('blogcopilot_api_url', '');
    $licenseKey = get_option('blogcopilot_license_number', '');
    $domain = get_option('blogcopilot_blog_domain', '');
    $updateUrl = $apiUrl . '/api-endpoint-phrases.php'; // Assuming a separate endpoint for updating
    $payload = [
        'action' => 'getPhrase',
        'phraseId' => $phrase_id,
        'licenseKey' => $licenseKey,
        'domain' => $domain,
    ];
    
    $response = wp_remote_post($updateUrl, [
        'body' => wp_json_encode($payload, JSON_UNESCAPED_UNICODE),
        'headers' => ['Content-Type' => 'application/json'],
    ]);        

    $body = wp_remote_retrieve_body($response); // Get the response body
    return json_decode($body, true); // Decode JSON to array    
}

function blogcopilot_io_get_phrases() {
    $apiUrl = get_option('blogcopilot_api_url', '') . '/api-endpoint-phrases.php';
    $licenseKey = get_option('blogcopilot_license_number', '');
    $domain = get_option('blogcopilot_blog_domain', '');

    $payload = [
        'licenseKey' => $licenseKey,
        'domain' => $domain,
        'action' => 'getPhrases',
    ];

    $response = wp_remote_post($apiUrl, [
        'body' => wp_json_encode($payload, JSON_UNESCAPED_UNICODE),
        'headers' => ['Content-Type' => 'application/json'],
    ]);

    if (is_wp_error($response)) {
        // Handle the API error (display a message, log, etc.)
        wp_send_json_error(['message' => $response->get_error_message()]);
    } else {
        wp_send_json_success(json_decode(wp_remote_retrieve_body($response), true));
    }
    wp_die();
}
add_action('wp_ajax_blogcopilot_get_phrases', 'blogcopilot_io_get_phrases');

function blogcopilot_io_check_phrase_exists($phrase) {
    $apiUrl = get_option('blogcopilot_api_url', '') . '/api-endpoint-phrases.php';
    $licenseKey = get_option('blogcopilot_license_number', '');
    $domain = get_option('blogcopilot_blog_domain', '');

    $payload = [
        'action' => 'getPhraseIdByName',
        'phrase' => $phrase,
        'licenseKey' => $licenseKey,
        'domain' => $domain,
    ];

    $response = wp_remote_post($apiUrl, [
        'body' => wp_json_encode($payload, JSON_UNESCAPED_UNICODE),
        'headers' => ['Content-Type' => 'application/json'],
    ]);

    if (is_wp_error($response)) {
        // Handle the API error (display a message, log, etc.)
        wp_die(esc_attr($response->get_error_message()), 'API Error', ['back_link' => true]);
    } else {
        $data = json_decode(wp_remote_retrieve_body($response), true);
        return $data; // Return the entire API response
    }
}

function blogcopilot_io_create_phrase($title, $category_id, $post_id, $status) {
    $apiUrl = get_option('blogcopilot_api_url') . '/api-endpoint-phrases.php';
    $licenseKey = get_option('blogcopilot_license_number');
    $domain = get_option('blogcopilot_blog_domain');
    $blog_location = get_option('blogcopilot_blog_location');

    $payload = [
        'action' => 'createPhrase',
        'phrase' => $title,
        'status' => $status,
        'categoryId' => $category_id,
        'domain' => $domain,
        'licenseKey' => $licenseKey,        
        'blogLocation' => $blog_location,        
        'WordPressPostId' => $post_id
    ];

    $response = wp_remote_post($apiUrl, [
        'body' => wp_json_encode($payload, JSON_UNESCAPED_UNICODE),
        'headers' => ['Content-Type' => 'application/json'],
    ]);

    if (is_wp_error($response)) {
        // Handle the API error (display a message, log, etc.)
        wp_die(esc_attr($response->get_error_message()), 'API Error', ['back_link' => true]);
    } else {
        return json_decode(wp_remote_retrieve_body($response), true);
    }
}
function blogcopilot_io_create_phrase_full($title, $article_title, $category_id, $status) {
    $apiUrl = get_option('blogcopilot_api_url') . '/api-endpoint-phrases.php';
    $licenseKey = get_option('blogcopilot_license_number');
    $domain = get_option('blogcopilot_blog_domain');
    $blog_location = get_option('blogcopilot_blog_location');

    $payload = [
        'action' => 'createPhraseFull',
        'phrase' => $title,
        'articleTitle' => $article_title,
        'status' => $status,
        'categoryId' => $category_id,
        'domain' => $domain,
        'licenseKey' => $licenseKey,        
        'blogLocation' => $blog_location
    ];

    $response = wp_remote_post($apiUrl, [
        'body' => wp_json_encode($payload, JSON_UNESCAPED_UNICODE),
        'headers' => ['Content-Type' => 'application/json'],
    ]);

    if (is_wp_error($response)) {
        // Handle the API error (display a message, log, etc.)
        wp_die(esc_attr($response->get_error_message()), 'API Error', ['back_link' => true]);
    } else {
        return json_decode(wp_remote_retrieve_body($response), true);
    }
}

function  blogcopilot_io_phrase_update($phrase_id, $phrase, $status, $WordPressPostId = null) {
    $apiUrl = get_option('blogcopilot_api_url', '');
    $licenseKey = get_option('blogcopilot_license_number', '');
    $domain = get_option('blogcopilot_blog_domain', '');
    $updateUrl = $apiUrl . '/api-endpoint-phrases.php'; // Assuming a separate endpoint for updating
    if ($WordPressPostId >= 0) {
        if ($WordPressPostId == 0) {
            $payload = [
                'action' => 'update',
                'phraseId' => $phrase_id,
                'phrase' => $phrase,
                'licenseKey' => $licenseKey,
                'domain' => $domain,
                'status' => $status,
                'WordPressPostId' => 'null'
            ];    
        } else {
            $payload = [
                'action' => 'update',
                'phraseId' => $phrase_id,
                'phrase' => $phrase,
                'licenseKey' => $licenseKey,
                'domain' => $domain,
                'status' => $status,
                'WordPressPostId' => $WordPressPostId
            ];            
        }
    } else {
        $payload = [
            'action' => 'update',
            'phraseId' => $phrase_id,
            'phrase' => $phrase,
            'licenseKey' => $licenseKey,
            'domain' => $domain,
            'status' => $status
        ];
    }

    $response = wp_remote_post($updateUrl, [
        'body' => wp_json_encode($payload, JSON_UNESCAPED_UNICODE),
        'headers' => ['Content-Type' => 'application/json'],
    ]);        

    $body = wp_remote_retrieve_body($response); // Get the response body
    $data = json_decode($body, true);

    $previously_linked_post_id = get_post_id_by_phrase_id($phrase_id);
    // If the phrase update was successful and it was previously linked to another post
    if ($data['status'] === 'Success' && $previously_linked_post_id) {
        // Remove the phrase from the previously linked post's meta
        $phrases_meta = get_post_meta($previously_linked_post_id, 'blogcopilot_phrases', true);
        $phrases_data = json_decode($phrases_meta, true);
    
        $updated_phrases_data = array_filter($phrases_data, function($phrase) use ($phrase_id) {
            return $phrase['id'] != $phrase_id;
        });
    
        if (empty($updated_phrases_data)) {
            delete_post_meta($previously_linked_post_id, 'blogcopilot_phrases');
        } else {
            update_post_meta($previously_linked_post_id, 'blogcopilot_phrases', wp_json_encode($updated_phrases_data, JSON_UNESCAPED_UNICODE));
        }        
    }

    return $data; 
}

// Helper function to get the post ID linked to a phrase ID
function get_post_id_by_phrase_id($phrase_id) {
    global $wpdb;

    $posts_with_phrases = $wpdb->get_results("
        SELECT post_id, meta_value
        FROM $wpdb->postmeta
        WHERE meta_key = 'blogcopilot_phrases'
    ");

    foreach ($posts_with_phrases as $post) {
        $phrases_data = json_decode($post->meta_value, true);
        foreach ($phrases_data as $phrase) {
            if ($phrase['id'] == $phrase_id) {
                return $post->post_id;
            }
        }
    }

    return null; // Phrase not linked to any post
}

function blogcopilot_io_add_linking_subphrases_ajax_handler() {
    // Verify the nonce
    check_ajax_referer('blogcopilot_generate_links_nonce', 'nonce'); 

    // Retrieve parameters from the AJAX request
    $selectedKeywords = isset($_POST['selectedKeywords']) ? $_POST['selectedKeywords'] : []; 
    $phraseId = intval($_POST['phraseId']); 
    $phraseCategory = sanitize_text_field($_POST['phraseCategory']);
    $permalink = urlencode(esc_url_raw(get_permalink(intval($_POST['wordpressId']))));

    $apiUrl = get_option('blogcopilot_api_url', '').'/api-endpoint-phrases.php';
    $licenseKey = get_option('blogcopilot_license_number', '');
    $domain = get_option('blogcopilot_blog_domain', '');
    $blog_description = get_option('blogcopilot_blog_description', '');    
    $language = get_option('blogcopilot_blog_lang', 'English');  

    $payload = [
        'action' => 'createSubphrases',
        'phraseId' => $phraseId,
        'phrases' => $selectedKeywords,
        'categoryId' => $phraseCategory,
        'language' => $language,
        'blog_description' => $blog_description,
        'licenseKey' => $licenseKey,
        'link_to' => $permalink,
        'domain' => $domain,
    ];

    $response = wp_remote_post($apiUrl, [
        'body' => wp_json_encode($payload, JSON_UNESCAPED_UNICODE),
        'headers' => ['Content-Type' => 'application/json'],
    ]);        


    if (is_wp_error($response)) { 
        // Handle the error from the wp_remote_post call itself
        wp_send_json_error(['message' => $response->get_error_message()]);
    } else {
        $body = wp_remote_retrieve_body($response); 
        $api_response = json_decode($body, true); // Decode the API's JSON response

        if ($api_response['status'] === 'Success') {
            wp_send_json_success($api_response); // Send the entire successful API response
        } else {
            wp_send_json_error($api_response); // Send the error response from the API
        }
    }

    wp_die(); 
}
add_action('wp_ajax_blogcopilot_io_add_linking_subphrases', 'blogcopilot_io_add_linking_subphrases_ajax_handler');

function blogcopilot_io_get_subphrases_ajax_handler() {
    check_ajax_referer('blogcopilot_get_subphrases_nonce', 'nonce');

    $phraseId = intval($_POST['phraseId']);

    $apiUrl = get_option('blogcopilot_api_url', '');
    $licenseKey = get_option('blogcopilot_license_number', '');
    $domain = get_option('blogcopilot_blog_domain', '');
    $payload = [
        'action' => 'getSubphrases',
        'phraseId' => $phraseId,
        'licenseKey' => $licenseKey,
        'domain' => $domain,
    ];
    
    $response = wp_remote_post($apiUrl.'/api-endpoint-phrases.php', [
        'body' => wp_json_encode($payload, JSON_UNESCAPED_UNICODE),
        'headers' => ['Content-Type' => 'application/json'],
    ]);        

    $body = wp_remote_retrieve_body($response); // Get the response body

    $subphrases = json_decode($body, true);

    // Add Edit and View links to each subphrase (if applicable)
    foreach ($subphrases as &$subphrase) {
        if ($subphrase['WordPressPostID']) {
            $post = get_post($subphrase['WordPressPostID']);
            if ($post) {
                $subphrase['editLink'] = get_edit_post_link($subphrase['WordPressPostID']);
                $subphrase['viewLink'] = get_permalink($subphrase['WordPressPostID']);
            }
        }
    }

    wp_send_json_success($subphrases);
    wp_die();
}
add_action('wp_ajax_blogcopilot_io_get_subphrases', 'blogcopilot_io_get_subphrases_ajax_handler');

function blogcopilot_io_save_phrases_and_generate_articles_ajax_handler() {
    // Verify the nonce
    check_ajax_referer('blogcopilot_generate_phrases_nonce', 'nonce'); 

    // Retrieve parameters from the AJAX request
    $selectedPhrases = isset($_POST['selectedPhrases']) ? $_POST['selectedPhrases'] : []; 
    foreach ($selectedPhrases as $phraseData) {
        $phrase = sanitize_text_field($phraseData['phrase']);
        $title = sanitize_text_field($phraseData['title']);

        // Get the category ID (you might need to adjust this based on your logic)
        $category_id = blogcopilot_io_get_category_id_by_name($phrase); // You'll need to implement this function

        // Call the API to create the phrase and generate the full article
        $response = blogcopilot_io_create_phrase_full($phrase, $title, $category_id, 'AI Published');

        if ($response['status'] !== 'Success') {
            // Handle API error (you might want to log it or send a more specific error response)
            wp_send_json_error(['message' => 'Error creating phrase or generating article: ' . $response['message']]);
            wp_die();
        }
    }

    wp_send_json_success(['message' => 'Phrases saved and articles are being generated.']);
    wp_die();
}
add_action('wp_ajax_blogcopilot_io_save_phrases_and_generate_articles', 'blogcopilot_io_save_phrases_and_generate_articles_ajax_handler');

function blogcopilot_io_get_category_id_by_name($phrase_name) {
    $categories = get_categories(array('hide_empty' => false));
    $category_data = array();
    foreach ($categories as $category) {
        $category_data[] = array(
            'id' => $category->term_id,
            'name' => $category->name
        );
    }

    $apiUrl = get_option('blogcopilot_api_url', '') . '/api-endpoint-get-category-id.php';
    $licenseKey = get_option('blogcopilot_license_number', '');
    $domain = get_option('blogcopilot_blog_domain', '');
    $language = get_option('blogcopilot_blog_lang', 'English');      

    $payload = [
        'phrase' => $phrase_name,
        'categories' => $category_data,
        'language' => $language,        
        'licenseKey' => $licenseKey,
        'domain' => $domain,
    ];

    $response = wp_remote_post($apiUrl, [
        'body' => wp_json_encode($payload, JSON_UNESCAPED_UNICODE),
        'headers' => ['Content-Type' => 'application/json'],
    ]);

    if (is_wp_error($response)) {
        return 0; // Or another default value if the API call fails
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    // 4. Return the suggested category ID or handle errors
    if ($data['status'] === 'Success' && isset($data['categoryId'])) {
        return $data['categoryId'];
    } else {
        return 0; // Or another default value
    }
}
?>