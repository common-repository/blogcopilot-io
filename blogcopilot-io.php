<?php
/**
 * @wordpress-plugin
 * Plugin Name:       BlogCopilot.io
 * Plugin URI:        https://blogcopilot.io/features/
 * Description:       AI-powered companion for blogging success, effortlessly generating SEO-optimized posts and images to captivate your audience. 
 * Version:           1.3.3
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            BlogCopilot
 * Author URI:        https://blogcopilot.io/
 * Text Domain:       blogcopilot-io
 * License:           GPLv3
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly
if (!defined('WPINC')) exit; // Exit if accessed directly

define( 'BLOGCOPILOT_PLUGIN_NAME_VERSION', '1.3.3' );

require_once plugin_dir_path(__FILE__) . 'layout/header.php';
require_once plugin_dir_path(__FILE__) . 'layout/top-nav.php';
require_once plugin_dir_path(__FILE__) . 'layout/footer.php';

require_once plugin_dir_path(__FILE__) . 'do-ajax-calls.php';

require_once plugin_dir_path(__FILE__) . 'page-main.php';
require_once plugin_dir_path(__FILE__) . 'page-phrase-mgmt.php';
require_once plugin_dir_path(__FILE__) . 'page-create-post.php';
require_once plugin_dir_path(__FILE__) . 'page-create-bulk.php';
require_once plugin_dir_path(__FILE__) . 'page-jobs.php';
require_once plugin_dir_path(__FILE__) . 'page-rankings.php';
require_once plugin_dir_path(__FILE__) . 'page-settings.php';
require_once plugin_dir_path(__FILE__) . 'page-help.php';

function blogcopilot_io_activate() {
    // update post meta values into singe post - multiple phrases setting
    global $wpdb;

    $posts_with_old_meta = $wpdb->get_results("
        SELECT post_id, meta_value 
        FROM $wpdb->postmeta 
        WHERE meta_key = 'blogcopilot_phrase_id'
    ");

    foreach ($posts_with_old_meta as $post) {
        $phrase_id = $post->meta_value;
        if (is_numeric($phrase_id) && $phrase_id > 0) {
            $phrase_name = get_post_meta($post->post_id, 'blogcopilot_phrase_name', true);

            $new_meta_data = [['id' => $phrase_id, 'name' => $phrase_name]];

            update_post_meta($post->post_id, 'blogcopilot_phrases', wp_json_encode($new_meta_data, JSON_UNESCAPED_UNICODE));
            delete_post_meta($post->post_id, 'blogcopilot_phrase_id');
            delete_post_meta($post->post_id, 'blogcopilot_phrase_name');
        } else {
            delete_post_meta($post->post_id, 'blogcopilot_phrase_id');
            delete_post_meta($post->post_id, 'blogcopilot_phrase_name');           
        }
    }

    // Get the current user's email
    $current_user = wp_get_current_user();
    $user_email = $current_user->user_email;
    $domain = wp_parse_url(home_url(), PHP_URL_HOST);

    $license_url_api = "https://api.blogcopilot.io/";
    $languageCode = substr(get_locale(), 0, 2);
    $wordpress_version = get_bloginfo('version'); 
    $plugin_version = BLOGCOPILOT_PLUGIN_NAME_VERSION; 

        // Prepare the data for the API call
    $body = array(
        'domain' => $domain,
        'contactEmail' => $user_email,
        'wordpressVersion' => $wordpress_version,
        'pluginVersion' => $plugin_version
    );

    // API request arguments
    $args = array(
        'body'        => wp_json_encode($body, JSON_UNESCAPED_UNICODE),
        'headers'     => array('Content-Type' => 'application/json'),
        'method'      => 'POST',
        'data_format' => 'body'
    );

    // Call the API
    $response = wp_remote_post($license_url_api.'api-endpoint-license-generate.php', $args);

    // Check for a valid response
    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();

        // Handle the error, e.g., show a message to the user
        set_transient('blogcopilot_activation_error', "Activation failed: $error_message", 10);
        wp_die(esc_html($error_message), 'Plugin Activation Error', array('back_link' => true)); // Die with a back link to plugins page
    }

    $response_body = wp_remote_retrieve_body($response);

    $response_body = json_decode($response_body, true);

    if ($response_body['status'] === 'success') {
        // Store the API URL in WordPress options
        update_option('blogcopilot_api_url', $response_body['data']['ApiUrl']);
        update_option('blogcopilot_license_number', $response_body['data']['LicenseKey']);
        update_option('blogcopilot_blog_domain', $domain);
        update_option('blogcopilot_blog_title', get_bloginfo('name'));
        update_option('blogcopilot_license_plan', $response_body['data']['PlanName']);

        switch ($languageCode) {
            case "pl":
                update_option('blogcopilot_blog_lang', "Polish");
                break;
            case "es":
                update_option('blogcopilot_blog_lang', "Spanish");
                break;
            case "de":
                update_option('blogcopilot_blog_lang', "German");
                break;
            case "fr":
                update_option('blogcopilot_blog_lang', "French");
                break;
            case "pt":
                update_option('blogcopilot_blog_lang', "Portuguese");
                break;
            case "ru":
                update_option('blogcopilot_blog_lang', "Russian");
                break;
            case "it":
                update_option('blogcopilot_blog_lang', "Italian");
                break;
            case "id":
                update_option('blogcopilot_blog_lang', "Indonesian");
                break;
            case "ja":
                update_option('blogcopilot_blog_lang', "Japanese");
                break;
            case "nl":
                update_option('blogcopilot_blog_lang', "Dutch");
                break;
            default:
                update_option('blogcopilot_blog_lang', "English");
                break;
        }
    } elseif ($response_body['status'] === 'error') {
        $error_message = $response_body['data']['message'];

        // Set the transient message for display
        set_transient('blogcopilot_activation_error', "Activation failed: $error_message", 10);
        wp_die(esc_html($response_body), 'Plugin Activation Error', array('back_link' => true)); // Die with a back link to plugins page     
    } else {
        // Handle non-200 response codes
        set_transient('blogcopilot_activation_error', "Activation failed: Received HTTP error", 10);
        wp_die(esc_html($response_body), 'Plugin Activation Error', array('back_link' => true)); // Die with a back link to plugins page      
    }
}

// Register the activation hook
register_activation_hook(__FILE__, 'blogcopilot_io_activate');

// Hook into the plugin deactivation
register_deactivation_hook(__FILE__, 'blogcopilot_io_deactivate');

// Cleanup function
function blogcopilot_io_deactivate() {
    $timestamp = wp_next_scheduled('blogcopilot_io_cron_autopublish');
    wp_unschedule_event($timestamp, 'blogcopilot_io_cron_autopublish');
}

function blogcopilot_display_activation_message() {
    if ($error_message = get_transient('blogcopilot_activation_error')) {
        ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html($error_message); ?></p>
        </div>
        <?php
        delete_transient('blogcopilot_activation_error');
    }
}
add_action('admin_notices', 'blogcopilot_display_activation_message');

add_action('init', 'blogcopilot_io_register_custom_cron_schedule');
add_action('blogcopilot_io_cron_autopublish', 'blogcopilot_io_cron_autopublish_function');
function blogcopilot_io_register_custom_cron_schedule() {
    // Schedule an event unless already scheduled
    if (!wp_next_scheduled('blogcopilot_io_cron_autopublish')) {
        wp_schedule_event(time(), 'hourly', 'blogcopilot_io_cron_autopublish');
    }
}

function blogcopilot_io_find_menu_position($start_position = 3) {
    global $menu;
    $position = $start_position;

    while (isset($menu[$position])) {
        $position++;
    }

    return $position;
}

add_action('admin_menu', 'blogcopilot_io_menu');

function blogcopilot_io_menu() {
    $icon_url = plugins_url('img/blogcopilotio-icon.png', __FILE__);
    $position = blogcopilot_io_find_menu_position(); // Start searching from position 3

    add_menu_page(
        'BlogCopilot',               // Page Title
        'BlogCopilot',               // Menu Title
        'edit_posts',                // Capability
        'blogcopilot-io',            // Menu Slug
        'blogcopilot_io_main_page',  // Function
        $icon_url,                   // Custom Icon URL
        $position                    // Dynamic Position
    );

    add_submenu_page(
        'blogcopilot-io',
        'Phrase Management',
        'Phrase Management',
        'edit_posts',
        'blogcopilot-phrase-mgmt',
        'blogcopilot_io_phrase_mgmt'
    );

    add_submenu_page(
        'blogcopilot-io',
        'Create Single Post',
        'Create Single Post',
        'edit_posts',
        'blogcopilot-create-post',
        'blogcopilot_io_create_post_page'
    );

    add_submenu_page(
        'blogcopilot-io',
        'Create Multiple Posts',
        'Create Multiple Posts',
        'edit_posts',
        'blogcopilot-mass-creation',
        'blogcopilot_io_mass_creation_page'
    );

    add_submenu_page(
        'blogcopilot-io',
        'Posts in Progress',
        'Posts in Progress',
        'edit_posts',
        'blogcopilot-job-status',
        'blogcopilot_io_job_status_page'
    );

    add_submenu_page(
        'blogcopilot-io', 
        'View Results',
        'View Results',
        'edit_posts',
        'blogcopilot-view-results',
        'blogcopilot_io_view_results_page'
    );    

    add_submenu_page(
        'blogcopilot-io', 
        'SEO Features',
        'SEO Features',
        'edit_posts',
        'blogcopilot-view-rankings',
        'blogcopilot_io_keyword_rankings_page'
    );    
        
    add_submenu_page(
        'blogcopilot-io',
        'Plugin Settings',
        'Plugin Settings',
        'manage_options',
        'blogcopilot-settings',
        'blogcopilot_io_settings_page'
    );

    add_submenu_page(
        'blogcopilot-io',
        'Help',
        'Help',
        'edit_posts',
        'blogcopilot-help',
        'blogcopilot_io_help_page'
    );
}

function blogcopilot_io_main_page() {
    blogcopilot_io_header();
    blogcopilot_io_show_nav();
    blogcopilot_io_check_license();
    blogcopilot_io_main_page_content();
    blogcopilot_io_footer();
    blogcopilot_publish_and_update_phrases();
}

function blogcopilot_io_help_page() {
    blogcopilot_io_header();
    blogcopilot_io_show_nav();
    blogcopilot_io_help_page_content();
    blogcopilot_io_footer();
}

function blogcopilot_io_phrase_mgmt() {
    blogcopilot_io_header();
    blogcopilot_io_show_nav();
    if (blogcopilot_io_check_license() == true) {
        blogcopilot_io_create_phrase_mgmt_content();
    }
    blogcopilot_io_footer();
}

function blogcopilot_io_create_post_page() {
    blogcopilot_io_header();
    blogcopilot_io_show_nav();
    if (blogcopilot_io_check_license() == true) {
        blogcopilot_io_create_post_page_content();
    }
    blogcopilot_io_footer();
}

function blogcopilot_io_mass_creation_page() {
    blogcopilot_io_header();
    blogcopilot_io_show_nav();
    if (blogcopilot_io_check_license() == true) {
        blogcopilot_io_mass_creation_page_content();
    }
    blogcopilot_io_footer();
}   

function blogcopilot_io_job_status_page() {
    blogcopilot_io_header();
    blogcopilot_io_show_nav();
    if (blogcopilot_io_check_license() == true) {
        blogcopilot_io_job_status_page_content();
    }
    blogcopilot_io_footer();
}

function blogcopilot_io_view_results_page() {
    blogcopilot_io_header();
    blogcopilot_io_show_nav();
    if (blogcopilot_io_check_license() == true) {
        blogcopilot_io_view_results_page_content();
    }
    blogcopilot_io_footer();
}

function blogcopilot_io_keyword_rankings_page() {
    blogcopilot_io_header();
    blogcopilot_io_show_nav();
    if (blogcopilot_io_check_license() == true) {
        blogcopilot_io_keyword_rankings_page_content();
    }
    blogcopilot_io_footer();
}

function blogcopilot_io_settings_page() {
    blogcopilot_io_header();
    blogcopilot_io_show_nav();
    blogcopilot_io_settings_page_content();
    blogcopilot_io_footer();
}   
function blogcopilot_io_add_custom_box() {
    add_meta_box(
        'blogcopilot_io_box', 
        'BlogCopilot.io',
        'blogcopilot_io_render_box',
        'post',
        'normal',
        'low'
    );
}
add_action('add_meta_boxes', 'blogcopilot_io_add_custom_box');
function blogcopilot_io_render_box($post) {
    $phrases_meta = get_post_meta($post->ID, 'blogcopilot_phrases', true);

    // Check if the meta value exists and is not empty
    if (!empty($phrases_meta)) {
        $initial_tags = json_decode($phrases_meta, true);
    
        $initial_tags = array_filter($initial_tags, function($phrase) {
            return isset($phrase['id']) && isset($phrase['name']);
        });

        // Prepare the initial tags for Tagify
        $initial_tags_array = array_map(function($phrase) {
            return ['value' => $phrase['name'], 'id' => $phrase['id']];
        }, $initial_tags);

        $initial_tags_json = wp_json_encode($initial_tags_array, JSON_UNESCAPED_UNICODE);
    } else {
        // If the meta value doesn't exist or is empty, set $initial_tags_json to an empty array
        $initial_tags_json = '[]';
    }

    // Initialize internal_links and serp_rank
    $internal_links = 0;
    $serp_rank = 0;

    wp_nonce_field('blogcopilot_io_save_metabox', 'blogcopilot_io_nonce');

    wp_enqueue_script('blogcopilot-tagify', plugins_url('assets/js/tagify.js', __FILE__), array('jquery'), '1.0', true);
    wp_enqueue_script('blogcopilot-tagify-poly', plugins_url('assets/js/tagify.polyfills.min.js', __FILE__), array('jquery'), '1.0', true);
    wp_enqueue_style('blogcopilot-tagify-css', plugins_url('assets/css/tagify.css', __FILE__), array(), null, 'all');
    wp_enqueue_script('blogcopilot-select-phrase', plugins_url('assets/js/blogcopilot-select-phrase.js', __FILE__), array('jquery'), '1.0', true);    
    ?>

    <div class="row mb-3">
        <div class="col-md-2 text-md-end">
            <label for="blogcopilot_phrase_name_display"><strong>Phrase(s) Assigned:</strong></label> 
        </div>
        <div class="col-md-10">
        <input name='tags-outside' class='tagify--outside' value='<?php echo esc_attr($initial_tags_json); ?>' placeholder='Write phrases to add to the Post'>

        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-2 text-md-end">
            <label><strong>Internal Linking Articles:</strong></label>
        </div>
        <div class="col-md-10">
            <div>
            <?php
                if ($internal_links > 0) {
                    echo '<span class="badge bg-success">'.esc_html($internal_links).'</span>';
                } else {
                    echo '<span class="badge bg-secondary">No links detected.</span>';
                }
            ?>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-2 text-md-end">
            <label><strong>SERP Rank:</strong></label>
        </div>
        <div class="col-md-10">
        <div>
            <?php
                if ($serp_rank > 0) {
                    echo '<span class="badge bg-success">'.esc_html($serp_rank).'</span>';
                } else {
                    echo '<span class="badge bg-secondary">No rankings detected.</span>';
                }
            ?>
            </div>            
        </div>
    </div>

    <?php
}

// More code for handling the functionality of each page goes here
add_action('admin_enqueue_scripts', 'blogcopilot_io_enqueue_scripts');
function blogcopilot_io_enqueue_scripts() {
    wp_enqueue_style('blogcopilot-custom', plugins_url('assets/css/blogcopilot.css', __FILE__), array(), null, 'all');
    wp_enqueue_style('google-fonts', 'https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700', array(), null);

    wp_enqueue_style('bootstrap-css', plugins_url('assets/css/bootstrap.min.css', __FILE__), array(), null, 'all');
    wp_enqueue_style('bootstrap-icons', plugins_url('assets/css/bootstrap-icons.min.css', __FILE__), array(), null, 'all');

    wp_enqueue_script('bootstrap-js', plugins_url('assets/js/bootstrap.bundle.min.js', __FILE__), array(), null, 'all');
        
    // optional scripts
    $current_screen = get_current_screen();
    if (isset($current_screen->id) && ($current_screen->id == 'blogcopilot_page_blogcopilot-phrase-mgmt')) {
        wp_enqueue_style('datatables-css', plugins_url('assets/css/datatables.min.css', __FILE__), array(), null, 'all');
        wp_enqueue_script('datatables-js', plugins_url('assets/js/datatables.min.js', __FILE__), array('jquery'), '1.0', true);    
        wp_enqueue_script('datatables-init', plugins_url('assets/js/blogcopilot-datatables.js', __FILE__), ['jquery', 'datatables-js'], null, true);           
    }   
    if (isset($current_screen->id) && (($current_screen->id == 'blogcopilot_page_blogcopilot-job-status') || ($current_screen->id == 'blogcopilot_page_blogcopilot-view-rankings') || ($current_screen->id == 'blogcopilot_page_blogcopilot-phrase-mgmt'))) {
        wp_enqueue_script('blogcopilot-events', plugins_url('assets/js/blogcopilot-events.js', __FILE__), array('jquery'), '1.0', true);
    }
    if (isset($current_screen->id) && ($current_screen->id == 'blogcopilot_page_blogcopilot-view-rankings')) {
        wp_enqueue_script('blogcopilot-seo-article', plugins_url('assets/js/blogcopilot-seo-article.js', __FILE__), array('jquery'), '1.0', true);

        wp_enqueue_style('datatables-css', plugins_url('assets/css/datatables.min.css', __FILE__), array(), null, 'all');
        wp_enqueue_script('datatables-js', plugins_url('assets/js/datatables.min.js', __FILE__), array('jquery'), '1.0', true);

        wp_enqueue_script('datatables-init', plugins_url('assets/js/blogcopilot-datatables.js', __FILE__), ['jquery', 'datatables-js'], null, true);
    }
    if (isset($current_screen->id) && ($current_screen->id == 'blogcopilot_page_blogcopilot-job-status')) {
        wp_enqueue_script('blogcopilot-jobs', plugins_url('assets/js/blogcopilot-jobs.js', __FILE__), array('jquery'), '1.0', true);        
    }      
    if (isset($current_screen->id) && ($current_screen->id == 'blogcopilot_page_blogcopilot-view-results')) {
        wp_enqueue_script('blogcopilot-images-in-bulk', plugins_url('assets/js/blogcopilot-images-in-bulk.js', __FILE__), array('jquery'), '1.0', true);
        $apiUrl = get_option('blogcopilot_api_url', '');
        wp_localize_script('blogcopilot-images-in-bulk', 'blogcopilotParams', array(
            'apiUrl' => $apiUrl,
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('blogcopilot_io_publish_post_action')            
        ));        
    }
    if (isset($current_screen->id) && ($current_screen->id == 'blogcopilot_page_blogcopilot-mass-creation')) {
        wp_enqueue_script('blogcopilot-bulk', plugins_url('assets/js/blogcopilot-bulk.js', __FILE__), array('jquery'), '1.0', true);
        $categories = get_categories(['hide_empty' => false]);
        $categories_data = array_map(function($category) {
            return array(
                'term_id' => $category->term_id,
                'name' => $category->name,
            );
        }, $categories);

        wp_localize_script('blogcopilot-bulk', 'bulkData', array(
            'categories' => $categories_data,
        ));        
    }                  
}

add_action( 'save_post', 'blogcopilot_io_update_phrase_on_post_save', 10, 3); 
function blogcopilot_io_update_phrase_on_post_save($post_id, $post, $update ) {
    if (!isset($_POST['blogcopilot_io_nonce']) || !wp_verify_nonce($_POST['blogcopilot_io_nonce'], 'blogcopilot_io_save_metabox')) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    $existing_phrases_meta = get_post_meta($post_id, 'blogcopilot_phrases', true);
    $existing_phrases_data = json_decode($existing_phrases_meta, true) ?: [];
    if (!is_array($existing_phrases_data)) {
        $existing_phrases_data = [];
    }

    $new_phrases_input = isset($_POST['tags-outside']) ? wp_unslash($_POST['tags-outside']) : '[]';
    $new_phrases_data = json_decode($new_phrases_input, true);
    if (!is_array($new_phrases_data)) {
        $new_phrases_data = [];
    }
    $new_phrases_data = array_map(function($phrase) {
        // If 'value' key exists, rename it to 'name'
        if (isset($phrase['value'])) {
            $phrase['name'] = $phrase['value'];
            unset($phrase['value']);
        }
        return $phrase;
    }, $new_phrases_data);
    $existing_phrase_ids = array_column($existing_phrases_data, 'id');
    $new_phrase_ids = array_column($new_phrases_data, 'id');

    $phrases_to_add = array_diff($new_phrase_ids, $existing_phrase_ids);
    $phrases_to_remove = array_diff($existing_phrase_ids, $new_phrase_ids);

    $new_status = ($post->post_status === 'publish') ? 'User Published' : 'Draft Available';

    // Add new phrases
    foreach ($phrases_to_add as $phrase_id) {
        // Fetch the phrase data using the ID
        $phrase = blogcopilot_io_phrase_get($phrase_id);

        if ($phrase['status'] == "Success") {
            $phrase_name = $phrase['phrases'][0]['Phrase']; // Assuming the API returns an array

            // Call the API to update the phrase status (linking it to the post)
            blogcopilot_io_phrase_update($phrase_id, $phrase_name, $new_status, $post_id);
        }
    }

    // Remove phrases
    foreach ($phrases_to_remove as $phrase_id) {
        // Fetch the phrase data using the ID
        $phrase = blogcopilot_io_phrase_get($phrase_id);

        if ($phrase['status'] == "Success") {
            $phrase_name = $phrase['phrases'][0]['Phrase'];
            blogcopilot_io_phrase_update($phrase_id, $phrase_name, 'No article', 0); 
        }
    }

    // Handle new phrases entered manually (phrases without an ID in new_phrases_data)
    $new_phrases_to_create = array_filter($new_phrases_data, function($phrase) {
        return !isset($phrase['id']) || $phrase['id'] === '' || $phrase['id'] === 0; // Check for missing or invalid ID
    });

    foreach ($new_phrases_to_create as $new_phrase) {
        $phrase_name = sanitize_text_field($new_phrase['name']);
        
        // New phrase, create it and link it to the post
        $post_categories = get_the_category($post_id);
        $category_id = !empty($post_categories) ? absint($post_categories[0]->term_id) : 0; 
        
        $data = blogcopilot_io_create_phrase($phrase_name, $category_id, $post_id, $new_status); 
        if ($data['status'] === 'Success') {
            if (count($data['phraseIds']) === 1) {
                // Single phrase added
                $phrase_id = $data['phraseIds'][0];
                $new_phrases_data[] = ['id' => $phrase_id, 'name' => $phrase_name];
            } else {
                // Multiple phrases added
                foreach ($data['phraseIds'] as $phrase_id) {
                    $new_phrases_data[] = ['id' => $phrase_id, 'name' => $phrase_name];
                }
            }
        }
    }
    
    $final_phrases_data = array_filter($existing_phrases_data, function($phrase) use ($phrases_to_remove) {
        return !in_array($phrase['id'], $phrases_to_remove);
    });
    $all_phrases_data = array_merge($final_phrases_data, $new_phrases_data);
    $unique_phrases_data = [];
    foreach ($all_phrases_data as $phrase) {
        if (isset($phrase['id']) && !empty($phrase['id']) && 
            !in_array($phrase['id'], array_column($unique_phrases_data, 'id'))) { // Check for uniqueness
            $unique_phrases_data[] = $phrase;
        }
    }

    // Update or delete the post meta
    if (!empty($unique_phrases_data)) {
        update_post_meta($post_id, 'blogcopilot_phrases', wp_json_encode($unique_phrases_data, JSON_UNESCAPED_UNICODE));
    } else {
        delete_post_meta($post_id, 'blogcopilot_phrases');
    }
    
}

?>