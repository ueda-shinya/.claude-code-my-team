<?php
/**
 * Template Name: LP 2026-03-19
 * Description: officeueda.com/lp/ ホームページ制作ランディングページ
 *
 * 配置先: wp-content/themes/{テーマ名}/lp-260319/index.php
 * アセット: 同ディレクトリの style.css / js/main.js / images/
 *
 * 依存プラグイン:
 *   - Contact Form 7 (フォーム)
 *   - Widgets for Google Reviews (口コミウィジェット)
 */

// アセットのベースURI（テーマルートからの相対パス）
$lp_uri = get_stylesheet_directory_uri() . '/lp-260319';

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
<meta property="og:title"       content="ホームページで、ちゃんと売れるようにする。| オフィスウエダ">
<meta property="og:description" content="東広島・呉・広島で、問い合わせが来るホームページ制作。専門用語なし・丸投げOK。まず無料でご相談ください。">
<meta property="og:image"       content="<?php echo esc_url($lp_uri); ?>/images/ogp.webp">
<meta property="og:image:width"  content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale"      content="ja_JP">

<meta name="twitter:card"        content="summary_large_image">
<meta name="twitter:title"       content="ホームページで、ちゃんと売れるようにする。| オフィスウエダ">
<meta name="twitter:description" content="東広島・呉・広島で、問い合わせが来るホームページ制作。専門用語なし・丸投げOK。">
<meta name="twitter:image"       content="<?php echo esc_url($lp_uri); ?>/images/ogp.webp">

<!-- ============================================================
     構造化データ（JSON-LD）
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
      "telephone": "YOUR_PHONE_NUMBER",
      "email": "YOUR_EMAIL_ADDRESS",
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
      "name": "広島 ホームページ制作 東広島・呉・広島 | オフィスウエダ",
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
            "text": "はい、もちろんです。「まだ検討段階」「予算感だけ知りたい」という段階のご相談も大歓迎です。お気軽にお問い合わせください。"
          }
        },
        {
          "@type": "Question",
          "name": "専門知識がなくても大丈夫ですか？",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "まったく問題ありません。打ち合わせは専門用語を使わず、わかりやすい言葉でご説明します。「ホームページのことは何もわからない」という方からのご相談も多いです。"
          }
        },
        {
          "@type": "Question",
          "name": "広島県外からの依頼はできますか？",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "はい、対応しています。遠方の方はオンラインでのお打ち合わせも可能ですので、お気軽にご相談ください。"
          }
        },
        {
          "@type": "Question",
          "name": "制作期間はどれくらいかかりますか？",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "サービスの種類や内容によって異なりますが、コーポレートサイトで1〜2ヶ月程度が目安です。詳しくはご相談の際にお伝えします。"
          }
        }
      ]
    }
  ]
}
</script>

<?php wp_head(); ?>

<link rel="stylesheet" href="<?php echo esc_url($lp_uri); ?>/style.css">
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
        ホームページで、<br>ちゃんと売れるようにする。
      </h1>
      <p class="lp-fv__sub">
        "あ、ちょっと来てよ"で呼べる、<br>
        東広島・呉・広島のWeb担当者です。
      </p>
      <a href="#contact" class="lp-btn">無料でホームページ相談してみる</a>
    </div>
    <div class="lp-fv__photo">
      <img
        src="<?php echo esc_url($lp_uri); ?>/images/shinyaueda.png"
        alt="オフィスウエダ 代表 上田伸也"
        width="300" height="300"
        loading="eager"
      >
    </div>
  </div>
</section>


<!-- ============================================================
     セクション2：FV直下CTA
     ============================================================ -->
<section class="lp-section lp-section--dark lp-cta-top">
  <div class="lp-inner" style="text-align:center;">
    <p>
      専門用語なし・丸投げOK。<br>
      「何から始めればいいかわからない」という段階でも、一緒に整理します。<br>
      相談は無料です。
    </p>
    <a href="#contact" class="lp-btn lp-btn--white">まず話だけでも聞いてみる</a>
  </div>
