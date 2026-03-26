<?php
/**
 * Template Name: LP 2026-03-26
 * Description: officeueda.com/lp/ ホームページ制作ランディングページ
 *
 * 配置先: wp-content/themes/{テーマ名}/lp-260326/index.php
 * アセット: 同ディレクトリの style.css / contact.css
 *           js は lp-260319/js/ を流用（パス参照のみ）
 *
 * 依存プラグイン:
 *   - Contact Form 7 (フォーム)
 *   - Widgets for Google Reviews (口コミウィジェット)
 */

// アセットのベースURI（テーマルートからの相対パス）
$lp_uri = get_stylesheet_directory_uri() . '/lp-260326';

// JS は旧LPのファイルを流用
$lp_js_uri = get_stylesheet_directory_uri() . '/lp-260319';

// ページの絶対URL（OGP用）
$page_url = get_permalink();

?><!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- ============================================================
     SEO メタタグ
     ============================================================ -->
<title>広島 ホームページ制作 東広島・呉・広島 | オフィスウエダ</title>
<meta name="description" content="東広島・呉・広島で、問い合わせが来るホームページ制作。専門用語なし・丸投げOK。コーポレートサイト・LP・採用サイト・WordPress保守まで対応。まず無料でご相談ください。">
<meta name="robots" content="index, follow">
<link rel="canonical" href="<?php echo esc_url($page_url); ?>">

<!-- ============================================================
     OGP / SNS シェア
     ============================================================ -->
<meta property="og:type"        content="website">
<meta property="og:url"         content="<?php echo esc_url($page_url); ?>">
<meta property="og:site_name"   content="オフィスウエダ">
<meta property="og:title"       content="作ったのに成果が出ない。その先から、一緒にやります。| オフィスウエダ">
<meta property="og:description" content="東広島・呉・広島で、問い合わせが来るホームページ制作。専門用語なし・丸投げOK。まず無料でご相談ください。">
<meta property="og:image"       content="<?php echo esc_url($lp_uri); ?>/images/ogp.webp">
<meta property="og:image:width"  content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale"      content="ja_JP">

<meta name="twitter:card"        content="summary_large_image">
<meta name="twitter:title"       content="作ったのに成果が出ない。その先から、一緒にやります。| オフィスウエダ">
<meta name="twitter:description" content="東広島・呉・広島で、問い合わせが来るホームページ制作。専門用語なし・丸投げOK。">
<meta name="twitter:image"       content="<?php echo esc_url($lp_uri); ?>/images/ogp.webp">

