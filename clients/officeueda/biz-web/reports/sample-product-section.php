<?php
/**
 * 商品紹介セクション サンプルコード
 * ─────────────────────────────────────────
 * 用途: WordPress テーマのカスタムページテンプレートや
 *       固定ページのカスタムHTMLブロックに貼り付けて使う。
 *
 * GMC対応: JSON-LD（Product schema）を必須フィールドで実装。
 *          Google Search Console / GMC のフィード自動検出に対応。
 *
 * 使い方:
 *   1. $products 配列に実際の商品情報を入れる
 *   2. 画像は WordPress メディアライブラリのURLに差し替える
 *   3. ページに貼り付けるだけでJSON-LDが出力される
 */

// ─────────────────────────────────────────
// 商品データ定義（実際の商品情報に差し替える）
// ─────────────────────────────────────────
$products = [
  [
    'id'          => 'SKU-001',                          // SKU（GMC必須）
    'name'        => '島根県産 無農薬コシヒカリ 5kg',
    'description' => '農薬・化学肥料不使用で育てた島根県産のコシヒカリ。もっちりとした食感と甘みが特徴です。',
    'price'       => '3500',                             // 税込価格（GMC必須）
    'currency'    => 'JPY',
    'availability'=> 'InStock',                          // InStock / OutOfStock / PreOrder
    'image'       => 'https://example.com/wp-content/uploads/koshihikari-5kg.webp',
    'url'         => 'https://example.com/products/koshihikari-5kg/',
    'brand'       => '岩本商店 神の里',
    'category'    => '食品 > 米・雑穀',                  // GMC商品カテゴリ
    'gtin'        => '',                                  // JANコードがあれば入れる（任意・あると審査通りやすい）
  ],
  [
    'id'          => 'SKU-002',
    'name'        => '島根県産 無農薬コシヒカリ 10kg',
    'description' => '農薬・化学肥料不使用で育てた島根県産のコシヒカリ。まとめ買いでお得な10kgサイズ。',
    'price'       => '6500',
    'currency'    => 'JPY',
    'availability'=> 'InStock',
    'image'       => 'https://example.com/wp-content/uploads/koshihikari-10kg.webp',
    'url'         => 'https://example.com/products/koshihikari-10kg/',
    'brand'       => '岩本商店 神の里',
    'category'    => '食品 > 米・雑穀',
    'gtin'        => '',
  ],
  [
    'id'          => 'SKU-003',
    'name'        => '国産 しめ縄 中サイズ',
    'description' => '島根県産の稲わらを使った手作りしめ縄。お正月の玄関飾りに。',
    'price'       => '2800',
    'currency'    => 'JPY',
    'availability'=> 'InStock',
    'image'       => 'https://example.com/wp-content/uploads/shimenawa-m.webp',
    'url'         => 'https://example.com/products/shimenawa-m/',
    'brand'       => '岩本商店 神の里',
    'category'    => '家庭用品 > 季節・行事用品',
    'gtin'        => '',
  ],
];

// ─────────────────────────────────────────
// JSON-LD 出力（GMC対応・Product schema）
// ─────────────────────────────────────────
// ※ ページ内に複数商品がある場合は @graph で配列にまとめる
?>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@graph": [
    <?php foreach ($products as $i => $p) : ?>
    {
      "@type": "Product",
      "@id": "<?php echo esc_url($p['url']); ?>#product",
      "name": "<?php echo esc_js($p['name']); ?>",
      "description": "<?php echo esc_js($p['description']); ?>",
      "image": "<?php echo esc_url($p['image']); ?>",
      "sku": "<?php echo esc_js($p['id']); ?>",
      "brand": {
        "@type": "Brand",
        "name": "<?php echo esc_js($p['brand']); ?>"
      },
      "category": "<?php echo esc_js($p['category']); ?>",
      <?php if (!empty($p['gtin'])) : ?>
      "gtin13": "<?php echo esc_js($p['gtin']); ?>",
      <?php endif; ?>
      "offers": {
        "@type": "Offer",
        "url": "<?php echo esc_url($p['url']); ?>",
        "priceCurrency": "<?php echo esc_js($p['currency']); ?>",
        "price": "<?php echo esc_js($p['price']); ?>",
        "availability": "https://schema.org/<?php echo esc_js($p['availability']); ?>",
        "itemCondition": "https://schema.org/NewCondition",
        "priceValidUntil": "<?php echo date('Y-12-31'); ?>"
      }
    }<?php echo ($i < count($products) - 1) ? ',' : ''; ?>

    <?php endforeach; ?>
  ]
}
</script>


<!-- ─────────────────────────────────────────
     商品紹介セクション HTML
     ─────────────────────────────────────────
     CSSクラスは既存テーマに合わせて変更してください。
     以下は officeueda LP の style.css に合わせたクラス名です。
     ───────────────────────────────────────── -->
<section class="lp-section lp-section--white" id="products">
  <div class="lp-inner">

    <h2 class="lp-h2" style="text-align:center;">商品紹介</h2>
    <p class="lp-lead" style="text-align:center;">
      島根県産の自然の恵みをお届けします。
    </p>

    <div class="lp-grid-3">

      <?php foreach ($products as $p) : ?>
      <div class="lp-card" style="text-align:center;">

        <!-- 商品画像 -->
        <a href="<?php echo esc_url($p['url']); ?>">
          <img
            src="<?php echo esc_url($p['image']); ?>"
            alt="<?php echo esc_attr($p['name']); ?>"
            style="width:100%;border-radius:8px;margin-bottom:16px;"
            loading="lazy"
          >
        </a>

        <!-- 商品名 -->
        <h3 class="lp-h3">
          <a href="<?php echo esc_url($p['url']); ?>" style="text-decoration:none;color:inherit;">
            <?php echo esc_html($p['name']); ?>
          </a>
        </h3>

        <!-- 説明文 -->
        <p style="font-size:14px;color:#555;margin-top:8px;">
          <?php echo esc_html($p['description']); ?>
        </p>

        <!-- 価格 -->
        <div style="margin-top:14px;">
          <span style="font-size:22px;font-weight:900;color:#0068b7;">
            ¥<?php echo number_format((int)$p['price']); ?>
          </span>
          <span style="font-size:13px;color:#777;">（税込）</span>
        </div>

        <!-- 在庫状況 -->
        <?php if ($p['availability'] === 'InStock') : ?>
          <div style="font-size:13px;color:#2a9d2a;margin-top:6px;font-weight:bold;">● 在庫あり</div>
        <?php elseif ($p['availability'] === 'OutOfStock') : ?>
          <div style="font-size:13px;color:#cc0000;margin-top:6px;font-weight:bold;">× 在庫なし</div>
        <?php else : ?>
          <div style="font-size:13px;color:#e07b00;margin-top:6px;font-weight:bold;">△ 予約受付中</div>
        <?php endif; ?>

        <!-- 購入ボタン -->
        <div style="margin-top:18px;">
          <a href="<?php echo esc_url($p['url']); ?>" class="lp-btn" style="font-size:15px;padding:12px 28px;">
            詳細・購入はこちら
          </a>
        </div>

      </div>
      <?php endforeach; ?>

    </div><!-- /.lp-grid-3 -->

  </div><!-- /.lp-inner -->
</section>
