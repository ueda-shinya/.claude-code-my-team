<?php
// 出力バッファリングを最優先で開始（BOM / 先頭空白による session_start() 失敗対策）
if (ob_get_level() === 0) {
  ob_start();
}
if (session_status() === PHP_SESSION_NONE) session_start();
include_once(dirname(__DIR__) . '/app_config.php');

// complete/index.php から戻ってきた場合の送信エラーメッセージを取得して消去
// ※ 早期リダイレクト判定より前に取得しておく
$send_error = '';
if (!empty($_SESSION['send_error'])) {
  $send_error = $_SESSION['send_error'];
  unset($_SESSION['send_error']);
}

// 送信エラーで戻ってきた場合（send_error あり）はリダイレクトしない
if (empty($_POST['actionFlag']) && empty($_SESSION['statusFlag']) && $send_error === '') {
  header('location: ' . APP_URL);
  exit;
}

$gtime = time();

//always keep this
$actionFlag       = (!empty($_POST['actionFlag'])) ? htmlspecialchars($_POST['actionFlag']) : '';
$reg_url          = (!empty($_POST['url'])) ? htmlspecialchars($_POST['url']) : '';
//end always keep this

//お問い合わせフォーム内容
// 送信エラー後のリダイレクトで POST が空になる場合、セッションの退避値を使う
$retry = (!empty($_SESSION['retry_data']) && is_array($_SESSION['retry_data']))
  ? $_SESSION['retry_data']
  : [];
if (!empty($retry)) {
  unset($_SESSION['retry_data']);
}

$reg_salon_name = (!empty($_POST['salon_name']))
  ? htmlspecialchars($_POST['salon_name'])
  : htmlspecialchars($retry['salon_name'] ?? '');
$reg_name       = (!empty($_POST['name']))        ? htmlspecialchars($_POST['name'])    : htmlspecialchars($retry['name'] ?? '');
$reg_email      = (!empty($_POST['email']))       ? htmlspecialchars($_POST['email'])   : htmlspecialchars($retry['email'] ?? '');
$reg_tel        = (!empty($_POST['tel']))         ? htmlspecialchars($_POST['tel'])     : htmlspecialchars($retry['tel'] ?? '');
$reg_address    = (!empty($_POST['address']))     ? htmlspecialchars($_POST['address']) : htmlspecialchars($retry['address'] ?? '');
$reg_comment    = (!empty($_POST['comment']))     ? htmlspecialchars($_POST['comment']) : htmlspecialchars($retry['comment'] ?? '');
// $br_reg_comment   = nl2br($reg_content);

// send_error がある場合は "confirm" 相当として扱い、確認画面を表示する
if ($send_error !== '' && $actionFlag === '') {
  $actionFlag = 'confirm';
}