<!-- ============================================================
     構造化データ（JSON-LD）
     LP 2026-03-26 版：FAQ Q6（LINEで相談できますか？）追加
     ============================================================ -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "LocalBusiness",
      "@id": "https://officeueda.com/#business",
      "name": "オフィスウエダ",
      "description": "東広島・呉・広島を拠点に、中小企業・個人事業主のホームページ制作・Web支援を行うWebデザイン事務所",
      "url": "https://officeueda.com",
      "foundingDate": "2022",
      "image": "<?php echo esc_url($lp_uri); ?>/images/shinyaueda.png",
      "logo": "https://officeueda.com/wp-content/uploads/logo.png",
      "address": {
        "@type": "PostalAddress",
        "addressLocality": "東広島市",
        "addressRegion": "広島県",
        "addressCountry": "JP"
      },
      "geo": {
        "@type": "GeoCoordinates",
        "latitude": 34.4269,
        "longitude": 132.7435
      },
      "areaServed": [
        { "@type": "City", "name": "東広島市" },
        { "@type": "City", "name": "呉市" },
        { "@type": "City", "name": "広島市" }
      ],
      "hasOfferCatalog": {
        "@type": "OfferCatalog",
        "name": "Web制作・支援サービス",
        "itemListElement": [
          { "@type": "Offer", "itemOffered": { "@type": "Service", "name": "コーポレートサイト・店舗サイト制作" } },
          { "@type": "Offer", "itemOffered": { "@type": "Service", "name": "採用サイト制作" } },
          { "@type": "Offer", "itemOffered": { "@type": "Service", "name": "LP制作" } },
          { "@type": "Offer", "itemOffered": { "@type": "Service", "name": "スワイプLP制作" } },
          { "@type": "Offer", "itemOffered": { "@type": "Service", "name": "集客強化リニューアル" } },
          { "@type": "Offer", "itemOffered": { "@type": "Service", "name": "WordPress保守・運用サポート" } },
          { "@type": "Offer", "itemOffered": { "@type": "Service", "name": "WP復旧24（緊急対応）" } }
        ]
      },
      "sameAs": [
        "https://www.instagram.com/officeueda/"
      ]
    },
    {
      "@type": "WebPage",
      "@id": "<?php echo esc_url($page_url); ?>#webpage",
      "url": "<?php echo esc_url($page_url); ?>",
      "name": "LP 2026-03-26 広島 ホームページ制作 東広島・呉・広島 | オフィスウエダ",
      "description": "東広島・呉・広島で、問い合わせが来るホームページ制作。専門用語なし・丸投げOK。",
      "isPartOf": { "@id": "https://officeueda.com/#website" },
      "about": { "@id": "https://officeueda.com/#business" },
      "breadcrumb": {
        "@type": "BreadcrumbList",
        "itemListElement": [
          { "@type": "ListItem", "position": 1, "name": "ホーム", "item": "https://officeueda.com/" },
          { "@type": "ListItem", "position": 2, "name": "ホームページ制作LP", "item": "<?php echo esc_url($page_url); ?>" }
        ]
      }
    },
    {
      "@type": "FAQPage",
      "@id": "<?php echo esc_url($page_url); ?>#faq",
      "mainEntity": [
        {
          "@type": "Question",
          "name": "相談だけでも大丈夫ですか？",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "はい、もちろんです。「まだ検討段階」「予算感だけ知りたい」という段階のご相談も大歓迎です。話を聞いたからといって、申し込みを迫ることは一切しません。安心してご連絡ください。"
          }
        },
        {
          "@type": "Question",
          "name": "費用はどのくらいかかりますか？",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "サービスや規模・ご要望によって異なります。まずご相談の中で予算感をお伝えします。「金額だけ知りたい」という段階でもお気軽にどうぞ。"
          }
        },
        {
          "@type": "Question",
          "name": "専門知識がなくても大丈夫ですか？",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "まったく問題ありません。打ち合わせは専門用語を使わず、わかりやすい言葉でご説明します。"
          }
        },
        {
          "@type": "Question",
          "name": "広島県外からの依頼はできますか？",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "はい、対応しています。遠方の方はオンラインでのお打ち合わせも可能です。"
          }
        },
        {
          "@type": "Question",
          "name": "制作期間はどれくらいかかりますか？",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "サービスの種類や内容によって異なりますが、コーポレートサイトで1〜2ヶ月程度が目安です。"
          }
        },
        {
          "@type": "Question",
          "name": "LINEで相談できますか？",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "はい、LINEでのご相談も受け付けています。フォームへの記入が面倒な方や、「ちょっと聞いてみたいだけ」という方も、気軽にメッセージを送ってください。同じく上田が直接対応します。"
          }
        }
      ]
    }
  ]
}
</script>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;700;900&display=swap" rel="stylesheet">

<?php wp_head(); ?>

<link rel="stylesheet" href="<?php echo esc_url($lp_uri); ?>/style.css">
<link rel="stylesheet" href="<?php echo esc_url($lp_uri); ?>/contact.css">

</head>

<body class="lp-body">

<!-- ============================================================
     セクション1：FV（ファーストビュー）
     ============================================================ -->
