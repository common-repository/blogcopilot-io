<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
if (!defined('WPINC')) exit; // Exit if accessed directly

require_once plugin_dir_path(__FILE__) . 'do-api-calls.php';

function blogcopilot_io_job_status_page_content() {
    // Handle deletion request
    if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['jobGroupId'])) {
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'delete-job_' . intval($_GET['jobGroupId']))) {
            wp_die('Nonce verification failed, unauthorized request.');
        }
    
        $jobGroupId = intval($_GET['jobGroupId']);

        $apiUrl = get_option('blogcopilot_api_url', '') . '/api-endpoint-mass-delete-job.php';
        $licenseKey = get_option('blogcopilot_license_number', '');
        $domain = get_option('blogcopilot_blog_domain', '');
    
        $payload = [
            'jobId' => $jobGroupId,
            'licenseKey' => $licenseKey,
            'domain' => $domain
        ];
    
        $response = wp_remote_post($apiUrl, [
            'body' => wp_json_encode($payload, JSON_UNESCAPED_UNICODE),
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);
    
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
    
    // Add status checking for each job
    $updatedJobGroups = [];
    foreach ($jobGroupIds['jobs']['jobGroups'] as $jobGroup) {
        $allPublished = true;
        $somePublished = false;
        foreach ($jobGroup['Tasks'] as $task) {
            $existing_posts = get_posts([
                'post_type' => 'post',
                'post_status' => 'any',
                'meta_query' => [
                    [
                        'key' => 'blogcopilot_job_id',
                        'value' => $jobGroup['JobGroupID'],
                    ],
                    [
                        'key' => 'blogcopilot_title',
                        'value' => $task['title'],
                    ]
                ],
                'posts_per_page' => 1
            ]);

            if (!empty($existing_posts)) {
                $post_status = get_post_status($existing_posts[0]->ID);
                if ($post_status != 'publish') {
                    $allPublished = false;
                } else {
                    $somePublished = true;
                }
            } else {
                $allPublished = false;
            }
        }
        if ($allPublished) {
            $jobGroup['articles_status'] = 'bi bi-check-circle';
        } elseif ($somePublished) {
            $jobGroup['articles_status'] = 'bi bi-circle-half';
        } else {
            $jobGroup['articles_status'] = 'bi bi-dash-circle';
        }
        $updatedJobGroups[] = $jobGroup;
    }
    $jobGroupIds['jobs']['jobGroups'] = $updatedJobGroups;
    
?>
    <div id="blogcopilot-create-form-div" class="p-4 bg-light">
        <h4>Posts in progress</h4>
        <div class="container card my-3">
            <div class="card-body">
            Articles are created in the background and only certain amount of articles can be created each day. Contact us if you want faster processing.
            <br/><br/>After generated articles are reviewed and are published, you can Delete them from this list to make view simpler. Deleting will not delete already published articles.
            <br/><br/>Whats in the queue now? <?php echo 'Completed so far (life time): '.esc_html($jobGroupIds['jobs']['statusCounts']['completed']).', generating now: '.esc_html($jobGroupIds['jobs']['statusCounts']['processing']).', to be generated: '.esc_html($jobGroupIds['jobs']['statusCounts']['pending']); ?>. 
            </div>
        </div>
        <div class="container card my-3">
            <div class="card-body">
                <!-- Search Form -->
                <form id="searchForm" class="row g-3 mb-3">
                    <?php wp_nonce_field('blogcopilot_search_action', 'blogcopilot_search_nonce'); ?>
                    <div class="col-md-3">
                        <label for="dateFrom" class="form-label">Date From</label>
                        <input type="date" class="form-control" id="dateFrom" name="dateFrom">
                    </div>
                    <div class="col-md-3">
                        <label for="dateTo" class="form-label">Date To</label>
                        <input type="date" class="form-control" id="dateTo" name="dateTo">
                    </div>
                    <div class="col-md-3">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="title" name="title">
                    </div>
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All</option>
                            <option value="completed">Completed</option>
                            <option value="processing">Processing</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="button" class="btn btn-primary" id="searchButton">Search</button>
                    </div>
                </form>
                <!-- End Search Form -->
                <div class="table-responsive">
                <table class="table align-items-center mb-0" id="jobTable">
                    <thead>
                        <tr>
                            <th class="text-uppercase text-secondary text-xs font-weight-bolder opacity-7">Date</th>
                            <th class="text-uppercase text-secondary text-xs font-weight-bolder opacity-7">Article Title</th>
                            <th class="text-uppercase text-secondary text-xs font-weight-bolder opacity-7">State</th>
                            <th class="text-uppercase text-secondary text-xs font-weight-bolder opacity-7">Published?</th>
                            <th class="text-uppercase text-secondary text-xs font-weight-bolder opacity-7">Actions</th>     
                            <th class="text-uppercase text-secondary text-xs font-weight-bolder opacity-7"></th>                                         
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($jobGroupIds['jobs']['jobGroups'] as $jobGroup): ?>
                        <tr data-job-id="<?php echo esc_attr($jobGroup['JobGroupID']); ?>">
                            <td><?php echo esc_html(gmdate('Y-m-d H:i:s', strtotime($jobGroup['CreatedAt']))); ?></td>
                            <td class="text-truncate" style="max-width: 20vw;" title="<?php 
                                // Show the title if only one task, otherwise count of articles
                                if (count($jobGroup['Tasks']) === 1) {
                                    echo esc_attr($jobGroup['Tasks'][0]['title']);
                                } else {
                                    echo count($jobGroup['Tasks']) . ' Articles';
                                }
                                ?>">
                                <?php 
                                // Display the title if only one task is present, otherwise show the count of tasks
                                if ($jobGroup['Tasks'][0]['articleLength'] >= 1000) {
                                    echo '<i class="bi bi-bookmark-star"></i> ';
                                }
                                if (count($jobGroup['Tasks']) === 1) {
                                    echo esc_html($jobGroup['Tasks'][0]['title']);
                                } else {
                                    echo count($jobGroup['Tasks']) . ' Articles';
                                }
                                ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo ($jobGroup['TasksStatus'] === 'completed' ? 'success' : 'secondary'); ?>"><?php echo esc_html($jobGroup['TasksStatus']); ?></span>
                            </td>
                            <td>
                                <i class="<?php echo esc_html($jobGroup['articles_status']); ?>"></i>
                            </td>                            
                            <td>
                                <?php if ($jobGroup['TasksStatus'] === 'completed'): ?>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=blogcopilot-view-results&jobGroupId=' . sanitize_text_field($jobGroup['JobGroupID']))); ?>" class="btn btn-primary btn-sm">View Articles</a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#jobDetailsModal<?php echo esc_attr($jobGroup['JobGroupID']); ?>">View Details</button>
                                <a href="#" class="btn btn-outline-info btn-sm ms-2" data-bs-toggle="modal" data-bs-target="#confirmationModal" data-delete-url="<?php echo esc_url(admin_url('admin.php?page=blogcopilot-job-status&action=delete&jobGroupId=' . $jobGroup['JobGroupID'])); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('delete-job_' . $jobGroup['JobGroupID'])); ?>">Delete</a>
                            </td>                                
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                </div>  
                
            </div>
        </div>
    </div>
    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="confirmationModalLabel">Confirm Delete</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            Are you sure you want to delete this job?
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
            <button type="button" class="btn btn-danger" id="confirmDelete">Yes</button>
        </div>
        </div>
    </div>
    </div>

    <!-- Modals for Each Job Group -->
    <?php foreach ($jobGroupIds['jobs']['jobGroups'] as $jobGroup): ?>
    <div class="modal fade" id="jobDetailsModal<?php echo esc_attr($jobGroup['JobGroupID']); ?>" tabindex="-1" aria-labelledby="jobDetailsModalLabel<?php echo esc_attr($jobGroup['JobGroupID']); ?>" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="jobDetailsModalLabel<?php echo esc_attr($jobGroup['JobGroupID']); ?>">Job <?php echo esc_html($jobGroup['JobGroupID']); ?> Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <ul>
                        <?php foreach ($jobGroup['Tasks'] as $task): ?>
                        <li><?php echo esc_html($task['title']) . ' - ' . esc_html($task['status']); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

<?php
}

