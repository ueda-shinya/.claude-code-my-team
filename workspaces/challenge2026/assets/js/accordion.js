/**
 * Accordion
 * - Header tap to open
 * - Footer "close" button to close
 * - Options:
 *    headerOpenOnly (default: true)
 *    singleOpen      (default: true)
 *    duration        (ms)
 */
(function () {
  const root = document.querySelector('[data-accordion]');
  if (!root) return;

  const triggers = Array.from(root.querySelectorAll('[data-accordion-trigger]'));
  const items = Array.from(root.querySelectorAll('[data-accordion-item]'));

  // オプション
  const options = {
    headerOpenOnly: false,  // デフォルト:true: ヘッダーは開く専用
    singleOpen: false,      // デフォルト:true: 常に1つだけ開く
    duration: 220
  };

  // 必要ならここで上書きしてください
  // options.headerOpenOnly = false; // ヘッダーで閉じるトグルを許可
  // options.singleOpen = false;     // 複数同時に開ける

  function getPanelFromTrigger(trigger) {
    const id = trigger.getAttribute('aria-controls');
    if (!id) return null;
    return root.querySelector(`#${CSS.escape(id)}`);
  }

  function isOpen(item) {
    return item.classList.contains('is-open');
  }

  function setAria(trigger, expanded) {
    trigger.setAttribute('aria-expanded', expanded ? 'true' : 'false');
  }

  function animateOpen(panel) {
    panel.hidden = false;
    panel.style.height = '0px';
    panel.style.transition = `height ${options.duration}ms ease`;

    // 強制 reflow
    panel.getBoundingClientRect();

    const target = panel.scrollHeight;
    panel.style.height = `${target}px`;

    const onEnd = () => {
      panel.style.transition = '';
      panel.style.height = '';
      panel.removeEventListener('transitionend', onEnd);
    };
    panel.addEventListener('transitionend', onEnd);
  }

  function animateClose(panel) {
    panel.style.height = `${panel.scrollHeight}px`;
    panel.style.transition = `height ${options.duration}ms ease`;

    // 強制 reflow
    panel.getBoundingClientRect();

    panel.style.height = '0px';

    const onEnd = () => {
      panel.hidden = true;
      panel.style.transition = '';
      panel.style.height = '';
      panel.removeEventListener('transitionend', onEnd);
    };
    panel.addEventListener('transitionend', onEnd);
  }

  function openItem(item) {
    const trigger = item.querySelector('[data-accordion-trigger]');
    const panel = item.querySelector('[data-accordion-panel]');
    if (!trigger || !panel) return;

    if (options.singleOpen) {
      items.forEach((other) => {
        if (other !== item && isOpen(other)) {
          closeItem(other);
        }
      });
    }

    if (isOpen(item)) return;

    item.classList.add('is-open');
    setAria(trigger, true);
    animateOpen(panel);
  }

  function closeItem(item) {
    const trigger = item.querySelector('[data-accordion-trigger]');
    const panel = item.querySelector('[data-accordion-panel]');
    if (!trigger || !panel) return;

    if (!isOpen(item)) return;

    item.classList.remove('is-open');
    setAria(trigger, false);
    animateClose(panel);
  }

  function toggleByHeader(trigger) {
    const item = trigger.closest('[data-accordion-item]');
    if (!item) return;

    if (isOpen(item)) {
      if (!options.headerOpenOnly) {
        closeItem(item);
      }
      // headerOpenOnly=true の場合は何もしない（閉じない）
      return;
    }
    openItem(item);
  }

  // Header events
  triggers.forEach((trigger) => {
    trigger.addEventListener('click', () => toggleByHeader(trigger));

    // キーボード操作はbuttonが標準サポート
  });

  // Footer close buttons
  root.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-accordion-close]');
    if (!btn) return;

    const item = btn.closest('[data-accordion-item]');
    if (!item) return;

    closeItem(item);

    // 閉じた後、意図が明確になるようヘッダーへフォーカスを戻す
    const trigger = item.querySelector('[data-accordion-trigger]');
    if (trigger) trigger.focus();
  });

})();
