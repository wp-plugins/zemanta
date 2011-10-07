<?php
/*
Copyright (c) 2007 - 2011, Zemanta Ltd.
The copyrights to the software code in this file are licensed under the (revised) BSD open source license.

Plugin Name: Zemanta
Plugin URI: http://wordpress.org/extend/plugins/zemanta/
Description: Contextual suggestions of links, pictures, related content and SEO tags that makes your blogging fun and efficient.
Version: 0.8
Author: Zemanta Ltd.
Author URI: http://www.zemanta.com/
Contributers: Kevin Miller (http://www.p51labs.com)
*/

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
  
  var $version = '0.8';
  
  var $api_url = 'http://api.zemanta.com/services/rest/0.0/';
  
  var $api_key;
  
  public function __construct()
  {
    add_action('admin_menu', array(&$this, 'add_options'));
    add_action('admin_menu', array(&$this, 'add_meta_box'));
    
    add_filter('content_save_pre', array(&$this, 'image_downloader'));

    register_activation_hook(dirname(__FILE__) . '/zemanta.php', array(&$this, 'activate'));
    
    add_action('edit_form_advanced', array(&$this, 'assets'), 1);
    add_action('edit_page_form', array(&$this, 'assets'), 1);
    
    add_action('wp_ajax_zemanta', array(&$this, 'proxy'));

    $this->api_key = $this->get_api_key();
    
    if (!$this->check_dependencies()) 
    {
      add_action('admin_notices', 'warning');
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
   * warning
   *
   * Display plugin warning
   */
  public function warning()
  {
    $this->render('message', array(
      'type' => 'updated fade'
      ,'message' => __('Zemanta needs either the cURL PHP module or allow_url_fopen enabled to work. Please ask your server administrator to set either of these up.')
    ));
  }
  
  /**
   * add_options
   *
   * Add configuration page to menu
   */
  public function add_options() 
  {
    if (function_exists('add_submenu_page'))
    {
      add_submenu_page('plugins.php', __('Zemanta Configuration'), __('Zemanta Configuration'), 'manage_options', 'zemanta', array(&$this, 'options'));
    }
  }
  
  /**
   * options
   *
   * Add configuration page
   */
  public function options() 
  {
    global $wp_version;

    if ($this->is_pro()) 
    {
      return zem_pro_wp_admin();
    }
    
    // variables for the field and option names
    $hidden_field_name = 'zemanta_submit_hidden';
    $key_field = 'zemanta_api_key';
    $uploader_field = 'zemanta_image_uploader';
    $uploader_promisc_field = 'zemanta_image_uploader_promisc';
    $uploader_custom_path_field = 'zemanta_image_uploader_custom_path';
    $uploader_dir_field = 'zemanta_image_uploader_dir';
    $uploader_url_field = 'zemanta_image_uploader_url';

    // Read in existing option value from database
    $key_val = $this->api_key;
    $uploader_val = $this->get_option($uploader_field);
    $uploader_promisc_val = $this->get_option($uploader_promisc_field);
    $uploader_custom_path_val = $this->get_option($uploader_custom_path_field);
    $uploader_dir_val = $this->image_upload_dir();
    $uploader_url_val = $this->image_upload_url();
    
    // See if the user has posted us some information
    // If they did, this hidden field will be set to 'Y'
    if ($_POST[$hidden_field_name] == 'Y') 
    {
      // Read their posted value
      $key_val = $_POST[$key_field];
      $uploader_val = $_POST[$uploader_field];
      $uploader_promisc_val = $_POST[$uploader_promisc_field];
      $uploader_custom_path_val = $_POST[$uploader_custom_path_field];
      $uploader_dir_val = $_POST[$uploader_dir_field];
      $uploader_url_val = $_POST[$uploader_url_field];

      // Save the posted value in the database
      $this->set_api_key($key_val);
      
      $this->set_option($uploader_field, $uploader_val);
      $this->set_option($uploader_promisc_field, $uploader_promisc_val);
      
      if ($uploader_val && !$uploader_custom_path_val) 
      {
        $uploads = wp_upload_dir();
        $upload_dir = $uploads['path'];
      
        if (!is_writable($upload_dir)) 
        {
          $this->render('message', array(
            'type' => 'error'
            ,'message' => __('Your wordpress upload directory (' . $upload_dir . ') cannot be written to. Zemanta will not be able to upload images there.', 'zemanta')
          ));
        }
      }
      
      if ($uploader_val && $uploader_custom_path_val) 
      {
        if (!is_writable($uploader_dir_val)) 
        {
          $this->render('message', array(
            'type' => 'error'
            ,'message' => __('Upload directory you have set (' . $uploader_dir_val . ') cannot be written to. Zemanta will not be able to upload images there.', 'zemanta')
          ));
        }
        
        $this->set_option($uploader_dir_field, $uploader_dir_val);
        $this->set_option($uploader_url_field, $uploader_url_val);
        $this->set_option($uploader_custom_path_field, $uploader_custom_path_val);
      } 
      else 
      {
        $this->set_option($uploader_dir_field, null);
        $this->set_option($uploader_url_field, null);
        $this->set_option($uploader_custom_path_field, $uploader_custom_path_val);
      }
      
      $this->render('message', array(
        'type' => 'updated'
        ,'message' => __('Configuration saved.', 'zemanta')
      ));
    }
    
    $this->render('options', array(
      'key_val' => $key_val
      ,'key_field' => $key_field
      ,'uploader_field' => $uploader_field
      ,'uploader_val' => $uploader_val
      ,'uploader_promisc_field' => $uploader_promisc_field
      ,'uploader_promisc_val' => $uploader_promisc_val
      ,'uploader_custom_path_field' => $uploader_custom_path_field
      ,'uploader_custom_path_val' => $uploader_custom_path_val
      ,'uploader_dir_field' => $uploader_dir_field
      ,'uploader_dir_val' => $uploader_dir_val
      ,'uploader_url_field' => $uploader_url_field
      ,'uploader_url_val' => $uploader_url_val
      ,'wp_version' => $wp_version
      ,'hidden_field_name' => $hidden_field_name
    ));
    
    $this->debug();
  }
  
  /**
   * image_upload_dir
   *
   * Add configuration page
   */
  public function image_upload_dir() 
  {
    $upload_dir = $this->get_option('zemanta_image_uploader_dir');
    
    if ($upload_dir == null) 
    {
      $uploads = wp_upload_dir();
      return $uploads['path'];
    }
    
    return $upload_dir;
  }

  /**
   * image_upload_url
   *
   * Add configuration page
   */
  public function image_upload_url() 
  {
    $upload_url = $this->get_option('zemanta_image_uploader_url');
    
    if ($upload_url == null) 
    {
      $uploads = wp_upload_dir();
      return $uploads['url'];
    }
    
    return $upload_url;
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
    $upload_dir = $this->image_upload_dir();
    
    $file_name = wp_unique_filename($upload_dir, basename($url));
    
    $file_path = $upload_dir . '/' . basename(urldecode($file_name));
    
    if (!file_exists($file_path)) 
    {
      list($response, $data) = $this->download($url);
      
      if ($response > 0) 
      {
        $_SESSION['image_download_error_string'] = __('Zemanta could not download some or all of the images referenced in your post to your server. Please, try again later.');
        
        return false;
      }
      
      global $wp_filesystem;

      add_filter('filesystem_method', array(&$this, 'filesystem_method'));

      WP_Filesystem();

      if (!$wp_filesystem->put_contents($file_path, $data, FS_CHMOD_FILE)) 
      {
        $_SESSION['image_download_error_string'] = __('Your image upload directory (' . $upload_dir . ') is not writable. Zemanta cannot upload images there.');
        
        return false;
      }

      return $this->image_upload_url() . '/' . $file_name;
    }
  }

  /**
   * is_uploader_enabled
   *
   * Add configuration page
   */
  public function is_uploader_enabled() 
  {
    return $this->get_option('zemanta_image_uploader');
  }
  
  /**
   * image_downloader
   *
   * Image Downloader
   */
  public function image_downloader($post_content) 
  {
    global $zem_images_downloaded;

    $content = stripslashes($post_content);

    if (!$this->is_uploader_enabled() || $zem_images_downloaded)
    {
      return $post_content;
    }
    
    // zemanta images
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

    // other images
    $nlcontent = str_replace("\n", "", $content);
    if ($this->get_option("zemanta_image_uploader_promisc"))
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
    
    for ($i=0; $i<sizeof($urls); $i++) 
    {
      $url = $urls[$i];
      $desc = $descs[$i];
    
      // skip images already downloaded and zemanta pixie
      if (strpos($url, $upload_url) !== false || strpos($url, "http://img.zemanta.com/") !== false) 
      {
        continue;
      }
      
      $localurl = $this->upload_image($url);
    
      if ($localurl !== false) 
      {
        $content = str_replace($url, $localurl, $content);
      } 
      else 
      {
        $_SESSION['image_download_errors'] = true;
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
    
      return !$matches ? __('Invalid Response') . ': "' . htmlspecialchars($response) . '"' : $matches[1];
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

    $matches = $this->match('/<status>(.+?)<\/status>/', $response['body']);
    
    if ($matches[1] == 'ok') 
    {
      $matches = $this->match('/<apikey>(.+?)<\/apikey>/', $response['body']);
      
      return $matches[1];
    }

    return '';
  }
  
  /**
   * debug
   *
   * Debug API output on configuration page
   */
  public function debug() 
  {
    if (!$this->api_key) 
    {
      $this->api_key = $this->fetch_api_key();
      
      update_option('zemanta_api_key', $this->api_key);
    }

    $this->render('debug', array(
      'api_key' => $this->api_key
      ,'api_test' => $this->api_test()
    ));
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
    
    return get_option($name, null);
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
    
    if ($value === null)
    {
      return delete_option($name);
    }
    
    return update_option($name, $value);
  }
  
  /**
   * get_api_key
   *
   * Get API Key
   */ 
  public function get_api_key() 
  {
    return $this->is_pro() ? zem_pro_api_key() : $this->get_option('zemanta_api_key');
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
    
    update_option('zemanta_api_key', $api_key);
  }
  
  /**
   * is_pro
   *
   * Check if the plugin upgraded to PRO
   */
  protected function is_pro() 
  {
    if (defined('ZEMANTA_API_KEY') && defined('ZEMANTA_SECRET')) 
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
   * d
   *
   * Outputs an object to the screen
   *
   * @param object $output Object to display
   */
  protected function d($output)
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
      $this->d('View Not Found: ' . $view);
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
