<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
if (!defined('WPINC')) exit; // Exit if accessed directly

require_once plugin_dir_path(__FILE__) . 'do-api-calls.php';

function blogcopilot_io_create_phrase_mgmt_content() {
    // Handle deletion request
    if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['phraseId'])) {
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'delete-phrase_' . intval($_GET['phraseId']))) {
            wp_die('Nonce verification failed, unauthorized request.');
        }
    
        $phraseId = intval($_GET['phraseId']);

        $apiUrl = get_option('blogcopilot_api_url', '') . '/api-endpoint-phrases.php';
        $licenseKey = get_option('blogcopilot_license_number', '');
        $domain = get_option('blogcopilot_blog_domain', '');
    
        $payload = [
            'phraseId' => $phraseId,
            'licenseKey' => $licenseKey,
            'domain' => $domain,
            'action' => 'deletePhrase',
        ];
    
        $response = wp_remote_post($apiUrl, [
            'body' => wp_json_encode($payload, JSON_UNESCAPED_UNICODE),
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);

        // delete post meta (if any)
        blogcopilot_remove_phrase_from_posts($phraseId);
    
        if (is_wp_error($response)) {
            echo '
            <div class="alert alert-success alert-dismissible fade show my-2" role="alert">
            <span class="alert-icon"><i class="bi bi-exclamation-diamond"></i> </span><span class="alert-text">' . esc_html($response->get_error_message()) . '</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
            </button>
            </div>
            ';
        } else {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            echo '
            <div class="alert alert-success alert-dismissible fade show my-2" role="alert">
            <span class="alert-icon"><i class="bi bi-exclamation-diamond"></i> </span><span class="alert-text">' . esc_html($data['status'] ?? 'Unknown') . '</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
            </button>
            </div>
            ';
        }
    }
    if (isset($_GET['action']) && $_GET['action'] == 'add') { 
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Verify the nonce (use a unique nonce for this form)
            if (isset($_POST['blogcopilot_create_phrase_nonce'])) {
                if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['blogcopilot_create_phrase_nonce'])), 'blogcopilot_create_phrase')) {
                    wp_die('Nonce verification failed, unauthorized submission.');
                } else {
                    blogcopilot_io_handle_phrase_form_submission();
                }
            } elseif (isset($_POST['blogcopilot_link_phrase_to_post_nonce'])) {
                if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['blogcopilot_link_phrase_to_post_nonce'])), 'blogcopilot_io_link_phrase_to_post')) {
                    wp_die('Nonce verification failed, unauthorized submission.');
                } else {
                    $phrase_ids_json = isset($_POST['phrase_ids']) ? $_POST['phrase_ids'] : null;
                    $phrase_ids_json = htmlspecialchars_decode($phrase_ids_json, ENT_QUOTES);
                    $phrase_ids_json = stripslashes($phrase_ids_json);
                    $phrase_ids = json_decode($phrase_ids_json, true);

                    $post_id = intval($_POST['linked_post_id']);
                    $phrase = isset($_POST['phrase']) ? sanitize_text_field($_POST['phrase']) : '';
                    $phrases = array_map('trim', explode(',', $phrase)); 
        
                    $phrases_data = [];
                    foreach ($phrase_ids as $phrase_id) {
                        $phrase_name = array_shift($phrases); // Get and remove the corresponding phrase name
                        $phrases_data[] = ['id' => $phrase_id, 'name' => $phrase_name];
                    
                        // Call your API to update each phrase individually
                        blogcopilot_io_phrase_update($phrase_id, $phrase_name, 'User Published', $post_id); 
                    }
                    $existing_phrases_meta = get_post_meta($post_id, 'blogcopilot_phrases', true);
                    $existing_phrases_data = json_decode($existing_phrases_meta, true) ?: [];
                    $final_phrases_data = array_merge($existing_phrases_data, $phrases_data);
                    
                    update_post_meta($post_id, 'blogcopilot_phrases', wp_json_encode($final_phrases_data, JSON_UNESCAPED_UNICODE));                     
                    
                    echo '
                    <div class="alert alert-success alert-dismissible fade show my-2" role="alert">
                    <span class="alert-icon"><i class="ni ni-like-2"></i> </span><span class="alert-text">Phrase linked to article.</span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                    </button>
                    <span class="alert-text"><br/><br/>Now create next Phrase below, or get back to the list of <a href="'.esc_url(admin_url('admin.php?page=blogcopilot-phrase-mgmt')).'">All Phrases</a>.</span>
                    </div>
                    ';                    
                }
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
            <h4>Create New Phrase</h4>
            <form method="POST" id="blogcopilot-create-form">
            <?php wp_nonce_field('blogcopilot_create_phrase', 'blogcopilot_create_phrase_nonce'); ?>      
            <div class="mb-3">
                <label class="form-label" for="title"><?php esc_html_e('Phrase Name (if you want to add few, just use comma to separate them)', 'blogcopilot-io'); ?></label>
                <input type="text" class="form-control" id="title" name="title" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="articleTitle"><?php esc_html_e('Article Title (enter if should be other than Phrase Name and you want system to write one)', 'blogcopilot-io'); ?></label>
                <input type="text" class="form-control" id="articleTitle" name="articleTitle">
            </div>            
            <div class="row">
                <div class="col-md-6 px-4">
                    <div class="form-check">
                    <input class="form-check-input" type="radio" id="flexRadioNo" name="flexPhraseOption" value="flexRadioNo" checked>
                    <label class="form-check-label" for="flexRadioNo">No Content Generation (can later link to your article)</label>
                    </div>
                    <div class="form-check">
                    <input class="form-check-input" type="radio" id="flexRadioDraft" name="flexPhraseOption" value="flexRadioDraft">
                    <label class="form-check-label" for="flexRadioDraft">Write Article Draft only</label>
                    </div>                    
                    <div class="form-check">
                    <input class="form-check-input" type="radio" id="flexRadioFull" name="flexPhraseOption" value="flexRadioFull">
                    <label class="form-check-label" for="flexRadioFull">Write Automatically Full Article with Images</label>
                    </div>
                </div>
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
            </div>
            <div class="row mb-3 align-items-center" style="padding-top: 15px">
            <?php
                $blog_lang = get_option('blogcopilot_blog_lang', 'English');
                $show_language_select = get_option('blogcopilot_dynamic_lang_selection', '0');
                if ($show_language_select === '1'):
            ?>
                <div class="col-md-1">
                    <label for="language" class="form-label"><?php esc_html_e('Article Language', 'blogcopilot-io'); ?></label>
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
                <input type="hidden" class="form-control" id="language" name="language" value="<?php echo esc_html($blog_lang); ?>">
                </div>
            <?php endif; ?>

            </div>
            <div class="row mb-3 align-items-center">
                <div class="col-md-1">
                    <label for="style" style="padding-right: 10px"><?php esc_html_e('Article Style', 'blogcopilot-io'); ?></label>
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
                <label for="keywords" class="form-label"><?php esc_html_e('Article Additional Keywords (separated by ,) (optional)', 'blogcopilot-io'); ?></label>
                <textarea class="form-control" id="keywords" name="keywords" rows="3"></textarea>
            </div>
            <div class="mb-3">
                <label for="content_description" class="form-label"><?php esc_html_e('Additional suggestions for article generation (optional)', 'blogcopilot-io'); ?></label>
                <textarea class="form-control" id="content_description" name="content_description" rows="3"></textarea>
            </div>

            <div class="mb-3">    
                <button type="submit" class="btn btn-primary"><?php esc_html_e('Create', 'blogcopilot-io'); ?></button>
            </div>

            </form>
            </div>
        <?php 
            wp_enqueue_script('blogcopilot-create-phase', plugins_url('assets/js/blogcopilot-create-phrase.js', __FILE__), array('jquery'), '1.0', true);
        ?>
        </div>

