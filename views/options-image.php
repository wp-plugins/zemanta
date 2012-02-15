
<?php global $wp_version; ?>

<?php if ($wp_version <= '2.5'): ?>
  
  <p>
    <strong><em><?php _e('Zemanta image uploader is only supported with Wordpress 2.6 or above.', 'zemanta'); ?></em></strong>
  </p>

<?php endif; ?>

<p>
  <?php _e('Zemanta gets images from a number of 3rd party hosts (as set in your preferences). To ensure the best experience of your readers you should mirror images on your server. This option turns on automatic mirroring to your server of images included in published post. If you decide not to download images, they can be removed from 3rd party hosts at any time and loading performance can be effected by their reliability.', 'zemanta'); ?>
</p>