<section class="lp-fv">
  <div class="lp-fv__overlay" aria-hidden="true"></div>
  <div class="lp-fv__inner">
    <div class="lp-fv__text">
      <h1 class="lp-fv__catch">
        作ったのに成果が出ない。<br>その先から、一緒にやります。<br>
        <span class="lp-fv__catch-area">東広島・広島のホームページ制作。</span>
      </h1>
      <p class="lp-fv__sub">
        「問い合わせが来ない」「何を直せばいいかわからない」——<br>
        原因の整理から改善・運用まで、専門用語なしで伴走します。
      </p>
      <div class="lp-fv__btn-wrap">
        <a href="#contact" class="lp-btn" data-cta-label="fv_form">無料でホームページ相談してみる</a>
        <a href="https://lin.ee/v7FmZuu" target="_blank" rel="noopener noreferrer"
           class="lp-btn-line" data-cta-label="fv_line">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="white" aria-hidden="true">
            <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63h2.386c.349 0 .63.285.63.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63.349 0 .631.285.631.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.281.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
          </svg>
          LINEで話してみる（無料）
        </a>
      </div>
    </div>
    <div class="lp-fv__photo">
      <img
        src="<?php echo esc_url($lp_uri); ?>/images/shinyaueda.webp"
        alt="オフィスウエダ 代表 上田伸也"
        width="300" height="400"
        loading="eager"
      >
    </div>
  </div>
</section>


<!-- ============================================================
     セクション2：数字バー
     ============================================================ -->
<section class="lp-numbers lp-section--white lp-animate">
  <div class="lp-inner">
    <div class="lp-numbers__grid">

      <div class="lp-number-item">
        <div class="lp-number-item__num">30</div>
        <div class="lp-number-item__unit">件以上</div>
        <div class="lp-number-item__label">制作・改修実績</div>
      </div>

      <div class="lp-number-item">
        <div class="lp-number-item__num lp-number-item__num--star">★5.0</div>
        <div class="lp-number-item__unit lp-number-item__unit--star">Google口コミ</div>
        <div class="lp-number-item__label">お客様評価</div>
      </div>

      <div class="lp-number-item">
        <div class="lp-number-item__num">0</div>
        <div class="lp-number-item__unit">円</div>
        <div class="lp-number-item__label">相談料（押し売りなし）</div>
      </div>

    </div>
  </div>
</section>


<!-- ============================================================
     セクション3：FV直下CTA
     ============================================================ -->
<section class="lp-section lp-section--dark lp-cta-top lp-animate">
  <div class="lp-inner lp-cta-top__inner">
    <p>
      専門用語なし・丸投げOK。<br>
      「何から始めればいいかわからない」という段階でも、一緒に整理します。<br>
      フォームでもLINEでも、まず話だけでOKです。
    </p>
    <div class="lp-cta-top__btn-wrap">
      <a href="#contact" class="lp-btn lp-btn--white" data-cta-label="ctatop_form">まず話だけでも聞いてみる</a>
      <a href="https://lin.ee/v7FmZuu" target="_blank" rel="noopener noreferrer"
         class="lp-btn-line" data-cta-label="ctatop_line">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="white" aria-hidden="true">
          <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63h2.386c.349 0 .63.285.63.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63.349 0 .631.285.631.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.281.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
        </svg>
        LINEで気軽に相談する
      </a>
    </div>
    <p class="lp-cta__micro">相談は無料・押し売りなし。話を聞いたからといって、申し込みを迫ることはしません。</p>
  </div>
</section>


<!-- ============================================================
     セクション4：課題提起
     ============================================================ -->
<section class="lp-section lp-section--light lp-animate">
  <div class="lp-inner">
    <h2 class="lp-h2" style="text-align:center;">こんなお悩み、ありませんか？</h2>
    <ul class="lp-troubles__list">
      <li class="lp-troubles__item">ホームページを作ったのに、問い合わせがほとんど来ない</li>
      <li class="lp-troubles__item">業者に頼みたいけど、専門用語が多くて話についていけない</li>
      <li class="lp-troubles__item">「作って終わり」で、その後のサポートがまったくない</li>
    </ul>
  </div>
</section>


<!-- ============================================================
     セクション5：選ばれる3つの理由
     ============================================================ -->
