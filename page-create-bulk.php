<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
if (!defined('WPINC')) exit; // Exit if accessed directly

require_once plugin_dir_path(__FILE__) . 'do-api-calls.php';

function blogcopilot_io_mass_creation_page_content() {
    // Check if the form is submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['titles']) && isset($_POST['blogcopilot_mass_creation_form_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['blogcopilot_mass_creation_form_nonce'])), 'blogcopilot_mass_creation_form')) {
        $articleLength = isset($_POST['articleLengthSlider']) ? intval($_POST['articleLengthSlider']) : 2500; // Default to 2500 if not set
        $autoPublish = isset($_POST['autoPublish']) ? filter_var($_POST['autoPublish'], FILTER_VALIDATE_BOOLEAN) : false; // Default to false if not set

        $sanitized_titles = isset($_POST['titles']) && is_array($_POST['titles']) ? array_map('sanitize_text_field', $_POST['titles']) : [];
        $sanitized_categories = isset($_POST['categories']) && is_array($_POST['categories']) ? array_map('intval', $_POST['categories']) : [];
    
        blogcopilot_io_handle_mass_creation_submission($sanitized_titles, $sanitized_categories, $articleLength, $autoPublish);

        echo '
        <div class="alert alert-success alert-dismissible fade show my-2" role="alert">
        <span class="alert-icon"><i class="ni ni-like-2"></i> </span><span class="alert-text">Posts generation has just started. Check the results on the status page <a href="'.esc_url(admin_url('admin.php?page=blogcopilot-job-status')).'">here</a>.</span>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
        </button>
        </div>
        ';
    }

?>
    <div id="blogcopilot-create-form-div" class="p-4 bg-light">
        <h4>Create Multiple Posts</h4>
        <form method="POST" id="blogcopilot-mass-creation-form">
            <?php wp_nonce_field('blogcopilot_mass_creation_form', 'blogcopilot_mass_creation_form_nonce'); ?>
            <div class="mb-3">
                <label for="articleLengthSlider" class="form-label">Post Length</label>
                <input type="range" class="form-range" id="articleLengthSlider" name="articleLengthSlider" min="500" max="5000" step="500" value="2500">
                <small id="articleLengthValue">2500 words</small><small> (system will try to be close to this number, but please treat it only as guildelines)</small>
            </div>

            <div class="mb-3">
                <input type="checkbox" class="form-check-input" id="autoPublishCheck" name="autoPublish">
                <label class="form-check-label" for="autoPublishCheck">Automatically publish generated articles with images</label>
            </div>

            <div id="rows-container">            
                <div class="row mt-4">
                <div class="col-md-8">
                <label class="form-label"><?php esc_html_e('Title', 'blogcopilot-io'); ?></label>
                </div>               
                <div class="col-md-4">
                <label class="form-label"><?php esc_html_e('Category', 'blogcopilot-io'); ?></label>
                </div>               
                </div>
                <?php for ($i = 0; $i < 10; $i++): ?>
                <div class="row my-2">
                    <!-- Title Input -->
                    <div class="col-md-8">
                        <input type="text" class="form-control" name="titles[]" <?php if ($i == 0) echo ' required';?>>
                    </div>

                    <!-- Category Selector -->
                    <div class="col-md-4">
                        <select name="categories[]" class="form-control">
                            <?php
                            $categories = get_categories(array('hide_empty' => false));
                            blogcopilot_io_display_categories($categories);
                            ?>
                        </select>
                    </div>
                </div>
                <?php endfor; ?>
            </div>

            <!-- Buttons -->
            <div class="row mt-3">
                <div class="col-md-12">
                    <button type="button" id="add-more-rows" class="btn btn-secondary">Add 10 More Rows</button>
                    <button type="submit" class="btn btn-primary" style="margin-left: 20px">Generate</button>
                </div>
            </div>      
        </form>
    </div>

<?php
}

function blogcopilot_io_handle_mass_creation_submission($titles, $categories, $articleLength, $autoPublish) {
    $apiUrl = get_option('blogcopilot_api_url', '') . '/api-endpoint-mass-start-processing.php';
    $licenseKey = get_option('blogcopilot_license_number', '');
    $domain = get_option('blogcopilot_blog_domain', '');
    $description = get_option('blogcopilot_blog_description', '');
    $blog_lang = get_option('blogcopilot_blog_lang', 'English');

    $articles = [];
    foreach ($titles as $articleIndex => $title) {
        $title = trim($title);
        // Skip empty titles
        if ($title === '') {
            continue;
        }
    
        // Optional: Match title with category if needed
        $category = $categories[$articleIndex] ?? ''; // Handle case where category is not set
    
        // Construct article data
        $articles[] = [
            'title' => sanitize_text_field($title),
            'category_id' => sanitize_text_field($category),
            // Add more fields if needed
        ];
    }

    $payload = [
        'titles' => $articles,
        'licenseKey' => $licenseKey,
        'domain' => $domain,
        'description' => $description,
        'language' => $blog_lang,
        'articleLength' => $articleLength
    ];

    $response = wp_remote_post($apiUrl, [
        'body' => wp_json_encode($payload, JSON_UNESCAPED_UNICODE),
        'headers' => [
            'Content-Type' => 'application/json',
        ],
    ]);

    if (is_wp_error($response)) {

    } else {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        $jobGroupId = $data['jobGroupId'] ?? null;

        if ($jobGroupId) {
            // Update the option with the last job group ID
            update_option('blogcopilot_job_group_id', $jobGroupId);

            if ($autoPublish) {
                $jobGroupIds = get_option('blogcopilot_job_group_ids_to_publish', array());
                
                // Check if the retrieved option is an array and if not, initialize it as an array
                if (!is_array($jobGroupIds)) {
                    $jobGroupIds = array();
                }
                
                if (!in_array($jobGroupId, $jobGroupIds)) {
                    $jobGroupIds[] = $jobGroupId;
                }

                update_option('blogcopilot_job_group_ids_to_publish', $jobGroupIds);     
            }
        }
    }
}

?>
