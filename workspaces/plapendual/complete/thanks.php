<?php

include_once(dirname(__DIR__) . '/app_config.php');
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="ja">
<head>
<meta name="viewport" content="width=device-width, user-scalable=no" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>送信完了｜バクチスコーポレーション株式会社</title>
<meta name="description" content="" />
<meta name="keywords" content="" />
<link rel="stylesheet" href="../css/style.css">
<script src="../js/jquery.js"></script>
<script src="../js/common.js"></script>

<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
<script type="text/javascript" src="lib/js/jquery.rollover.js"></script>

<!-- <meta http-equiv="refresh" content="15; url=<?php echo APP_URL ?>"> -->
<script type="text/javascript">
  history.pushState({
    page: 1
  }, "title 1", "#noback");
  window.onhashchange = function(event) {
    window.location.hash = "#noback";
  };
</script>
<!-- <link rel="stylesheet" href="<?php echo APP_ASSETS ?>css/page/lp.min.css"> -->
<!-- <link rel="stylesheet" href="<?php echo APP_ASSETS ?>css/page/confirm.min.css"> -->
<!-- <link rel="stylesheet" href="<?php echo APP_ASSETS ?>css/page/thanks.min.css"> -->
</head>


<body id="complete">
  <div id="wrapper">
    <!--フォームここから -->
    <section>
      <div class="scrFadeTop">
        <h2><img src="../images/cont_contact_01.jpg" width="750" alt=""/></h2>
      </div>
      <div id="form_wrapper" class="cotactform-wrap">
        <!--header -->
        <div id="header" class="contactform clearfix">
          <p class="caption">担当者が内容を確認し、通常3日程でご連絡をさしあげますが、<br />
          3日以上たってもお返事が届かない場合は、<br>お手数ですが本院までご連絡ください。</p>
        </div>
        <!--header -->

        <p class="contactform text thanks">お問い合わせ・ご予約ありがとうございました。</p>
      </div>
      <div class="scrFadeTop">
        <p><img src="../images/cont_footer.jpg" width="750" alt=""/></p>
      </div>

  </section>
  </div>
  <!--wrapper -->
</body>
</html>