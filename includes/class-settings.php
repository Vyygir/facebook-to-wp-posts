<?php
/**
 * A class to handle the settings page for the plugin
 *
 * @package FBWPP
 * @since 0.1.0
 */
class FB_WPP_Settings {
	protected static $data = [
		'app_id' => '',
		'app_secret' => '',
		'access_token' => '',
		'page_id' => '',
		'post_type' => ''
	];

	public static function load_template() {
		$options = get_option(OPTIONS_SLUG);
		$post_types = get_post_types([
			'public'             => true,
			'publicly_queryable' => true
		], 'objects');

		require_once TEMPLATES_DIR . '/settings.php';
	}

	public static function admin_init() {
		register_setting(OPTIONS_GROUP, OPTIONS_SLUG);
	}

	public static function activate() {
		update_option(OPTIONS_SLUG, self::$data);
		FB_WPP_Feed::log(200, 'Plugin activated');
	}

	public static function deactivate() {
		delete_option(OPTIONS_SLUG);
		FB_WPP_Feed::log(200, 'Plugin de-activated');
	}

	public static function get_option($name) {
		$options = get_option(OPTIONS_SLUG);

		if ($options && isset($options[$name])) {
			return $options[$name];
		}

		return false;
	}
}