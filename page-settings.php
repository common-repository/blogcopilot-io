<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
if (!defined('WPINC')) exit; // Exit if accessed directly

function blogcopilot_io_settings_page_content() {
    // Check if the user is allowed to update options
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_POST['blogcopilot_settings_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['blogcopilot_settings_nonce'])), 'blogcopilot_update_settings')) {
        // Process and save the form data
        update_option('blogcopilot_blog_title', sanitize_textarea_field($_POST['blogcopilot_blog_title']));
        update_option('blogcopilot_blog_description', sanitize_textarea_field($_POST['blogcopilot_blog_description']));
        update_option('blogcopilot_blog_lang', sanitize_textarea_field($_POST['blogcopilot_blog_lang']));
        update_option('blogcopilot_dynamic_lang_selection', isset($_POST['blogcopilot_dynamic_lang_selection']));
        update_option('blogcopilot_blog_location', sanitize_textarea_field($_POST['blogcopilot_blog_location']));
        update_option('blogcopilot_image_with_caption', isset($_POST['blogcopilot_image_with_caption']));                

        echo '
        <div class="alert alert-success alert-dismissible fade show my-2" role="alert">
            <span class="alert-icon"><i class="bi bi-hand-thumbs-up-fill"></i> </span><span class="alert-text"> Settings were updated.</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
            </button>
        </div>
        ';        
    }

    // Retrieve the current values
    $license_number = get_option('blogcopilot_license_number', '');
    $blog_title = get_option('blogcopilot_blog_title', '');
    $blog_description = get_option('blogcopilot_blog_description', '');
    $api_url = get_option('blogcopilot_api_url', '');
    $blog_domain = get_option('blogcopilot_blog_domain', '');
    $blog_lang = get_option('blogcopilot_blog_lang', 'English');
    $blog_location = get_option('blogcopilot_blog_location', '2840');
    $dynamic_lang_selection = get_option('blogcopilot_dynamic_lang_selection', '');
    $image_with_caption = get_option('blogcopilot_image_with_caption', '');

    ?>
    <div id="blogcopilot-create-form-div">
    <div class="p-4 bg-light">
        <h4>BlogCopilot Settings</h4>
        <form method="post" action="" id="blogcopilot-settings-form">
            <?php wp_nonce_field('blogcopilot_update_settings', 'blogcopilot_settings_nonce'); ?>

            <!-- License Number Field -->
            <div class="mb-3 row my-4 align-items-center">
                <label for="blogcopilot_license_number" class="col-md-3 col-form-label"><?php esc_html_e('License Number', 'blogcopilot-io'); ?></label>
                <div class="col-md-9">
                    <input type="text" class="form-control" id="blogcopilot_license_number" name="blogcopilot_license_number" value="<?php echo esc_attr($license_number); ?>" disabled>
                </div>
            </div>

            <!-- Blog Domain Field -->
            <div class="mb-3 row align-items-center">
                <label for="blogcopilot_blog_domain" class="col-md-3 col-form-label"><?php esc_html_e('Blog Domain', 'blogcopilot-io'); ?></label>
                <div class="col-md-9">
                    <input type="text" class="form-control" id="blogcopilot_blog_domain" value="<?php echo esc_attr($blog_domain); ?>" disabled>
                </div>
            </div>

            <!-- API URL Field -->
            <div class="mb-3 row align-items-center">
                <label for="blogcopilot_api_url" class="col-md-3 col-form-label"><?php esc_html_e('API URL', 'blogcopilot-io'); ?></label>
                <div class="col-md-9">
                    <input type="text" class="form-control" id="blogcopilot_api_url" name="blogcopilot_api_url" value="<?php echo esc_url($api_url); ?>" disabled>
                </div>
            </div>

            <!-- Blog Title Field -->
            <div class="mb-3 row align-items-center">
                <label for="blogcopilot_blog_title" class="col-md-3 col-form-label"><?php esc_html_e('Blog Title', 'blogcopilot-io'); ?></label>
                <div class="col-md-9">
                    <input type="text" class="form-control" id="blogcopilot_blog_title" name="blogcopilot_blog_title" value="<?php echo esc_attr($blog_title); ?>">
                </div>
            </div>

            <!-- Default New Post Language Field -->
            <div class="mb-3 row align-items-center">
                <label for="blogcopilot_blog_lang" class="col-md-3 col-form-label"><?php esc_html_e('Default New Post Language', 'blogcopilot-io'); ?></label>
                <div class="col-md-9">
                    <select name="blogcopilot_blog_lang" id="blogcopilot_blog_lang" class="form-control" required>
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
            </div>

            <!-- Dynamic Language Selection Checkbox -->
            <div class="mb-3 row align-items-center">
                <label for="blogcopilot_dynamic_lang_selection" class="col-md-3 col-form-label"><?php esc_html_e('Show language selection during each post generation', 'blogcopilot-io'); ?></label>
                <div class="col-md-9">
                    <input type="checkbox" class="form-check-input" id="blogcopilot_dynamic_lang_selection" name="blogcopilot_dynamic_lang_selection" <?php checked($dynamic_lang_selection, 1); ?>>
                </div>
            </div>

            <!-- Image caption -->
            <div class="mb-3 row align-items-center">
                <label for="blogcopilot_image_with_caption" class="col-md-3 col-form-label"><?php esc_html_e('Add \'AI Generated\' caption below images', 'blogcopilot-io'); ?></label>
                <div class="col-md-9">
                    <input type="checkbox" class="form-check-input" id="blogcopilot_image_with_caption" name="blogcopilot_image_with_caption" <?php checked($image_with_caption, 1); ?>>
                </div>
            </div>            

            <!-- Default Search Location for SERP -->
            <div class="mb-3 row align-items-center">
                <label for="blogcopilot_blog_location" class="col-md-3 col-form-label"><?php esc_html_e('Blog location for search engine rank checking', 'blogcopilot-io'); ?></label>
                <div class="col-md-9">
                    <select name="blogcopilot_blog_location" id="blogcopilot_blog_location" class="form-control" required>
                    <option value="2840" <?php echo ($blog_location == "2840") ? 'selected' : ''; ?>>United States</option>
                    <option value="2826" <?php echo ($blog_location == "2826") ? 'selected' : ''; ?>>United Kingdom</option>
                    <option value="2356" <?php echo ($blog_location == "2356") ? 'selected' : ''; ?>>India</option>
                    <option value="2724" <?php echo ($blog_location == "2724") ? 'selected' : ''; ?>>Spain</option>
                    <option value="2032" <?php echo ($blog_location == "2032") ? 'selected' : ''; ?>>Argentina</option>
                    <option value="2484" <?php echo ($blog_location == "2484") ? 'selected' : ''; ?>>Mexico</option>
                    <option value="2760" <?php echo ($blog_location == "2760") ? 'selected' : ''; ?>>Germany</option>
                    <option value="2250" <?php echo ($blog_location == "2250") ? 'selected' : ''; ?>>France</option>
                    <option value="2284" <?php echo ($blog_location == "2284") ? 'selected' : ''; ?>>Portugal</option>
                    <option value="2762" <?php echo ($blog_location == "2762") ? 'selected' : ''; ?>>Russia</option>
                    <option value="2380" <?php echo ($blog_location == "2380") ? 'selected' : ''; ?>>Italy</option>
                    <option value="2360" <?php echo ($blog_location == "2360") ? 'selected' : ''; ?>>Indonesia</option>
                    <option value="2392" <?php echo ($blog_location == "2392") ? 'selected' : ''; ?>>Japan</option>
                    <option value="2616" <?php echo ($blog_location == "2616") ? 'selected' : ''; ?>>Poland</option>
                    <option value="2528" <?php echo ($blog_location == "2528") ? 'selected' : ''; ?>>Netherlands</option>
                    </select>
                </div>
            </div>

            <!-- Blog Description Field -->
            <div class="mb-3 row align-items-center">
                <label for="blogcopilot_blog_description" class="col-md-3 col-form-label"><?php esc_html_e('Blog Description (optional)', 'blogcopilot-io'); ?></label>
                <div class="col-md-9">
                    <textarea class="form-control" id="blogcopilot_blog_description" name="blogcopilot_blog_description" rows="3"><?php echo esc_textarea($blog_description); ?></textarea>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Save Settings</button>
        </form>
    </div>
    </div>
    <?php
}
?>