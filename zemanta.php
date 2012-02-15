<?php
/*
Copyright (c) 2007 - 2012, Zemanta Ltd.
The copyrights to the software code in this file are licensed under the (revised) BSD open source license.

Plugin Name: Zemanta
Plugin URI: http://wordpress.org/extend/plugins/zemanta/
Description: Contextual suggestions of links, pictures, related content and SEO tags that makes your blogging fun and efficient.
Version: 1.0.6
Author: Zemanta Ltd.
Author URI: http://www.zemanta.com/
Contributers: Kevin Miller (http://www.p51labs.com)
*/

define('ZEMANTA_PLUGIN_VERSION_OPTION', 'zemanta_plugin_version');

$zemanta = new Zemanta();

/**
* zemanta_get_api_key
*
* Helper function to return api key
*
* @return string
*/
function zemanta_get_api_key()
{
	global $zemanta;

	return $zemanta->get_api_key();
}

class Zemanta {

	var $version = '1.0.6';
	var $api_url = 'http://api.zemanta.com/services/rest/0.0/';
	var $api_key = '';
	var $options = array();
	var $update_notes = array();

	public function __construct()
	{
		// initialize update notes shown once on plugin update
		$this->update_notes['1.0.5'] = __('Please double-check your upload paths in Zemanta Settings, we changed some things that might affect your images.', 'zemanta');

		add_action('admin_menu', array(&$this, 'add_options'));
		add_action('admin_menu', array(&$this, 'add_meta_box'));
		add_action('admin_init', array(&$this, 'register_options'));
		add_action('admin_menu', array(&$this, 'check_plugin_updated'));

		add_filter('content_save_pre', array(&$this, 'image_downloader'));

		register_activation_hook(dirname(__FILE__) . '/zemanta.php', array(&$this, 'activate'));

		add_action('edit_form_advanced', array(&$this, 'assets'), 1);
		add_action('edit_page_form', array(&$this, 'assets'), 1);

		add_action('wp_ajax_zemanta', array(&$this, 'proxy'));

		$this->check_plugin_installed();
		$this->create_options();
		$this->check_options();

		if (!$this->check_dependencies()) 
		{
			add_action('admin_notices', array(&$this, 'warning'));
		}
	}

	/**
	* activate
	*
	* Run any functions needed for plugin activation
	*/
	public function activate() 
	{
		$this->fix_user_meta();
	}

	/**
	* admin_head
	*
	* Add any assets to the edit page
	*/
	public function assets() 
	{
		$this->render('assets', array(
			'api_key' => $this->api_key
			,'version' => $this->version
			));
	}

	/**
	* warning for no api key
	*
	* Display api key warning
	*/
	public function warning_no_api_key()
	{
		$this->render('message', array(
			'type' => 'error'
			,'message' => __('You have no Zemanta API key and the plugin was unable to retrieve one. You can still use Zemanta, '.
			'but until the new key is successfully obtained you will not be able to customize the widget or remove '.
			'this warning. You may try to deactivate and activate the plugin again to make it retry to obtain the key.')
			));
	}

	/**
	* warning
	*
	* Display plugin warning
	*/
	public function warning()
	{
		$this->render('message', array(
			'type' => 'updated fade'
			,'message' => __('Zemanta needs either the cURL PHP module or allow_url_fopen enabled to work. Please ask your server administrator to set either of these up.', 'zemanta')
			));
	}
	
	/**
	* plugin_update_notice
	*
	* Display plugin update notes
	*/
	public function plugin_update_notice()
	{
		if(isset($this->update_notes[$this->version])) {
			$this->render('message', array(
				'type' => 'updated fade',
				'message' => __($this->update_notes[$this->version], 'zemanta')
			));
		}
	}

	/**
	* add_options
	*
	* Add configuration page to menu
	*/
	public function add_options() 
	{
		add_options_page(__('Zemanta', 'zemanta'), __('Zemanta', 'zemanta'), 'manage_options', 'zemanta', array(&$this, 'options'));
	}