<section class="lp-section lp-section--white lp-reasons lp-animate">
  <div class="lp-inner">
    <h2 class="lp-h2" style="text-align:center;">オフィスウエダが選ばれる3つの理由</h2>
    <div class="lp-grid-3">

      <div class="lp-card">
        <img
          src="<?php echo esc_url($lp_uri); ?>/images/icon-local.webp"
          alt="地域密着アイコン"
          class="lp-card__icon"
          width="88" height="88"
          loading="lazy"
        >
        <h3 class="lp-h3">東広島在住の上田が、直接伺います。</h3>
        <p>チームへの丸投げや外注なし。問い合わせから打ち合わせ・制作・納品後のサポートまで、上田伸也が一人で担当します。「あの人に聞けばわかる」という安心感が、長く続くお付き合いにつながっています。東広島を拠点に、呉・広島にも対面で対応しています。</p>
      </div>

      <div class="lp-card">
        <img
          src="<?php echo esc_url($lp_uri); ?>/images/icon-easy.webp"
          alt="わかりやすさアイコン"
          class="lp-card__icon"
          width="88" height="88"
          loading="lazy"
        >
        <h3 class="lp-h3">打ち合わせでIT用語を使いません。</h3>
        <p>「ドメイン」「レスポンシブ」「CMS」——こういった言葉は、こちらから使いません。「どんな人に来てほしいか」「今困っていること」を普通の言葉で話してもらえれば、あとは全部こちらで整理します。原稿作成・写真選定・更新作業も代行できます。</p>
      </div>

      <div class="lp-card">
        <img
          src="<?php echo esc_url($lp_uri); ?>/images/icon-result.webp"
          alt="成果重視アイコン"
          class="lp-card__icon"
          width="88" height="88"
          loading="lazy"
        >
        <h3 class="lp-h3">公開後に「音沙汰なし」にはなりません。</h3>
        <p>「業者に作ってもらったあと、連絡が取れなくなった」という経験はありませんか。オフィスウエダでは、納品後も更新・改善・相談の窓口を続けます。「なんか最近問い合わせ減った気がする」そんな一言から、一緒に原因を探します。</p>
      </div>

    </div>

    <!-- Before/After（理由3直後） -->
    <div class="lp-reasons__result lp-animate">
      <p class="lp-reasons__result-lead">伴走の実績、数字でお見せします。</p>
      <div class="lp-works__result">
        <p class="lp-works__result-item">採用専用サイト制作後 3ヶ月で採用応募<br><strong>月0件 → 5件</strong>（東広島市・建設業・匿名）</p>
      </div>
    </div>

  </div>
</section>


<!-- ============================================================
     セクション6：代表プロフィール
     ============================================================ -->
<section class="lp-section lp-section--light lp-animate">
  <div class="lp-inner">
    <div class="lp-grid-2 lp-grid-2--center">

      <div class="lp-profile__photo">
        <img
          src="<?php echo esc_url($lp_uri); ?>/images/shinyaueda.webp"
          alt="オフィスウエダ 代表 上田伸也"
          width="360" height="400"
          loading="lazy"
        >
      </div>

      <div class="lp-profile__text">
        <h2 class="lp-h2">
          はじめまして。<br>オフィスウエダの上田伸也です。
        </h2>
        <p>広島県東広島市を拠点に、地域の中小企業・個人事業主のホームページ制作と活用サポートをしています。</p>
        <p>「ホームページを作ったのに問い合わせが来ない」「業者に頼んだら話が通じなかった」——そんな声を聞くたびに、悔しいと思ってきました。</p>
        <p>だから私は、成果が出るまで隣にいることを仕事にしています。"あ、ちょっと来てよ"と気軽に呼んでもらえる、そういうWeb担当者でありたいと思っています。</p>
      </div>

    </div>
  </div>
</section>


<!-- ============================================================
     セクション7：中間CTA
     ============================================================ -->
<section class="lp-section lp-section--dark lp-cta-mid lp-animate">
  <div class="lp-inner">
    <h2 class="lp-h2">何も決まっていなくて、<br>大丈夫です。</h2>
    <p>
      「うちに何が必要かわからない」「そもそも何から始めればいいか」——<br>
      そんな段階からでも、一緒に整理します。専門用語なし・押し売りなし。
    </p>
    <div class="lp-cta-mid__btn-wrap">
      <a href="#contact" class="lp-btn lp-btn--white" data-cta-label="ctamid_form">無料で相談してみる</a>
      <a href="https://lin.ee/v7FmZuu" target="_blank" rel="noopener noreferrer"
         class="lp-btn-line" data-cta-label="ctamid_line">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="white" aria-hidden="true">
          <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63h2.386c.349 0 .63.285.63.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63.349 0 .631.285.631.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.281.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
        </svg>
        LINEで気軽に話す
      </a>
    </div>
    <p class="lp-cta__micro">フォームが苦手な方はLINEでどうぞ。どちらでも同じ上田が対応します。</p>
  </div>
