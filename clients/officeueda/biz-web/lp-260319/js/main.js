/* ============================================================
   officeueda LP — main.js
   ============================================================ */

(function () {
  'use strict'

  /* ── スムーススクロール ── */
  document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
    anchor.addEventListener('click', function (e) {
      var target = document.querySelector(this.getAttribute('href'))
      if (!target) return
      e.preventDefault()
      var offset = 24
      var top = target.getBoundingClientRect().top + window.pageYOffset - offset
      window.scrollTo({ top: top, behavior: 'smooth' })
    })
  })

  /* ── スクロール後に固定CTAを表示 ── */
  var fixedCta = document.querySelector('.lp-fixed-cta')
  if (fixedCta) {
    var threshold = 400
    var onScroll = function () {
      if (window.scrollY > threshold) {
        fixedCta.style.display = 'block'
      } else {
        fixedCta.style.display = 'none'
      }
    }
    window.addEventListener('scroll', onScroll, { passive: true })
  }

})()
