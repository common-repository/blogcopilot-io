<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
if (!defined('WPINC')) exit; // Exit if accessed directly

function blogcopilot_io_show_nav() {
  $logo_url = plugins_url('../img/blogcopilotio-logo-35.png', __FILE__);
?>

<!-- Navbar Light -->
<nav class="navbar navbar-expand-lg navbar-light bg-white py-3">
  <div class="container">
    <a class="navbar-brand" href="<?php echo esc_url(admin_url('admin.php?page=blogcopilot-io'));?>" target="_blank">
      <img src="<?php echo esc_url($logo_url); ?>" alt="BlogCopilot Icon" />
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navigation" aria-controls="navigation" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navigation">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownPhrases" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Phrases
          </a>
          <ul class="dropdown-menu" aria-labelledby="navbarDropdownPhrases">
            <li><a class="dropdown-item" href="<?php echo esc_url(admin_url('admin.php?page=blogcopilot-phrase-mgmt'));?>">Manage</a></li>
            <li><a class="dropdown-item" href="<?php echo esc_url(admin_url('admin.php?page=blogcopilot-phrase-mgmt&action=add'));?>">Add Phrase</a></li>
          </ul>
        </li>        
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownPosts" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Posts
          </a>
          <ul class="dropdown-menu" aria-labelledby="navbarDropdownPosts">
            <li><a class="dropdown-item" href="<?php echo esc_url(admin_url('admin.php?page=blogcopilot-create-post'));?>">Create Single Post</a></li>
            <li><a class="dropdown-item" href="<?php echo esc_url(admin_url('admin.php?page=blogcopilot-mass-creation'));?>">Create Multiple Posts</a></li>
            <li><a class="dropdown-item" href="<?php echo esc_url(admin_url('admin.php?page=blogcopilot-job-status'));?>">Posts in Progress</a></li>
          </ul>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownKeywords" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            SEO
          </a>
          <ul class="dropdown-menu" aria-labelledby="navbarDropdownKeywords">
            <li><a class="dropdown-item" href="<?php echo esc_url(admin_url('admin.php?page=blogcopilot-view-rankings'));?>">SEO Home</a></li>
            <li><a class="dropdown-item" href="<?php echo esc_url(admin_url('admin.php?page=blogcopilot-view-rankings&tab=2'));?>">Rankings</a></li>
            <li><a class="dropdown-item" href="<?php echo esc_url(admin_url('admin.php?page=blogcopilot-view-rankings&tab=3'));?>">Keywords</a></li>
            <li><a class="dropdown-item" href="<?php echo esc_url(admin_url('admin.php?page=blogcopilot-view-rankings&tab=6'));?>">Site Audit</a></li>                        
          </ul>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownSettings" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Settings
          </a>
          <ul class="dropdown-menu" aria-labelledby="navbarDropdownSettings">
            <li><a class="dropdown-item" href="<?php echo esc_url(admin_url('admin.php?page=blogcopilot-settings'));?>">API</a></li>
            <li><a class="dropdown-item" href="<?php echo esc_url(admin_url('admin.php?page=blogcopilot-settings'));?>">License</a></li>
          </ul>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownHelp" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Help
          </a>
          <ul class="dropdown-menu" aria-labelledby="navbarDropdownHelp">
            <li><a class="dropdown-item" href="<?php echo esc_url(admin_url('admin.php?page=blogcopilot-help#sec_intro'));?>">Getting Started</a></li>
            <li><a class="dropdown-item" href="<?php echo esc_url(admin_url('admin.php?page=blogcopilot-help#sec_phrases'));?>">Phrase Management</a></li>            
            <li><a class="dropdown-item" href="<?php echo esc_url(admin_url('admin.php?page=blogcopilot-help#sec_create_post'));?>">Creating Single Post</a></li>
            <li><a class="dropdown-item" href="<?php echo esc_url(admin_url('admin.php?page=blogcopilot-help#sec_create_multiple'));?>">Creating Multiple Posts</a></li>            
            <li><a class="dropdown-item" href="<?php echo esc_url(admin_url('admin.php?page=blogcopilot-help#sec_seo'));?>">SEO</a></li>
            <li><a class="dropdown-item" href="<?php echo esc_url(admin_url('admin.php?page=blogcopilot-help#sec_settings'));?>">Plugin Settings</a></li>
          </ul>
        </li>
        <?php 
          $license_plan = get_option('blogcopilot_license_plan', '');
          if ($license_plan == 'Free') { ?>
            <li class="nav-item" id="nav-upgrade-button">
              <a href="https://blogcopilot.io" target="_blank" class="btn btn-sm btn-info bg-gradient mt-1 ms-3" role="button">Upgrade!</a>
            </li>
        <?php } ?>
      </ul>
    </div>
  </div>
</nav>
<!-- End Navbar -->

<?php
  $blogcopilot_exec_time_slow = get_option('blogcopilot_exec_time_slow', 2);

  if ($blogcopilot_exec_time_slow == 2) {
    $required_time = 180;
    $current_max_execution_time = ini_get('max_execution_time');
  
    if ((int) $current_max_execution_time < $required_time) {
      update_option('blogcopilot_exec_time_slow', 1);
      echo '
        <div class="alert alert-secondary alert-dismissible fade show my-2" role="alert">
            <span class="alert-icon"><i class="bi bi-exclamation-diamond"></i> </span><span class="alert-text"> Your server settings might prevent this plugin to run properly. Current value of "php max_execution_time" is '.esc_html($current_max_execution_time).', please contact your system administrator or hosting provider and change it to 180 or more.</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
            </button>
        </div>';
    } else {
      update_option('blogcopilot_exec_time_slow', 0);      
    }
  }
}
?>