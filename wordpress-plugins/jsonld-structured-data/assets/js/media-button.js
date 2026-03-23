jQuery(document).ready(function($) {
  $('.jsonld-sd-select-media').on('click', function(e) {
    e.preventDefault();
    const target = $(this).data('target');
    const frame = wp.media({
      title: '画像を選択',
      button: { text: '選択' },
      multiple: false
    });

    frame.on('select', function() {
      const attachment = frame.state().get('selection').first().toJSON();
      $('#' + target).val(attachment.url);
    });

    frame.open();
  });
});
