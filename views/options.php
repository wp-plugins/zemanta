<?php include(dirname(__FILE__) . '/stylesheet.php'); ?>
<?php include(dirname(__FILE__) . '/scripts.php'); ?>

<div class="wrap" id="wp-zemanta">

    <div class="logo"><img src="<?php echo plugins_url('/img/logo.png', dirname(__FILE__)); ?>" alt="Zemanta | social blogging" /></div>

    <div class="cols clearfix">
      <div class="col-left">
        <div class="video">
         <iframe src="//player.vimeo.com/video/46745200?title=0&amp;byline=0&amp;portrait=0" width="100%" height="280px" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>
        </div>
        <p>
          <a href="http://prefs.zemanta.com/" target="_blank" class="signin-button prefs-signin">Sign In</a>
        </p>
        <p class="below-signin-button"><a href="http://prefs.zemanta.com/" target="_blank" class="prefs-signin">to check stats, change settings and more</a></p>
      </div>
      <div class="col-right">
        <div id="tweets_div"></div>
      </div>
    </div>

	<div class="cols clearfix">
		<h2>Amazing new feature - Upload your history of posts</h2>
		<p>
			You can now upload your content! This means you will be reaching out to even more people that write about similar topics – not only new but also old posts will now be suggested in related articles section, to you and to other bloggers.
		</p>
		<p>
			Note: If you have a very large database, it can take some time to upload your articles, so please be patient. =)
		</p>
		<?php if (! $articles_uploaded): ?>
		<form method="get" action="">
			<fieldset>
				<input type="hidden" name="page" value="zemanta" />
				<input class="button-primary" type="submit" name="zemanta_upload_articles" value="<?php _e('Upload my articles to Zemanta','zemanta');?>">
			</fieldset>
		</form>
		<?php else: ?>
		<p>Your articles are being uploaded.</p>
		<?php endif; ?>
		<h3>Number of articles in Zemanta network: <?php echo $articles_count; ?></h3>
	</div>
	
  <form action="options.php" method="post" class="settings-form">
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
