
<script type="text/javascript">
//<![CDATA[  
  window.ZemantaGetAPIKey = function () { 
    return '<?php echo $api_key; ?>'; 
  };
  
  window.ZemantaPluginVersion = function () { 
    return '<?php echo $version; ?>'; 
  };
  
  window.ZemantaProxyUrl = function() { 
    return '<?php echo admin_url('admin-ajax.php'); ?>'; 
  };
//]]>
</script>

<script type="text/javascript" id="zemanta-loader" src="http://fstatic.zemanta.com/plugins/wordpress/loader.js"></script>