	/**
	* check_options
	*
	* Check to see if we need to create or import options
	*/
	public function check_options()
	{
		$this->api_key = $this->get_api_key();

		if (!$this->api_key) 
		{
			$options = get_option('zemanta_options');

			if (!$options)
			{
				$options = $this->legacy_options($options);
			}

			$this->api_key = $this->get_api_key();
			if (!$this->api_key) 
			{
				$this->api_key = $this->fetch_api_key();
				if ($this->api_key) 
				{
					$this->set_api_key($this->api_key);
				} 
				else 
				{
					add_action('admin_notices', array(&$this, 'warning_no_api_key'));
				}
			}
		}
	}

	/**
	* create_options
	*
	* Create the Initial Options
	*/
	public function create_options()
	{
		$wp_upload_dir = wp_upload_dir();

		$this->options = apply_filters('zemanta_options', array(
		'zemanta_option_api_key' => array(
			'type' => 'path'
			,'field' => 'api_key'
			,'default_value' => $this->api_key
			)
		,'zemanta_option_image_upload' => array(
			'type' => 'checkbox'
			,'field' => 'image_uploader'
			,'description' => __('Using Zemanta image uploader in this way may download copyrighted images to your blog. Make sure you and your blog writers check and understand licenses of each and every image before using them in your blog posts and delete them if they infringe on author\'s rights.')
			)
		,'zemanta_option_image_uploader_custom_path' => array(
			'type' => 'checkbox'
			,'field' => 'image_uploader_custom_path'
			,'description' => __('Use a custom path to store your images?')
			)
		,'zemanta_option_image_upload_dir' => array(
			'type' => 'path'
			,'field' => 'image_uploader_dir'
			,'description' => 'The path must be relative to <code>' . str_replace(ABSPATH, '', $wp_upload_dir['basedir']) . '</code>'
			,'default_value' => ''
			)
		));
	}

	/**
	* register_options
	*
	* Register options with Settings API
	*/
	public function register_options()
	{
		register_setting('zemanta_options', 'zemanta_options', array(&$this, 'validate_options'));

		add_settings_section('zemanta_options_plugin', __('Credentials', 'zemanta'), array(&$this, 'callback_options_plugin'), 'zemanta');
		add_settings_field('zemanta_option_api_key', 'API Key', array(&$this, 'options_set'), 'zemanta', 'zemanta_options_plugin', $this->options['zemanta_option_api_key']);

		add_settings_section('zemanta_options_image', __('Image Handling', 'zemanta'), array(&$this, 'callback_options_image'), 'zemanta');
		add_settings_field('zemanta_option_image_upload', 'Enable image uploader', array(&$this, 'options_set'), 'zemanta', 'zemanta_options_image', $this->options['zemanta_option_image_upload']);
		add_settings_field('zemanta_option_image_uploader_custom_path', 'Enable custom path', array(&$this, 'options_set'), 'zemanta', 'zemanta_options_image', $this->options['zemanta_option_image_uploader_custom_path']);
		add_settings_field('zemanta_option_image_upload_dir', 'Store uploads in this folder', array(&$this, 'options_set'), 'zemanta', 'zemanta_options_image', $this->options['zemanta_option_image_upload_dir']);
	}

	/**
	* callback_options_plugin
	*
	* Show the leader information for the main option section
	*/
	public function callback_options_plugin()
	{
		$this->render('options-plugin');
	}

	/**
	* callback_options_image
	*
	* Show the leader information for the image option section
	*/
	public function callback_options_image()
	{
		$this->render('options-image');
	}

	/**
	* options_set
	*
	* Output the fields for the options
	*/
	public function options_set($option = null)
	{
		// WordPress < 2.9 has a bug where the settings callback is not passed the arguments value so we check for it here.
		if ($option == null)
		{
			$option = array_shift($this->options);
		}

		$this->render('options-input-' . $option['type'], array(
			'option' => $this->get_option($option['field'])
			,'field' => $option['field']
			,'default_value' => isset($option['default_value']) ? $option['default_value'] : null
			,'description' => isset($option['description']) ? $option['description'] : null
			));
	}

	/**
	* validate_options
	*
	* Handle input Validation
	*/
	public function validate_options($input)
	{
		$wp_upload_dir = wp_upload_dir();

		$input['image_uploader_dir'] = trim($input['image_uploader_dir'], '\\/');

		return $input;
	}

