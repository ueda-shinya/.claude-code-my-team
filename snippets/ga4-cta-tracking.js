/* ============================================================
   GA4 CTAクリック計測スニペット
   ============================================================
   使い方：
   1. このコードをページのJSに組み込む（または別ファイルで読み込む）
   2. 計測したいボタン・リンクに data-cta-label="任意の名前" を付ける

   例：
     <a href="#contact" data-cta-label="fv">相談する</a>
     <a href="https://lin.ee/xxx" data-cta-label="line">LINEで相談</a>

   GA4での確認：
   - イベント名：cta_click
   - パラメータ：label（ボタン識別子）、page（pathname）
   ============================================================ */

document.querySelectorAll('[data-cta-label]').forEach(function (el) {
  el.addEventListener('click', function () {
    if (typeof gtag !== 'function') return
    gtag('event', 'cta_click', {
      label: el.getAttribute('data-cta-label'),
      page: window.location.pathname
    })
  })
})