</section>


<!-- ============================================================
     セクション8：サービス一覧
     ============================================================ -->
<section class="lp-section lp-section--light lp-services lp-animate">
  <div class="lp-inner">
    <h2 class="lp-h2" style="text-align:center;">ホームページ制作・Web支援サービス</h2>
    <p class="lp-lead" style="text-align:center;">
      制作から運用サポートまで。必要なことは、ぜんぶ対応します。
    </p>

    <div class="lp-grid-3">

      <div class="lp-card">
        <img src="<?php echo esc_url($lp_uri); ?>/images/svc-corporate.webp"
             alt="コーポレートサイト・店舗サイト制作" class="lp-service-card__img"
             width="400" height="180" loading="lazy">
        <div class="lp-service-card__body">
          <h3 class="lp-h3">コーポレートサイト・<br>店舗サイト制作</h3>
          <p>会社・お店の顔を、問い合わせが来るデザインに。オリジナルデザイン＋SEO対策込み。中小企業・個人事業主の規模感に合わせた複数プランをご用意しています。</p>
        </div>
      </div>

      <div class="lp-card">
        <img src="<?php echo esc_url($lp_uri); ?>/images/svc-recruit.webp"
             alt="採用サイト制作" class="lp-service-card__img"
             width="400" height="180" loading="lazy">
        <div class="lp-service-card__body">
          <h3 class="lp-h3">採用サイト制作</h3>
          <p>採りたい人に届く、採用専用サイト。求人に特化したサイト構成で、応募数の改善を目指します。</p>
        </div>
      </div>

      <div class="lp-card">
        <img src="<?php echo esc_url($lp_uri); ?>/images/svc-lp.webp"
             alt="LP制作" class="lp-service-card__img"
             width="400" height="180" loading="lazy">
        <div class="lp-service-card__body">
          <h3 class="lp-h3">LP制作</h3>
          <p>問い合わせを取りに行く、縦スクロールLP。コンバージョン設計に特化したLPを制作。</p>
        </div>
      </div>

      <div class="lp-card">
        <img src="<?php echo esc_url($lp_uri); ?>/images/svc-swipe.webp"
             alt="スワイプLP制作" class="lp-service-card__img"
             width="400" height="180" loading="lazy">
        <div class="lp-service-card__body">
          <h3 class="lp-h3">スワイプLP制作</h3>
          <p>スマホ世代に届く、スワイプ型LP。Instagram広告などスマホファーストな集客施策との相性が抜群です。</p>
        </div>
      </div>

      <div class="lp-card">
        <img src="<?php echo esc_url($lp_uri); ?>/images/svc-renewal.webp"
             alt="集客強化リニューアル" class="lp-service-card__img"
             width="400" height="180" loading="lazy">
        <div class="lp-service-card__body">
          <h3 class="lp-h3">集客強化リニューアル</h3>
          <p>今あるサイトを、問い合わせが増える形に改修。</p>
        </div>
      </div>

      <div class="lp-card">
        <img src="<?php echo esc_url($lp_uri); ?>/images/svc-maintenance.webp"
             alt="WordPress保守・運用サポート" class="lp-service-card__img"
             width="400" height="180" loading="lazy">
        <div class="lp-service-card__body">
          <h3 class="lp-h3">WordPress保守・<br>運用サポート</h3>
          <p>作ったあとも、ずっと任せられる。更新・バックアップ・セキュリティ対応まで。</p>
        </div>
      </div>

    </div>

    <!-- 緊急対応（7枚目・中央寄せ1枚） -->
    <div class="lp-service-emergency">
      <div class="lp-card">
        <img src="<?php echo esc_url($lp_uri); ?>/images/svc-emergency.webp"
             alt="WP復旧24（緊急対応）" class="lp-service-card__img"
             width="400" height="180" loading="lazy">
        <div class="lp-service-card__body">
          <h3 class="lp-h3">WP復旧24（緊急対応）</h3>
          <p>WordPressが突然動かなくなったら、すぐ呼んでください。</p>
        </div>
      </div>
    </div>

  </div>
