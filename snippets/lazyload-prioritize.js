/* ============================================================
   Lazy画像 優先読み込みスニペット
   ============================================================
   使い方：
   1. このファイルをページのJSとして読み込む（defer推奨）
      <script src="lazyload-prioritize.js" defer></script>

   2. 特別な設定は不要。loading="lazy" の画像を自動検出する

   動作：
   - loading="lazy" のない画像（FVなど）が全部読み込まれた後、
     lazy画像を「ビューポートに近い順」に並べ直して
     チェーン方式で100ms刻みにロードする（帯域を分散しつつ先読み）

   ※ img.src = img.src の no-op 可否はブラウザ依存。
     デプロイ後に Chrome / Safari / Firefox で動作確認すること。
   ============================================================ */

;(function () {

  function prioritizeLazy(lazyImgs) {
    if (lazyImgs.length === 0) return

    // ビューポートからの距離でソート（近い順）
    var sorted = Array.from(lazyImgs).map(function (img) {
      return { img: img, dist: Math.abs(img.getBoundingClientRect().top) }
    })
    sorted.sort(function (a, b) { return a.dist - b.dist })

    // チェーン方式で100ms刻みにロード（タイマーは常に1つ）
    var index = 0
    function loadNext() {
      if (index >= sorted.length) return
      var img = sorted[index].img
      img.removeAttribute('loading')
      img.src = img.src
      index++
      setTimeout(loadNext, 100)
    }
    loadNext()
  }

  function init() {
    var lazyImgs = document.querySelectorAll("img[loading='lazy']")
    var eagerImgs = document.querySelectorAll("img:not([loading='lazy'])")
    var count = eagerImgs.length
    var loaded = 0
    var fired = false

    function check() {
      if (fired) return
      if (++loaded >= count) {
        fired = true
        prioritizeLazy(lazyImgs)
      }
    }

    if (count === 0) { prioritizeLazy(lazyImgs); return }

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
