if (((navigator.userAgent.indexOf('iPhone') > 0) || (navigator.userAgent.indexOf('Android') > 0) && (navigator.userAgent.indexOf('Mobile') > 0) && (navigator.userAgent.indexOf('SC-01C') == -1))) {
	document.write('<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no">');
}

//page-scroller
$(function () {

  if ($('#cookie_box').length) {
    if ($.cookie('rhythmcookie')) {

    }
    else {
      $('#cookie_box').addClass('ope');
      $('#cookieelink').click(function () {
        $.cookie('rhythmcookie','1',{expires:7});
        $('#cookie_box').fadeOut(400);
        return false;
      });
      $('.cookie_btn .not').click(function () {
        $('#cookie_box').fadeOut(400);
        return false;
      });
    }
  }

	$('a[href*=#]:not([href=#],.fancybox,.various,[href*=#])').click(function () {
		if (location.pathname.replace(/^\//, '') == this.pathname.replace(/^\//, '')
			&& location.hostname == this.hostname) {
			var $target = $(this.hash);
			$target = $target.length && $target || $('[name=' + this.hash.slice(1) + ']');
			if ($target.length) {
				var targetOffset = $target.offset().top
				$('html,body').animate({
					scrollTop: targetOffset
				}, 1000);
				return false;
			}
		}
	});

	$(window).scroll(function () {
		var windowHeight = $(window).height(),
			topWindow = $(window).scrollTop();
		$('.scrBlur,.scrFadeTop,.txt_endmineral').each(function () {
			var targetPosition = $(this).offset().top;
			if (topWindow > targetPosition - windowHeight + 80) {
				$(this).addClass("view");
			}
		});
	}).trigger('scroll');

	var state = false;
	var scrollpos;

	$('#gHeader .menu').on('click', function () {
		if (state == false) {
			scrollpos = $(window).scrollTop();
			$('body').addClass('fixed').css({
				'top': -scrollpos
			});
			$('#gHeader .menu').addClass('on');
			$('#gHeader .menuBox').slideDown(300);
			state = true;
		} else {
			$('body').removeClass('fixed').css({
				'top': 0
			});
			window.scrollTo(0, scrollpos);
			$('#gHeader .menu').removeClass('on');
			$('#gHeader .menuBox').slideUp(300);
			state = false;
		}
	});

	$('#gHeader .menuBox .close').on('click', function () {
		if (state == false) {
			scrollpos = $(window).scrollTop();
			$('body').addClass('fixed').css({
				'top': -scrollpos
			});
			$('#gHeader .menu').addClass('on');
			$('#gHeader .menuBox').slideDown(300);
			state = true;
		} else {
			$('body').removeClass('fixed').css({
				'top': 0
			});
			window.scrollTo(0, scrollpos);
			$('#gHeader .menu').removeClass('on');
			$('#gHeader .menuBox').slideUp(300);
			state = false;
		}
	});

	$('#main .item_text strong').on('click', function () {
		var th = $(this);
		if (typeof $(this).attr('class') === 'undefined' || $(this).attr('class') == '') {
			th.addClass('on');
			th.siblings('span').addClass('on').css({
				height: th.siblings('span').find('em').outerHeight(true)
			});
		} else {
			th.removeClass('on');
			th.siblings('span').removeClass('on').css({
				height: 0
			});
		}
		return false;
	});

	$('#main .item_use strong').on('click', function () {
		var th = $(this);
		if (typeof $(this).attr('class') === 'undefined' || $(this).attr('class') == '') {
			th.addClass('on');
			th.siblings('div').addClass('on').css({
				height: th.siblings('div').find('ul,ol').outerHeight(true)
			});
		} else {
			th.removeClass('on');
			th.siblings('div').removeClass('on').css({
				height: 0
			});
		}
		return false;
	});

  var lang = $('.lang');
  lang.find('select').change(function () {
    var th = $(this).val();
    window.location.href = th;

  });

});

$(window).on('load', function () {
	var localLink = window.location + '';
	setTimeout(function () {
		if (localLink.indexOf("#") != -1 && localLink.slice(-1) != '#') {
			localLink = localLink.slice(localLink.indexOf("#") + 1);
			$('html,body').animate({
				scrollTop: $('#' + localLink).offset().top
			}, 500);
		}
	}, 300);
});