</section>


<!-- ============================================================
     セクション3：数字バー
     ============================================================ -->
<section class="lp-numbers lp-section--white">
  <div class="lp-inner">
    <div class="lp-numbers__grid">

      <div class="lp-number-item">
        <div class="lp-number-item__num">2022</div>
        <div class="lp-number-item__unit">年〜</div>
        <div class="lp-number-item__label">サービス開始</div>
      </div>

      <div class="lp-number-item">
        <div class="lp-number-item__num">3</div>
        <div class="lp-number-item__unit">市</div>
        <div class="lp-number-item__label">対応エリア（東広島・呉・広島）</div>
      </div>

      <div class="lp-number-item">
        <div class="lp-number-item__num">7</div>
        <div class="lp-number-item__unit">種類</div>
        <div class="lp-number-item__label">対応サービス数</div>
      </div>

    </div>
  </div>
</section>


<!-- ============================================================
     セクション4：課題提起
     ============================================================ -->
<section class="lp-section lp-section--light">
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
<section class="lp-section lp-section--white lp-reasons">
  <div class="lp-inner">
    <h2 class="lp-h2" style="text-align:center;">オフィスウエダが選ばれる理由</h2>
    <div class="lp-grid-3">

      <div class="lp-card">
        <img
          src="<?php echo esc_url($lp_uri); ?>/images/icon-local.webp"
          alt="地域密着アイコン"
          class="lp-card__icon"
          width="64" height="64"
          loading="lazy"
        >
        <h3 class="lp-h3">顔が見える、話せる。<br>地域密着のWeb担当者。</h3>
        <p>東広島を拠点に、呉・広島の事業者様とも対面でお会いして進めます。"あ、ちょっと相談して"と気軽に連絡できる距離感が、長く続くお付き合いにつながっています。</p>
      </div>

      <div class="lp-card">
        <img
          src="<?php echo esc_url($lp_uri); ?>/images/icon-easy.webp"
          alt="わかりやすさアイコン"
          class="lp-card__icon"
          width="64" height="64"
          loading="lazy"
        >
        <h3 class="lp-h3">専門用語なし。<br>丸投げでOKです。</h3>
        <p>難しいIT用語はいりません。打ち合わせから原稿作成・更新作業まで、面倒なことはすべて代行します。「何もわからない」という状態からでも、安心してお任せください。</p>
      </div>

      <div class="lp-card">
        <img
          src="<?php echo esc_url($lp_uri); ?>/images/icon-result.webp"
          alt="成果重視アイコン"
          class="lp-card__icon"
          width="64" height="64"
          loading="lazy"
        >
        <h3 class="lp-h3">"作る"ではなく<br>"売れる"を目指して設計します。</h3>
        <p>見た目だけきれいなサイトでは集客できません。問い合わせが来る・応募が増えるという成果を前提に、マーケティング視点で設計・実装・改善まで伴走します。</p>
      </div>

    </div>
  </div>
</section>


<!-- ============================================================
     セクション6：サービス一覧
     ============================================================ -->
