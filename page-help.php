<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
if (!defined('WPINC')) exit; // Exit if accessed directly


function blogcopilot_io_help_page_content() {
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

  $current_user = wp_get_current_user();  
?>
  <div class="p-4 bg-light">

  <section class="my-4" id="sec_start">
    <div class="container card">
        <form action="<?php echo esc_url(sanitize_text_field($_SERVER['REQUEST_URI'])); ?>" method="post">
          <?php wp_nonce_field('blogcopilot_contact_form', 'blogcopilot_contact_nonce'); ?>
          <div class="col-md-12 m-auto my-3 row">
            <h4>Contact support if you need an assistance</h4>
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

  <section class="my-4" id="sec_intro">
    <div class="container card">
      <div class="row">
        <div class="col-md-6 m-auto my-3">
          <h4>Getting to Know Your Plugin</h4>
          <p class="mt-2">
            The main menu is straightforward, featuring sections like:</p>
              <ul>
              <li><strong>BlogCopilot:</strong> Your starting point with helpful insights.</li>
              <li><strong>Phrase Management</strong>  Manage SEO phrases and associated content efficiently.</li>
              <li><strong>Create Single Post:</strong> Where you craft new blog entries.</li>
              <li><strong>Create Multiple Posts:</strong> For generating multiple posts simultaneously.</li>
              <li><strong>Posts in Progress:</strong> To track the progress of your post creations.</li>
              <li><strong>View Results:</strong> Check out your content here (primarily for bulk posts).</li>
              <li><strong>SEO Features:</strong> Access advanced SEO tools and insights in the Silver and Gold versions.</li>
              <li><strong>Plugin Settings:</strong> Customize your plugin preferences for optimal post creation.</li>
              <li><strong>Help:</strong> Find manuals and tutorials here.</li>
              </ul>
          <p class="mt-2">
            Each page includes a top menu for simpler navigation.
          </p>
        </div>
        <div class="col-md-5 m-auto">
          <div class="position-relative">
            <img class="w-100 z-index-2" src="<?php echo esc_url(plugins_url('assets/blogcopilot-01.jpg', __FILE__)); ?>" alt="Plugin Main Menu">
        </div>
        </div>
      </div>
    </div>
  </section>

  <section class="my-4" id="sec_main_page">
    <div class="container card">
      <div class="row">
        <div class="col-md-6 m-auto my-3">
          <h4>Main Blog Page Overview</h4>
          <p class="mt-2">
          The main page offers a snapshot of your license type, phrases and how they rank and article generation statistics. License types come with specific quotas to ensure system integrity, showing how many articles you can generate.
          </p>
        </div>
        <div class="col-md-5 m-auto">
          <div class="position-relative">
            <img class="w-100 z-index-2" src="<?php echo esc_url(plugins_url('assets/blogcopilot-02.jpg', __FILE__));?>" alt="Plugin Main Page">
          </div>
        </div>
      </div>
    </div>
  </section>


  <section class="my-4" id="sec_phrases">
    <div class="container card">
      <div class="row">
        <div class="col-md-6 m-auto my-3">
          <h4>Phrase Management</h4>
          <h5>Key Features</h5>
          <ul>
          <li><strong>Add and Organize Phrases:</strong> Manage your SEO phrases easily.</li>
          <li><strong>Link Articles:</strong> Connect phrases to drafts, published articles, or existing posts.</li>
          <li><strong>Track Status:</strong> Monitor the creation and publication status of your articles.</li>
          </ul>
          <h5>Phrase Statuses</h5>
          <ul>
          <li><strong>Pending (AI Awaiting):</strong> Phrase added, and article creation is in progress (AI generated).
          <li><strong>Draft Available:</strong> Draft created but not published.
          <li><strong>No Article:</strong> Phrase added, but no linking article.
          <li><strong>User Published:</strong> User-updated draft published live.
          <li><strong>AI Published:</strong> AI-generated article published live.
          </ul>
          <h5>Basic Operations</h5>
          <h6>Adding a Phrase</h6>
          <ol>
            <li><strong>Go to Phrase Management:</strong> Select "Phrase Management" under the BlogCopilot menu in the WordPress admin panel.</li>
            <li><strong>Add a New Phrase:</strong> Click "Add New Phrase," enter the phrase, select the category, language, style and some other optional elements.</li>
            <li><strong>Decide if you want AI to generate article for the new phrase</strong>. Select "Generate Draft Content only" and system will generate draft content, linked to this phrase. Select "Generate Full Article" and system will write full article with images(!). You can also select "No content generation" if you plan to write the port yourself or post is already written.</li>            
            <li><strong>Click "Create"</strong>.</li>                        
          </ol>
          <h6>Linking Posts to Phrases</h6>
          <ol>
            <li><strong>Generate Draft Content only:</strong> - draft post will be created (not published)</li>
            <li><strong>Generate Full Article:</strong> - post with images will be created and published automatically</li>
            <li><strong>No content generation (link to your article):</strong> - after adding new phrase, you will be able to link it to existing article (or leave unlinked).</li>
          </ol>
          <h6>Generating linking posts</h6>
          <ol>
            <li>For Phrases added, you can boost their rankings by automatically generating linking articles. Just click "Generate Linking Articles", system will display popup with suggested keywords (in paid versions), select keywords and click Generate Articles. System will create new articles, each will have from 2 to 4 links linking master Phrase article.</li>
          </ol>          
          <h6>Managing Phrases</h6>
          <ul>
            <li><strong>View and Filter:</strong> Use the search form to filter phrases by keyword, category, or status.</li>
            <li><strong>Delete a Phrase:</strong> Locate the phrase, click "Delete," and confirm in the modal.</li>
          </ul>
          <h6>Improving SEO</h6>
          <ul>
            <li><strong>Add Relevant Phrases:</strong> Continuously manage key phrases.</li>
            <li><strong>Create Quality Content:</strong> Draft, edit, and publish high-quality articles linked to phrases.</li>
            <li><strong>Optimize:</strong> Regularly check and optimize the performance of your phrases and articles.</li>
          </ul>
        </div>
        <div class="col-md-5 m-auto">
          <div class="position-relative">
            <img class="w-100 z-index-2" src="<?php echo esc_url(plugins_url('assets/blogcopilot-03.jpg', __FILE__)); ?>" alt="Phrase Main">
            <img class="w-100 z-index-2" src="<?php echo esc_url(plugins_url('assets/blogcopilot-04.jpg', __FILE__)); ?>" alt="Phrase Main">
            <img class="w-100 z-index-2" src="<?php echo esc_url(plugins_url('assets/blogcopilot-05.jpg', __FILE__)); ?>" alt="Phrase Main">
            <img class="w-100 z-index-2 mt-3" src="<?php echo esc_url(plugins_url('assets/blogcopilot-06.jpg', __FILE__)); ?>" alt="Phrase Main">
            <img class="w-100 z-index-2 mt-3" src="<?php echo esc_url(plugins_url('assets/blogcopilot-07.jpg', __FILE__)); ?>" alt="Phrase Main">                        
        </div>
        </div>
      </div>
    </div>
  </section>

  <section class="my-4" id="sec_create_post">
    <div class="container card">
      <div class="row">
        <div class="col-md-6 m-auto my-3">
          <h4>Crafting Your First Blog Entry</h4>
          <p class="mt-2">
          Navigate to "Create Single Post"<br/><br/>
          Here input your Post Title, choose a Category, and hit "Generate." It's that simple. Content generation may take 1-3 minutes per article - if you ticked "Generate in real time" (might not work on some hosting platforms).
          <br/><br/>
          On our example we have entered “Ragatoni Pasta”, and selected Recipes category in our demo blog. Post generation can take few minutes so please be patient - and if your server settings are preventing live generation (after some time you see a blank page), please do not select live generation.
          </p>
        </div>
        <div class="col-md-5 m-auto">
          <div class="position-relative">
            <img class="w-100 z-index-2" src="<?php echo esc_url(plugins_url('assets/blogcopilot-10.jpg', __FILE__));?>" alt="New post screen">
            <img class="w-100 z-index-2" src="<?php echo esc_url(plugins_url('assets/blogcopilot-11.jpg', __FILE__));?>" alt="Post generation screen">            
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="my-4" id="sec_create_post2">
    <div class="container card">
      <div class="row">
        <div class="col-md-6 m-auto my-3">
          <h4>Crafting Your First Blog Entry</h4>
          <p class="mt-2">
          Now, a new article is generated - you can see how many words it has and also see pictures that could be used - now, it is time to select pictures for the generated article. Just click below the image you want to add to the content, or to add it as a featured image - after the click, a message will appear.
          <br/><br/>
          If you need more pictures - just click Generate More Images button - new images will be generated and will appear at the bottom.
          </p>
        </div>
        <div class="col-md-5 m-auto">
          <div class="position-relative">
            <img class="w-100 z-index-2" src="<?php echo esc_url(plugins_url('assets/blogcopilot-12.jpg', __FILE__));?>" alt="Generated post result">
          </div>
        </div>
      </div>
    </div>
  </section>  
  
  <section class="my-4" id="sec_create_post3">
    <div class="container card">
      <div class="row">
        <div class="col-md-6 m-auto my-3">
          <h4>Additional Post Creation Options</h4>
          <p class="mt-2">
          On "Create Post" page, by selecting "Show Optional Fields," you unlock more features:
          <ul>
          <li><strong>Generate Premium:</strong> Create longer posts.</li>
          <li><strong>"Save as draft" toggle:</strong> Create posts as drafts for later editing.</li>
          <li><strong>Language Selection:</strong> Ideal for multilingual blogs.</li>
          <li><strong>Keywords and Style Customization:</strong> Enhance content relevance and alignment with your blog's style.</li>
          </ul>
          Just click Generate, and the article will be created.
          </p>
        </div>
        <div class="col-md-5 m-auto">
          <div class="position-relative">
            <img class="w-100 z-index-2" src="<?php echo esc_url(plugins_url('assets/blogcopilot-13.jpg', __FILE__));?>" alt="New post screen with extra options">
          </div>
        </div>
      </div>
    </div>
  </section>  

  <section class="my-4" id="sec_create_post4">
    <div class="container card">
      <div class="row">
        <div class="col-md-6 m-auto my-3">
          <h4>Article Regeneration</h4>
          <p class="mt-2">
          If the generated content doesn't meet your expectations, you can regenerate the article. Note that this will remove previously selected images, requiring manual re-addition (in post edit features from wordpress gallery).
          </p>
        </div>
        <div class="col-md-5 m-auto">
          <div class="position-relative">
            
          </div>
        </div>
      </div>
    </div>
  </section>  

  <section class="my-4" id="sec_create_post5">
    <div class="container card">
      <div class="row">
        <div class="col-md-6 m-auto my-3">
          <h4>Premium Articles</h4>
          <p class="mt-2">
          The default post size is usually around 500-800 words. For more in-depth content, premium articles extend up to 3000, 4000 or even 5000 words but take longer to process. Please tick checkbox called "Generate Premium Post" and select required article length. Article generation will happen in the background. Check the Posts in Progress page for updates.
          </p>
        </div>
        <div class="col-md-5 m-auto">
          <div class="position-relative">
            <img class="w-100 z-index-2" src="<?php echo esc_url(plugins_url('assets/blogcopilot-14.jpg', __FILE__));?>" alt="New post screen with extra options">            
          </div>
        </div>
      </div>
    </div>
  </section>  

  <section class="my-4" id="sec_create_multiple">
    <div class="container card">
      <div class="row">
        <div class="col-md-6 m-auto my-3">
          <h4>Create Multiple Posts</h4>
          <p class="mt-2">
          Generate several articles at once, keeping in mind your monthly quota. Overreaching this limit will delay the completion of all articles.
          <br/><br/>
          Enter as many article titles as you want, select the proper category for each article, and press the Generate button. If 10 articles at one time is not enough, click Add 10 More Rows and enter even more. Generation has monthly quotas, so if you request 20 articles to be generated and your plan quota is 10 articles per month - come back after 1 month, and you will see the results.
          </p>
        </div>
        <div class="col-md-5 m-auto">
          <div class="position-relative">
          <img class="w-100 z-index-2" src="<?php echo esc_url(plugins_url('assets/blogcopilot-20.jpg', __FILE__));?>" alt="Bult post generation">
          </div>
        </div>
      </div>
    </div>
  </section>  

  <section class="my-4" id="sec_create_multiple2">
    <div class="container card">
      <div class="row">
        <div class="col-md-6 m-auto my-3">
          <h4>Checking Status</h4>
          <p class="mt-2">
          The Post in Progress status page shows the progress and completion of your requests, including a detailed view of each job's status. 
          <br/><br/>
          View Details button will show what's in each job - requested article titles and generation status.
          <br/><br/>
          Icon in "Published?" column will indicate if all posts are published, if some are published and some are not, or that no post is published yet.          
          </p>
        </div>
        <div class="col-md-5 m-auto">
          <div class="position-relative">
          <img class="w-100 z-index-2" src="<?php echo esc_url(plugins_url('assets/blogcopilot-21.jpg', __FILE__));?>" alt="Bult post generation">
          </div>
        </div>
      </div>
    </div>
  </section>  

  <section class="my-4" id="sec_create_multiple3">
    <div class="container card">
      <div class="row">
        <div class="col-md-6 m-auto my-3">
          <h4>Publishing Generated Articles</h4>
          <p class="mt-2">
          Once your articles are ready, you can add images, edit, or publish them directly. If needed, generate additional images to enhance your posts.
          </p>
        </div>
        <div class="col-md-5 m-auto">
          <div class="position-relative">
          <img class="w-100 z-index-2" src="<?php echo esc_url(plugins_url('assets/blogcopilot-22.jpg', __FILE__));?>" alt="Bult post generation results">
          </div>
        </div>
      </div>
    </div>
  </section>    

  <section class="my-4" id="sec_seo">
    <div class="container card">
      <div class="row">
        <div class="col-md-6 m-auto my-3">
          <h4>SEO for Silver and Gold Users</h4>
          <p class="mt-2">
          Advanced SEO features are available for Silver or Gold license holders. Upgrade for enhanced optimization tools and insights.
          </p>
          <h4>Dashboard: Monitor Your SEO Performance</h4>
          <p class="mt-2">
            The <strong>Dashboard</strong> is your central hub for monitoring the overall performance of your SEO efforts. 
            <ul>
              <li><strong>View SERP Statistics:</strong> Quickly access statistics about your site’s Search Engine Results Page (SERP) performance.</li>
              <li><strong>Performance Over Time:</strong> Track how your rankings have changed over time, allowing you to measure the effectiveness of your SEO strategies.</li>
            </ul>
          </p>
          <h4>Keyword Rankings: Track Your SERP Positions</h4>
          <p class="mt-2">
            In the <strong>Keyword Rankings</strong> section, you can dive deeper into the performance of your specific keywords.
            <ul>
              <li><strong>SERP Positions:</strong> See where each of your tracked keywords ranks on search engines, both for desktop and mobile.</li>
              <li><strong>All rankings:</strong> All keywords we could find in our database where google ranks your website.</li>
            </ul>
          </p>
          <h4>Keywords: Manage Your Tracked Keywords</h4>
          <p class="mt-2">
            The <strong>Keywords</strong> section allows you to manage the keywords you are tracking.
            <ul>
              <li><strong>Add Keywords:</strong> Easily add new keywords that you want to track and optimize for.</li>
              <li><strong>Remove Keywords:</strong> If certain keywords are no longer relevant, remove them to maintain focus.</li>
            </ul>
          </p>
          <h4>Keywords Research: Discover New Opportunities</h4>
          <p class="mt-2">
            The <strong>Keywords Research</strong> section helps you explore new keyword opportunities.
            <ul>
              <li><strong>Search for Keywords:</strong> Use the built-in keyword research tool to discover new keywords relevant to your content.</li>
              <li><strong>Generate Articles:</strong> Ask the system to generate article ideas or full articles based on your selected keywords.</li>
            </ul>
          </p>
          <h4>SEO Competition: Analyze Your Competitors</h4>
          <p class="mt-2">
            The <strong>SEO Competition</strong> section gives you insights into your competitors' strategies.
            <ul>
              <li><strong>Competing Sites:</strong> Identify the top competitors for your tracked keywords and see where they rank.</li>
              <li><strong>Note, that for niche sites we can have troubles identifying competition.</strong></li>
            </ul>
          </p>
          <h4>SEO Site Audit: Evaluate Your Site’s SEO Health</h4>
          <p class="mt-2">
            The <strong>SEO Site Audit</strong> section provides a comprehensive overview of your domain’s SEO quality.
            <ul>
              <li><strong>Domain Overview:</strong> Get a brief report on your domain’s overall SEO health, including on-page SEO, backlinks, and site speed.</li>
            </ul>
          </p>          
        </div>
        <div class="col-md-5 m-auto">
          <div class="position-relative">
            <img class="w-100 z-index-2" src="<?php echo esc_url(plugins_url('assets/blogcopilot-30.jpg', __FILE__));?>" alt="SEO Functionalities">
            <img class="w-100 z-index-2" src="<?php echo esc_url(plugins_url('assets/blogcopilot-31.jpg', __FILE__));?>" alt="SEO Functionalities">
            <img class="w-100 z-index-2" src="<?php echo esc_url(plugins_url('assets/blogcopilot-32.jpg', __FILE__));?>" alt="SEO Functionalities">
            <img class="w-100 z-index-2" src="<?php echo esc_url(plugins_url('assets/blogcopilot-33.jpg', __FILE__));?>" alt="SEO Functionalities">
            <img class="w-100 z-index-2" src="<?php echo esc_url(plugins_url('assets/blogcopilot-34.jpg', __FILE__));?>" alt="SEO Functionalities">
            <img class="w-100 z-index-2" src="<?php echo esc_url(plugins_url('assets/blogcopilot-35.jpg', __FILE__));?>" alt="SEO Functionalities">                                                            
          </div>
        </div>
      </div>
    </div>
  </section>  

  <section class="my-4" id="sec_settings">
    <div class="container card">
      <div class="row">
        <div class="col-md-6 m-auto my-3">
          <h4>Configuring Settings</h4>
          <p class="mt-2">
          Visit the Settings page to confirm your Blog Title and Default Language are correct. Adding a Blog Description can enhance article alignment with your blog's theme. Don't forget to save your changes.
          Here you can also ask the system to add "AI Generated" caption to each AI generated image.
          </p>
        </div>
        <div class="col-md-5 m-auto">
          <div class="position-relative">
            <img class="w-100 z-index-2" src="<?php echo esc_url(plugins_url('assets/blogcopilot-40.jpg', __FILE__));?>" alt="Plugin settings">
          </div>
        </div>
      </div>
    </div>
  </section>


  <section class="my-4" id="sec_plugin_activation">
    <div class="container card">
      <div class="row">
        <div class="col-md-6 m-auto my-3">
          <h4>Activating the Plugin</h4>
          <p class="mt-2">
            After installing the plugin, click "Activate Plugin" to get started. Once activated, you'll find the plugin easily accessible under a new menu at the very top of the left sidebar in your WordPress dashboard.
          </p>
        </div>
        <div class="col-md-5 m-auto">
          <div class="position-relative">

          </div>
        </div>
      </div>
    </div>
  </section>

  </div>

<?php
}
?>