</section>


<!-- ============================================================
     セクション9：サービス後CTA（新規）
     ============================================================ -->
<section class="lp-section lp-section--dark lp-cta-services lp-animate">
  <div class="lp-inner lp-cta-services__inner">
    <p class="lp-cta-services__lead">
      気になるサービスはありましたか？「うちはどれが合うんだろう」という段階で大丈夫です。まず話を聞かせてください。
    </p>
    <div class="lp-cta-services__btn-wrap">
      <a href="#contact" class="lp-btn lp-btn--white" data-cta-label="cta_services_form">どのサービスか相談してみる（無料）</a>
      <a href="https://lin.ee/v7FmZuu" target="_blank" rel="noopener noreferrer"
         class="lp-btn-line" data-cta-label="cta_services_line">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="white" aria-hidden="true">
          <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63h2.386c.349 0 .63.285.63.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63.349 0 .631.285.631.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.281.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
        </svg>
        LINEで「どれが合うか」聞いてみる
      </a>
    </div>
    <p class="lp-cta__micro">相談料0円・押し売りなし。あなたに合ったプランを一緒に考えます。</p>
  </div>
</section>


<!-- ============================================================
     セクション10：制作実績
     ============================================================ -->
<section class="lp-section lp-section--white lp-animate">
  <div class="lp-inner">
    <h2 class="lp-h2" style="text-align:center;">制作実績</h2>
    <p class="lp-lead" style="text-align:center;">
      これまで30件以上のサイト制作・改修に携わってきました。
    </p>

    <div class="lp-grid-2">

      <!-- 公開案件 -->
      <div class="lp-card lp-works__card">
        <img
          src="<?php echo esc_url($lp_uri); ?>/images/portfolio-iwamoto.webp"
          alt="岩本商店「神の里」様 ホームページ"
          width="800" height="500"
          loading="lazy"
        >
        <div class="lp-works__badge-wrap">
          <span class="lp-badge">農産物小売</span>
          <span class="lp-badge">コーポレートサイト・EC</span>
        </div>
        <h3 class="lp-h3">岩本商店「神の里」様</h3>
        <p class="lp-works__role">担当：コーディング</p>
        <p>島根県産の無農薬米・しめ縄を販売する岩本商店様のサイト。</p>
        <a href="https://iwamotoshoten.com/" target="_blank" rel="noopener noreferrer" class="lp-btn">
          サイトを見る ↗
        </a>
      </div>

      <!-- 対応業種まとめ -->
      <div class="lp-card lp-works__genres">
        <h3 class="lp-h3">対応業種（一部）</h3>
        <div class="lp-works__badge-wrap">
          <span class="lp-badge">飲食・小売</span>
          <span class="lp-badge">医療・介護</span>
          <span class="lp-badge">建設・不動産</span>
          <span class="lp-badge">士業・コンサル</span>
          <span class="lp-badge">採用・人材</span>
          <span class="lp-badge">農業・食品</span>
        </div>
        <div class="lp-works__result">
          <p class="lp-works__result-item">採用専用サイト制作後、3ヶ月で採用応募<br><strong>月0件 → 5件</strong>（東広島市・建設業・匿名）</p>
        </div>
        <a href="#contact" class="lp-btn" data-cta-label="works_form">実績を聞いてみる</a>
      </div>

    </div>
  </div>
</section>


<!-- ============================================================
     セクション11：お客様の声（Widgets for Google Reviews）
     ============================================================ -->
