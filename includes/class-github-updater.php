<?php

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly
class Github_Updater
{
    public $plugin_url = 'https://paycove.io';
    public $plugin_slug = 'paycove';

    public function __construct()
    {
        add_filter('site_transient_update_plugins', [$this, 'check_github_plugin_update']);
        add_filter('admin_url', [$this, 'updatePluginDetailsUrl'], 10, 4);
        add_action('upgrader_process_complete', [$this, 'after_plugin_is_updated'], 10, 2);
    }

    /**
     * check_github_plugin_update
     *
     * @param object|bool $transient
     * @return object
     */
    public function check_github_plugin_update($transient)
    {
        // If the transient is not an object, return it as is.
        if (!is_object($transient)) {
            return $transient;
        }

        // Check for the paycove_last_checked transient.
        // @todo reface to use the $data transient
        $last_checked = get_transient('paycove_last_checked');

        // Return early if the transient is not expired.
        if ($last_checked && $last_checked > time() - 10 * MINUTE_IN_SECONDS) {
            return $transient;
        }

        // GitHub repository information
        $repo_owner = 'Paycove';
        $repo_name = 'woocommerce-plugin';
        $repo_url = "https://api.github.com/repos/$repo_owner/$repo_name/releases/latest";
        $data = get_transient('paycove_update_data');

        // If the transient is empty, get the data from GitHub API and set the transients.
        if(empty($data)) {
            // Make a request to GitHub API to get the latest release
            $response = wp_remote_get($repo_url);
            if (is_wp_error($response)) {
                return $transient;
            }
            $data = json_decode(wp_remote_retrieve_body($response));

            set_transient('paycove_update_data', $data, 10 * MINUTE_IN_SECONDS);
            set_transient('paycove_last_checked', true, 10 * MINUTE_IN_SECONDS);
        }

        // Get the latest release version
        $latest_version = $data->tag_name ?? '0.0.0';
        $plugin_slug = 'paycove';

        // Get the current installed version
        $installed_version = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_slug . '/paycove.php')['Version'];

        // Compare versions and update if necessary
        if (version_compare($installed_version, $latest_version, '<')) {
            $transient->response[$plugin_slug . '/paycove.php'] = (object) array(
              'url' => $data->html_url,
              'slug' => $plugin_slug,
              'new_version' => $latest_version,
              'package' => $data->assets[0]->browser_download_url,
              'icons' => [
                '2x' => PAYCOVE_GATEWAY_URL . '/assets/icon.png',
              ],
            );
        }

        return $transient;
    }

    /**
     * updatePluginDetailsUrl
     *
     * @param string $url
     * @param string $path
     * @return string
     */
    public function updatePluginDetailsUrl(string $url, string $path): string
    {
        $query = 'plugin=' . $this->plugin_slug;

        if (!str_contains($path, $query)) {
            return $url;
        }

        return sprintf(
            '%s?TB_iframe=true&width=600&height=550',
            // @todo eventually change this to the changelog of the plugin?
            $this->plugin_url
        );
    }

    /**
     * after_plugin_is_updated
     *
     * @param object $upgrader_object
     * @param array $options
     * @return void
     */
    public function after_plugin_is_updated(object $upgrader_object, array $options): void
    {
        if ($options['action'] == 'update' && $options['type'] == 'plugin') {
            // just update the version number
            if (isset($options['plugins']) && is_array($options['plugins'])) {
                foreach ($options['plugins'] as $plugin) {
                    if ($plugin == 'paycove/paycove.php') {
                        delete_transient('paycove_last_checked');
                        delete_transient('paycove_update_data');
                    }
                }
            }
        }
    }
}

new Github_Updater();
