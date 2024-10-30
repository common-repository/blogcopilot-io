<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
if (!defined('WPINC')) exit; // Exit if accessed directly

require_once plugin_dir_path(__FILE__) . 'do-api-calls.php';

function blogcopilot_io_create_post_page_content() {
    // Check if the form is submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['blogcopilot_create_form_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['blogcopilot_create_form_nonce'])), 'blogcopilot_create_form')) {
        // Handling regeneration request
        if (isset($_GET['regenerate'])) {
            $post_id_to_regenerate = intval($_GET['regenerate']);
            blogcopilot_io_handle_regenerate_form_submission($post_id_to_regenerate);
            return;
        }  else {
            blogcopilot_io_handle_form_submission();
            return;
        }
    }
?>
<div id="blogcopilot-create-form-div">
    <div id="blogcopilot-spinner-container">
        <div id="blogcopilot-spinner" class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <div class="mt-3 fs-4">
                Content generation can take up to 2-3 minutes, please be patient.
            </div>
        </div>
    </div>

    <div class="p-4 bg-light">
    <h4>Create Single Post</h4>

    <form method="POST" id="blogcopilot-create-form">
    <?php wp_nonce_field('blogcopilot_create_form', 'blogcopilot_create_form_nonce'); ?>        
    <div class="mb-3">
        <label class="form-label" for="title"><?php esc_html_e('Title', 'blogcopilot-io'); ?></label>
        <input type="text" class="form-control" id="title" name="title" required>
    </div>
    <div class="row">
        <div class="col-md-4">
            <div class="input-group">
            <label for="category" style="padding-right: 15px"><?php esc_html_e('Category', 'blogcopilot-io'); ?></label>
            <select name="category" id="category" class="form-control" required>
                <?php
                $categories = get_categories(array('hide_empty' => false));
                blogcopilot_io_display_categories($categories);
                ?>
            </select>
            </div>
        </div>
        <div class="col-md-4 px-4">
            <div class="form-check">
            <input class="form-check-input" type="checkbox" id="flexSwitchDraft" name="flexSwitchDraft">
            <label class="form-check-label" for="flexSwitchDraft">Save as draft (do not publish)</label>
            </div>
        </div>
        <div class="col-md-4">        
            <div class="mb-3">
            <button class="btn btn-info btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#additionalFields" aria-expanded="false" aria-controls="additionalFields" id="additionalFieldsButton">
                Show Optional Fields
            </button>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="form-check">
            <input class="form-check-input" type="checkbox" id="flexSwitchLive" name="flexSwitchLive">
            <label class="form-check-label" for="flexSwitchLive">Generate in real time (might not work on some hosting platforms)</label>
            </div>
        </div>
    </div>

    <!-- Additional fields (hidden by default) -->
    <div class="collapse" id="additionalFields">
        <div class="row">
        <hr/>
        </div>
        <div class="mb-3">    
            <div class="form-check">
            <input class="form-check-input" type="checkbox" value="yes" id="premiumCheck" name="premiumCheck">
            <label class="form-check-label" for="premiumCheck">Generate <strong>Premium</strong> Post (creation will take more time and results will be available in Posts in Progress)</label>
            </div>
        </div>
        <div id="premiumArticleLength" style="display:none;" class="mb-3">
            <label for="premiumArticleLengthSlider" class="form-label">Premium Post Length Target</label>
            <input type="range" class="form-range" id="premiumArticleLengthSlider" name="premiumArticleLengthSlider" min="500" max="5000" step="500" value="2500">
            <small id="premiumArticleLengthValue">2500 words</small>
        </div>
        <div class="row mb-3 align-items-center">
        <?php
            $blog_lang = get_option('blogcopilot_blog_lang', 'English');
            $show_language_select = get_option('blogcopilot_dynamic_lang_selection', '0');
            if ($show_language_select === '1'):
        ?>
            <div class="col-md-1">
                <label for="language" class="form-label"><?php esc_html_e('Language', 'blogcopilot-io'); ?></label>
            </div>
            <div class="col-md-11">
                <select name="language" id="language" class="form-select" required>
                    <option value="English" <?php echo ($blog_lang == "English") ? 'selected' : ''; ?>>English</option>
                    <option value="Spanish" <?php echo ($blog_lang == "Spanish") ? 'selected' : ''; ?>>Spanish</option>
                    <option value="German" <?php echo ($blog_lang == "German") ? 'selected' : ''; ?>>German</option>
                    <option value="French" <?php echo ($blog_lang == "French") ? 'selected' : ''; ?>>French</option>
                    <option value="Portuguese" <?php echo ($blog_lang == "Portuguese") ? 'selected' : ''; ?>>Portuguese</option>
                    <option value="Russian" <?php echo ($blog_lang == "Russian") ? 'selected' : ''; ?>>Russian</option>
                    <option value="Italian" <?php echo ($blog_lang == "Italian") ? 'selected' : ''; ?>>Italian</option>
                    <option value="Indonesian" <?php echo ($blog_lang == "Indonesian") ? 'selected' : ''; ?>>Indonesian</option>
                    <option value="Japanese" <?php echo ($blog_lang == "Japanese") ? 'selected' : ''; ?>>Japanese</option>
                    <option value="Polish" <?php echo ($blog_lang == "Polish") ? 'selected' : ''; ?>>Polish</option>
                    <option value="Dutch" <?php echo ($blog_lang == "Dutch") ? 'selected' : ''; ?>>Dutch</option>
                </select>
            </div>
        <?php else: ?>
            <div class="col-md-12">
            <p>Article will be in <?php echo esc_html($blog_lang); ?>. (please enable language selection in the Settings menu if you want to select different language).</p>
            </div>
        <?php endif; ?>

        </div>
        <div class="row mb-3 align-items-center">
            <div class="col-md-1">
                <label for="style" style="padding-right: 10px"><?php esc_html_e('Style', 'blogcopilot-io'); ?></label>
            </div>
            <div class="col-md-11">
                <select name="style" id="style" class="form-control">
                    <option value="casual">Casual</option>
                    <option value="formal">Formal</option>
                    <option value="conversational">Conversational</option>
                    <option value="technical">Technical</option>
                    <option value="humorous">Humorous</option>
                </select>
            </div>
        </div>        
        <div class="mb-3">
            <label for="keywords" class="form-label"><?php esc_html_e('Keywords (separated by ,)', 'blogcopilot-io'); ?></label>
            <textarea class="form-control" id="keywords" name="keywords" rows="3"></textarea>
        </div>
        <div class="mb-3">
            <label for="content_description" class="form-label"><?php esc_html_e('Additional suggestions for content generation', 'blogcopilot-io'); ?></label>
            <textarea class="form-control" id="content_description" name="content_description" rows="3"></textarea>
        </div>
    </div>

    <div class="mb-3">    
        <button type="submit" class="btn btn-primary"><?php esc_html_e('Generate', 'blogcopilot-io'); ?></button>
    </div>

    </form>
    </div>