	/**
	* options
	*
	* Add configuration page
	*/
	public function options() 
	{
		if ($this->is_pro()) 
		{
			return zem_pro_wp_admin();
		}

		if ($this->get_option('image_uploader') == 1 && $this->get_option('image_uploader_dir'))
		{
			$upload_dir = $this->image_upload_dir();

			if (!is_writable($upload_dir))
			{
				$this->render('message', array(
					'type' => 'error'
					,'message' => __('Your upload directory (' . $upload_dir . ') cannot be written to. Zemanta will not be able to upload images there.', 'zemanta')
					));
			}
		}

		if (!$this->api_key) 
		{
			$this->api_key = $this->fetch_api_key();

			$this->set_option('api_key', $this->api_key);
		}

		$this->render('options', array(
			'api_key' => $this->api_key
			,'api_test' => $this->api_test()
			));
	}

	/**
	* image_upload_dir
	*
	* Add configuration page
	*/
	public function image_upload_dir()
	{
		$wp_upload_dir = wp_upload_dir();

		if($this->is_uploader_enabled() && $this->is_uploader_custom_path()) 
		{
			$upload_dir = $this->get_option('image_uploader_dir');
			return $wp_upload_dir['basedir'] . '/' . $upload_dir;
		} 

		return $wp_upload_dir['path'];
	}

	/**
	* image_upload_url
	*
	* Add configuration page
	*/
	public function image_upload_url() 
	{
		$wp_upload_dir = wp_upload_dir();

		if($this->is_uploader_enabled() && $this->is_uploader_custom_path()) 
		{
			$dir = $this->get_option('image_uploader_dir');
			return $wp_upload_dir['baseurl'] . '/' . str_replace('\\', '/', $dir);
		}

		return $wp_upload_dir['url'];
	}

	/**
	* filesystem_method
	*
	* Change WP_Filesystem method to direct for this plugin
	*
	* @param string $method File System Method
*/
	public function filesystem_method($method)
	{
		return 'direct';
	}

	/**
	* upload_image
	*
	* Add configuration page
	*/
	public function upload_image($url) 
	{
		global $wp_filesystem;
	
		$upload_dir = $this->image_upload_dir();

		$file_name = wp_unique_filename($upload_dir, basename($url));
		$file_path = $upload_dir . '/' . $file_name;

		if (!file_exists($file_path)) 
		{
			list($response, $data) = $this->download($url);

			if ($response > 0)      
				return false;

			add_filter('filesystem_method', array(&$this, 'filesystem_method'));

			WP_Filesystem();

			if (!$wp_filesystem->put_contents($file_path, $data, FS_CHMOD_FILE)) 
			{        
				return false;
			}

			return $file_name;
		}
		
		return false;
	}

	/**
	* is_uploader_enabled
	*
	*/
	public function is_uploader_enabled() 
	{
		return $this->get_option('image_uploader');
	}
	
	/**
	* is_uploader_custom_path
	*
	*/
	public function is_uploader_custom_path()
	{
		return $this->get_option('image_uploader_custom_path');
	}

