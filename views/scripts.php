
<script type="text/javascript">
jQuery(document).ready(function ($) {

  $.getScript('http://widgets.twimg.com/j/2/widget.js', function () {
    new TWTR.Widget({
        version: 2,
        type: 'profile',
        rpp: 4,
        interval: 30000,
        width: 250,
        height: 300,
        id: 'tweets_div',
        theme: {
          shell: {
            background: '#90a6b5',
            color: '#ffffff'
          },
          tweets: {
            background: '#ffffff',
            color: '#000000',
            links: '#f68720'
          }
        },
        features: {
          scrollbar: true,
          loop: false,
          live: true,
          behavior: 'all'
        }
      }).render().setUser('ZemantaSupport').start();
  });

  $('.basepath').each(function () {
    var n = $(this).next('input');
    n.css('padding-left', parseInt($(this).width(),10)+2);
    $(this).click(function () {
      n.focus();
    });
  });

  $('#zemanta_options_image_uploader_custom_path').click(function () {
    $('#zemanta_options_image_uploader_dir').parents('tr').toggle(!!$(this).attr('checked'));
  }).triggerHandler('click');

});
</script>