<section class="lp-section lp-section--light lp-services">
  <div class="lp-inner">
    <h2 class="lp-h2" style="text-align:center;">ホームページ制作・Web支援サービス</h2>
    <p class="lp-lead" style="text-align:center;">
      制作から補助金相談・運用サポートまで。必要なことは、ぜんぶ対応します。
    </p>

    <div class="lp-grid-3">

      <div class="lp-card">
        <img src="<?php echo esc_url($lp_uri); ?>/images/svc-corporate.webp"
             alt="コーポレートサイト・店舗サイト制作" class="lp-card__icon"
             width="60" height="60" loading="lazy">
        <h3 class="lp-h3">コーポレートサイト・<br>店舗サイト制作</h3>
        <p>会社・お店の顔を、問い合わせが来るデザインに。オリジナルデザイン＋SEO対策込み。中小企業・個人事業主の規模感に合わせた複数プランをご用意しています。</p>
      </div>

      <div class="lp-card">
        <img src="<?php echo esc_url($lp_uri); ?>/images/svc-recruit.webp"
             alt="採用サイト制作" class="lp-card__icon"
             width="60" height="60" loading="lazy">
        <h3 class="lp-h3">採用サイト制作</h3>
        <p>採りたい人に届く、採用専用サイト。求人に特化したサイト構成で、応募数の改善を目指します。コストを抑えたい方向けのminiプランもあります。</p>
      </div>

      <div class="lp-card">
        <img src="<?php echo esc_url($lp_uri); ?>/images/svc-lp.webp"
             alt="LP制作" class="lp-card__icon"
             width="60" height="60" loading="lazy">
        <h3 class="lp-h3">LP制作</h3>
        <p>問い合わせを取りに行く、縦スクロールLP。コンバージョン設計に特化したLPを制作。広告と組み合わせた費用対効果の高い集客を実現します。</p>
      </div>

      <div class="lp-card">
        <img src="<?php echo esc_url($lp_uri); ?>/images/svc-swipe.webp"
             alt="スワイプLP制作" class="lp-card__icon"
             width="60" height="60" loading="lazy">
        <h3 class="lp-h3">スワイプLP制作</h3>
        <p>スマホ世代に届く、スワイプ型LP。横スワイプ操作に最適化したLP。Instagram広告などスマホファーストな集客施策との相性が抜群です。</p>
      </div>

      <div class="lp-card">
        <img src="<?php echo esc_url($lp_uri); ?>/images/svc-renewal.webp"
             alt="集客強化リニューアル" class="lp-card__icon"
             width="60" height="60" loading="lazy">
        <h3 class="lp-h3">集客強化リニューアル</h3>
        <p>今あるサイトを、問い合わせが増える形に改修。「作り直すほどではないが成果が出ていない」サイトを、マーケティング視点で全面改修します。</p>
      </div>

      <div class="lp-card">
        <img src="<?php echo esc_url($lp_uri); ?>/images/svc-maintenance.webp"
             alt="WordPress保守・運用サポート" class="lp-card__icon"
             width="60" height="60" loading="lazy">
        <h3 class="lp-h3">WordPress保守・<br>運用サポート</h3>
        <p>作ったあとも、ずっと任せられる。更新・バックアップ・セキュリティ対応・Zoom支援まで継続サポート。「作って終わり」にしない伴走体制です。</p>
      </div>

    </div>

    <!-- 緊急対応（7枚目・横並び） -->
    <div class="lp-service-emergency lp-card" style="margin-top:24px;">
      <img src="<?php echo esc_url($lp_uri); ?>/images/svc-emergency.webp"
           alt="WP復旧24（緊急対応）" class="lp-card__icon"
           width="60" height="60" loading="lazy">
      <div>
        <h3 class="lp-h3">WP復旧24（緊急対応）</h3>
        <p>WordPressが突然動かなくなったら、すぐ呼んでください。表示崩れ・ログインできない・真っ白になったなど、WordPressの緊急トラブルに即対応します。</p>
      </div>
    </div>

  </div>
</section>


<!-- ============================================================
     セクション7：サービス後CTA
     ============================================================ -->
<section class="lp-section lp-section--dark lp-cta-mid">
  <div class="lp-inner">
    <h2 class="lp-h2">どのサービスか迷ったら、<br>まず相談してください。</h2>
    <p>
      「うちに合うのはどれ？」「予算感を知りたい」だけでも大丈夫です。<br>
      専門用語なし・押し売りなしでお答えします。
    </p>
    <a href="#contact" class="lp-btn lp-btn--white">無料で相談してみる</a>
  </div>
</section>