	/**
	* image_downloader
	*
	* Image Downloader
	*/
	public function image_downloader($post_content) 
	{
		global $zem_images_downloaded, $post;

		$content = stripslashes($post_content);

		if (!$this->is_uploader_enabled() || $zem_images_downloaded)
		{
			return $post_content;
		}

		$nlcontent = str_replace("\n", "", $content);
		$urls = array();
		$descs = array();

		while (true) 
		{
			$matches = $this->match("/<div[^>]+zemanta-img[^>]+>.+?<\/div>/", $nlcontent);

			if (!sizeof($matches))
			{
				break;
			}

			$srcurl = $this->match('/src="([^"]+)"/', $matches[0]);
			$desc = $this->match('/href="([^"]+)"/', $matches[0]);
			$urls[] = $srcurl[1];
			$descs[] = $desc[1];
			$nlcontent = substr($nlcontent, strpos($nlcontent, $matches[0]) + strlen($matches[0]));
		}

		$nlcontent = str_replace("\n", "", $content);
		if ($this->is_uploader_enabled())
		{
			while (true) 
			{
				$matches = $this->match('/<img .*?src="[^"]+".*?>/', $nlcontent);

				if (!sizeof($matches))
				{
					break;
				}

				$srcurl = $this->match('/src="([^"]+)"/', $matches[0]);

				if (!in_array($srcurl[1], $urls)) 
				{
					$desc = $this->match('/alt="([^"]+)"/', $matches[0]);
					$urls[] = $srcurl[1];
					$descs[] = strlen($desc[1]) ? $desc[1] : $srcurl[1];
				}

				$nlcontent = substr($nlcontent, strpos($nlcontent, $matches[0]) + strlen($matches[0]));
			}
		}

		$upload_url = $this->image_upload_url();

		if (sizeof($urls) == 0)
		{
			return $post_content;
		}

		for ($i = 0; $i < sizeof($urls); $i++) 
		{
			$url = $urls[$i];
			$desc = $descs[$i];

			if (strpos($url, $upload_url) !== false || strpos($url, 'http://img.zemanta.com/') !== false) 
			{
				continue;
			}

			$file_name = $this->upload_image($url);

			if ($file_name !== false) 
			{
				$localurl = $this->image_upload_url() . '/' . $file_name;
				$localfile = $this->image_upload_dir() . '/' . $file_name;
				$wp_filetype = wp_check_filetype($file_name, null);

				$content = str_replace($url, $localurl, $content);

				$attach_id = wp_insert_attachment(array(
					'post_mime_type' => $wp_filetype['type'],
					'post_title' => preg_replace('/\.[^.]+$/', '', $file_name),
					'post_content' => '',
					'post_status' => 'inherit',
					'guid' => $localurl
				), $localfile, $post->ID);

				$attach_data = wp_generate_attachment_metadata($attach_id, $localfile);
				wp_update_attachment_metadata($attach_id, $attach_data);
			} 
		}

		$zem_images_downloaded = true;
		$post_content = addslashes($content);

		return $post_content;
	}

	/**
	* download
	*
	* Download File
	*
	* @param string $url Image URL to download
	*/
	public function download($url) 
	{
		if (function_exists('curl_init')) 
		{
			$session = curl_init($url);

			curl_setopt($session, CURLOPT_HEADER, false);
			curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

			$response = curl_exec($session);

			curl_close($session);

			return $response === false ? array(1, "Problem reading data from $url : @$php_errormsg\n") : array(0, $response);
		} 
		else if (ini_get('allow_url_fopen')) 
		{
			$fp = @fopen($url, 'rb');

			if (!$fp) 
			{
				return array(1, "Problem connecting to $url : @$php_errormsg\n");
			}

			$response = @stream_get_contents($fp);

			if ($response === false) 
			{
				return array(2, "Problem reading data from $url : @$php_errormsg\n");
			}

			return array(0, $response);
		}
	}

	/**
	* api
	*
	* API Call
	*
	* @param array $arguments Arguments to pass to the API
	*/
	public function api($arguments)
	{
		if (!class_exists('WP_Http'))
		{
			include_once(ABSPATH . WPINC . '/class-http.php');
		}

		$arguments = array_merge($arguments, array(
			'api_key'=> $this->api_key
			));

		if (!isset($arguments['format']))
		{
			$arguments['format'] = 'xml';
		}

		$request = new WP_Http();

		return $request->request($this->api_url, array('method' => 'POST', 'body' => $arguments));
	}

	/**
	* api_test
	*
	* Test the API
	*/
	public function api_test() 
	{
		$response = $this->api(array(
			'method' => 'zemanta.suggest'
			,'text'=> ''
			));

		if (is_wp_error($response))
		{
			return __('API ERROR');
		}
		else
		{
			$matches = $this->match('/<status>(.+?)<\/status>/', $response['body']);

			return !$matches ? __('Invalid Response') . ': "' . @htmlspecialchars($response) . '"' : $matches[1];
		}
	}

