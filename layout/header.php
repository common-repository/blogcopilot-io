<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
if (!defined('WPINC')) exit; // Exit if accessed directly

function blogcopilot_io_header() {
?>

<div class="page">

<?php
}

function blogcopilot_io_check_license() {
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
  
  $is_license_ok = true;
  if (is_wp_error($response)) {
      return false;
  } else {
      $body = wp_remote_retrieve_body($response);
      $license_data = json_decode($body, true);

      if (isset($license_data['status']) && $license_data['status'] === 'error') {
        $is_license_ok = false;
      } else {
        update_option('blogcopilot_license_plan', $license_data['data']['PlanName']);
      }      
  }

  if (!$is_license_ok) {
?>

<div class="p-4 bg-light">

<section class="my-1">
  <div class="container card">
    <div class="row">
      <div class="col-md-12 m-auto my-3 ps-4">
        <h4>Welcome to BlogCopilot.io</h4>
        <div class="container card">
              <div class="row">
                <div class="col-md-12 m-auto my-3" style="color: #856404; background-color: #fff3cd; border-color: #ffeeba;">
                <i class="fas fa-exclamation-triangle"></i>        
                We appologies, but there seems to be an issue with your license. 
                </div>
                <div class="col-md-12 m-auto my-3">        
                Please note that the BlogCopilot.io plugin requires internet access to function properly. Therefore, it can only work correctly on a publicly hosted website and will not function well in a local development environment. Domains such as localhost, 127.0.0.1, and similar will not support the plugin's features.
                <br/><br/>
                Additionally, your website must be able to access https://api.blogcopilot.io to ensure seamless integration and functionality. If you encounter any issues, please verify that your website has internet access and is not restricted by firewall or network settings.
                <br/><br/>
                Thank you for understanding and ensuring these requirements are met for optimal performance of BlogCopilot.io.
                </div>
                <div class="col-md-12 m-auto my-3" style="color: #856404; background-color: #fff3cd; border-color: #ffeeba;">
                If above is setup properly, please contact our support using <a href="mailto:support@blogcopilot.io">support@blogcopilot.io</a> email.
                </div>
              </div>
        </div>
      </div>
    </div>
  </div>
</section>

</div>

<?php
        return false;
    } else {
        return true;
    }

}
?>