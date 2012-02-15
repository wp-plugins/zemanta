
<div class="wrap">
    
  <h2><?php _e('Zemanta Plugin Configuration', 'zemanta'); ?></h2>
  
  <form name="zemanta-configuration" method="post" action="<?php echo str_replace('%7E', '~', $_SERVER['REQUEST_URI']); ?>">
   
    <input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">
   
    <p>
      API key is an authentication token that allows Zemanta service to know who you are. We automatically assigned you one when you first used this plug-in.
    </p>
    
    <p>
      If you would like to use a different API key you can enter it here.
    </p>
   
    <p>
      <?php _e('Zemanta API key:', 'zemanta'); ?>
      <input type="text" name="<?php echo $key_field; ?>" value="<?php echo $key_val; ?>" size="25"> 
    </p>
   
    <h2>Image uploading</h2>
   
    <p>
      Zemanta gets images from a number of 3rd party hosts (as set in your preferences). To ensure the best experience of your readers you should mirror images on your server. This option turns on automatic mirroring to your server of images included in published post.
    </p>
   
    <p>
      If you decide not to download images, they can be removed from 3rd party hosts at any time and loading performance can be effected by their reliability.
    </p>
   
    <p>
      <?php _e('Enable Zemanta image uploader:', 'zemanta'); ?>
      <input id="zemsettings_uploader_checkbox" type="checkbox" name="<?php echo $uploader_field; ?>" <?php if ($uploader_val) echo "checked=\"checked\""; ?> onclick="zemsettings_togglepanel('zemsettings_pathinfo', !document.getElementById('zemsettings_advanced_checkbox').checked); return zemsettings_togglepanel('zemsettings_uploader', this.checked);" <?php if ($wp_version <= '2.5') echo 'disabled="disabled" '; ?>/>
    </p>
   
    <?php if ($wp_version <= '2.5'): ?>
      
      <p class="updated">
        <?php _e('Zemanta image uploader is only supported with Wordpress 2.6 or above.', 'zemanta'); ?>
      </p>
    
    <?php endif; ?>
    
    <div id="zemsettings_uploader" style="display: none;">
    
      <p>
        <?php _e('Allow Zemanta uploader to upload any image referenced by your post to your blog:', 'zemanta'); ?>
        <input id="zemsettings_promisc_checkbox" type="checkbox" name="<?php echo $uploader_promisc_field; ?>" <?php if ($uploader_promisc_val) echo "checked=\"checked\""; ?> onclick="return zemsettings_togglepanel('zemsettings_disclaim', (this.checked&&document.getElementById('zemsettings_uploader_checkbox').checked));" />
      </p>
    
      <div id="zemsettings_disclaim" style="display: none;">
        <p class="error">
          Using Zemanta image uploader in this way may download copyrighted images to your blog. Make sure you and your blog writers check and understand licenses of each and every image before using them in your blog posts and delete them if they infringe on author's rights.
        </p>
      </div>

      <p>
        <?php _e('Use custom path for automatically uploaded images:', 'zemanta'); ?>
        <input id="zemsettings_advanced_checkbox" type="checkbox" name="<?php echo $uploader_custom_path_field; ?>" <?php if ($uploader_custom_path_val) echo "checked=\"checked\""; ?> onclick="zemsettings_togglepanel('zemsettings_pathinfo', (!this.checked&&document.getElementById('zemsettings_uploader_checkbox').checked)); return zemsettings_togglepanel('zemsettings_advanced', this.checked);" />
      </p>
      
      <div id="zemsettings_pathinfo" style="display: none;">
      
        <p>
          Wordpress will by default save images to its media directories, which according to settings may change monthly:
        </p>
        
        <p class="updated">
          Path: <?php echo $uploader_dir_val; ?>
        </p>
        
        <p class="updated">
          URL: <?php echo $uploader_url_val; ?>
        </p>
      
      </div>
      
      <div id="zemsettings_advanced" style="display: none;">
      
        <p>
          You may set the path for downloader to save images to and the url where these images will be available via the web. Pre-filled are defaults that wordpress sets up for you. These generally change with each new month, depending on your wordpress preferences.
        </p>
      
        <p>
          <?php _e('Uploader should save images to this directory:', 'zemanta'); ?>
          <input type="text" name="<?php echo $uploader_dir_field; ?>" value="<?php echo $uploader_dir_val; ?>" size="60" />
        </p>
      
        <p>
          <?php _e('The contents of the directory above are accessible through this url:', 'zemanta'); ?>
          <input type="text" name="<?php echo $uploader_url_field; ?>" value="<?php echo $uploader_url_val; ?>" size="60" />
        </p>
        
      </div>
    
    </div>

    <?php do_action('zemanta_options_form'); ?>
    
    <p class="submit">
      <input type="submit" name="Submit" value="<?php _e('Save changes', 'zemanta') ?>" />
    </p>
  
  </form>
  
<script type="text/javascript">
//<![CDATA[ 
  
  function zemsettings_togglepanel(id, onoff) 
  {
    document.getElementById(id).style.display = onoff ? 'block' : 'none';
    return true;
  }
  
  zemsettings_togglepanel('zemsettings_uploader', document.getElementById('zemsettings_uploader_checkbox').checked);
  zemsettings_togglepanel('zemsettings_disclaim', (document.getElementById('zemsettings_uploader_checkbox').checked && document.getElementById('zemsettings_promisc_checkbox').checked));
  zemsettings_togglepanel('zemsettings_pathinfo', (document.getElementById('zemsettings_uploader_checkbox').checked && !document.getElementById('zemsettings_advanced_checkbox').checked));
  zemsettings_togglepanel('zemsettings_advanced', document.getElementById('zemsettings_advanced_checkbox').checked);

//]]>
</script>

</div>