<?php 
    wp_enqueue_script('blogcopilot-create-post', plugins_url('assets/js/blogcopilot-create-post-1.js', __FILE__), array('jquery'), '1.0', true);
?>
</div>
<?php
}

function blogcopilot_io_handle_form_submission() {
    if (!isset($_POST['blogcopilot_create_form_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['blogcopilot_create_form_nonce'])), 'blogcopilot_create_form')) {
        wp_die('Nonce verification failed, unauthorized submission.');
    }

    $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
    $category_id = isset($_POST['category']) ? intval($_POST['category']) : 0;
    $language = isset($_POST['language']) ? sanitize_text_field($_POST['language']) : get_option('blogcopilot_blog_lang', 'English');
    $keywords = isset($_POST['keywords']) ? sanitize_text_field($_POST['keywords']) : '';
    $content_description = isset($_POST['content_description']) ? sanitize_text_field($_POST['content_description']) : '';
    $style = isset($_POST['style']) ? sanitize_text_field($_POST['style']) : '';
    $premium = isset($_POST['premiumCheck']) ? 'yes' : 'no';
    $publish_as_draft = isset($_POST['flexSwitchDraft']) ? 'draft' : 'publish';
    $live = isset($_POST['flexSwitchLive']) ? 'yes' : 'no';
    $conspect = 'no';    
    $articleLength = isset($_POST['premiumArticleLengthSlider']) ? intval($_POST['premiumArticleLengthSlider']) : 2500; // Default to 2500 if not set

    // Call API
    $api_response = blogcopilot_io_call_api_generate_content($title, $category_id, $language, $keywords, $content_description, $style, $premium, $live, $conspect, $articleLength);

    // Check for errors in the API response
    if (isset($api_response['error']) && $api_response['error']) {
        echo '<div class="alert alert-secondary fade show my-2" role="alert">
        <span class="alert-icon"><i class="bi bi-exclamation-diamond"></i> </span><span class="alert-text">Cannot generate new post: ' . esc_html($api_response['error']) . '</span><span>';

        if (isset($api_response['monthlyGeneratedArticles'])) {
            echo '<br/><br/>Monthly Generated Articles: ' . esc_html($api_response['monthlyGeneratedArticles']);
        }
        if (isset($api_response['monthlyArticleQuota'])) {
            echo '<br/>Monthly Article Quota: ' . esc_html($api_response['monthlyArticleQuota']);
        }
        if (isset($api_response['remainingArticleQuota'])) {
            echo '<br/>Remaining Article Quota: ' . esc_html($api_response['remainingArticleQuota']);
            echo '<br/><br/><strong>Upgrade to higher version to increase limits!</strong>';
        }
        echo '</span></div>';

        return; 
    }

    if ($premium == "no") {
        if ($live == "yes") {
            // Create new post
            $post_id = blogcopilot_io_create_new_post($category_id, $title, $api_response['article'], $publish_as_draft);

            // Update SEO and do summarization (via Ajax, as that takes extra time)
            wp_enqueue_script('blogcopilot-seo-update', plugins_url('assets/js/blogcopilot-seo-update.js', __FILE__), array('jquery'), '1.0', true);
            wp_localize_script('blogcopilot-seo-update', 'blogcopilotParams', array(
                'postId' => wp_json_encode($post_id, JSON_UNESCAPED_UNICODE),
                'title' => wp_json_encode($title, JSON_UNESCAPED_UNICODE),
                'article' => wp_json_encode($api_response['article'], JSON_UNESCAPED_UNICODE),
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('blogcopilot_io_update_post_nonce')
            )); 

            blogcopilot_io_display_confirmation($post_id, $title, $language, $keywords, $content_description, $style);
            // Display generated images
            blogcopilot_io_display_generated_images($post_id, $title);
        } else {
            // Update the option with the last job group ID and Display message with jobid
            update_option('blogcopilot_job_group_id', $api_response['jobGroupId']); 
            
            echo '
            <div class="alert alert-success alert-dismissible fade show my-2" role="alert">
            <span class="alert-icon"><i class="ni ni-like-2"></i> </span><span class="alert-text">Posts creation has just started. Check the results on the status page <a href="'.esc_url(admin_url('admin.php?page=blogcopilot-view-results&jobGroupId='.$api_response['jobGroupId'])).'" class="btn btn-primary btn-sm" style="margin-bottom:0">here</a>.</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
            </button>
            <span class="alert-text"><br/><br/>Post creation can take anywhere from 5 to 50 minutes - you can also see status of all work in progress at <a href="'.esc_url(admin_url('admin.php?page=blogcopilot-job-status')).'">this page</a>.</span>
            </div>
            ';               
        }
    } else {
        // Update the option with the last job group ID and Display message with jobid
        update_option('blogcopilot_job_group_id', $api_response['jobGroupId']);

        echo '
        <div class="alert alert-success alert-dismissible fade show my-2" role="alert">
        <span class="alert-icon"><i class="ni ni-like-2"></i> </span><span class="alert-text">Posts creation has just started. Check the results on the status page <a href="'.esc_url(admin_url('admin.php?page=blogcopilot-view-results&jobGroupId='.$api_response['jobGroupId'])).'" class="btn btn-primary btn-sm" style="margin-bottom:0">here</a>.</span>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
        </button>
        <span class="alert-text"><br/><br/>Post creation can take anywhere from 5 to 50 minutes - you can also see status of all work in progress at <a href="'.esc_url(admin_url('admin.php?page=blogcopilot-job-status')).'">this page</a>.</span>
        </div>
        ';                       
    }

}

function blogcopilot_io_handle_regenerate_form_submission($post_id) {
    if (!isset($_POST['blogcopilot_create_form_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['blogcopilot_create_form_nonce'])), 'blogcopilot_create_form')) {
        wp_die('Nonce verification failed, unauthorized submission.');
    }

    $post = get_post($post_id);
    $title = $post->post_title;
    $article = $post->post_content;

    $language = isset($_POST['language']) ? sanitize_text_field($_POST['language']) : get_option('blogcopilot_blog_lang', 'English');
    $keywords = isset($_POST['keywords']) ? sanitize_text_field($_POST['keywords']) : '';
    $content_description = isset($_POST['content_description']) ? sanitize_text_field($_POST['content_description']) : '';
    $style = isset($_POST['style']) ? sanitize_text_field($_POST['style']) : '';

    // Call API
    $api_response = blogcopilot_io_call_api_regenerate_content($title, $article, $language, $keywords, $content_description, $style);

    // Check for errors in the API response
    if (isset($api_response['error']) && $api_response['error']) {
        echo '<div class="alert alert-secondary fade show my-2" role="alert">
        <span class="alert-icon"><i class="bi bi-exclamation-diamond"></i> </span><span class="alert-text">Cannot generate new post: ' . esc_html($api_response['error']) . '</span><span>';

        // Include additional quota information if present
        if (isset($api_response['monthlyGeneratedArticles'])) {
            echo '<br/><br/>Monthly Generated Articles: ' . esc_html($api_response['monthlyGeneratedArticles']);
        }
        if (isset($api_response['monthlyArticleQuota'])) {
            echo '<br/>Monthly Article Quota: ' . esc_html($api_response['monthlyArticleQuota']);
        }
        if (isset($api_response['remainingArticleQuota'])) {
            echo '<br/>Remaining Article Quota: ' . esc_html($api_response['remainingArticleQuota']);
            echo '<br/><br/><strong>Upgrade to higher version to increase limits!</strong>';
        }        
        echo '</span></div>';

        return; // Exit the function to prevent further processing
    }

    // Update post
    $post_id = blogcopilot_io_post_update($post_id, $api_response['article']);

    // Display confirmation message
    blogcopilot_io_display_confirmation($post_id, $title, $language, $keywords, $content_description, $style);
    // Display generated images
    blogcopilot_io_display_generated_images($post_id, $title);
}

function blogcopilot_io_create_new_post($category_id, $title, $content, $status = 'publish', $jobGroupId = null) {
    if ($jobGroupId !== null) {
        $existing_posts = get_posts([
            'post_type' => 'post',
            'post_status' => 'any',
            'meta_query' => [
                [
                    'key' => 'blogcopilot_job_id',
                    'value' => $jobGroupId,
                ],
                [
                    'key' => 'blogcopilot_title',
                    'value' => $title,
                ]                
            ],            
            'posts_per_page' => 1
        ]);

        if (!empty($existing_posts)) {
            return $existing_posts[0]->ID;
        }
    }

    $post_author_id = get_current_user_id();

    if ($post_author_id === 0) {
        // Used if called by CRON. Get a list of all users who can publish posts (e.g., authors, editors, and administrators)
        $args = array(
            'role__in' => array('Author', 'Editor', 'Administrator'),
            'orderby' => 'rand',
            'number' => 1
        );
        $users = get_users($args);

        // Pick a random user from the list
        if (!empty($users)) {
            $random_user = $users[array_rand($users)];
            $post_author_id = $random_user->ID;
        } else {
            // Fallback to ID 1 if no suitable user is found
            $post_author_id = 1;
        }
    }

    $new_post = array(
        'post_title'    => $title,
        'post_content'  => $content,
        'post_category' => array($category_id),
        'post_author'   => $post_author_id,
        'post_status'   => $status,
        'post_type'     => 'post',
        'post_date'     => function_exists('blogcopilot_io_generate_random_date_within_last_week') ? blogcopilot_io_generate_random_date_within_last_week() : current_time('mysql'),
    );

    $post_id = wp_insert_post($new_post);

    // Add custom meta to link this draft to the job ID
    if (!is_wp_error($post_id) && $jobGroupId !== null) {
        add_post_meta($post_id, 'blogcopilot_job_id', $jobGroupId);
        add_post_meta($post_id, 'blogcopilot_title', $title);
    }

    return $post_id;
}

// Function to generate a random date within the last week
function blogcopilot_io_generate_random_date_within_last_week() {
    $current_time = current_time('timestamp'); // Get current time in WordPress
    $one_week_ago = strtotime('-1 week', $current_time); // Timestamp for one week ago

    // Generate a random timestamp between one week ago and now
    $random_timestamp = wp_rand($one_week_ago, $current_time);

    // Format the timestamp for WordPress
    return gmdate('Y-m-d H:i:s', $random_timestamp);
}

// Function to generate a random date within the last 6 months
function blogcopilot_io_generate_random_date_within_last_year() {
    $current_time = current_time('timestamp'); // Get current time in WordPress
    $one_year_ago = strtotime('-6 months', $current_time); // Timestamp for 6 months ago

    // Generate a random timestamp between one week ago and now
    $random_timestamp = wp_rand($one_year_ago, $current_time);

    // Format the timestamp for WordPress
    return gmdate('Y-m-d H:i:s', $random_timestamp);
}

function blogcopilot_io_post_update($post_id, $content) {
    // Update the existing post with new content
    $updated_post = array(
        'ID'           => $post_id,
        'post_content' => $content
    );

    $post_id = wp_update_post($updated_post);

    return $post_id;    
}

function blogcopilot_io_update_post_seo_parameters($post_id, $title, $summary) {
    if ($post_id > 0 && !empty($title) && !empty($summary)) {    
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );  
        if (is_plugin_active('wordpress-seo/wp-seo.php')) {
            // Yoast SEO is installed and active
            update_post_meta($post_id, '_yoast_wpseo_focuskw', $title);
            update_post_meta($post_id, '_yoast_wpseo_metadesc', $summary);

            return 1;
        } else {
            return 0;
        }       
    }
}

function blogcopilot_io_display_confirmation($post_id, $title, $language, $keywords, $content_description, $style) {
    $post = get_post($post_id);
    $word_count = str_word_count($post->post_content);
    $regenerate_url = admin_url('admin.php?page=blogcopilot-create-post&regenerate=' . $post_id);
?>
    <div id="blogcopilot-image-selection-div">
    <div class="p-4 bg-light">
    <?php if ($post->post_status == "publish") { ?>
        <h4>Post titled "<?php echo esc_html($post->post_title);?>" was created and is Published.</h4>
    <?php } else { ?>
        <h4>Post titled "<?php echo esc_html($post->post_title);?>" was created and is available to Edit (is not Published).</h4>
    <?php } ?>

        <div id="blogcopilot-spinner-container">
            <div id="blogcopilot-spinner" class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="mt-3 fs-4">
                    Content generation can take up to 2-3 minutes, please be patient.
                </div>
            </div>
        </div>

        <p>Words Count: <?php echo esc_html($word_count);?></p>
        <a href="<?php echo esc_url(get_permalink($post_id)); ?>" class="btn btn-info btn-sm" target="_blank">View Post</a>
        <a href="<?php echo esc_url(get_edit_post_link($post_id));?>" class="btn btn-primary btn-sm" target="_blank">Edit Newly Created Post</a>
        
        <form action="<?php echo esc_url($regenerate_url); ?>" method="POST" id="blogcopilot-recreate-form" style="display: inline-block; margin-right: 10px;">
            <?php wp_nonce_field('blogcopilot_create_form', 'blogcopilot_create_form_nonce'); ?>           
            <input type="hidden" name="title" value="<?php echo esc_attr($title); ?>">
            <input type="hidden" name="language" value="<?php echo esc_attr($language); ?>">
            <input type="hidden" name="keywords" value="<?php echo esc_attr($keywords); ?>">
            <input type="hidden" name="content_description" value="<?php echo esc_attr($content_description); ?>">
            <input type="hidden" name="style" value="<?php echo esc_attr($style); ?>">

            <button type="submit" class="btn btn-secondary btn-sm" id="regenerate-content" data-post-id="<?php echo esc_attr($post_id);?>">Regenerate Post</button>
        </form>
        
        <span style="margin-left:20px;">or</span>
        <a href="<?php echo esc_url(admin_url('admin.php?page=blogcopilot-create-post'));?>" class="btn btn-success btn-sm">Create Another Post</a>
    </div>

<?php 
    wp_enqueue_script('blogcopilot-create-post', plugins_url('assets/js/blogcopilot-create-post-2.js', __FILE__), array('jquery'), '1.0', true);
?>
<?php
}

function blogcopilot_io_display_generated_images($post_id, $title) {
    $api_url = get_option('blogcopilot_api_url', '');
    $api_response = blogcopilot_io_call_api_generate_images($title);

    // Check if the API response indicates an error or quota exceedance
    if (isset($api_response['error']) && $api_response['error']) {
        echo '<div class="alert alert-secondary fade show my-2" role="alert">
        <span class="alert-icon"><i class="bi bi-exclamation-diamond"></i> </span><span class="alert-text">Cannot generate new post: ' . esc_html($api_response['error']) . '</span><span>';

        if (isset($api_response['monthlyGeneratedArticles'])) {
            echo '<br/><br/>Monthly Generated Articles: ' . esc_html($api_response['monthlyGeneratedArticles']);
        }
        if (isset($api_response['monthlyArticleQuota'])) {
            echo '<br/>Monthly Article Quota: ' . esc_html($api_response['monthlyArticleQuota']);
        }
        if (isset($api_response['remainingArticleQuota'])) {
            echo '<br/>Remaining Article Quota: ' . esc_html($api_response['remainingArticleQuota']);
            echo '<br/><br/><strong>Upgrade to higher version to increase limits!</strong>';
        }
        echo '</span></div>';

        return; // Exit the function to prevent further processing
    }

    $images = $api_response['image_urls'] ?? [];

?>
    <div id="blogcopilot-image-selection-div-2">
    <div id="ai_image_selection" class="p-4 bg-light">
    <h4>Select Images for Featured Image and Content Images:</h4>

    <div id="ai_image_grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px;">
<?php    
    foreach ($images as $index => $image_url) {
        $image_url = $api_url . $image_url;
        echo '<div class="ai_image_container" style="text-align: center;">';
        echo '<img src="' . esc_url($image_url) . '" style="width: 100%; height: auto; border-radius: 10px; transition: transform 0.3s ease;">'; // Responsive and with hover effect
        
        $confirmation_id = "ai_confirmation_message_$index";
        
        echo '<button class="btn btn-primary btn-sm ai_select_image" style="margin-top: 10px;" data-image-url="' . esc_attr($image_url) . '" data-post-id="' . esc_attr($post_id) . '" data-confirmation-target="' . esc_attr($confirmation_id) . '">As Featured</button> ';
        echo '<button class="btn btn-secondary btn-sm ai_use_in_content" style="margin-top: 10px; margin-left: 5px;" data-image-url="' . esc_attr($image_url) . '" data-post-id="' . esc_attr($post_id) . '" data-confirmation-target="' . esc_attr($confirmation_id) . '">In Content</button>';
        
        echo '<div id="' . esc_attr($confirmation_id) . '" style="display:none; color: green; margin-top: 5px;">Image added successfully!</div>';
        echo '</div>';
    }
    echo '</div>';
    echo '<div id="blogcopilot_io_generate_more_images_container" class="ai_image_container" style="text-align: center;">';
    echo '<button id="ai_generate_more_images" class="btn btn-success btn-sm generate-more-images-button" style="margin-top: 20px;"  data-post-title="'.esc_html($title).'" data-post-id="' . esc_attr($post_id) . '">Generate More Images</button>';
    echo '</div>';
    echo '</div></div>'; // Close grid and container div

    // AJAX script for generating more images
    wp_enqueue_script('blogcopilot-images-in-single', plugins_url('assets/js/blogcopilot-images-in-single.js', __FILE__), array('jquery'), '1.0', true);
    wp_localize_script('blogcopilot-images-in-single', 'blogcopilotParams', array(
        'apiUrl' => $api_url,
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('blogcopilot_io_generate_more_images_nonce')           
    ));      
}

?>