<section class="lp-section lp-section--light lp-animate">
  <div class="lp-inner">
    <h2 class="lp-h2" style="text-align:center;">お客様の声</h2>
    <p class="lp-lead" style="text-align:center;">
      Googleの口コミから、実際にご依頼いただいた方の感想をご紹介します。
    </p>

    <div class="lp-reviews__widget">
      <?php echo do_shortcode('[trustindex no-registration=google]'); ?>
    </div>

    <div class="lp-reviews__link-wrap">
      <a href="https://www.google.com/search?sca_esv=87c0228ed7e9c332&hl=ja-JP&si=AL3DRZEsmMGCryMMFSHJ3StBhOdZ2-6yYkXd_doETEE1OR-qOVUKgm6erX-PqI7HpX-SxkscvZKny9rzBYu_O_fnBXYtUbBc3-pcyrsC37cjJmMNMdHvxwqxuKEnKahqCWF_vlsfgae0VW6nALGHjVMOAmj3IvHyiw%3D%3D&q=%E3%82%AA%E3%83%95%E3%82%A3%E3%82%B9%E3%82%A6%E3%82%A8%E3%83%80+%E3%82%AF%E3%83%81%E3%82%B3%E3%83%9F&sa=X&ved=2ahUKEwiG9pzszKuTAxWxsVYBHSQSCLUQ0bkNegQIIRAH"
         target="_blank"
         rel="noopener noreferrer"
         class="lp-reviews__link">
        Google口コミをすべて見る ↗
      </a>
    </div>
  </div>
</section>


<!-- ============================================================
     セクション12：制作の流れ
     ============================================================ -->
<section class="lp-section lp-section--white lp-flow lp-animate">
  <div class="lp-inner">
    <h2 class="lp-h2" style="text-align:center;">制作の流れ</h2>
    <p class="lp-lead" style="text-align:center;">相談から公開まで、安心してお任せください。</p>

    <div class="lp-flow__steps">

      <div class="lp-flow__step">
        <div class="lp-flow__num">01</div>
        <h3>無料相談</h3>
        <p>ご希望・課題・予算感をお聞きします。専門用語なし、押し売りなし。</p>
      </div>

      <div class="lp-flow__step">
        <div class="lp-flow__num">02</div>
        <h3>ヒアリング・お見積もり</h3>
        <p>詳細をお伺いし、内容と費用をご提案します。</p>
      </div>

      <div class="lp-flow__step">
        <div class="lp-flow__num">03</div>
        <h3>制作・確認</h3>
        <p>デザイン・コーディングを進めながら、随時確認いただけます。</p>
      </div>

      <div class="lp-flow__step">
        <div class="lp-flow__num">04</div>
        <h3>納品・公開</h3>
        <p>最終確認後、公開します。操作説明もしっかり行います。</p>
      </div>

      <div class="lp-flow__step">
        <div class="lp-flow__num">05</div>
        <h3>運用サポート</h3>
        <p>公開後も更新・改善・相談をサポート。作って終わりにしません。</p>
      </div>

    </div>
  </div>
</section>


<!-- ============================================================
     セクション13：FAQ
     ============================================================ -->
<section class="lp-section lp-section--light lp-animate">
  <div class="lp-inner">
    <h2 class="lp-h2" style="text-align:center;">よくあるご質問</h2>

    <div class="lp-faq__list">

      <details class="lp-faq__item">
        <summary>相談だけでも大丈夫ですか？</summary>
        <div class="lp-faq__answer">
          <p>はい、もちろんです。「まだ検討段階」「予算感だけ知りたい」という段階のご相談も大歓迎です。話を聞いたからといって、申し込みを迫ることは一切しません。安心してご連絡ください。</p>
        </div>
      </details>

      <details class="lp-faq__item">
        <summary>費用はどのくらいかかりますか？</summary>
        <div class="lp-faq__answer">
          <p>サービスや規模・ご要望によって異なります。まずご相談の中で予算感をお伝えします。「金額だけ知りたい」という段階でもお気軽にどうぞ。</p>
        </div>
      </details>

      <details class="lp-faq__item">
        <summary>専門知識がなくても大丈夫ですか？</summary>
        <div class="lp-faq__answer">
          <p>まったく問題ありません。打ち合わせは専門用語を使わず、わかりやすい言葉でご説明します。</p>
        </div>
      </details>

      <details class="lp-faq__item">
        <summary>広島県外からの依頼はできますか？</summary>
        <div class="lp-faq__answer">
          <p>はい、対応しています。遠方の方はオンラインでのお打ち合わせも可能です。</p>
        </div>
      </details>

      <details class="lp-faq__item">
        <summary>制作期間はどれくらいかかりますか？</summary>
        <div class="lp-faq__answer">
          <p>サービスの種類や内容によって異なりますが、コーポレートサイトで1〜2ヶ月程度が目安です。</p>
        </div>
      </details>

      <!-- Q6：新規追加 -->
      <details class="lp-faq__item">
        <summary>LINEで相談できますか？</summary>
        <div class="lp-faq__answer">
          <p>はい、LINEでのご相談も受け付けています。フォームへの記入が面倒な方や、「ちょっと聞いてみたいだけ」という方も、気軽にメッセージを送ってください。同じく上田が直接対応します。</p>
        </div>
      </details>

    </div>
  </div>
