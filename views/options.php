
<div class="wrap">

  <div id="icon-options-general" class="icon32"><br></div>

  <h2><?php _e('Zemanta', 'zemanta'); ?></h2>
  
  <h3>
    
    <?php _e('Service Status', 'zemanta'); ?> &raquo;
    
    <?php if ($api_test == 'ok'): ?>
      
      <span style="color: green;"><?php _e('OK', 'zemanta'); ?></span>

    <?php else: ?>

      <span style="color: red;"><?php _e('Failure', 'zemanta'); ?></span>
      
    <?php endif; ?>
    
  </h3>

  <form action="options.php" method="post">

    <?php settings_fields('zemanta_options'); ?>

    <?php do_settings_sections('zemanta'); ?>

    <?php do_action('zemanta_options_form'); ?>

	<?php if(!$is_pro) : ?>
    <p class="submit">
      
      <input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" class="button-primary" />
      
    </p>
	<?php endif; ?>
    
  </form>
  
</div>