<?php
    } else {
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

        $body = wp_remote_retrieve_body($response); // Get the response body
        $phrases = json_decode($body, true); // Decode JSON to array

        wp_enqueue_script('blogcopilot-phrases', plugins_url('assets/js/blogcopilot-phrases.js', __FILE__), array('jquery'), '1.0', true);       
?>
        <div id="blogcopilot-create-form-div" class="p-4 bg-light">
            <h4>Phrase Management</h4>
            <div class="container card my-3">
                <div class="card-body">
                Welcome to Phrase Management! Here, you can add and organize key phrases you want to rank for. Track their performance, generate content, and optimize your SEO strategy effortlessly (in paid versions). Start by adding your target phrases. 
                </div>
            </div>
            <div class="container card my-3">
                <div class="card-body">
                    <?php 
                        $blogcopilot_page_url = admin_url('admin.php?page=blogcopilot-phrase-mgmt&action=add'); 
                        echo '<a href="'.esc_url($blogcopilot_page_url).'" class="btn btn-primary">Add New Phase</a>';
                    ?>
                    <span> or ... </span>
                    <button type="button" class="btn btn-primary btn-sm" 
                            data-bs-toggle="modal" 
                            data-bs-target="#generatePhrasesModal" 
                            data-nonce="<?php echo esc_attr(wp_create_nonce('blogcopilot_generate_phrases_nonce')); ?>"> 
                        Get Suggestions!
                    </button>

                    <div style="padding-top: 20px;">
                    </div>

                    <!-- End Search Form -->
                    <div class="table-responsive">
                    <table class="table align-items-center mb-0 table-striped" id="phrasesTable">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xs font-weight-bolder opacity-7">Phrase</th>
                                <th class="text-uppercase text-secondary text-xs font-weight-bolder opacity-7">Category</th>
                                <th class="text-uppercase text-secondary text-xs font-weight-bolder opacity-7">Status</th>
                                <th class="text-uppercase text-secondary text-xs font-weight-bolder opacity-7">Linking</th>     
                                <th class="text-uppercase text-secondary text-xs font-weight-bolder opacity-7">Rank</th>
                                <th class="text-uppercase text-secondary text-xs font-weight-bolder opacity-7">URL</th>                                
                                <th class="text-uppercase text-secondary text-xs font-weight-bolder opacity-7">Actions</th>                                         
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($phrases as $phrase): ?>
                            <tr data-phrase-id="<?php echo esc_attr($phrase['PhraseID']); ?>">
                                <td class="text-truncate"><?php echo esc_html($phrase['Phrase']); ?></td>
                                <td class="text-truncate">
                                    <?php
                                    $category_id = $phrase['Category'];
                                    if ($category_id) {
                                        $category = get_term($category_id, 'category');
                                        if (!is_wp_error($category) && $category) {
                                            echo esc_html($category->name);
                                        } else {
                                            echo esc_html_e('Unknown Category', 'blogcopilot-io');
                                        }
                                    } else {
                                        echo esc_html_e('No Category', 'blogcopilot-io');
                                    }
                                    ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo ($phrase['Status'] === 'AI Published' || $phrase['Status'] === 'User Published' ? 'success' : 'secondary'); ?>"><?php echo esc_html($phrase['Status']); ?></span>
                                </td>
                                <td>
                                    <?php if ($phrase['LinkingPhraseCount'] > 0) {
                                        echo '<span class="badge bg-success">'.esc_html($phrase['LinkingPhraseCount']).' art</span>';                                        
                                    } ?>
                                </td>                                
                                <td>
                                <?php if ($phrase['PositionDesktop'] > 0) {
                                        echo '<a href="' . esc_url($phrase['url']) . '" target="_blank">'.esc_html($phrase['PositionDesktop']).'</a>';
                                    } else {
                                        echo '-';
                                    } ?>
                                </td>   
                                <td>
                                    <?php 
                                    if ($phrase['WordPressPostID']) {
                                        $post = get_post($phrase['WordPressPostID']);

                                        if ($post) {
                                            $edit_link = get_edit_post_link($phrase['WordPressPostID']);
                                            echo '<a href="' . esc_url($edit_link) . '" target="_blank">Edit</a>';
                                        } else {
                                            echo '-'; 
                                        }
                                    } else {
                                        echo '-'; 
                                    }
                                    ?>
                                </td>                                                         
                                <td>
                                    <button type="button" class="btn btn-primary btn-sm" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#phraseDetailsModal" 
                                            data-phrase-id="<?php echo esc_attr($phrase['PhraseID']); ?>"
                                            data-phrase-name="<?php echo esc_attr($phrase['Phrase']); ?>"
                                            data-nonce="<?php echo esc_attr(wp_create_nonce('blogcopilot_get_subphrases_nonce')); ?>"> 
                                        View Details
                                    </button>
                                    <a href="#" class="btn btn-outline-info btn-sm ms-2" data-bs-toggle="modal" data-bs-target="#confirmationModal" data-delete-url="<?php echo esc_url(admin_url('admin.php?page=blogcopilot-phrase-mgmt&action=delete&phraseId=' . $phrase['PhraseID'])); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('delete-phrase_' . $phrase['PhraseID'])); ?>">Delete</a>                                    
                                    <?php if ($phrase['Status'] === 'AI Published' || $phrase['Status'] === 'User Published') { ?>
                                    <button type="button" class="btn btn-primary btn-sm" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#generateLinksModal" 
                                            data-phrase-id="<?php echo esc_attr($phrase['PhraseID']); ?>"
                                            data-phrase-name="<?php echo esc_attr($phrase['Phrase']); ?>"
                                            data-phrase-category="<?php echo esc_attr($phrase['Category']); ?>"  
                                            data-wordpress-id="<?php echo esc_attr($phrase['WordPressPostID']); ?>"                                          
                                            data-nonce="<?php echo esc_attr(wp_create_nonce('blogcopilot_generate_links_nonce')); ?>"> 
                                        Generate Linking Articles
                                    </button>
                                    <?php } ?>
                                </td>                                
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>  
                </div>
            </div>
        </div>
        <!-- View Details Modal -->
        <div class="modal fade" id="phraseDetailsModal" tabindex="-1" aria-labelledby="phraseDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="phraseDetailsModalLabel">Phrase Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Subphrases and linking articles for <span id="detailsModalPhrase"></span>:</p>
                    <div id="subphrasesList"></div> 
                </div>
            </div>
        </div>
        </div>         
        <!-- Delete Confirmation Modal -->
        <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmationModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this phrase? (articles will not be deleted)
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Yes</button>
            </div>
            </div>
        </div>
        </div>
        <!-- Generete Links Modal -->
        <div class="modal fade" id="generateLinksModal" tabindex="-1" aria-labelledby="generateLinksModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="generateLinksModalLabel">Generate Articles Linking to <span id="modalPhrase"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6>Select article titles - all generated articles will be linking to your main article. List of proposed keywords:</h6>
                    <div id="keywordsList"></div> 
                    <h6>or add keywords manually:</h6>
                    <div id="manualKeywords"></div> <button type="button" class="btn btn-secondary mt-3" id="addKeywordButton">Add Keyword Manually</button>
                    <br/>
                    <button type="button" class="btn btn-secondary mt-3" id="refreshKeywordsButton">Refresh Keywords</button>
                    <button type="button" class="btn btn-primary mt-3" id="generateArticlesButton">Generate Articles</button>
                </div>
            </div>
        </div>
        </div>
        <!-- Generete Phrases Suggestions Modal -->
        <div class="modal fade" id="generatePhrasesModal" tabindex="-1" aria-labelledby="generatePhrasesModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
            <div class="modal-header">
                    <h5 class="modal-title" id="generatePhrasesModalLabel">Suggest Phrases</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>                
                <div class="modal-body">
                    <h6>Select some from proposed Phrases - our system will generate posts with images to speed up your website building progress. List of proposed Phrases:</h6>
                    <div id="phrasesList"></div> 
                    <br/>
                    <button type="button" class="btn btn-secondary mt-3" id="refreshPhrasesButton">Refresh Phrases</button>
                    <button type="button" class="btn btn-primary mt-3" id="generatePhrasesArticlesButton">Save Phrases and Generate Articles</button>
                </div>
            </div>
        </div>
        </div>        
<?php
    }
}

