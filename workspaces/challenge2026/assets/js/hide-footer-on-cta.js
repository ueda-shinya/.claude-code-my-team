// hide-footer-on-triggers-animated-robust.js
(() => {
  const HIDDEN_CLASS = "is-hidden";
  const footerEls = Array.from(document.querySelectorAll(".footer_a"));
  const TRIGGER_SELECTORS = [".cta_a", ".header-wrapper", "#contact-form"];

  // 取得 & 重複排除
  const triggerEls = Array.from(
    new Set(
      TRIGGER_SELECTORS.flatMap((sel) =>
        Array.from(document.querySelectorAll(sel)),
      ),
    ),
  );

  if (footerEls.length === 0 || triggerEls.length === 0) return;

  const setFooterHidden = (hidden) => {
    requestAnimationFrame(() => {
      footerEls.forEach((el) => {
        el.classList.toggle(HIDDEN_CLASS, hidden);
        if ("inert" in HTMLElement.prototype) el.inert = hidden;
        el.setAttribute("aria-hidden", hidden ? "true" : "false");
      });
    });
  };

  // ---- 可視状態を要素ごとに持つ（カウントの取り違え防止）----
  const visibilityMap = new Map(triggerEls.map((el) => [el, false]));
  const recomputeFooter = () => {
    const anyVisible = Array.from(visibilityMap.values()).some(Boolean);
    setFooterHidden(anyVisible);
  };

  const isVisibleInViewportNow = (el) => {
    const r = el.getBoundingClientRect();
    const vh = window.innerHeight || document.documentElement.clientHeight;
    const vw = window.innerWidth || document.documentElement.clientWidth;
    return r.bottom > 0 && r.right > 0 && r.top < vh && r.left < vw;
  };

  // ---- 強制再判定（初期 & レイアウト変化直後の取りこぼし対策）----
  const refreshAllNow = () => {
    triggerEls.forEach((el) =>
      visibilityMap.set(el, isVisibleInViewportNow(el)),
    );
    recomputeFooter();
  };

  // ---- IntersectionObserver（閾値0も含めて“1pxでも見えたら”拾う）----
  if ("IntersectionObserver" in window) {
    const io = new IntersectionObserver(
      (entries) => {
        let touched = false;
        for (const entry of entries) {
          const isOn = entry.isIntersecting && entry.intersectionRatio > 0;
          // const isOn = entry.isIntersecting;
          if (visibilityMap.get(entry.target) !== isOn) {
            visibilityMap.set(entry.target, isOn);
            touched = true;
          }
        }
        if (touched) recomputeFooter();
      },
      {
        root: null,
        rootMargin: "0px",
        threshold: [0, 0.001, 0.05], // 0を含め“見え始め”を確実に拾う
      },
    );
    triggerEls.forEach((el) => io.observe(el));
  }

  // ---- 初期・遷移直後の取りこぼしを埋めるフック群 ----
  // DOMが落ち着くのを待って複数回チェック（1フレ内→遅延0→遅延100ms）
  const stagedRefresh = () => {
    requestAnimationFrame(() => {
      refreshAllNow();
      setTimeout(refreshAllNow, 0);
      setTimeout(refreshAllNow, 100);
    });
  };

  // ページライフサイクル
  if (document.readyState === "complete") {
    stagedRefresh();
  } else {
    window.addEventListener("DOMContentLoaded", stagedRefresh, { once: true });
    window.addEventListener("load", stagedRefresh, { once: true });
  }
  window.addEventListener("pageshow", stagedRefresh);
  document.addEventListener("visibilitychange", () => {
    if (!document.hidden) stagedRefresh();
  });

  // レイアウト変化（リサイズ・回転・スクロール）
  ["resize", "orientationchange"].forEach((ev) =>
    window.addEventListener(ev, stagedRefresh, { passive: true }),
  );
  // スクロール中はrAF節約版
  let ticking = false;
  window.addEventListener(
    "scroll",
    () => {
      if (!ticking) {
        ticking = true;
        requestAnimationFrame(() => {
          ticking = false;
          refreshAllNow();
        });
      }
    },
    { passive: true },
  );

  // header-wrapper のCSSトランジション/アニメーション終了時にも再判定
  triggerEls
    .filter((el) => el.matches(".header-wrapper"))
    .forEach((el) => {
      el.addEventListener("transitionend", stagedRefresh);
      el.addEventListener("animationend", stagedRefresh);
    });

  // SPA等で class/style/属性が変わって表示状態が切り替わるケースに対応
  const mo = new MutationObserver((muts) => {
    let need = false;
    for (const m of muts) {
      if (
        triggerEls.includes(m.target) ||
        (m.target.nodeType === 1 &&
          (m.target.matches?.(".header-wrapper, .cta_a, #contact-form") ||
            m.target.closest?.(".header-wrapper, .cta_a, #contact-form")))
      ) {
        need = true;
        break;
      }
    }
    if (need) stagedRefresh();
  });
  mo.observe(document.documentElement, {
    attributes: true,
    childList: true,
    subtree: true,
    attributeFilter: ["class", "style", "hidden", "aria-hidden"],
  });

  // 念のための初回即時判定
  refreshAllNow();
})();