	/**
	* proxy
	*
	* Work as a proxy for the embedded Zemanta widget
	*/
	public function proxy() 
	{
		if (!isset($_POST['api_key'])) 
		{
			header($_SERVER['SERVER_PROTOCOL'] . ' 500');
			header('Content-Type: text/plain');

			die('No API Key.');
		}

		$arguments = $_POST;

		$base = dirname(__FILE__);

		if (file_exists($base . '/SECRET.php')) 
		{
			require_once($base . '/SECRET.php');

			if (defined('ZEMANTA_SECRET')) 
			{
				$arguments['signature'] = ZEMANTA_SECRET . join('', $arguments);
			}
		}

		$arguments['format'] = 'json';

		if (isset($arguments['text']) && $arguments['method'] == 'zemanta.suggest')
		{
			$arguments['text'] = apply_filters('zemanta_proxy_text_filter', $arguments['text']);
		}

		$response = $this->api($arguments);

		if ($response['response']['code'] != 200) 
		{
			header($_SERVER['SERVER_PROTOCOL'] . ' 500');
			header('Content-Type: text/plain');

			die($response['response']['message']);
		}
		else 
		{
			header('Content-Type: text/plain');

			echo $response['body'];
		}

		die('');
	}

	/**
	* fetch_api_key
	*
	* Get API Key
	*/
	public function fetch_api_key() 
	{
		if ($this->is_pro()) 
		{
			return '';
		}

		$response = $this->api(array(
			'method' => 'zemanta.auth.create_user'
			));

		if (!is_wp_error($response))
		{
			$matches = $this->match('/<status>(.+?)<\/status>/', $response['body']);

			if ($matches[1] == 'ok') 
			{
				$matches = $this->match('/<apikey>(.+?)<\/apikey>/', $response['body']);

				return $matches[1];
			}
		}

		return '';
	}

	/**
	* add_meta_box
	*
	* Adds meta box to posts/pages
	*/
	public function add_meta_box() 
	{
		if (function_exists('add_meta_box')) 
		{
			add_meta_box('zemanta-wordpress', __('Content Recommendations'), array(&$this, 'shim'), 'post', 'side', 'high');
			add_meta_box('zemanta-wordpress', __('Content Recommendations'), array(&$this, 'shim'), 'page', 'side', 'high');
		}
	}

	/**
	* shim
	*
	* Adds Shim to Edit Page for Zemanta Plugin
	*/
	public function shim() 
	{
		echo '<div id="zemanta-sidebar"></div>';
	}

	/**
	* match
	*
	* Backwards Compatible Regex Matching
	* 
	* @param string $rstr Regular Expression
	* @param string $str String to match against
	* 
	* @return array
	*/
	protected function match($rstr, $str) 
	{
		if (function_exists('preg_match'))
		{
			preg_match($rstr, $str, $matches);
		}
		elseif (function_exists('ereg'))
		{
			ereg($rstr, $str, $matches);
		}
		else
		{
			$matches = array('', '');
		}

		return $matches;
	}

	/**
	* legacy_options
	*
	* Get Options from Legacy Options if available
	*/
	protected function legacy_options($options)
	{
		if (empty($this->options))
		{
			return false;
		}

		foreach ($this->options as $option => $details)
		{
			$old_option = get_option('zemanta_' . $details['field']);

			if ($old_option && !isset($options[$details['field']]))
			{
				$options[$details['field']] = $old_option == 'on' ? 1 : $old_option;
			}
		}

		update_option('zemanta_options', $options);

		return get_option('zemanta_options');
	}

	/**
	* get_option
	*
	* Get Option
	*
	* @param string $name Name of option to retrieve
	*/
	protected function get_option($name) 
	{
		if ($this->is_pro())
		{
			return zem_get_pro_option($name);
		}

		$options = get_option('zemanta_options');

		return isset($options[$name]) ? $options[$name] : null;
	}

	/**
	* set_option
	*
	* Set Option
	*
	* @param string $name Name of option to set
	* @param string $value Value of option
	*/
	protected function set_option($name, $value) 
	{
		if ($this->is_pro())
		{
			return zem_set_pro_option($name, $value);
		}

		$options = get_option('zemanta_options');

		if ($value === null)
		{
			unset($options[$name]);
		}
		else
		{
			$options[$name] = $value;
		}

		return update_option('zemanta_options', $options);
	}

	/**
	* get_api_key
	*
	* Get API Key
	*/ 
	public function get_api_key() 
	{
		return $this->is_pro() ? zem_pro_api_key() : $this->get_option('api_key');
	}