</section>


<!-- ============================================================
     セクション14：フォームCTA
     ============================================================ -->
<section id="contact" class="lp-section lp-section--dark lp-form-section lp-animate">
  <div class="lp-inner">
    <h2 class="lp-h2">まず、話してみませんか。</h2>
    <p class="lp-form-lead">
      「うちはホームページで売れるようになるの？」<br>
      そんな疑問から、ぜひ聞かせてください。<br><br>
      専門用語なし・押し売りなし。<br>
      ホームページ制作の相談は無料です。
    </p>

    <!-- LINEが上 -->
    <div class="lp-form-line lp-form-line--top">
      <p class="lp-form-line__heading">フォームが面倒な方はLINEで。</p>
      <a href="https://lin.ee/v7FmZuu" target="_blank" rel="noopener noreferrer"
         class="lp-btn-line lp-btn-line--lg" data-cta-label="contact_line">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="white" aria-hidden="true">
          <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63h2.386c.349 0 .63.285.63.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63.349 0 .631.285.631.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.281.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
        </svg>
        LINEで相談する（無料）
      </a>
      <p class="lp-form-line__micro">LINEを追加するだけ。決めなくていい。上田が直接返信します。</p>
    </div>

    <!-- 区切り -->
    <div class="lp-form-divider">
      <span class="lp-form-divider__text">またはフォームからご相談ください</span>
    </div>

    <!-- フォーム -->
    <div class="lp-form-wrap">
      <p class="lp-form-wrap__micro">まず話だけでも大丈夫です。</p>
      <?php
        echo do_shortcode('[contact-form-7 id="13bab8f" title="LP-260326用"]');
      ?>
      <p class="lp-form__privacy">
        <a href="<?php echo esc_url(home_url('/privacy/')); ?>">個人情報の取り扱いについて</a>に同意の上、送信してください。
      </p>
    </div>

    <!-- P.S. -->
    <p class="lp-form__ps">P.S. 送信いただいた方には、1営業日以内に上田本人からご連絡します。</p>

  </div>
</section>


<!-- ============================================================
     LP専用フッター
     ============================================================ -->
<footer class="lp-footer">
  <div class="lp-footer__links">
    <a href="https://officeueda.com/">オフィスウエダ トップページ</a>
    <a href="<?php echo esc_url(home_url('/privacy/')); ?>">個人情報の取り扱い</a>
  </div>
  <p>&copy; <?php echo wp_date('Y'); ?> オフィスウエダ All Rights Reserved.</p>
</footer>


<!-- ============================================================
     スマホ固定CTA（スクロール後に表示）
     ============================================================ -->
<div class="lp-fixed-cta" id="js-fixed-cta" style="display:none;" aria-hidden="true">
  <div class="lp-fixed-cta__inner">
    <a href="#contact" class="lp-fixed-cta__btn lp-fixed-cta__btn--form" data-cta-label="fixed_form">無料で相談してみる</a>
    <a href="https://lin.ee/v7FmZuu" target="_blank" rel="noopener noreferrer"
       class="lp-fixed-cta__btn lp-fixed-cta__btn--line" data-cta-label="fixed_line">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="white" aria-hidden="true">
        <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63h2.386c.349 0 .63.285.63.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63.349 0 .631.285.631.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.281.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
      </svg>
      LINEで話す
    </a>
  </div>
</div>


<?php wp_footer(); ?>

<script src="<?php echo esc_url($lp_js_uri); ?>/js/main.js" defer></script>
<script src="<?php echo esc_url($lp_js_uri); ?>/js/lazyload-prioritize.js" defer></script>
</body>
</html>
