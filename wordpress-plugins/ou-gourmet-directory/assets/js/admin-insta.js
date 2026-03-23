(function($){
  $(function(){
    var $btn   = $('#ou-insta-refetch-btn');
    if(!$btn.length) return;
    var $msg   = $('#ou-insta-refetch-msg');
    var $wrap  = $('#ou-insta-thumb-preview');

    function renderDebug(debug){
      if(!debug) return '';
      var lines = [];
      if(debug.http_code)  lines.push('HTTP: ' + debug.http_code);
      if(debug.api_type)   lines.push('Type: ' + debug.api_type);
      if(debug.api_code)   lines.push('Code: ' + debug.api_code);
      if(debug.api_subcode)lines.push('Subcode: ' + debug.api_subcode);
      if(debug.api_message)lines.push('Message: ' + debug.api_message);
      if(!lines.length) return '';
      var $details = $('<details>').css({marginTop:'6px'});
      $details.append($('<summary>').text('詳細'));
      $details.append($('<pre>').css({whiteSpace:'pre-wrap', margin:0}).text(lines.join('\n')));
      return $details;
    }

    $btn.on('click', function(){
      var postId = $(this).data('post');
      var force  = $('#ou-insta-force-media').is(':checked') ? 1 : 0;
      var insta  = $('#ou_instagram_url').val() || '';

      $btn.prop('disabled', true);
      $msg.empty().text(OU_GD_ADMIN.i18n.working);

      $.post(OU_GD_ADMIN.ajax_url, {
        action: 'ou_gd_refetch_insta',
        nonce: OU_GD_ADMIN.nonce,
        post_id: postId,
        force_media: force,
        insta_url: insta
      }).done(function(resp){
        $msg.empty();
        if(resp && resp.success){
          if(resp.data.thumb_url){
            var img = $('<img>', {
              src: resp.data.thumb_url,
              alt: '',
              css: { width: '100%', height: 'auto', display: 'block', border: '1px solid #ddd' }
            });
            $wrap.empty().append(img);
          }
          $msg.text(OU_GD_ADMIN.i18n.done);
        }else{
          var err = (resp && resp.data && resp.data.message) ? resp.data.message : OU_GD_ADMIN.i18n.failed;
          $msg.text(err);
          if(resp && resp.data && resp.data.debug){
            var $details = renderDebug(resp.data.debug);
            if($details) $msg.append($details);
          }
        }
      }).fail(function(){
        $msg.text(OU_GD_ADMIN.i18n.failed);
      }).always(function(){
        $btn.prop('disabled', false);
      });
    });
  });
})(jQuery);
