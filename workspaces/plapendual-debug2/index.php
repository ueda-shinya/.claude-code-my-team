<?php if (session_status() === PHP_SESSION_NONE) session_start();
header("Cache-control: public");
ob_start();
$thisPageName = 'LP';
$path = realpath(dirname(__FILE__) . '') . "/";
// echo $path;
include_once($path . 'app_config.php');
?>
<!DOCTYPE html>
<html lang="ja">

<head>
	<!-- Google Tag Manager -->
	<script>
		(function(w, d, s, l, i) {
			w[l] = w[l] || [];
			w[l].push({
				'gtm.start': new Date().getTime(),
				event: 'gtm.js'
			});
			var f = d.getElementsByTagName(s)[0],
				j = d.createElement(s),
				dl = l != 'dataLayer' ? '&l=' + l : '';
			j.async = true;
			j.src =
				'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
			f.parentNode.insertBefore(j, f);
		})(window, document, 'script', 'dataLayer', 'GTM-W87N7HZ');
	</script>
	<!-- End Google Tag Manager -->
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width,initial-scale=1.0">
	<title></title>
	<meta name="description" content="" />
	<link rel="stylesheet" href="js/swiper/swiper-bundle.min.css">
	<!-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"> -->
	<link rel="stylesheet" href="css/style.css">

	<script src="./js/jquery.js"></script>
	<!-- <script src="//ajax.googleapis.com42/ajax/libs/jqueryui/1/jquery-ui.min.js"></script> -->
	<script src="./js/common.js"></script>
</head>

