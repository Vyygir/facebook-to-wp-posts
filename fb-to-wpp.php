<?php
/**
 * Plugin Name: FB to WP Posts
 * Description: Grab posts from your Facebook page and import them into WordPress
 * Version: 0.1.1
 *
 * @package FBWPP
 */
if (!defined('ABSPATH')) {
	exit;
}

define('FBWPP_FILE', __FILE__);
define('FBWPP_INCLUDES_DIR', __DIR__ . '/includes');
define('FBWPP_TEMPLATES_DIR', __DIR__ . '/templates');
define('FBWPP_OPTIONS_SLUG', 'fb_to_wpp');
define('FBWPP_OPTIONS_GROUP', 'fb_to_wpp_options');

require_once __DIR__ . '/vendor/autoload.php';
require_once FBWPP_INCLUDES_DIR . '/class-settings.php';
require_once FBWPP_INCLUDES_DIR . '/class-feed.php';

register_activation_hook(__FILE__, function() {
	wp_schedule_event(time(), 'hourly', 'fb_wpp_feed');
	FB_WPP_Settings::activate();
});

register_deactivation_hook(__FILE__, function() {
	wp_clear_scheduled_hook('fb_wpp_feed');
	FB_WPP_Settings::deactivate();
});

add_action('admin_init', function() {
	FB_WPP_Settings::admin_init();
	FB_WPP_Feed::admin_init();
});

add_action('admin_menu', function() {
	add_options_page(
		'Facebook Posts',
		'FB to WP Posts',
		'manage_options',
		FBWPP_OPTIONS_GROUP,
		['FB_WPP_Settings', 'load_template']
	);
});

add_action('fb_wpp_feed', ['FB_WPP_Feed', 'import_facebook_posts']);