<!-- ============================================================
     セクション8：制作実績
     ============================================================ -->
<section class="lp-section lp-section--white">
  <div class="lp-inner">
    <h2 class="lp-h2" style="text-align:center;">制作実績</h2>
    <p class="lp-lead" style="text-align:center;">
      公開許可をいただいた一部をご紹介しています。非公開案件を含め、多くのお客様のサイト制作に携わってきました。
    </p>

    <div class="lp-grid-2">

      <!-- 公開案件 -->
      <div class="lp-card lp-works__card">
        <!--
          ↓ 岩本商店様サイトのスクリーンショットをWordPressメディアライブラリにアップロードし、
            URLを差し替えてください。
            サイズ推奨: 800×500px
        -->
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
        <p class="lp-works__role">担当：コーディング（デザイナーと協業）</p>
        <p>島根県産の無農薬米・しめ縄を販売する岩本商店様のサイト。レスポンシブ対応・Instagram連携など、フルコーディングを担当しました。</p>
        <a href="https://iwamotoshoten.com/" target="_blank" rel="noopener noreferrer" class="lp-btn">
          サイトを見る ↗
        </a>
      </div>

      <!-- 非公開案件まとめ -->
      <div class="lp-works__empty">
        <div class="lp-works__empty-icon" aria-hidden="true">🔒</div>
        <h3>非公開案件 多数</h3>
        <p>その他、非公開案件を含め多数の制作実績があります。業種・規模・予算などの詳細はお問い合わせください。</p>
      </div>

    </div>
  </div>
</section>


<!-- ============================================================
     セクション9：お客様の声（Widgets for Google Reviews）
     ============================================================ -->
<section class="lp-section lp-section--light">
  <div class="lp-inner">
    <h2 class="lp-h2" style="text-align:center;">お客様の声</h2>
    <p class="lp-lead" style="text-align:center;">
      Googleの口コミから、実際にご依頼いただいた方の感想をご紹介します。
    </p>

    <div class="lp-reviews__widget">
      <?php
        /*
         * Widgets for Google Reviews のショートコードをここに入れてください。
         * 例: [gr-reviews-widget id="1"]
         * または Googleマイビジネスを接続後、プラグインで生成されたショートコードを使用。
         */
        echo do_shortcode('[gr-reviews-widget id="YOUR_WIDGET_ID"]');
      ?>
    </div>

    <div class="lp-reviews__link-wrap">
      <!--
        GoogleマイビジネスのURLを差し替えてください。
        例: https://g.page/r/XXXXXXXXXXXXXXXX/review
      -->
      <a href="https://g.page/r/YOUR_GOOGLE_BUSINESS_URL/review"
         target="_blank"
         rel="noopener noreferrer"
         class="lp-reviews__link">
        Google口コミをすべて見る ↗
      </a>
    </div>
  </div>
</section>


<!-- ============================================================
     セクション10：代表プロフィール
     ============================================================ -->
<section class="lp-section lp-section--white">
  <div class="lp-inner">
    <div class="lp-grid-2 lp-grid-2--center">

      <div class="lp-profile__photo">
        <img
          src="<?php echo esc_url($lp_uri); ?>/images/shinyaueda.png"
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
        <p>「ホームページを作ったのに問い合わせが来ない」「業者に頼んだら話が通じなかった」——そんな声をたくさん聞いてきました。</p>
        <p>私はお客様にとって、"あ、ちょっと来てよ"と気軽に呼んでもらえるWeb担当者でありたいと思っています。専門用語なし、丸投げOK。打ち合わせから納品・運用まで、成果にこだわって一緒に進めます。</p>
      </div>

    </div>
  </div>
</section>


<!-- ============================================================
     セクション11：FAQ
     ============================================================ -->