<body id="home">
	<!-- Google Tag Manager (noscript) -->
	<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-W87N7HZ"
			height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
	<!-- End Google Tag Manager (noscript) -->
	<main id="wrapper">
		<header>
			<a href="#contactform" class="btn">
				<img src="images/btn_cv.png" width="750" alt="" />
			</a>
		</header>
		<section>
			<div class="scrFadeTop">
				<p><img src="images/cont01_01_nomal.jpg" width="750" alt="" /></p>
				<p><img src="images/cont01_02_nomal.jpg" width="750" alt="" /></p>
				<h1><img src="images/cont01_03_nomal.jpg" width="750" alt="" /></h1>
				<p class="cv cv__nomal">
					<img src="images/cont01_04_nomal.jpg" width="750" alt="" />
					<a class="btn cv_btn" href="#contactform">
						<img src="images/btn_cv.png" alt="">
					</a>
				</p>
				<p><img src="images/cont01_05_nomal.jpg" width="750" alt="" /></p>
				<p class="mov mov__cont01_06">
					<img src="images/cont01_06_nomal.jpg" width="750" alt="" />
					<span class="mov-wrap">
						<video autoplay loop muted playsinline preload="auto">
							<source src="mov/cont01_06.mp4" type="video/mp4" />
						</video>
					</span>
				</p>
				<!-- <p><img src="images/cont01_10.jpg" width="750" alt=""/></p>
			<p class="mov mov__cont01_11">
				<img src="images/cont01_11.jpg" width="750" alt=""/>
				<span class="mov-wrap">
					<video autoplay loop muted playsinline preload="auto" >
						<source src="mov/cont01_06.mp4" type="video/mp4" />
					</video>
				</span>
			</p> -->
			</div>
			<div class="scrFadeTop">
				<h2><img src="images/cont02_01_nomal.jpg" width="750" alt="" /></h2>
				<p><img src="images/cont02_02.jpg" width="750" alt="" /></p>
				<p><img src="images/cont02_03.jpg" width="750" alt="" /></p>
				<p><img src="images/cont02_04.jpg" width="750" alt="" /></p>
				<p><img src="images/cont02_05.jpg" width="750" alt="" /></p>
				<p><img src="images/cont02_06.jpg" width="750" alt="" /></p>
				<p><img src="images/cont02_07.jpg" width="750" alt="" /></p>
				<p><img src="images/cont02_08.jpg" width="750" alt="" /></p>
				<p><img src="images/cont02_09.jpg" width="750" alt="" /></p>
			</div>
			<div class="scrFadeTop">
				<h2><img src="images/cont03_01.jpg" width="750" alt="" /></h2>
				<p><img src="images/cont03_02.jpg" width="750" alt="" /></p>
				<h3><img src="images/cont03_03.jpg" width="750" alt="" /></h3>
				<p><img src="images/cont03_04.jpg" width="750" alt="" /></p>
				<p><img src="images/cont03_05.jpg" width="750" alt="" /></p>
				<p><img src="images/cont03_06.jpg" width="750" alt="" /></p>
				<p><img src="images/cont03_07.jpg" width="750" alt="" /></p>
				<p><img src="images/cont03_08.jpg" width="750" alt="" /></p>
				<p><img src="images/cont03_09.jpg" width="750" alt="" /></p>
				<p class="mov mov__cont03_10">
					<img src="images/cont03_10.jpg" width="750" alt="" />
					<span class="mov-wrap">
						<video autoplay loop muted playsinline preload="auto">
							<source src="mov/cont03_10.mp4" type="video/mp4" />
						</video>
					</span>
				</p>
				<p><img src="images/cont03_11.jpg" width="750" alt="" /></p>
				<p class="mov mov__cont03_12">
					<img src="images/cont03_12.jpg" width="750" alt="" />
					<span class="mov-wrap">
						<video autoplay loop muted playsinline preload="auto">
							<source src="mov/cont03_12.mp4" type="video/mp4" />
						</video>
					</span>
				</p>
				<p><img src="images/cont03_13.jpg" width="750" alt="" /></p>
				<p><img src="images/cont03_14.jpg" width="750" alt="" /></p>
			</div>
			<div class="scrFadeTop">
				<h2><img src="images/cont04_01.jpg" width="750" alt="" /></h2>
				<p><img src="images/cont04_02.jpg" width="750" alt="" /></p>
				<p><img src="images/cont04_03.jpg" width="750" alt="" /></p>

				<!-- <p><img src="images/cont04_04.jpg" width="750" alt=""/></p> -->

				<div class="cont04_04 swiper js-swiper">
					<div class="swiper-wrapper">
						<div class="swiper-slide">
							<img src="images/jetchip_step01.png" alt="">
						</div>
						<div class="swiper-slide">
							<img src="images/jetchip_step02.png" alt="">
						</div>
						<div class="swiper-slide">
							<img src="images/jetchip_step03.png" alt="">
						</div>
						<div class="swiper-slide">
							<img src="images/jetchip_step04.png" alt="">
						</div>
					</div>

					<!-- ドット -->
					<div class="swiper-pagination"></div>
				</div>

				<p><img src="images/cont04_05.jpg" width="750" alt="" /></p>
				<div class="cont04_06 swiper js-swiper">
					<div class="swiper-wrapper">
						<div class="swiper-slide">
							<img src="images/showerchip_step01.png" alt="">
						</div>
						<div class="swiper-slide">
							<img src="images/showerchip_step02.png" alt="">
						</div>
					</div>

					<!-- ドット -->
					<div class="swiper-pagination"></div>
				</div>
				<p><img src="images/cont04_07.jpg" width="750" alt="" /></p>
			</div>
			<div class="scrFadeTop">
				<h2><img src="images/cont05_01.jpg" width="750" alt="" /></h2>
				<p><img src="images/cont05_02.jpg" width="750" alt="" /></p>
				<p><img src="images/cont05_03.jpg" width="750" alt="" /></p>
				<p><img src="images/cont05_04.jpg" width="750" alt="" /></p>
			</div>
			<div class="scrFadeTop">
				<h2><img src="images/cont06_01.jpg" width="750" alt="" /></h2>
				<p><img src="images/cont06_02.jpg" width="750" alt="" /></p>
				<p><img src="images/cont06_03.jpg" width="750" alt="" /></p>
				<p><img src="images/cont06_04.jpg" width="750" alt="" /></p>
				<p><img src="images/cont06_05.jpg" width="750" alt="" /></p>
			</div>
			<div class="scrFadeTop">
				<h2><img src="images/cont07_01.jpg" width="750" alt="" /></h2>
				<p><img src="images/cont07_02.jpg" width="750" alt="" /></p>
				<p><img src="images/cont07_03.jpg" width="750" alt="" /></p>
				<p><img src="images/cont07_04.jpg" width="750" alt="" /></p>
				<p><img src="images/cont07_05.jpg" width="750" alt="" /></p>
				<p class="mov mov__cont07_06">
					<img src="images/cont07_06.jpg" width="750" alt="" />
					<img class="over" src="images/cont07_06_over.png" width="750" alt="" />
					<span class="mov-wrap">
						<video autoplay loop muted playsinline preload="auto">
							<source src="mov/cont01_06.mp4" type="video/mp4" />
						</video>
					</span>
				</p>
				<p><img src="images/cont07_07.jpg" width="750" alt="" /></p>
				<p class="cv">
					<img src="images/cont07_08.jpg" width="750" alt="" />
					<a class="btn cv_btn" href="#contactform">
						<img src="images/btn_cv.png" alt="">
					</a>
				</p>
			</div>
			<div class="scrFadeTop">
				<h2><img src="images/cont08_01.jpg" width="750" alt="" /></h2>
				<p><img src="images/cont08_02.jpg" width="750" alt="" /></p>
				<p><img src="images/cont08_03.jpg" width="750" alt="" /></p>
				<p><img src="images/cont08_04.jpg" width="750" alt="" /></p>
				<p><img src="images/cont08_05.jpg" width="750" alt="" /></p>
				<p class="mov mov__cont08_06">
					<img src="images/cont08_06.jpg" width="750" alt="" />
					<span class="mov-wrap">
						<video autoplay loop muted playsinline preload="auto">
							<source src="mov/cont08_06.mp4" type="video/mp4" />
						</video>
					</span>
				</p>
			</div>
			<div class="cont_faq">
				<div class="scrFadeTop">
					<h2><img src="images/cont_faq_01.png" width="750" alt="" /></h2>
				</div>
				<div class="faq js-toggle">
					<dl class="slide-item slide-item1">
						<dt>
							<a href="#faq01">
								<i class="icon icon-q"><img src="images/ico_q.webp" alt="" width="29" height="33"></i>
								<p>操作は難しくないですか？</p>
								<i class="icon icon-btn"></i>
							</a>
						</dt>
						<dd id="faq01">
							<div class="imt cont_text">
								<p>短時間のレクチャーで施術可能です。</p>
							</div>
						</dd>
					</dl>
					<dl class="slide-item slide-item1">
						<dt>
							<a href="#faq02">
								<i class="icon icon-q"><img src="images/ico_q.webp" alt="" width="29" height="33"></i>
								<p>導入してどれくらいで<br>回収できますか？</p>
								<i class="icon icon-btn"></i>
							</a>
						</dt>
						<dd id="faq02">
							<div class="imt cont_text">
								<p>月15名程度の施術で十分に可能です。</p>
							</div>
						</dd>
					</dl>
					<dl class="slide-item slide-item1">
						<dt>
							<a href="#faq03">
								<i class="icon icon-q"><img src="images/ico_q.webp" alt="" width="29" height="33"></i>
								<p>施術時に痛みは<br>ありますか？</p>
								<i class="icon icon-btn"></i>
							</a>
						</dt>
						<dd id="faq03">
							<div class="imt cont_text">
								<p>ほとんど痛みはなく、リラックスして受けられます。</p>
							</div>
						</dd>
					</dl>
					<dl class="slide-item slide-item1">
						<dt>
							<a href="#faq04">
								<i class="icon icon-q"><img src="images/ico_q.webp" alt="" width="29" height="33"></i>
								<p>赤みやダウンタイムは<br>ありますか？</p>
								<i class="icon icon-btn"></i>
							</a>
						</dt>
						<dd id="faq04">
							<div class="imt cont_text">
								<p>基本的にダウンタイムはなく、当日メイクも可能です。</p>
							</div>
						</dd>
					</dl>
					<dl class="slide-item slide-item1">
						<dt>
							<a href="#faq05">
								<i class="icon icon-q"><img src="images/ico_q.webp" alt="" width="29" height="33"></i>
								<p>既存メニューと<br>組み合わせられますか？</p>
								<i class="icon icon-btn"></i>
							</a>
						</dt>
						<dd id="faq05">
							<div class="imt cont_text">
								<p>
									フェイシャルへの追加で自然に単価アップできます。
								</p>
							</div>
						</dd>
					</dl>
					<dl class="slide-item slide-item1">
						<dt>
							<a href="#faq06">
								<i class="icon icon-q"><img src="images/ico_q.webp" alt="" width="29" height="33"></i>
								<p>導入講習はありますか？</p>
								<i class="icon icon-btn"></i>
							</a>
						</dt>
						<dd id="faq06">
							<div class="imt cont_text">
								<p>はい、ございます。機器の納品前に導入講習を行っておりますので、使用方法や施術の流れなどをしっかりご理解いただいたうえで安心して導入していただけます。</p>
							</div>
						</dd>
					</dl>
					<dl class="slide-item slide-item1">
						<dt>
							<a href="#faq07">
								<i class="icon icon-q"><img src="images/ico_q.webp" alt="" width="29" height="33"></i>
								<p>どのくらいの期間で<br>導入ができますか？</p>
								<i class="icon icon-btn"></i>
							</a>
						</dt>
						<dd id="faq07">
							<div class="imt cont_text">
								<p>導入までの期間は、最短で約3週間ほどとなっております。ご契約後、機器の手配と導入講習の日程を調整させていただき、スムーズに導入いただけるようサポートいたします。</p>
							</div>
						</dd>
					</dl>
					<dl class="slide-item slide-item1">
						<dt>
							<a href="#faq08">
								<i class="icon icon-q"><img src="images/ico_q.webp" alt="" width="29" height="33"></i>
								<p>メニュー組みはレクチャー<br>してもらえますか？</p>
								<i class="icon icon-btn"></i>
							</a>
						</dt>
						<dd id="faq08">
							<div class="imt cont_text">
								<p>サロン様のコンセプトや既存メニューに合わせて、導入後すぐに活用できるメニューをご提案させていただきます。</p>
							</div>
						</dd>
					</dl>
				</div>
			</div>
			<div class="scrFadeTop">
				<h2><img src="images/cont09_01.jpg" width="750" alt="" /></h2>
				<p><img src="images/cont09_02.jpg" width="750" alt="" /></p>
			</div>
			<div id="contactform" class="scrFadeTop">
				<h2><img src="images/cont_contact_01.jpg" width="750" alt="" /></h2>
			</div>
			<div class="cotactform-wrap scrFadeTop">
				<form method="post" class="contactform" action="confirm/?g=<?php echo time() ?>" name="contactform">
					<div class="form-row">
						<label for="salon_name">サロン名</label>
						<input type="text" class="validate[required]" id="salon_name" name="salon_name" placeholder="サロン名">
					</div>

					<div class="form-row">
						<label for="name">お名前</label>
						<input type="text" class="validate[required]" id="name" name="name" placeholder="お名前">
					</div>

					<div class="form-row">
						<label for="tel">電話番号</label>
						<input type="tel" class="validate[required]" id="tel" name="tel" placeholder="電話番号">
					</div>

					<div class="form-row">
						<label for="address">住所</label>
						<textarea class="validate[required]" id="address" name="address" placeholder="住所"></textarea>
					</div>

					<div class="form-row">
						<label for="email">メールアドレス</label>
						<input type="email" class="validate[required]" id="email" name="email" placeholder="メールアドレス">
					</div>

					<div class="form-row">
						<label for="comment">コメント</label>
						<textarea id="comment" name="comment" placeholder="コメント"></textarea>
					</div>

					<div class="form-row submit">
						<button class="submit over" type="submit">確認</button>
					</div>
					<input type="hidden" name="actionFlag" value="confirm">
				</form>
			</div>
			<div class="footer scrFadeTop">
				<p><img src="images/cont_footer.jpg" width="750" alt="" /></p>
			</div>
		</section>
	</main>

	<script src="js/swiper/swiper-bundle.min.js"></script>
	<!-- <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script> -->
	<script>
		new Swiper('.js-swiper', {
			slidesPerView: 2,
			centeredSlides: true,
			spaceBetween: 48,
			autoHeight: true,
			pagination: {
				el: '.swiper-pagination',
				clickable: true,
			},
		});

		(function($) {
			$('.js-toggle dt a').click(function() {
				var link = $(this);
				var target = link.attr('href');

				if ($(this).attr('class') == 'on') {
					link.removeClass('on');
					$(target).stop().animate({
						'height': 0
					}, 200);
				} else {
					link.addClass('on');
					$(target).stop().animate({
						'height': $(target).find('.imt').outerHeight(true)
					}, 300);
				}
				return false;
			});
		})(jQuery);
	</script>


	<script defer src="<?php echo APP_ASSETS; ?>js/form/jquery.validationEngine.js"></script>
	<script defer src="<?php echo APP_ASSETS; ?>js/form/languages/jquery.validationEngine-ja.js"></script>
	<link rel="stylesheet" href="<?php echo APP_ASSETS; ?>js/form/validationEngine.jquery.css">

	<script>
		function initValidate() {
			0 < $("#contactform").length && $("#contactform").validationEngine({
				promptPosition: "bottomLeft",
				maxErrorsPerField: 1,
				showArrow: !1,
				custom_error_messages: {
					"#salon_name": {
						required: {
							message: "サロン名を入力してください"
						}
					},
					"#name": {
						required: {
							message: "お名前を入力してください"
						}
					},
					"#email": {
						required: {
							message: "メールアドレスを入力してください"
						}
					},
					"#tel": {
						required: {
							message: "電話番号を入力してください"
						}
					},
					"#address": {
						required: {
							message: "住所を入力してください"
						}
					},
					// "#comment": {
					// 		required: {
					// 				message: "住所を入力してください"
					// 		}
					// },
				}
			})
			// , $('input[name="check1"]').on("change", function() {
			// 		$(".form-block__button button").toggleClass("active"), checkStatusButton()
			// })
		}
		$(function() {
			initValidate();
		});
	</script>
</body>

</html>