function blogcopilot_io_get_job_status($jobGroupId) {
    $apiUrl = get_option('blogcopilot_api_url', '') . '/api-endpoint-mass-get-status.php';
    $licenseKey = get_option('blogcopilot_license_number', '');
    $domain = get_option('blogcopilot_blog_domain', '');

    $payload = [
        'jobId' => $jobGroupId,
        'licenseKey' => $licenseKey,
        'domain' => $domain
    ];

    $response = wp_remote_post($apiUrl, [
        'body' => wp_json_encode($payload, JSON_UNESCAPED_UNICODE),
        'headers' => [
            'Content-Type' => 'application/json',
        ],
    ]);

    if (is_wp_error($response)) {
        return 'Error';
    } else {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        return $data['status'] ?? 'Unknown';
    }
}

function blogcopilot_io_view_results_page_content() {
    // Get the job group ID from the URL
    $jobGroupId = isset($_GET['jobGroupId']) ? sanitize_text_field($_GET['jobGroupId']) : '';

    if (!$jobGroupId) {
        // Retrieve the existing array of job group IDs
        $existingJobGroupId = get_option('blogcopilot_job_group_id', -1);
        if ($existingJobGroupId != -1) {
            $jobGroupId = $existingJobGroupId;
        } else {
?>
    <div class="alert alert-warning alert-dismissible fade show my-2" role="alert">
        <span class="alert-icon"><i class="bi bi-exclamation-diamond"></i> </span><span class="alert-text"> Cannot display the results (no ID provided).</span>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
        </button>
        </div>
<?php
        return;
        }
    }

    // Call your function to display generated articles
    blogcopilot_io_display_generated_articles($jobGroupId);
}

