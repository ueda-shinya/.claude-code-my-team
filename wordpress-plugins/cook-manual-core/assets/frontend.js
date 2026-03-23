(function($){
  $(function(){

    // ==== 1) 連番付与 & id 生成 ====
    var $lists = $('.p-steps');
    $lists.each(function(listIndex){
      var $list = $(this);
      var $steps = $list.children('.p-step');

      $steps.each(function(i){
        var idx = i + 1;
        var $step = $(this);
        var stepId = 'step-' + idx;

        // id 付与（既存idがあっても上書きしない）
        if (!$step.attr('id')) $step.attr('id', stepId);

        // 番号（.p-step__num）に表示
        var $num = $step.find('.p-step__num').first();
        if ($num.length) $num.text(idx);
      });

      // ==== 2) 目次の自動生成 ====
      if ($steps.length > 0) {
        var $tocWrap = $('<div class="p-tocWrap" />');
        var $btn = $('<button type="button" class="p-tocBtn" aria-expanded="false" aria-controls="toc-'+listIndex+'">📑 目次をひらく</button>');
        var $toc = $('<nav class="p-toc" id="toc-'+listIndex+'" aria-label="このページの目次"></nav>');
        var $title = $('<div class="p-toc__title">手順一覧</div>');
        var $ul = $('<ol class="p-toc__list"></ol>');

        $steps.each(function(i){
          var idx = i + 1;
          var $step = $(this);
          var id = $step.attr('id') || ('step-'+idx);
          var titleText = $.trim($step.find('.p-step__title').text()) || ('手順 ' + idx);
          // 番号を含むと冗長になりがちなので整形
          titleText = titleText.replace(/^(\d+)\s*/, '');

          var $li = $('<li class="p-toc__item"></li>');
          var $a = $('<a href="#'+id+'"></a>').text(idx + '. ' + titleText);
          $li.append($a);
          $ul.append($li);
        });

        $toc.append($title).append($ul);
        $tocWrap.append($btn).append($toc);
        $tocWrap.insertBefore($list);

        // トグル
        $btn.on('click', function(){
          var open = $toc.hasClass('is-open');
          $toc.toggleClass('is-open', !open);
          $btn.attr('aria-expanded', String(!open));
          $btn.text(!open ? '📑 目次をとじる' : '📑 目次をひらく');
        });

        // 目次クリックでスムーススクロール
        $toc.on('click', 'a[href^="#"]', function(e){
          var href = $(this).attr('href');
          var $target = $(href);
          if ($target.length) {
            e.preventDefault();
            $target.get(0).scrollIntoView({ behavior: 'smooth', block: 'start' });
            setTimeout(function(){ $target.focus(); }, 400);
          }
        });
      }

    });

    // ==== 3) ページ読み込み時にハッシュがあればスクロール ====
    if (location.hash && /^#step-\d+$/.test(location.hash)) {
      var $target = $(location.hash);
      if ($target.length) {
        setTimeout(function(){
          $target.get(0).scrollIntoView({ behavior: 'smooth', block: 'start' });
          $target.focus();
        }, 150);
      }
    }

    // ==== 4) タイマー（複数同時管理） ====
    var timers = new Map();

    $(document).on('click', '.js-step-timer', function(){
      var $btn = $(this);
      var sec  = parseInt($btn.data('seconds') || 0, 10);
      if (!sec) return;

      var $wrap = $btn.closest('.p-step');
      var $out  = $wrap.find('.p-step__countdown').first();

      if (timers.has($wrap.get(0))) {
        clearInterval(timers.get($wrap.get(0)));
      }

      var remain = sec;
      $out.text(remain + ' 秒');
      $btn.attr('aria-live', 'polite');

      var id = setInterval(function(){
        remain--;
        if (remain <= 0) {
          clearInterval(id);
          timers.delete($wrap.get(0));
          $out.text('完了');
          try { if (navigator.vibrate) navigator.vibrate(200); } catch(e){}
          return;
        }
        $out.text(remain + ' 秒');
      }, 1000);

      timers.set($wrap.get(0), id);
    });

  });
})(jQuery);