if ($actionFlag == "confirm") {
  $thisPageName = 'contact';
  $_SESSION['ses_from_step2'] = true;
  // エラーリダイレクト後の再表示でも gtime を更新して新しい g パラメータと一致させる
  $_SESSION['ses_gtime_step2'] = $gtime;
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta name="viewport" content="width=device-width, user-scalable=no" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="format-detection" content="telephone=no">
<title>入力内容確認｜</title>
<meta name="description" content="" />
<meta name="keywords" content="" />
<link rel="stylesheet" href="../css/style.css">
<script src="../js/jquery.js"></script>
<script src="../js/common.js"></script>

<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
<script type="text/javascript" src="../lib/js/jquery.rollover.js"></script>

<?php if (defined('GOOGLE_RECAPTCHA_KEY_API') && GOOGLE_RECAPTCHA_KEY_API != '' && defined('GOOGLE_RECAPTCHA_KEY_SECRET') && GOOGLE_RECAPTCHA_KEY_SECRET != '') { ?>
  <script src="https://www.google.com/recaptcha/api.js?hl=ja" async defer></script>
  <script>
    function onSubmit(token) {
      document.getElementById("confirmform").submit();
    }
  </script>
  <style>
    .grecaptcha-badge {
      display: none
    }
  </style>
<?php } ?>

</head>
<body id="confirm">
<main id="wrapper">
  <section>
		<div class="scrFadeTop">
			<h2><img src="../images/cont_contact_01.jpg" width="750" alt=""/></h2>
		</div>
    <!--フォームここから -->

    <div id="form">
      <div id="form_wrapper" class="cotactform-wrap">
        <!--header -->
        <div id="header" class="contactform clearfix">
          <?php if ($send_error !== ''): ?>
          <p class="error-message"><?php echo htmlspecialchars($send_error, ENT_QUOTES, 'UTF-8'); ?></p>
          <?php endif; ?>
          <p class="caption">
            担当者が内容を確認し、通常3日程でご連絡をさしあげますが、<br />
            3日以上たってもお返事が届かない場合は、お手数ですが以下連絡先までご連絡ください。
          </p>
          <!-- 電話でのご予約申し込みはこちら -->
          <!-- <p class="caption_red">【必須】項目は必ず入力してください</p> -->
        </div>
        <!--header -->

        <form method="post" class="contactform confirmform" action="../complete/?g=<?php echo $gtime ?>" name="confirmform" id="confirmform">
          <div class="form-row">
            <label class="form-label">サロン名</label>
            <div class="form-value"><?php echo $reg_salon_name; ?></div>
          </div>

          <div class="form-row">
            <label class="form-label">お名前</label>
            <div class="form-value"><?php echo $reg_name; ?></div>
          </div>

          <div class="form-row">
            <label class="form-label">電話番号</label>
            <div class="form-value"><?php echo $reg_tel; ?></div>
          </div>

          <div class="form-row">
            <label class="form-label">住所</label>
            <div class="form-value"><?php echo nl2br($reg_address); ?></div>
          </div>

          <div class="form-row">
            <label class="form-label">メールアドレス</label>
            <div class="form-value"><?php echo $reg_email; ?></div>
          </div>

          <div class="form-row">
            <label class="form-label">コメント</label>
            <div class="form-value"><?php echo nl2br($reg_comment); ?></div>
          </div>

          <p class="text">入力が正しければ、送信ボタンを押して下さい。</p>
          <div class="form-row submit">
            <?php // echo $sfm_submit; ?>
            <!-- <button type="submit">送信</button> -->

            <input type="hidden" name="mode" id="mode" value="SEND" />
            <button id="sbm_btn" class="submit over sbm_btn" type="submit">送信</button><br>

            <a class="btn" href="javascript:history.back()">修正</a>
            <input type="hidden" name="actionFlag" value="send">
          </div>
          <input type="hidden" name="salon_name" value="<?php echo $reg_salon_name; ?>">
          <input type="hidden" name="name" value="<?php echo $reg_name; ?>">
          <input type="hidden" name="tel" value="<?php echo $reg_tel; ?>">
          <input type="hidden" name="address" value="<?php echo $reg_address; ?>">
          <input type="hidden" name="email" value="<?php echo $reg_email; ?>">
          <input type="hidden" name="comment" value="<?php echo $reg_comment; ?>">
          <!-- always keep this -->
          <input type="hidden" name="url" value="<?php echo $reg_url; ?>">
        </form>
    </div>

    <div class="scrFadeTop">
      <p><img src="../images/cont_footer.jpg" width="750" alt=""/></p>
    </div>
</section>

  </div>
    <!--wrapper -->

  </div>
<!--container-->
<script>
function on_submit(){
    // submitイベントが発生したらアラートを表示。
    // alert('submitted');
    // ボタン 'btn' を disabled に。
    document.getElementById( 'sbm_btn' ).disabled = true;
}

// フォームで submit イベントが発生したら、関数 on_submit() を呼ぶ。
document.getElementById( 'confirmform' ).addEventListener( 'submit' , on_submit );
</script>
</body>
</html>
<?php } ?>