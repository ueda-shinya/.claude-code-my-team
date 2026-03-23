(function () {
  const $ = (s, c = document) => c.querySelector(s);
  const api = (params) => {
    const url = new URL(window.location.origin + "/wp-json/oupm/v1/search");
    Object.keys(params).forEach((k) => {
      const v = params[k];
      if (v !== "" && v !== null && v !== undefined) url.searchParams.set(k, v);
    });
    return fetch(url.toString()).then((r) => r.json());
  };

  function card(item) {
    const wrap = document.createElement("article");
    wrap.className = "c-card c-card--property";
    const img = item.thumb
      ? `<img class="c-card__img" src="${item.thumb}" alt="${item.title}">`
      : "";
    wrap.innerHTML = `
<a class="c-card__link" href="${item.permalink}">
<div class="c-card__media">${img}</div>
<div class="c-card__body">
<h3 class="c-card__title">${item.title}</h3>
<ul class="c-card__meta">
<li class="c-card__meta-item">${item.price || ""}</li>
<li class="c-card__meta-item">${item.addr || ""}</li>
<li class="c-card__meta-item">土地 ${item.land || ""}㎡</li>
${item.walk ? `<li class="c-card__meta-item">駅徒歩 ${item.walk}分</li>` : ""}
</ul>
</div>
</a>`;
    return wrap;
  }

  function readForm(form) {
    const data = Object.fromEntries(new FormData(form).entries());
    // チェックボックス未チェックは入らないので無視
    return data;
  }

  async function runSearch(form) {
    const params = readForm(form);
    const res = await api(params);
    const list = $("#oupm-results");
    list.innerHTML = "";
    if (!res || !res.items || !res.items.length) {
      list.innerHTML =
        '<p class="c-empty">該当する物件はありませんでした。</p>';
      return;
    }
    res.items.forEach((item) => list.appendChild(card(item)));
  }

  document.addEventListener("DOMContentLoaded", function () {
    const form = $("#oupm-form");
    if (!form) return;
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      runSearch(form);
    });
    // 初回は空検索（新着）
    runSearch(form);
  });
})();