<section class="lp-section lp-section--light">
  <div class="lp-inner">
    <h2 class="lp-h2" style="text-align:center;">よくあるご質問</h2>

    <div class="lp-faq__list">

      <details class="lp-faq__item">
        <summary>相談だけでも大丈夫ですか？</summary>
        <div class="lp-faq__answer">
          <p>はい、もちろんです。「まだ検討段階」「予算感だけ知りたい」という段階のご相談も大歓迎です。お気軽にお問い合わせください。</p>
        </div>
      </details>

      <details class="lp-faq__item">
        <summary>専門知識がなくても大丈夫ですか？</summary>
        <div class="lp-faq__answer">
          <p>まったく問題ありません。打ち合わせは専門用語を使わず、わかりやすい言葉でご説明します。「ホームページのことは何もわからない」という方からのご相談も多いです。</p>
        </div>
      </details>

      <details class="lp-faq__item">
        <summary>広島県外からの依頼はできますか？</summary>
        <div class="lp-faq__answer">
          <p>はい、対応しています。遠方の方はオンラインでのお打ち合わせも可能ですので、お気軽にご相談ください。</p>
        </div>
      </details>

      <details class="lp-faq__item">
        <summary>制作期間はどれくらいかかりますか？</summary>
        <div class="lp-faq__answer">
          <p>サービスの種類や内容によって異なりますが、コーポレートサイトで1〜2ヶ月程度が目安です。詳しくはご相談の際にお伝えします。</p>
        </div>
      </details>

    </div>
  </div>
</section>


<!-- ============================================================
     セクション13：フッター前CTA（ContactForm7）
     ============================================================ -->
<section id="contact" class="lp-section lp-section--dark lp-form-section">
  <div class="lp-inner">
    <h2>まず、話してみませんか。</h2>
    <p class="lp-form-lead">
      「うちはホームページで売れるようになるの？」<br>
      そんな疑問から、ぜひ聞かせてください。<br><br>
      専門用語なし・押し売りなし。<br>
      ホームページ制作の相談は無料です。
    </p>

    <div class="lp-form-wrap">
      <?php
        /*
         * Contact Form 7 のフォームIDを差し替えてください。
         * WordPress管理画面 > お問い合わせ > フォーム一覧 でIDを確認できます。
         * 例: [contact-form-7 id="123" title="LP相談フォーム"]
         *
         * 推奨フォーム項目（CF7で作成）:
         *   お名前（必須）     [text* your-name placeholder "例：山田 太郎"]
         *   会社名・屋号       [text company placeholder "例：株式会社〇〇"]
         *   メール（必須）     [email* your-email placeholder "例：info@example.com"]
         *   電話番号           [tel tel-number placeholder "例：090-0000-0000"]
         *   ご相談内容（必須） [textarea* your-message placeholder "例：コーポレートサイトの制作を検討しています..."]
         *   送信ボタン         [submit "相談を申し込む（無料）"]
         */
        echo do_shortcode('[contact-form-7 id="YOUR_FORM_ID" title="LP相談フォーム"]');
      ?>

      <p class="lp-form__privacy">
        <a href="/privacy-policy/">個人情報の取り扱いについて</a>に同意の上、送信してください。
      </p>
    </div>
  </div>
</section>


<!-- ============================================================
     LP専用フッター
     ============================================================ -->
<footer class="lp-footer">
  <div class="lp-footer__links">
    <a href="https://officeueda.com/">オフィスウエダ トップページ</a>
    <a href="/privacy-policy/">個人情報の取り扱い</a>
  </div>
  <p>&copy; <?php echo date('Y'); ?> オフィスウエダ All Rights Reserved.</p>
</footer>


<!-- ============================================================
     スマホ固定CTA（スクロール後に表示）
     ============================================================ -->
<div class="lp-fixed-cta" style="display:none;">
  <a href="#contact">無料でホームページ相談してみる</a>
</div>


<?php wp_footer(); ?>

<script src="<?php echo esc_url($lp_uri); ?>/js/main.js" defer></script>
</body>
</html>
