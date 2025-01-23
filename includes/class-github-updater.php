<?php

class Github_Updater {

  public function __construct() {
    add_filter('site_transient_update_plugins', [$this, 'check_github_plugin_update']);
  }

  public function check_github_plugin_update($transient) {
    // Check for the paycove_last_checked transient.
    $last_checked = get_transient('paycove_last_checked');

    // Return early if the transient is not expired.
    if ($last_checked && $last_checked > time() - 10 * MINUTE_IN_SECONDS) {
        set_transient('paycove_last_checked', time(), 10 * MINUTE_IN_SECONDS);
        return $transient;
    }
  
    if (empty($transient->checked)) {
        return $transient;
    }

    // GitHub repository information
    $repo_owner = 'Paycove';
    $repo_name = 'woocommerce-plugin';
    $repo_url = "https://api.github.com/repos/$repo_owner/$repo_name/releases/latest";
    
    // Make a request to GitHub API to get the latest release
    $response = wp_remote_get($repo_url);
    
    if (is_wp_error($response)) {
        return $transient;
    }
    
    $data = json_decode(wp_remote_retrieve_body($response));

    // Get the latest release version
    $latest_version = $data->tag_name ?? '0.0.0';
    $plugin_slug = 'paycove';

    // Get the current installed version
    $installed_version = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_slug . '/index.php')['Version'];

    // Compare versions and update if necessary
    if (version_compare($installed_version, $latest_version, '<')) {
        $transient->response[$plugin_slug . '/index.php'] = (object) array(
            'url' => $data->html_url,
            'slug' => $plugin_slug,
            'new_version' => $latest_version,
            'package' => $data->assets[0]->browser_download_url,
        );
    }
    
    return $transient;
  }
}

new Github_Updater();