function blogcopilot_io_display_generated_articles($jobGroupId) {
    $apiUrl = get_option('blogcopilot_api_url', '');
    $licenseKey = get_option('blogcopilot_license_number', '');
    $domain = get_option('blogcopilot_blog_domain', '');

    $response = wp_remote_post($apiUrl.'/api-endpoint-mass-get-results.php', [
        'body' => wp_json_encode(['jobId' => $jobGroupId, 'licenseKey' => $licenseKey, 'domain' => $domain], JSON_UNESCAPED_UNICODE),
        'headers' => ['Content-Type' => 'application/json']
    ]);

    if (is_wp_error($response)) {
        // Handle error
        echo 'Error fetching articles: ' . esc_html($response->get_error_message());
        return;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    $articles = isset($data['articles']) ? $data['articles'] : [];
?>

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

    <div id="blogcopilot-display-div" class="p-4 bg-light">
    <h4>Generated Articles</h4>
<?php
    if (!is_array($articles)) {
        if ($articles == "Posts creation still in progress.") {
            echo '<div>Posts creation still in progress. Check moment later.</div>';
        }
        else {
            echo '<div>Invalid articles format</div>';
        }
        echo '</div>';
        return;
    }

    foreach ($articles as $articleIndex => $article) {
        $title = $article['title']; // Use the title from the API response
        $categoryId = $article['category_id']; // Use the category_id from the API response
        $summary = $article['summary'];
        $articleContent = $article['content'];

        // Create a draft post (or do not create, if already created, logic is inside of function below)
        $draft_post_id = blogcopilot_io_create_new_post($categoryId, $title, $articleContent, 'draft', $jobGroupId);

        // Update SEO parameters
        blogcopilot_io_update_post_seo_parameters($draft_post_id, $title, $summary);

        // Fetch the post status
        $post_status = get_post_status($draft_post_id);

        // Add the post status and ID to the article array
        $articles[$articleIndex]['post_status'] = $post_status;
        $articles[$articleIndex]['draft_post_id'] = $draft_post_id;
    }    

    // put not published articles at the beginning of the list
    usort($articles, function($a, $b) {
        // Assuming 'publish' means the article is published
        if ($a['post_status'] == 'publish' && $b['post_status'] != 'publish') {
            return 1;
        } elseif ($a['post_status'] != 'publish' && $b['post_status'] == 'publish') {
            return -1;
        }
        return 0;
    });
    
    foreach ($articles as $article) {
        $taskId = $article['taskId'];
        $title = $article['title'];
        $categoryId = $article['category_id'];
        $articleContent = $article['content'];
        $draft_post_id = $article['draft_post_id'];
        $post_status = $article['post_status'];
        
        $wordCount = str_word_count(wp_strip_all_tags($articleContent));

        $categoryName = 'Uncategorized'; // Default value        
        if (!empty($categoryId)) {
            $categoryObject = get_category($categoryId);
            if ($categoryObject instanceof WP_Term) {
                $categoryName = $categoryObject->name;
            }
        }
?>
    <div class="bg-white p-3 mb-3 rounded">
        <div class="d-flex justify-content-between">
            <div class="flex-grow-1 me-3">
                <p class="fs-5 fw-bold">Title: <?php echo esc_html($article['title']); ?></p>
                <p><strong>Excerpt:</strong> <?php echo esc_html(wp_trim_words($article['content'], 45, '...')); ?></p>
                <p><strong>Category:</strong> <?php echo esc_html($categoryName); ?></p>
                <p><strong>Word count:</strong> <?php echo esc_html($wordCount); ?></p>
            </div>
            <div class="d-flex flex-column flex-shrink-1 align-items-center">

<?php
        if ($post_status == 'publish') {
?>
                <div class="flex-grow-1">
                    <button class="btn btn-primary mb-2" style="width: 140px;" onclick="window.open('<?php echo esc_url(get_edit_post_link($draft_post_id));?>', '_blank')">Edit Article</button>
                </div>
<?php
        } else {
?>
                <div class="flex-grow-1">
                    <button class="btn btn-secondary mb-2" style="width: 140px;" id="edit-draft-btn-<?php echo esc_attr($draft_post_id);?>" onclick="window.open('<?php echo esc_url(get_edit_post_link($draft_post_id));?>', '_blank')">Edit Draft</button>
                </div>
                <div class="flex-grow-1">
                    <button class="btn btn-primary mb-2 publish-button" style="width: 140px;" data-article-id="<?php echo esc_attr($draft_post_id);?>">Publish</button>
                </div>
<?php
        }
        echo '</div>'; // Close container for buttons

        echo '</div>';

        // Placeholder for the message
        echo '<div id="message-' . esc_attr($draft_post_id) . '" class="publish-message" style="color: green;"></div>';

        echo '<div id="ai_image_grid_' . esc_attr($draft_post_id) . '" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px;">';
        foreach ($article['image_urls'] as $imageIndex => $imageUrl) {
            $imageUrl = $apiUrl . $imageUrl;
            $confirmationId = "image_confirmation_{$draft_post_id}_{$imageIndex}";
            // Retrieve the usage status of the image
            $usage_status = get_post_meta($draft_post_id, 'blogcopilot_image_usage_' . esc_attr($imageUrl), true);

            echo '<div class="ai_image_container" style="text-align: center;">';
            echo '<img src="' . esc_url($imageUrl) . '" style="width: 100%; height: auto; border-radius: 10px; transition: transform 0.3s ease;">';

            if ($post_status == 'publish') {
                // Display usage status for published articles
                if ($usage_status == 'featured') {
                    echo '<p>This image is used as a Post Featured.</p>';
                } elseif ($usage_status == 'content') {
                    echo '<p>This image is used in Content.</p>';
                } else {
                    echo '<p>This image is not used.</p>';
                    echo '<button class="btn btn-primary btn-sm ai_select_image" data-image-url="' . esc_attr($imageUrl) . '" data-post-id="' . esc_attr($draft_post_id) . '" data-confirmation-target="' . esc_attr($confirmationId) . '" style="margin-top: 10px;">As Featured</button> ';
                    echo '<button class="btn btn-secondary btn-sm ai_use_in_content" data-image-url="' . esc_attr($imageUrl) . '" data-post-id="' . esc_attr($draft_post_id) . '" data-confirmation-target="' . esc_attr($confirmationId) . '" style="margin-top: 10px;">In Content</button>';    
                 }
            } else {
                // Display usage status for published articles
                if ($usage_status == 'featured') {
                    echo '<p>This image is used as a Post Featured.</p>';
                } elseif ($usage_status == 'content') {
                    echo '<p>This image is used in Content.</p>';
                } else {
                    echo '<button class="btn btn-primary btn-sm ai_select_image" data-image-url="' . esc_attr($imageUrl) . '" data-post-id="' . esc_attr($draft_post_id) . '" data-confirmation-target="' . esc_attr($confirmationId) . '" style="margin-top: 10px;">As Featured</button> ';
                    echo '<button class="btn btn-secondary btn-sm ai_use_in_content" data-image-url="' . esc_attr($imageUrl) . '" data-post-id="' . esc_attr($draft_post_id) . '" data-confirmation-target="' . esc_attr($confirmationId) . '" style="margin-top: 10px;">In Content</button>';    
                }
           }

            echo '<div id="' . esc_attr($confirmationId) . '" style="color: green; display:none; margin-top: 5px;"></div>';
            echo '</div>'; // Close image container
        }
        echo '</div>'; // Close grid layout

        echo '<div id="blogcopilot_io_generate_more_images_container_' . esc_attr($draft_post_id) . '" class="ai_image_container" style="text-align: center;">';
        echo '<button id="ai_generate_more_images_' . esc_attr($draft_post_id) . '" class="btn btn-success btn-sm generate-more-images-button" style="margin-top: 20px;" data-post-title="'.esc_html($title).'" data-post-task="'.esc_html($taskId).'" data-post-id="' . esc_attr($draft_post_id) . '">Generate More Images</button>';
        echo '</div>';

        echo '</div>'; // Close article container
    }
    echo '</div>';
}

function blogcopilot_io_publish_post_callback() {
    // Verify the nonce
    if (!isset($_POST['_ajax_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_ajax_nonce'])), 'blogcopilot_io_publish_post_action')) {
        wp_die('Nonce verification failed, or you do not have permission to perform this action.');
    }

    $post_id = intval($_POST['post_id']);
    $random_date = blogcopilot_io_generate_random_date_within_last_year();

    $post = array(
        'ID' => $post_id,
        'post_date' => $random_date, // Set random post date
        'post_date_gmt' => get_gmt_from_date($random_date), // Set corresponding GMT date        
        'post_status' => 'publish'
    );

    wp_update_post($post);

    if (is_wp_error($post_id)) {
        echo 'Error: ' . esc_html($post_id->get_error_message());
    } else {
        echo esc_html($post_id);
    }

    wp_die(); // this is required to terminate immediately and return a proper response
}
add_action('wp_ajax_blogcopilot_io_publish_post', 'blogcopilot_io_publish_post_callback');

