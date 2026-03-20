;(function () {

  function prioritizeLazy(lazyImgs) {
    if (lazyImgs.length === 0) return

    // ビューポートからの距離でソート（近い順）
    var sorted = Array.from(lazyImgs).map(function (img) {
      return { img: img, dist: Math.abs(img.getBoundingClientRect().top) }
    })
    sorted.sort(function (a, b) { return a.dist - b.dist })

    // 全lazy画像を100ms刻みで順番にロード
    sorted.forEach(function (item, i) {
      setTimeout(function () {
        item.img.removeAttribute('loading')
        item.img.src = item.img.src
      }, 100 * i)
    })
  }

  function init() {
    var lazyImgs = document.querySelectorAll("img[loading='lazy']")
    var eagerImgs = document.querySelectorAll("img:not([loading='lazy'])")
    var count = eagerImgs.length
    var loaded = 0

    function check() {
      if (++loaded >= count) prioritizeLazy(lazyImgs)
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
