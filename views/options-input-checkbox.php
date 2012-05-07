
<input id="zemanta_options_<?php echo $field; ?>" name="zemanta_options[<?php echo $field; ?>]" type="checkbox" value="1" <?php echo isset($option) && !empty($option) ? 'checked="checked"' : ''; ?><?php if(isset($disabled) && $disabled) : ?> disabled="disabled"<?php endif; ?>  />

<?php if (isset($description)): ?>

  <label for="zemanta_options_<?php echo $field; ?>">
    <?php echo $description; ?>
  </label>

<?php endif; ?>