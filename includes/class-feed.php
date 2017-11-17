<?php
/**
 * This object handles feeding through posts from Facebook based on a WP_Cron interval
 *
 * @package FBWPP
 * @since 0.1.0
 */
class FB_WPP_Feed {
	public static $settings = [];

	public static function admin_init() {
		self::assign_settings();

		if (!self::validate_settings()) {
			add_action('admin_notices', function() {
				require_once FBWPP_TEMPLATES_DIR . '/settings-error.php';
			});
		}
	}

	protected static function assign_settings() {
		self::$settings['app_id'] = FB_WPP_Settings::get_option('app_id');
		self::$settings['app_secret'] = FB_WPP_Settings::get_option('app_secret');
		self::$settings['access_token'] = FB_WPP_Settings::get_option('access_token');
		self::$settings['page_id'] = FB_WPP_Settings::get_option('page_id');
		self::$settings['post_type'] = FB_WPP_Settings::get_option('post_type');
	}

	public static function import_facebook_posts() {
		FB_WPP_Feed::log(200,'Attempt to import posts');

		$response_data = self::get_facebook_posts();

		if ($response_data['status'] == 200) {
			$posts = $response_data['content']['posts'];

			if ($posts) {
				foreach ($posts as $fb_post) {
					self::create_post($fb_post);
				}
			}
		} else {
			self::log($response_data['status'], $response_data['status']['message']);
		}
	}

	protected static function create_post($fb_post) {
		if (!self::post_exists($fb_post['fb_id'])) {
			$title = wp_strip_all_tags($fb_post['content']);

			if (strlen($title) > 30) {
				$title = sprintf('%s...', substr($title, 0, 30));
			}

			$post_data = [
				'post_title'   => $title,
				'post_content' => $fb_post['content'],
				'post_status'  => 'publish'
			];

			$post_id = wp_insert_post($post_data);

			self::attach_media(1, $fb_post);

			if ($post_id) {
				add_post_meta($post_id, 'fb_id', $fb_post['fb_id']);

				self::attach_media($post_id, $fb_post);
				self::log(200, 'Post imported from Facebook: "' . $title . '"');
			}
		}
	}

	protected static function attach_media($post_id, $fb_post) {
		if (isset($fb_post['media'])) {
			$first = true;
			$meta_attachments = [];

			foreach ($fb_post['media'] as $attachment) {

				switch ($attachment['type']) {
					case 'photo' :
						$image = self::upload_image($attachment['media']['image']['src']);

						if ($first) {
							require_once (ABSPATH . 'wp-admin/includes/image.php');

							$first = false;
							$attachment_data = wp_generate_attachment_metadata($image['id'], $image['file']);

							wp_update_attachment_metadata($image['id'], $attachment_data);
							set_post_thumbnail($post_id, $image['id']);
						}

						$image_src = wp_get_attachment_image_src($image['id'], 'full');
						$meta_attachments[] = $image_src[0];
					break;
				}
			}

			update_post_meta($post_id, 'fb_attachments', $meta_attachments);
		}
	}

	protected static function upload_image($url) {
		$upload_dir = wp_upload_dir();
		$image_data = file_get_contents($url);
		$filename = basename($url);

		if (strpos($filename, '?') !== false) {
			$filename = explode('?', $filename);
			$filename = $filename[0];
		}

		if (wp_mkdir_p($upload_dir['path'])) {
			$file = $upload_dir['path'] . '/' . $filename;
		} else {
			$file = $upload_dir['basedir'] . '/' . $filename;
		}

		file_put_contents($file, $image_data);

		$wp_filetype = wp_check_filetype($filename, null);
		$attachment = [
			'post_mime_type' => $wp_filetype['type'],
			'post_title' => sanitize_file_name($filename),
			'post_content' => '',
			'post_status' => 'inherit'
		];

		return [
			'id'   => wp_insert_attachment($attachment, $file),
			'file' => $file
		];
	}

	protected static function post_exists($fb_id) {
		$posts = get_posts([
			'post_type'      => self::$settings['post_type'],
			'posts_per_page' => -1,
			'meta_key'       => 'fb_id',
			'meta_value'     => $fb_id
		]);

		return $posts ? true : false;
	}

	public static function get_facebook_posts() {
		self::assign_settings();

		$data = [
			'status' => 200,
			'content' => []
		];

		$fb = new \Facebook\Facebook([
			'app_id' => self::$settings['app_id'],
			'app_secret' => self::$settings['app_secret'],
			'default_graph_version' => 'v2.10',
			'default_access_token' => self::$settings['access_token']
		]);

		try {
			$response = $fb->get('/' . self::$settings['page_id'] . '/posts?fields=id,created_time,message,attachments');
			$posts = $response->getGraphList();
			$data['content']['posts'] = [];

			foreach ($posts as $post) {
				$post = $post->asArray();

				do {
					$post_object = [
						'fb_id' => $post['id'],
						'content' => $post['message'],
						'date' => $post['created_time']
					];

					if (isset($post['attachments'])) {
						$post_object['media'] = $post['attachments'];
					}

					$data['content']['posts'][] = $post_object;
				} while ($fb->next($posts));
			}
		} catch (\Facebook\Exceptions\FacebookResponseException $e) {
			$data['status'] = $e->getSubErrorCode();
			$data['content']['message'] = $e->getMessage();
		} catch (\Facebook\Exceptions\FacebookSDKException $e) {
			$data['status'] = $e->getSubErrorCode();
			$data['content']['message'] = $e->getMessage();
		}

		return $data;
	}

	protected static function validate_settings() {
		foreach (self::$settings as $value) {
			if (!$value) {
				return false;
			}
		}

		return true;
	}

	public static function log($status, $message) {
		$file = sprintf('%s/%s.log', WP_CONTENT_DIR, FBWPP_OPTIONS_SLUG);
		$message = sprintf("[%s](%s) %s\r\n", date('Y-m-d H:i:s', time()), $status, $message);

		file_put_contents($file, $message, FILE_APPEND);
	}
}