function blogcopilot_io_search_jobs() {
    // Verify the nonce
    if (!isset($_POST['blogcopilot_search_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['blogcopilot_search_nonce'])), 'blogcopilot_search_action')) {
        wp_send_json_error('Nonce verification failed, unauthorized request.');
    }

    // Retrieve search parameters
    $dateFrom = isset($_POST['dateFrom']) ? sanitize_text_field($_POST['dateFrom']) : '';
    $dateTo = isset($_POST['dateTo']) ? sanitize_text_field($_POST['dateTo']) : '';
    $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
    $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';

    // Your logic to get and filter job groups based on the search parameters
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

    if (is_wp_error($response)) {
        wp_send_json_error('Error fetching job data.');
    }

    $body = wp_remote_retrieve_body($response);
    $jobGroupIds = json_decode($body, true);

    if (empty($jobGroupIds['jobs']['jobGroups'])) {
        wp_send_json_success([]);
    }

    $filteredJobs = array_filter($jobGroupIds['jobs']['jobGroups'], function($jobGroup) use ($dateFrom, $dateTo, $title, $status) {
        $jobDate = gmdate('Y-m-d', strtotime($jobGroup['CreatedAt']));
        $jobTitle = count($jobGroup['Tasks']) === 1 ? $jobGroup['Tasks'][0]['title'] : '';
        $jobStatus = $jobGroup['TasksStatus'];

        $dateCondition = (!$dateFrom || $jobDate >= $dateFrom) && (!$dateTo || $jobDate <= $dateTo);
        $titleCondition = !$title || stripos($jobTitle, $title) !== false;
        $statusCondition = !$status || $jobStatus === $status;

        return $dateCondition && $titleCondition && $statusCondition;
    });

    wp_send_json_success(array_values($filteredJobs));
    wp_die(); // this is required to terminate immediately and return a proper response    
}
add_action('wp_ajax_blogcopilot_io_search_jobs', 'blogcopilot_io_search_jobs');

?>
