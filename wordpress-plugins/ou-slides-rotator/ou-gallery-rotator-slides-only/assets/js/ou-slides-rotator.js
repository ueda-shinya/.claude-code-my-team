(() => {
  'use strict';

  const DEF = (typeof OU_SLIDES_ROTATOR_DEFAULTS === 'object')
    ? Object.assign({
        interval: 3500,
        fade: 1200,
        minItems: 2,
        selector: '.slides',
        itemSel: '.slide',
        rootAttr: 'data-ousr-root',
        activeCls: 'ousr__item--active',
        pausedCls: 'ousr--paused'
      }, OU_SLIDES_ROTATOR_DEFAULTS)
    : {
        interval: 3500,
        fade: 1200,
        minItems: 2,
        selector: '.slides',
        itemSel: '.slide',
        rootAttr: 'data-ousr-root',
        activeCls: 'ousr__item--active',
        pausedCls: 'ousr--paused'
      };

  class Rotator {
    constructor(root) {
      this.root = root;
      // .slide を取得（カラム配下も含む）
      this.items = Array.from(root.querySelectorAll(DEF.itemSel));
      this.idx = 0;
      this.timer = null;
      this.visible = false;
      this.paused = false;

      // スコープ属性を付与（CSSの適用範囲）
      root.setAttribute(DEF.rootAttr, '');

      // data属性で上書き
      const intAttr  = parseInt(root.getAttribute('data-ou-interval'), 10);
      const fadeAttr = parseInt(root.getAttribute('data-ou-fade'), 10);
      this.interval  = Number.isFinite(intAttr)  ? intAttr  : DEF.interval;
      this.fade      = Number.isFinite(fadeAttr) ? fadeAttr : DEF.fade;

      // CSS変数に反映
      root.style.setProperty('--ousr-fade', `${this.fade}ms`);

      if (this.items.length < DEF.minItems) return;

      // 初期アクティブ
      this.items.forEach(el => el.classList.remove(DEF.activeCls));
      this.items[0].classList.add(DEF.activeCls);

      // 画像ロードを待って高さフィット
      this._prepareImageLoads();

      // 可視領域でのみ再生
      this.io = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.target !== this.root) return;
          this.visible = entry.isIntersecting;
          this._sync();
          if (this.visible) this._fitHeight();
        });
      }, { threshold: 0.2 });
      this.io.observe(this.root);

      // タブ切替で制御
      document.addEventListener('visibilitychange', () => this._sync());

      // ホバー一時停止
      this.root.addEventListener('mouseenter', () => {
        this.paused = true;
        this.root.classList.add(DEF.pausedCls);
        this._stop();
      });
      this.root.addEventListener('mouseleave', () => {
        this.paused = false;
        this.root.classList.remove(DEF.pausedCls);
        this._sync();
      });

      // リサイズで高さ再計算
      window.addEventListener('resize', () => this._fitHeight());

      // 初回同期
      this._sync();
    }

    _prepareImageLoads() {
      const imgs = this.items.map(it => it.querySelector('img')).filter(Boolean);
      let pending = imgs.length;
      if (pending === 0) {
        this._fitHeight();
        return;
      }
      const done = () => {
        pending--;
        if (pending <= 0) this._fitHeight();
      };
      imgs.forEach(img => {
        if (img.complete && img.naturalWidth) {
          done();
        } else {
          img.addEventListener('load', done, { once: true });
          img.addEventListener('error', done, { once: true });
        }
      });
    }

    _activeItem() {
      return this.items[this.idx] || null;
    }

    _activeImage() {
      const it = this._activeItem();
      return it ? it.querySelector('img') : null;
    }

    _fitHeight() {
      // 行(.slides)に対し、現在の可視画像の高さに合わせる
      const img = this._activeImage();
      if (!img) return;

      // getBoundingClientRect で表示高さを取得
      const rect = img.getBoundingClientRect();
      let h = rect.height;

      // 表示前で0になる場合は自然サイズから推定
      if (!h && img.naturalWidth && img.naturalHeight) {
        const rootW = this.root.getBoundingClientRect().width || this.root.clientWidth || 0;
        if (rootW) {
          const ratio = img.naturalHeight / img.naturalWidth;
          h = Math.round(rootW * ratio);
        }
      }
      if (!h) h = 300;

      this.root.style.height = `${h}px`;
    }

    _shouldRun() {
      return this.visible && !this.paused && document.visibilityState === 'visible';
    }

    _sync() {
      if (this._shouldRun()) this._start(); else this._stop();
    }

    _start() {
      if (this.timer) return;
      this.timer = setInterval(() => this.next(), this.interval);
    }

    _stop() {
      if (!this.timer) return;
      clearInterval(this.timer);
      this.timer = null;
    }

    next() {
      const prev = this.items[this.idx];
      this.idx = (this.idx + 1) % this.items.length;
      const next = this.items[this.idx];

      prev.classList.remove(DEF.activeCls);
      next.classList.add(DEF.activeCls);

      this._fitHeight();
    }

    destroy() {
      this._stop();
      if (this.io) this.io.disconnect();
      this.root.removeAttribute(DEF.rootAttr);
      this.root.classList.remove(DEF.pausedCls);
      this.items.forEach(el => el.classList.remove(DEF.activeCls));
      this.root.style.height = '';
    }
  }

  const initAll = () => {
    // .slides のみ対象（副作用回避）
    const roots = Array.from(document.querySelectorAll(DEF.selector))
      .filter(root => !root.hasAttribute('data-ousr-initialized'));

    roots.forEach(root => {
      root.setAttribute('data-ousr-initialized', '1');
      new Rotator(root);
    });
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAll);
  } else {
    initAll();
  }
})();
