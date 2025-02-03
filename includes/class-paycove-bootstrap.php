<?php

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly
class Paycove_Bootstrap
{
    public $plugin_url = 'https://paycove.io';
    public $plugin_slug = 'paycove';

    public function __construct()
    {
        add_filter('admin_url', [$this, 'updatePluginDetailsUrl'], 10, 4);
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
            // @todo maybe change this to the changelog of the plugin?
            $this->plugin_url
        );
    }
}

new Paycove_Bootstrap();