function blogcopilot_io_search_phrases() {
    // Verify the nonce
    if (!isset($_POST['blogcopilot_search_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['blogcopilot_search_nonce'])), 'blogcopilot_search_action')) {
        wp_send_json_error('Nonce verification failed, unauthorized request.');
    }

    // Retrieve search parameters
    $searchPhrase = isset($_POST['phrase']) ? sanitize_text_field($_POST['phrase']) : '';
    $searchCategory = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
    $searchStatus = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';

    // Your logic to get and filter job groups based on the search parameters
    $apiUrl = get_option('blogcopilot_api_url', '') . '/api-endpoint-phrases.php';
    $licenseKey = get_option('blogcopilot_license_number', '');
    $domain = get_option('blogcopilot_blog_domain', '');

    $payload = [
        'licenseKey' => $licenseKey,
        'domain' => $domain,
        'action' => 'getPhrases'        
    ];

    $response = wp_remote_post($apiUrl, [
        'body' => wp_json_encode($payload, JSON_UNESCAPED_UNICODE),
        'headers' => ['Content-Type' => 'application/json'],
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error('Error fetching job data.');
    }

    $body = wp_remote_retrieve_body($response);
    $phrases = json_decode($body, true);

    if (empty($phrases)) {
        wp_send_json_success([]);
    }

    $filteredPhrases = array_filter($phrases, function($phrase) use ($searchPhrase, $searchCategory, $searchStatus) {
        $phraseTitle = $phrase['Phrase'];
        $phraseCategory = $phrase['Category'];
        $phraseStatus = $phrase['Status'];

        $phraseCondition = !$searchPhrase || stripos($phraseTitle, $searchPhrase) !== false;
        $categoryCondition = !$searchCategory || $phraseCategory === $searchCategory;
        $statusCondition = !$searchStatus || $phraseStatus === $searchStatus;

        return $phraseCondition && $categoryCondition && $statusCondition;
    });
    wp_send_json_success(array_values($filteredPhrases));
    wp_die(); // this is required to terminate immediately and return a proper response    
}
add_action('wp_ajax_blogcopilot_io_search_phrases', 'blogcopilot_io_search_phrases');


function blogcopilot_io_handle_phrase_form_submission() {
    $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
    $article_title = isset($_POST['articleTitle']) ? sanitize_text_field($_POST['articleTitle']) : ''; 
    $category_id = isset($_POST['category']) ? intval($_POST['category']) : 0;
    $language = isset($_POST['language']) ? sanitize_text_field($_POST['language']) : get_option('blogcopilot_blog_lang', 'English');
    $keywords = isset($_POST['keywords']) ? $title.','.sanitize_text_field($_POST['keywords']) : '';
    if ($keywords == '') $keywords = $title; 
    $content_description = isset($_POST['content_description']) ? sanitize_text_field($_POST['content_description']) : '';
    $style = isset($_POST['style']) ? sanitize_text_field($_POST['style']) : '';
    $blog_location = get_option('blogcopilot_blog_location', '2840');
    $blog_title = get_option('blogcopilot_blog_title', '');
    $blog_description = get_option('blogcopilot_blog_description', '');

    //Content Generation option:
    $contentGeneration = isset($_POST['flexPhraseOption']) ? sanitize_text_field($_POST['flexPhraseOption']) : 'flexRadioNo';
    $additional_message = "";
    $post_id = null;
    $api_response = "";

    // API call urls
    $apiUrl = get_option('blogcopilot_api_url') . '/api-endpoint-phrases.php';
    $licenseKey = get_option('blogcopilot_license_number');
    $domain = get_option('blogcopilot_blog_domain');    

    //Check if phrase is not already in the system
    $api_response = blogcopilot_io_check_phrase_exists($title);

    if ($api_response['status'] === 'Success' && !is_null($api_response['phraseData']) && !empty($api_response['phraseData'])) {
        if ($api_response['message'] === 'All phrases found.') {
            echo '
            <div class="alert alert-secondary alert-dismissible fade show my-2" role="alert">
            <span class="alert-icon"><i class="bi bi-exclamation-diamond"></i> </span><span class="alert-text">Phrase already exist - please don\'t add duplicates.</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
            </button>
            </div>
            ';
        
            return;   
        } else {
            echo '
            <div class="alert alert-secondary alert-dismissible fade show my-2" role="alert">
            <span class="alert-icon"><i class="bi bi-exclamation-diamond"></i> </span><span class="alert-text">Some of entered Phrases already exists - only new ones will be added.</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
            </button>
            </div>
            ';

            $existing_phrases = array_column($api_response['phraseData'], 'Phrase'); 
            $titles_array = array_map('trim', explode(',', $title));
            $new_titles_array = array_diff($titles_array, $existing_phrases);
            $title = implode(', ', $new_titles_array);          // have only phrases left to be added (eliminated ones that exists)  
        }
         
    } 

    $titles = array_map('trim', explode(',', $title)); // Split the title into an array of phrases
    $phrasesNotFound = []; // To store phrases for which no articles were found
    
    foreach ($titles as $individualTitle) {
        $args = array(
            'post_type'      => 'post', 
            'posts_per_page' => 1,
            'title'          => $individualTitle, // Search for each individual title
            'orderby'        => 'post_status',
            'order'          => 'DESC' 
        );
    
        $query = new WP_Query($args);
        $existing_posts = $query->get_posts();
    
        if (!empty($existing_posts)) {
            $existing_post = $existing_posts[0];
            $existing_post_id = $existing_post->ID;
            $existing_post_status = $existing_post->post_status;
            $new_status = ($existing_post_status === 'publish') ? 'User Published' : 'Draft Available';
    
            // Link the individual phrase to the existing post
            $payload = [
                'action' => 'createPhrase',
                'phrase' => $individualTitle, // Send the individual title to the API
                'categoryId' => $category_id,
                'language' => $language,
                'blogLocation' => $blog_location,
                'keywords' => $keywords,
                'description' => $content_description,
                'style' => $style,
                'licenseKey' => $licenseKey,
                'domain' => $domain,
                'status' => $new_status,
                'WordPressPostId' => $existing_post_id,
                'contentGenerate' => 'flexRadioNo' 
            ];
    
            $response = wp_remote_post($apiUrl, [
                'body' => wp_json_encode($payload, JSON_UNESCAPED_UNICODE),
                'headers' => ['Content-Type' => 'application/json'],
            ]);
    
            if (is_wp_error($response)) {
                wp_die(esc_attr($response->get_error_message()), 'API Error', ['back_link' => true]);
            } else {
                $data = json_decode(wp_remote_retrieve_body($response), true);
                if ($data['status'] === 'Success') {
                    $phrases_data = [['id' => $data['phraseIds'][0], 'name' => $individualTitle]]; 
                    update_post_meta($existing_post_id, 'blogcopilot_phrases', wp_json_encode($phrases_data, JSON_UNESCAPED_UNICODE));
    
                    $additional_message .= "Some phrases were added, but linked to existing articles - based on article titles. ";
                } else {
                    wp_die(esc_attr($data['message']) ?? 'An error occurred', 'API Error', ['back_link' => true]);
                }
            }
        } else {
            // No existing post found for this title, add it to phrasesNotFound
            $phrasesNotFound[] = $individualTitle;
        }
    }

    if (empty($phrasesNotFound)) {
        echo '
        <div class="alert alert-success alert-dismissible fade show my-2" role="alert">
            <span class="alert-icon"><i class="ni ni-like-2"></i> </span>
            <span class="alert-text">Phrase created and linked to existing articles</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            <span class="alert-text"><br/><br/>You can also create next Phrase below, or get back to the list of <a href="' . esc_url(admin_url('admin.php?page=blogcopilot-phrase-mgmt')) . '">All Phrases</a>.</span>
        </div>
        ';

        return;
    }
    
    // Update $titles to only contain phrases not found
    $title = implode(',', $phrasesNotFound); 
    if ($article_title == '') {
        $titles = explode(',', $title);
        $article_title = ucfirst(trim($titles[0]));
    }

    if ($contentGeneration == 'flexRadioDraft') {
        $api_response = blogcopilot_io_call_api_generate_content($article_title, $category_id, $language, $keywords, $content_description, $style, 'no', 'yes', 'yes', 0);   
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
        } else {
            $post_id = blogcopilot_io_create_new_post($category_id, $article_title, $api_response['article'], 'draft');
            $post = get_post($post_id);
            $edit_link = get_edit_post_link($post_id);
      
            $additional_message .= sprintf(
                'Post titled "%s" was created and is available to <a href="%s" target="_blank">Edit</a> (it is not Published).',
                esc_html($post->post_title),
                esc_url($edit_link)
            );
        }
    }

    // API URL and other required details
    if ($contentGeneration == 'flexRadioFull') {
        $action = 'createPhraseFull';
    } else {
        $action = 'createPhrase';
    }

    $payload = [
        'action' => $action,
        'phrase' => $title,
        'articleTitle' => $article_title,
        'categoryId' => $category_id,
        'language' => $language,
        'blogLocation' => $blog_location,
        'keywords' => $keywords,
        'description' => $blog_description,
        'content_description' => $content_description,
        'style' => $style,
        'licenseKey' => $licenseKey,
        'domain' => $domain,
        'WordPressPostId' => $post_id,
        'contentGenerate' => $contentGeneration
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
        if ($data['status'] === 'Success') {
            if ($contentGeneration == 'flexRadioDraft') {
                $phrases_data = [];
                $titles_array = array_map('trim', explode(',', $title)); // Split the title into an array
                foreach ($data['phraseIds'] as $phraseId) {
                    $phrase_name = array_shift($titles_array); // Get and remove the corresponding phrase name from the array
                    $phrases_data[] = ['id' => $phraseId, 'name' => $phrase_name];
                }
                update_post_meta($post_id, 'blogcopilot_phrases', wp_json_encode($phrases_data, JSON_UNESCAPED_UNICODE)); 

                echo '
                <div class="alert alert-success alert-dismissible fade show my-2" role="alert">
                <span class="alert-icon"><i class="ni ni-like-2"></i> </span><span class="alert-text">Phrase and draft of its content is created.</span>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                </button>
                <span class="alert-text">'.wp_kses_post($additional_message).'</span>
                <span class="alert-text"><br/><br/>You can also create next Phrase below, or get back to the list of <a href="'.esc_url(admin_url('admin.php?page=blogcopilot-phrase-mgmt')).'">All Phrases</a>.</span>
                </div>
                ';
            } elseif ($contentGeneration == 'flexRadioFull') {
                echo '
                <div class="alert alert-success alert-dismissible fade show my-2" role="alert">
                <span class="alert-icon"><i class="ni ni-like-2"></i> </span><span class="alert-text">Phrase created. Please note, that Post Content for Phrase creation can take anywhere from 5 to 50 minutes.</span>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                </button>
                <span class="alert-text"><br/><br/>Now create next Phrase below, or get back to the list of <a href="'.esc_url(admin_url('admin.php?page=blogcopilot-phrase-mgmt')).'">All Phrases</a>.</span>
                </div>
                ';
            } elseif ($contentGeneration == 'flexRadioNo') {
                echo '
                <div class="alert alert-success alert-dismissible fade show my-2" role="alert">
                    <span class="alert-icon"><i class="ni ni-like-2"></i> </span>
                    <span class="alert-text">Phrase created. Now you can link it to existing post (or you can do that later as well).</span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    
                    <br/><br/>
    
                    <form method="POST" id="blogcopilot-link-phrase-form">';
                        wp_nonce_field('blogcopilot_io_link_phrase_to_post', 'blogcopilot_link_phrase_to_post_nonce');
                    $phrase_ids_json = wp_json_encode($data['phraseIds'], JSON_UNESCAPED_UNICODE); 
                    echo '<input type="hidden" name="action" value="blogcopilot_io_link_phrase_to_post" />
                        <input type="hidden" name="phrase_ids" value="' . esc_attr($phrase_ids_json) . '" />
                        <input type="hidden" name="phrase" value="' . esc_attr($title) . '" />                        
                        <input type="hidden" name="linked_post_id" id="linked_post_id" value=""> 
                        <label for="post_id">Link to Existing Article:</label>
                        <input class="form-control" list="datalistArticles" id="post_id" placeholder="Type to search...">
                        <datalist id="datalistArticles">';
    
                            // Fetch all published posts
                            $args = array(
                                'post_type' => 'post',
                                'post_status' => 'publish',
                                'posts_per_page' => -1, // Get all posts
                                'orderby' => 'title', // Order by title
                                'order' => 'ASC' // Order in ascending order (A-Z)                                
                            );
                            $posts_query = new WP_Query($args);
                            if ($posts_query->have_posts()) {
                                while ($posts_query->have_posts()) {
                                    $posts_query->the_post();
                                    echo '<option data-post-id="'.esc_attr(get_the_ID()).'" value="'. esc_attr(get_the_title()) . '">';
                                }
                            }
                
                            wp_reset_postdata(); // Restore global post data
    
                echo '</datalist>
                            <button type="submit" class="btn btn-primary btn-sm mt-2">Link</button>
                        </form>
    
                    <span class="alert-text"><br/>You can also create next Phrase below, or get back to the list of <a href="' . esc_url(admin_url('admin.php?page=blogcopilot-phrase-mgmt')) . '">All Phrases</a>.</span>
                </div>';
    
                wp_enqueue_script('blogcopilot-select-article', plugins_url('assets/js/blogcopilot-select-article.js', __FILE__), array('jquery'), '1.0', true);
            } else {                
                echo '
                <div class="alert alert-success alert-dismissible fade show my-2" role="alert">
                <span class="alert-icon"><i class="ni ni-like-2"></i> </span><span class="alert-text">Phrase created. Please note, that Post Content creation can take anywhere from 5 to 50 minutes.</span>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                </button>
                <span class="alert-text"><br/><br/>Now create next Phrase below, or get back to the list of <a href="'.esc_url(admin_url('admin.php?page=blogcopilot-phrase-mgmt')).'">All Phrases</a>.</span>
                </div>
                ';
            }
        } else {
            // Handle the API response indicating an error
            wp_die(esc_attr($data['message']) ?? 'An error occurred', 'API Error', ['back_link' => true]);
        }
    }
}

function blogcopilot_remove_phrase_from_posts($phraseId) {
    global $wpdb;

    // Get all posts that have the 'blogcopilot_phrases' meta key
    $posts_with_phrases = $wpdb->get_results("
        SELECT post_id, meta_value
        FROM $wpdb->postmeta
        WHERE meta_key = 'blogcopilot_phrases'
    ");

    foreach ($posts_with_phrases as $post) {
        $phrases_data = json_decode($post->meta_value, true);

        // Filter out the phrase with the given ID
        $updated_phrases_data = array_filter($phrases_data, function($phrase) use ($phraseId) {
            return $phrase['id'] != $phraseId;
        });

        // Re-index the array to ensure sequential numeric keys
        $updated_phrases_data = array_values($updated_phrases_data);

        // If there are no phrases left, delete the meta entirely
        if (empty($updated_phrases_data)) {
            delete_post_meta($post->post_id, 'blogcopilot_phrases');
        } else {
            // Otherwise, update the meta with the filtered data
            update_post_meta($post->post_id, 'blogcopilot_phrases', wp_json_encode($updated_phrases_data, JSON_UNESCAPED_UNICODE));
        }
    }
}
?>