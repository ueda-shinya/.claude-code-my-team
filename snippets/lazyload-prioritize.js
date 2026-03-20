/* ============================================================
   Lazy画像 先読みスニペット
   ============================================================
   使い方：
   1. このファイルをページのJSとして読み込む（defer推奨）
      <script src="lazyload-prioritize.js" defer></script>

   2. 特別な設定は不要。loading="lazy" の画像を自動検出する

   動作：
   - loading="lazy" のない画像（FVなど）が全部読み込まれた後、
     ビューポートから PRELOAD_MARGIN px 以内の lazy 画像を先読みする
   - 遠くにある画像はブラウザのネイティブ lazy loading に任せる

   カスタマイズ：
   - PRELOAD_MARGIN の値を変更するとプリロード範囲を調整できる
   ============================================================ */

;(function () {
  // ビューポートからこの距離内の lazy 画像を先読みする（px）
  var PRELOAD_MARGIN = 1000

  function loadImage(img) {
    var src = img.currentSrc || img.src
    if (!src) return
    img.removeAttribute('loading')
    img.src = ''
    img.src = src
  }

  function prioritizeLazy() {
    var vh = window.innerHeight
    document.querySelectorAll("img[loading='lazy']").forEach(function (img) {
      var top = img.getBoundingClientRect().top
      if (top < vh + PRELOAD_MARGIN) loadImage(img)
    })
  }

  function init() {
    var eagerImgs = document.querySelectorAll("img:not([loading='lazy'])")
    var count = eagerImgs.length
    var loaded = 0

    function check() {
      if (++loaded >= count) prioritizeLazy()
    }

    if (count === 0) { prioritizeLazy(); return }

    eagerImgs.forEach(function (img) {
      if (img.complete) { check() } else {
        img.addEventListener('load', check)
        img.addEventListener('error', check)
      }
    })
  }

  document.readyState === 'loading'
    ? document.addEventListener('DOMContentLoaded', init)
    : init()
})()
