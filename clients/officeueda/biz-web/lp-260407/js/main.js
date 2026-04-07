/* ============================================================
   officeueda LP 2026-04-07 — main.js
   ============================================================ */

(function () {
  'use strict'

  /* ── スムーススクロール ── */
  document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
    anchor.addEventListener('click', function (e) {
      var href = this.getAttribute('href')
      if (!href || !/^#[\w-]+$/.test(href)) return
      var target = document.getElementById(href.substring(1))
      if (!target) return
      e.preventDefault()
      var offset = 24
      var top = target.getBoundingClientRect().top + window.pageYOffset - offset
      window.scrollTo({ top: top, behavior: 'smooth' })
    })
  })

  /* ── GA4 CTAクリック計測 ── */
  document.querySelectorAll('[data-cta-label]').forEach(function (el) {
    el.addEventListener('click', function () {
      if (typeof gtag !== 'function') return
      gtag('event', 'cta_click', {
        label: el.getAttribute('data-cta-label'),
        page: window.location.pathname
      })
    })
  })

  /*
   * スマホ固定CTAは常時表示（CSS側で制御済み）
   * 既存lp-260319のスクロール制御は本LPでは不要のため削除
   */

})()