	/**
	* set_api_key
	*
	* Get API Key
	*
	* @param string $api_key API Key to set
	*/ 
	protected function set_api_key($api_key) 
	{
		if ($this->is_pro())
		{
			return zem_set_pro_api_key($api_key);
		}

		$this->set_option('api_key', $api_key);
	}

	/**
	* is_pro
	*
	* Check if the plugin upgraded to PRO
	*/
	protected function is_pro() 
	{
		if (defined('api_key') && defined('ZEMANTA_SECRET')) 
		{
			return true;
		}

		if (file_exists(dirname(__FILE__) . '/zemantapro.php'))
		{
			require_once(dirname(__FILE__) . "/zemantapro.php");
		}

		if (function_exists('zem_load_pro'))
		{
			zem_load_pro();
		}
		else
		{
			return false;
		}
	}

	/**
	* fix_user_meta
	*
	* If WP > 3.0 remove Zemanta User Meta
	*/
	protected function fix_user_meta()
	{
		if (function_exists('delete_user_meta')) 
		{ 
			global $wpdb;

			$prefix = like_escape($wpdb->base_prefix);

			$r = $wpdb->get_results("SELECT user_id, meta_key FROM $wpdb->usermeta WHERE meta_key LIKE '{$prefix}%metaboxorder%' OR meta_key LIKE '{$prefix}%meta-box-order%'", ARRAY_N);

			if ($r) 
			{
				foreach ($r as $i) 
				{
					delete_user_meta($i[0], $i[1]);
				}
			}
		}
	}

	/**
	* check_dependencies
	*
	* Return true if CURL and DOM XML modules exist and false otherwise
	*
	* @return boolean
	*/
	protected function check_dependencies() 
	{
		return ((function_exists('curl_init') || ini_get('allow_url_fopen')) && (function_exists('preg_match') || function_exists('ereg')));
	}
	
	/**
	* check_plugin_installed
	*
	* Checks whether plugin was just installed and adds version information to database
	* This also used for smooth migration from 0.8.2 to 1.0
	*	
	*/
	protected function check_plugin_installed()
	{
		// this should happen only on plugin installation to suppress update notes
		if(!get_option('zemanta_options')) {
			update_option(ZEMANTA_PLUGIN_VERSION_OPTION, $this->version, '', true);
		}
	}
	
	/**
	* check_plugin_updated
	*
	* Checks whether plugin update happened and triggers update notice
	*	
	*/
	public function check_plugin_updated()
	{	
		$last_plugin_version = get_option(ZEMANTA_PLUGIN_VERSION_OPTION);

		// it'll trigger only if previous version of plugin were installed before
		if(!$last_plugin_version || version_compare($last_plugin_version, $this->version, '<'))
		{
			// save new version string to database to avoid event doubling
			update_option(ZEMANTA_PLUGIN_VERSION_OPTION, $this->version);

			// show update notice once
			add_action('admin_notices', array(&$this, 'plugin_update_notice'));
		}
	}

	/**
	* pre
	*
	* Outputs an object to the screen
	*
	* @param object $output Object to display
	*/
	protected function pre($output)
	{
		echo '<pre>';

		print_r($output);

		echo '</pre>';
	}

	/**
	* render
	*
	* Render HTML/JS/CSS to screen
	*
	* @param string $view File to display
	* @param array $arguments Arguments to pass to file
	* @param boolean $return Whether or not to return the output or print it
	* @param boolean $theme Whether or not to check the theme for an override
	*/
	protected function render($view, $arguments = array(), $return = false, $theme = false) 
	{
		foreach ($arguments as $key => $value) 
		{
			$$key = $value;
		}

		if ($return)
		{
			ob_start();
		}

		$theme = explode('/themes/', get_bloginfo('stylesheet_directory'));

		$theme_root = get_theme_root() . '/' . $theme[1] . '/views/' . $view . '.php';
		$application_root = rtrim(dirname(__FILE__), '/') . '/views/' . $view . '.php';

		if (file_exists($theme_root))
		{
			$file = $theme_root;
		}
		else if (file_exists($application_root))
		{
			$file = $application_root;
		}

		if (file_exists($file))
		{
			include $file;
		}
		else
		{
			$this->pre('View Not Found: ' . $view);
		}

		if ($return)
		{
			$output = ob_get_contents();

			ob_end_clean();

			return $output;
		}
	}

}

?>
