<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
if (!defined('WPINC')) exit; // Exit if accessed directly

function blogcopilot_io_call_dataforseo_api() {
    // Retrieve the current values from settings
    $license_number = get_option('blogcopilot_license_number', '');
    $api_url = get_option('blogcopilot_api_url', '');
    $blog_domain = get_option('blogcopilot_blog_domain', '');
    $blog_location = get_option('blogcopilot_blog_location', '2840');
    $blog_lang = get_option('blogcopilot_blog_lang', 'English');       

    $api_url = $api_url.'api-endpoint-seo-rankings.php';
    $api_params = array(
        'body' => wp_json_encode(array(
            'domainToCheck' => $blog_domain,   
            'licenseKey' => $license_number,
            'domain' => $blog_domain,
            'location' => $blog_location,
            'language' => $blog_lang            
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

function blogcopilot_io_call_seo_dashboard() {
    // Retrieve the current values from settings
    $license_number = get_option('blogcopilot_license_number', '');
    $api_url = get_option('blogcopilot_api_url', '');
    $blog_domain = get_option('blogcopilot_blog_domain', '');

    $api_url = $api_url.'api-endpoint-seo-dashboard.php';
    $api_params = array(
        'body' => wp_json_encode(array(
            'domainToCheck' => $blog_domain,   
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

function blogcopilot_io_call_keywords($action, $keywords = null, $keywordId = null, $location = null, $language = null) {
    // Retrieve the current values from settings
    $license_number = get_option('blogcopilot_license_number', '');
    $api_url = get_option('blogcopilot_api_url', '');
    $blog_domain = get_option('blogcopilot_blog_domain', '');

    $api_url = $api_url.'api-endpoint-seo-keywords.php';
    $api_params = array(
        'body' => wp_json_encode(array(
            'domainToCheck' => $blog_domain,          
            'licenseKey' => $license_number,
            'domain' => $blog_domain,
            'action' => $action,
            'keywords' => $keywords,
            'keywordId' => $keywordId,
            'location' => $location,
            'language' => $language
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

function blogcopilot_io_get_proposed_keywords_ajax_handler() {
    check_ajax_referer( 'blogcopilot_generate_links_nonce', 'nonce' ); 

    $keywordsToCheck = sanitize_text_field($_POST['keywords']);
    $location = get_option('blogcopilot_blog_location', '2840');
    $language = get_option('blogcopilot_blog_lang', 'English');  

    // 1. Get proposed keywords from your existing API call
    $proposedKeywords = blogcopilot_io_call_keywords('getProposedKeywords', $keywordsToCheck, null, $location, $language);

    // Check if paid plan
    if (isset($proposedKeywords['error'])) {
        wp_send_json_success($proposedKeywords['error']); // Re-index the array after filtering
        wp_die();        
    }

    // 2. Fetch existing post and page titles
    $existingTitles = get_posts(array(
        'post_type' => array('post', 'page'),
        'posts_per_page' => -1, // Get all
        'fields' => 'post_title' // Only fetch titles
    ));
    $existingTitles = array_map('strtolower', wp_list_pluck($existingTitles, 'post_title'));

    // 3. Fetch existing phrases and subphrases
    $apiUrl = get_option('blogcopilot_api_url', '') . '/api-endpoint-phrases.php';
    $licenseKey = get_option('blogcopilot_license_number', '');
    $domain = get_option('blogcopilot_blog_domain', '');

    $payload = [
        'licenseKey' => $licenseKey,
        'domain' => $domain,
        'action' => 'getPhrasesAll',
    ];

    $response = wp_remote_post($apiUrl, [
        'body' => wp_json_encode($payload, JSON_UNESCAPED_UNICODE),
        'headers' => ['Content-Type' => 'application/json'],
    ]);

    $body = wp_remote_retrieve_body($response);
    $phrasesData = json_decode($body, true);

    $existingPhrases = array();
    foreach ($phrasesData as $phraseData) {
        $existingPhrases[] = strtolower($phraseData['Phrase']);
    }

    // 4. Filter out duplicates
    $filteredKeywords = array_filter($proposedKeywords, function($keywordData) use ($existingTitles, $existingPhrases) {
        $keyword = strtolower($keywordData['Keyword']); 
        return !in_array($keyword, $existingTitles) && !in_array($keyword, $existingPhrases);
    });

    wp_send_json_success(array_values($filteredKeywords)); // Re-index the array after filtering
    wp_die(); 
}
add_action('wp_ajax_blogcopilot_io_get_proposed_keywords', 'blogcopilot_io_get_proposed_keywords_ajax_handler');

function blogcopilot_io_get_proposed_phrases_ajax_handler() {
   check_ajax_referer('blogcopilot_generate_phrases_nonce', 'nonce'); 

    $apiUrl = get_option('blogcopilot_api_url', '');
    $licenseKey = get_option('blogcopilot_license_number', '');
    $domain = get_option('blogcopilot_blog_domain', '');
    $blogDescription = get_option('blogcopilot_blog_description', ''); 
    $language = get_option('blogcopilot_blog_lang', 'English');  
    
    $payload = [
        'language' => $language,
        'description' => $blogDescription,
        'licenseKey' => $licenseKey,
        'domain' => $domain
    ];

    $response = wp_remote_post($apiUrl . '/api-endpoint-phrases-generate.php', [
        'body' => wp_json_encode($payload, JSON_UNESCAPED_UNICODE),
        'headers' => array('Content-Type' => 'application/json'),
        'timeout' => 120
    ]);        
    if (is_wp_error($response)) {
        return ['error' => true, 'message' => 'An error occurred while contacting the API.'];
    }
    $body = wp_remote_retrieve_body($response); 

    $apiResponse = json_decode($body, true);

    if ($apiResponse['status'] === 'Success') {
        // Extract only the phrase and title from each phraseData
        $simplifiedPhrases = array_map(function($phraseData) {
            return [
                'phrase' => $phraseData['phrase'],
                'title' => $phraseData['title']
            ];
        }, $apiResponse['phrases']);

        wp_send_json_success($simplifiedPhrases);
    } else {
        wp_send_json_error($apiResponse['message']);
    }

    wp_die();
}
add_action('wp_ajax_blogcopilot_io_get_proposed_phrases', 'blogcopilot_io_get_proposed_phrases_ajax_handler');

function blogcopilot_io_call_backlink($action) {
      // Retrieve the current values from settings
      $license_number = get_option('blogcopilot_license_number', '');
      $api_url = get_option('blogcopilot_api_url', '');
      $blog_domain = get_option('blogcopilot_blog_domain', '');
  
      $api_url = $api_url.'api-endpoint-seo-backlinks.php';
      $api_params = array(
          'body' => wp_json_encode(array(
              'domainToCheck' => $blog_domain,   
              'licenseKey' => $license_number,
              'domain' => $blog_domain,
              'action' => $action
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

function blogcopilot_io_call_competitors() {
      // Retrieve the current values from settings
      $license_number = get_option('blogcopilot_license_number', '');
      $api_url = get_option('blogcopilot_api_url', '');
      $blog_domain = get_option('blogcopilot_blog_domain', '');
      $blog_location = get_option('blogcopilot_blog_location', '2840');
      $blog_lang = get_option('blogcopilot_blog_lang', 'English');    

      $api_url = $api_url.'api-endpoint-seo-competitors.php';
      $api_params = array(
          'body' => wp_json_encode(array(
              'domainToCheck' => $blog_domain,   
              'licenseKey' => $license_number,
              'domain' => $blog_domain,
              'location' => $blog_location,
              'language' => $blog_lang
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

function blogcopilot_io_call_on_page_summary() {
      // Retrieve the current values from settings
      $license_number = get_option('blogcopilot_license_number', '');
      $api_url = get_option('blogcopilot_api_url', '');
      $blog_domain = get_option('blogcopilot_blog_domain', '');
  
      $api_url = $api_url.'api-endpoint-seo-analysis.php';
      $api_params = array(
          'body' => wp_json_encode(array(
              'domainToCheck' => $blog_domain,   
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
?>