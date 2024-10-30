<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
if (!defined('WPINC')) exit; // Exit if accessed directly

function blogcopilot_io_main_page_content() {
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['blogcopilot_contact_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['blogcopilot_contact_nonce'])), 'blogcopilot_contact_form')) {
    // Sanitize and process form data
    $name = sanitize_text_field($_POST['name']);
    $email = sanitize_email($_POST['email']);
    $message = sanitize_textarea_field($_POST['message']);
    $licenseKey = get_option('blogcopilot_license_number', '');
    $domain = get_option('blogcopilot_blog_domain', '');
    
    $to = 'support@blogcopilot.io'; 
    $subject = 'New Support Case from ' . $name;
    $body = "Name: $name\n\nEmail: $email\n\nDomain: $domain\n\nLicense: $licenseKey\n\nMessage:\n$message";
    $headers = array('Content-Type: text/plain; charset=UTF-8');
    
    // Send email
    wp_mail($to, $subject, $body, $headers);
    
    echo '
    <div class="alert alert-success alert-dismissible fade show my-2" role="alert">
        <span class="alert-icon"><i class="bi bi-hand-thumbs-up-fill"></i> </span><span class="alert-text"> Thank you for your message. We will get back to you soon.</span>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
        </button>
    </div>
    ';    
  }  

  // get license info
  $apiUrl = get_option('blogcopilot_api_url', '') . '/api-endpoint-license-info.php';
  $licenseKey = get_option('blogcopilot_license_number', '');
  $domain = get_option('blogcopilot_blog_domain', '');

  $payload = [
      'licenseKey' => $licenseKey,
      'domain' => $domain
  ];

  $response = wp_remote_post($apiUrl, [
      'body' => wp_json_encode($payload, JSON_UNESCAPED_UNICODE),
      'headers' => [
          'Content-Type' => 'application/json',
      ],
  ]);
  
  $is_license_error = false;
  if (is_wp_error($response)) {
      return 'Error. Please refresh the page.';
  } else {
      $body = wp_remote_retrieve_body($response);
      $license_data = json_decode($body, true);

      if (isset($license_data['status']) && $license_data['status'] === 'error') {
        $is_license_error = true;
      } else {
        update_option('blogcopilot_license_plan', $license_data['data']['PlanName']);
      }      
  }

  // get usage info
  $apiUrl = get_option('blogcopilot_api_url', '') . '/api-endpoint-license-usage.php';
  $licenseKey = get_option('blogcopilot_license_number', '');
  $domain = get_option('blogcopilot_blog_domain', '');

  $payload = [
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
      return 'Error. Please refresh the page.';
  } else {
      $body = wp_remote_retrieve_body($response);

      if (isset($body['status']) && $body['status'] === 'error') {
        $is_license_error = true;
      } else {
        $stat_data = json_decode($body, true);
      }        
  }

  if ($is_license_error) {
    $current_user = wp_get_current_user();      
?>

<div class="px-4 bg-light">

<section>
  <div class="container card">
      <form action="<?php echo esc_url(sanitize_text_field($_SERVER['REQUEST_URI'])); ?>" method="post">
        <?php wp_nonce_field('blogcopilot_contact_form', 'blogcopilot_contact_nonce'); ?>
        <div class="col-md-12 m-auto my-3 row">
          <h4>Contact support</h4>
          <!-- Name Field -->
          <div class="col-md-6 align-items-center row">
              <label for="name" class="col-md-4 col-form-label text-md-end"><?php esc_html_e('Your Name', 'blogcopilot-io'); ?></label>
              <div class="col-md-6">
                  <input type="text" class="form-control" id="name" name="name" value="<?php echo esc_attr($current_user->display_name); ?>" required>
              </div>
          </div>
          
          <!-- Email Field -->
          <div class="col-md-6 align-items-center row">
              <label for="email" class="col-md-3 col-form-label text-md-end"><?php esc_html_e('Your Email', 'blogcopilot-io'); ?></label>
              <div class="col-md-6">
                  <input type="email" class="form-control" id="email" name="email" value="<?php echo esc_attr($current_user->user_email); ?>" required>
              </div>
          </div>
        </div>
      
        <!-- Message Field -->
        <div class="mb-3 align-items-center row">
            <label for="message" class="col-md-2 col-form-label text-md-end"><?php esc_html_e('Your Message', 'blogcopilot-io'); ?></label>
            <div class="col-md-8">
                <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
            </div>
        </div>
      
        <!-- Submit Button -->
        <div class="mb-3 row">
            <div class="col-md-9">
                <button type="submit" class="btn btn-primary">Send</button>
            </div>
        </div>

      </form>
  </div>
</section>

</div>

<?php
    exit;
  }

?>
    <div class="p-4 bg-light">

    <section class="my-1">
      <div class="container card">
        <div class="row">
          <div class="col-md-12 m-auto my-3 ps-4">
            <h4>Welcome to BlogCopilot.io</h4>
            <div class="container card">
              <div class="row">
                <div class="col-md-3 m-auto my-3">        
                Your license:
                </div>
                <div class="col-md-9 m-auto my-3">        
                <span class="badge badge-pill bg-dark"><?php echo esc_html($license_data['data']['PlanName']); ?></span>
                <?php if ($license_data['data']['PlanName'] == "Free") { ?>
                &nbsp; &nbsp; <a href="https://blogcopilot.io" target="_blank">Upgrade to Silver and increase usage quotas!</a>
                <?php } ?>
                <?php if ($license_data['data']['PlanName'] == "Silver") { ?>
                &nbsp; &nbsp; <a href="https://blogcopilot.io" target="_blank">Upgrade to Gold and increase usage quotas!</a>
                <?php } ?>                
                </div>  
              </div>
            
              <div class="row">
                <div class="col-md-3 m-auto my-3">        
                  Usage statistics:<br/>(quota renews on <?php echo esc_html($license_data['data']['ExpiryDate']); ?>)
                </div>
                <div class="col-md-9 m-auto my-3">
                  <div class="card table-responsive" style="padding: 0.2em">
                    <table class="table align-items-center mb-0">
                    <thead>
                    <tr>
                    <th class="text-uppercase text-secondary text-xs font-weight-bolder opacity-7"></th>
                    <th class="text-uppercase text-secondary text-xs font-weight-bolder opacity-7">No</th>
                    <th class="text-uppercase text-secondary text-xs font-weight-bolder opacity-7">SERP</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                    <td><p class="text-secondary mb-0">Main Phrases Total</p></td>
                    <td><p class="text-secondary mb-0"><?php echo esc_html($stat_data['data']['total_phrases']); ?></p></td>
                    <td><p class="text-secondary mb-0">?</p></td>
                    </tr>
                    <tr>
                    <td><p class="text-secondary mb-0">Linking Phrases Total</p></td>
                    <td><p class="text-secondary mb-0"><?php echo esc_html($stat_data['data']['subphrases_count']); ?></td>
                    <td><p class="text-secondary mb-0">?</p></td>
                    </tr>
                    <tr>
                    <td><p class="text-secondary mb-0">Phrases without articles</p></td>
                    <td><p class="text-secondary mb-0"><?php echo esc_html($stat_data['data']['no_article_count']); ?></td>
                    <td></td>
                    </tr>
                    <tr>
                    <td><p class="text-secondary mb-0">Phrases with draft article (not published)</p></td>
                    <td><p class="text-secondary mb-0"><?php echo esc_html($stat_data['data']['draft_available_count']); ?></td>
                    <td></td>
                    </tr>                    
                    <tr>
                    <td><p class="text-secondary mb-0">Phrases where article generation is in progress))</p></td>
                    <td><p class="text-secondary mb-0"><?php echo esc_html($stat_data['data']['pending_count']); ?></td>
                    <td></td>
                    </tr>                    
                    <tr>
                    <td><p class="text-secondary mb-0">Phrases with article (ready to rank)</p></td>
                    <td><p class="text-secondary mb-0"><?php echo esc_html($stat_data['data']['user_published_count']); ?></td>
                    <td></td>
                    </tr>                                                            
                    <tr>
                    <td><p class="text-secondary mb-0">Phrases with AI generated article (ready to rank)</p></td>
                    <td><p class="text-secondary mb-0"><?php echo esc_html($stat_data['data']['ai_published_count']); ?></td>
                    <td></td>
                    </tr>                                                            
                    </tbody>
                    </table>
                  </div>  

                  <div class="card table-responsive" style="padding: 0.2em">
                    <table class="table align-items-center mb-0">
                    <thead>
                    <tr>
                    <th class="text-uppercase text-secondary text-xs font-weight-bolder opacity-7"></th>
                    <th class="text-uppercase text-secondary text-xs font-weight-bolder opacity-7">Today</th>
                    <th class="text-uppercase text-secondary text-xs font-weight-bolder opacity-7">This Subscription</th>
                    <th class="text-uppercase text-secondary text-xs font-weight-bolder opacity-7">All</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                    <td><p class="text-secondary mb-0">Articles</p></td>
                    <td><p class="text-secondary mb-0"><?php echo esc_html($stat_data['data']['today_articles']); ?></p></td>
                    <td><p class="<?php if ($stat_data['data']['this_month_articles'] >= $stat_data['data']['quota_articles_monthly']) echo "text-danger"; else echo "text-secondary mb-0";?>"><?php echo esc_html($stat_data['data']['this_month_articles']).' out of '.esc_html($stat_data['data']['quota_articles_monthly']); ?></td>
                    <td><p class="text-secondary mb-0"><?php echo esc_html($stat_data['data']['total_articles']); ?></td>
                    </tr>
                    <tr>
                    <td><p class="text-secondary mb-0">Words</p></td>
                    <td><p class="text-secondary mb-0"><?php echo esc_html($stat_data['data']['today_words']); ?></td>
                    <td><p class="text-secondary mb-0"><?php echo esc_html($stat_data['data']['this_month_words']).' out of Unlimited '; ?></td>
                    <td><p class="text-secondary mb-0"><?php echo esc_html($stat_data['data']['total_words']); ?></td>
                    </tr>
                    <tr>
                    <td><p class="text-secondary mb-0">Images</p></td>
                    <td><p class="text-secondary mb-0"><?php echo esc_html($stat_data['data']['today_images']); ?></p></td>
                    <td><p class="<?php if ($stat_data['data']['this_month_images'] >= $stat_data['data']['quota_images_monthly']) echo "text-danger"; else echo "text-secondary mb-0";?>"><?php echo esc_html($stat_data['data']['this_month_images']).' out of '.esc_html($stat_data['data']['quota_images_monthly']); ?></td>
                    <td><p class="text-secondary mb-0"><?php echo esc_html($stat_data['data']['total_images']); ?></td>
                    </tr>
                    <?php if ($license_data['data']['PlanName'] != "Free") { ?>
                      <tr>
                        <td><p class="text-secondary mb-0">Keywords research</p></td>
                        <td><p class="text-secondary mb-0"><?php echo esc_html($stat_data['data']['today_seo_keywords_calls']); ?></p></td>
                        <td><p class="<?php if (($stat_data['data']['this_month_seo_keywords_calls'])>=($stat_data['data']['quota_seo_keywords_monthly'])) echo "text-danger"; else echo "text-secondary mb-0";?>"><?php echo esc_html($stat_data['data']['this_month_seo_keywords_calls']).' out of '.esc_html($stat_data['data']['quota_seo_keywords_monthly']); ?></td>
                        <td><p class="text-secondary mb-0"><?php echo esc_html($stat_data['data']['total_seo_keywords_calls']); ?></td>
                      </tr>                                              
                    <?php } ?>                                  
                    </tbody>
                    </table>
                  </div>  
                </div>
              </div>          
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="my-4">
      <div class="container card">
        <div class="row">
          <div class="col-md-6 m-auto my-3">
            <h4>What is BlogCopilot.io ?</h4>
            <p class="mt-2">
           <strong>BlogCopilot.io</strong> is your the ultimate tool for elevating blogging journey. This cutting-edge WordPress plugin is designed to revolutionize your content creation and management, making the blogging process smoother and more efficient. Experience the transformative power of BlogCopilot's intuitive interface and robust features tailored for blogging excellence.
            </p>
          </div>
          <div class="col-md-5 m-auto">
            <div class="position-relative">
              <img class="w-100 z-index-2" src="<?php echo esc_url(plugins_url('img/macbook.png', __FILE__));?>" alt="image of computer">
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="my-4">
      <div class="container card">
        <div class="row">
          <div class="col-md-7 m-auto my-3">
            <h4>What can BlogCopilot.io do?</h4>
            <div class="row justify-content-start my-4">
              <div class="col-md-6 ">
                <div class="info">
                  <i class="bi bi-journal-text fs-1 text-info mb-3"></i>
                  <h5>Create Posts</h5>
                  <p>Effortlessly create captivating posts complete with images. Simply input your title and watch as BlogCopilot crafts a polished article in moments.</p>
                </div>
              </div>
              <div class="col-md-6">
                <div class="info">
                  <i class="bi bi-stack fs-1 text-info mb-3"></i>
                  <h5>Bulk Creation</h5>
                  <p>Maximize your productivity with our bulk creation feature. Input a list of topics and let BlogCopilot generate multiple posts simultaneously. Review and refine them at your leisure.</p>
                </div>
              </div>
            </div>
            <div class="row justify-content-start">
              <div class="col-md-6">
                <div class="info">
                  <i class="bi bi-globe fs-1 text-info mb-3"></i>
                  <h5>Phrase Management</h5>
                  <p>Manage phrases you want to rank high, and avoid canibalization.</p>
                </div>
              </div>
              <div class="col-md-6">
                <div class="info">
                  <i class="bi bi-sliders fs-1 text-info mb-3"></i>
                  <h5>Customization</h5>
                  <p>BlogCopilot is plug-and-play with no additional setup required. For those seeking a personalized touch, delve into our advanced tuning settings to make each post uniquely yours.</p>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-4 m-auto">
            <div class="card">
              <div class="card-header p-2 z-index-2">
                <a class="d-block blur-shadow-image">
                  <img src="<?php echo esc_url(plugins_url('img/update-to-pro.jpeg', __FILE__));?>" alt="img-colored-shadow" class="img-fluid border-radius-lg">
                </a>
              </div>
              <div class="card-body text-center">
              <?php if ($license_data['data']['PlanName'] == "Free") { ?>
                <h5 class="font-weight-normal">
                  <a href="https://blogcopilot.io" target="_blank">Get even more with Silver or Gold versions</a>
                </h5>
                <p class="mb-0">
                Elevate your blogging with BlogCopilot Silver: Enhanced post limits, advanced SEO tools, strategic content planning features, and much more!
                </p>
                <a href="https://blogcopilot.io" target="_blank" class="btn btn-sm btn-info bg-gradient mb-0 mt-4 mt-md-2">Find out more</a>                
              <?php } ?>
              <?php if ($license_data['data']['PlanName'] == "Silver") { ?>
                <h5 class="font-weight-normal">
                  <a href="https://blogcopilot.io" target="_blank">Get even more with Gold versions</a>
                </h5>
                <p class="mb-0">
                Elevate your blogging with BlogCopilot Gold: Enhanced post limits, advanced SEO tools, strategic content planning features, and much more!
                </p>
                <a href="https://blogcopilot.io" target="_blank" class="btn btn-sm btn-info bg-gradient mb-0 mt-4 mt-md-2">Find out more</a>                
              <?php } ?>   
              <?php if ($license_data['data']['PlanName'] == "Gold") { ?>
                <h5 class="font-weight-normal">
                  Enjoy your blogcopilot.io experience.
                </h5>
                <p class="mb-0">
                Check our company latest news.
                </p>
                <a href="https://blogcopilot.io" target="_blank" class="btn btn-sm btn-info bg-gradient mb-0 mt-4 mt-md-2">Find out more</a>                
              <?php } ?>   
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    </div>

<?php
}
?>
