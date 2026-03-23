// admin-results.js
// 管理UI：結果レンジ管理（Vanilla JS / defer）
(function(){
  'use strict';

  const apiRoot = (document.querySelector('meta[name="ouq-rest-root"]')||{}).content || (window.wpApiSettings && window.wpApiSettings.root) || '/wp-json/';
  const restNS  = 'ouq/v1';
  function route(path){ return apiRoot.replace(/\/+$/,'') + '/' + restNS + path; }

  const $  = (s, r=document)=>r.querySelector(s);
  const $$ = (s, r=document)=>Array.from(r.querySelectorAll(s));

  const state = {
    version: 0,
    bands: [],
  };

  function makeId(){ return (window.crypto?.randomUUID?.() || ('id-' + Math.random().toString(36).slice(2))); }

  function toast(msg){ console.log(msg); }

  // 初期ロード（タブが存在するときのみ）
  document.addEventListener('DOMContentLoaded', ()=>{
    const panel = $('#ouq-tab-results');
    if (!panel) return;
    load();
    bindEvents();
  });

  async function load(){
    try{
      const res = await fetch(route('/results'), {credentials:'same-origin'});
      const j = await res.json();
      if (!res.ok) throw j;
      state.version = j.version || 0;
      state.bands   = Array.isArray(j.result_bands) ? j.result_bands : [];
      render();
    }catch(err){
      alert('結果レンジの読み込みに失敗しました');
      console.error(err);
    }
  }

  function bindEvents(){
    document.addEventListener('click', (e)=>{
      if (e.target.matches('.js-add-band')) addBand();
      if (e.target.matches('.js-save-results')) save();
      if (e.target.matches('.js-export-results')) exportJSON();
      if (e.target.matches('.js-import-results')) importJSON();
    });
  }

  function addBand(){
    state.bands.push({
      id: makeId(),
      order: state.bands.length + 1,
      min: 0,
      max: 100,
      title: '',
      summary: '',
      recommend_pages: [],
      recommend_features: [],
      estimate_timeline: '',
      estimate_budget: '',
      cta_label: '',
      cta_url: '',
    });
    render();
  }

  function render(){
    const root = $('.js-bands');
    root.innerHTML = '';
    if (state.bands.length === 0){
      const p = document.createElement('p');
      p.textContent = '結果レンジがありません。「帯を追加」で作成してください。';
      root.appendChild(p);
      return;
    }

    state.bands.sort((a,b)=> (a.order||0) - (b.order||0));
    state.bands.forEach((b, idx)=>{
      const card = document.createElement('div');
      card.className = 'ouq-qcard';
      card.dataset.bid = b.id;
      card.innerHTML = `
        <div class="ouq-qcard__head">
          <strong class="ouq-qcard__title">帯 ${idx+1}（${b.min}–${b.max}）</strong>
          <div class="ouq-qcard__ops">
            <button type="button" class="button js-up">↑</button>
            <button type="button" class="button js-down">↓</button>
            <button type="button" class="button js-dup">複製</button>
            <button type="button" class="button button-link-delete js-del">削除</button>
          </div>
        </div>
        <div class="ouq-qcard__body">
          <div class="ouq-cols">
            <label><span class="ouq-label">min</span>
              <input type="number" class="small-text js-min" value="${b.min}">
            </label>
            <label><span class="ouq-label">max</span>
              <input type="number" class="small-text js-max" value="${b.max}">
            </label>
            <label style="flex:1"><span class="ouq-label">タイトル</span>
              <input type="text" class="regular-text js-title" value="${esc(b.title)}">
            </label>
          </div>

          <label class="ouq-row" style="align-items:flex-start">
            <span class="ouq-label">要約</span>
            <textarea class="large-text js-summary" rows="3">${esc(b.summary)}</textarea>
          </label>

          <div class="ouq-cols">
            <label style="flex:1"><span class="ouq-label">推奨ページ（カンマ区切り）</span>
              <input type="text" class="regular-text js-pages" placeholder="例: TOP, サービス, 料金" value="${esc((b.recommend_pages||[]).join(', '))}">
            </label>
            <label style="flex:1"><span class="ouq-label">推奨機能（カンマ区切り）</span>
              <input type="text" class="regular-text js-features" placeholder="例: 予約, 事例, FAQ" value="${esc((b.recommend_features||[]).join(', '))}">
            </label>
          </div>

          <div class="ouq-cols">
            <label><span class="ouq-label">目安期間</span>
              <input type="text" class="regular-text js-timeline" placeholder="例: 4–6週間" value="${esc(b.estimate_timeline)}">
            </label>
            <label><span class="ouq-label">概算</span>
              <input type="text" class="regular-text js-budget" placeholder="例: 80–150万円" value="${esc(b.estimate_budget)}">
            </label>
          </div>

          <div class="ouq-cols">
            <label style="flex:1"><span class="ouq-label">CTAラベル</span>
              <input type="text" class="regular-text js-cta-label" placeholder="例: 無料相談を予約" value="${esc(b.cta_label)}">
            </label>
            <label style="flex:1"><span class="ouq-label">CTA URL</span>
              <input type="url" class="regular-text js-cta-url" placeholder="https://..." value="${esc(b.cta_url)}">
            </label>
          </div>
        </div>
      `;
      root.appendChild(card);

      // events
      card.querySelector('.js-up').addEventListener('click', ()=>move(idx,-1));
      card.querySelector('.js-down').addEventListener('click', ()=>move(idx,+1));
      card.querySelector('.js-dup').addEventListener('click', ()=>dup(idx));
      card.querySelector('.js-del').addEventListener('click', ()=>del(idx));

      card.querySelector('.js-min').addEventListener('input', e=>{ b.min = parseInt(e.target.value||'0',10)||0; updateTitle(card,b,idx); });
      card.querySelector('.js-max').addEventListener('input', e=>{ b.max = parseInt(e.target.value||'0',10)||0; updateTitle(card,b,idx); });
      card.querySelector('.js-title').addEventListener('input', e=>{ b.title = e.target.value; });
      card.querySelector('.js-summary').addEventListener('input', e=>{ b.summary = e.target.value; });
      card.querySelector('.js-pages').addEventListener('input', e=>{ b.recommend_pages = splitCsv(e.target.value); });
      card.querySelector('.js-features').addEventListener('input', e=>{ b.recommend_features = splitCsv(e.target.value); });
      card.querySelector('.js-timeline').addEventListener('input', e=>{ b.estimate_timeline = e.target.value; });
      card.querySelector('.js-budget').addEventListener('input', e=>{ b.estimate_budget = e.target.value; });
      card.querySelector('.js-cta-label').addEventListener('input', e=>{ b.cta_label = e.target.value; });
      card.querySelector('.js-cta-url').addEventListener('input', e=>{ b.cta_url = e.target.value; });
    });
  }

  function updateTitle(card, b, idx){
    $('.ouq-qcard__title', card).textContent = `帯 ${idx+1}（${b.min}–${b.max}）`;
  }

  function move(idx, delta){
    const ni = idx + delta;
    if (ni < 0 || ni >= state.bands.length) return;
    const t = state.bands[idx];
    state.bands[idx] = state.bands[ni];
    state.bands[ni] = t;
    state.bands.forEach((b,i)=> b.order = i+1);
    render();
  }

  function dup(idx){
    const src = state.bands[idx];
    const d = JSON.parse(JSON.stringify(src));
    d.id = makeId();
    state.bands.splice(idx+1,0,d);
    state.bands.forEach((b,i)=> b.order = i+1);
    render();
  }

  function del(idx){
    if (!confirm('この結果帯を削除します。よろしいですか？')) return;
    state.bands.splice(idx,1);
    state.bands.forEach((b,i)=> b.order = i+1);
    render();
  }

  function validate(){
    if (state.bands.length === 0) { alert('結果レンジが1件もありません'); return false; }
    for (const b of state.bands){
      if (!b.title || b.title.trim()===''){ alert('タイトルが空の結果帯があります'); return false; }
      const min = parseInt(b.min,10)||0, max = parseInt(b.max,10)||0;
      if (min > max){ alert('min が max を超えています'); return false; }
    }
    // 重複レンジが無いか軽くチェック
    const arr = state.bands.slice().sort((a,b)=>(a.min-b.min)|| (a.max-b.max));
    for (let i=1; i<arr.length; i++){
      if (arr[i-1].max >= arr[i].min){ alert('レンジが重複しています'); return false; }
    }
    return true;
  }

  async function save(){
    if (!validate()) return;
    const nonce = ($('#ouq-results-nonce')||{}).value || '';
    try{
      const res = await fetch(route('/results') + '?nonce=' + encodeURIComponent(nonce), {
        method: 'POST',
        credentials: 'same-origin',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({
          version: state.version,
          result_bands: state.bands.map((b,i)=>({
            id: b.id || makeId(),
            order: i+1,
            min: parseInt(b.min,10)||0,
            max: parseInt(b.max,10)||0,
            title: String(b.title||''),
            summary: String(b.summary||''),
            recommend_pages: Array.isArray(b.recommend_pages)? b.recommend_pages : [],
            recommend_features: Array.isArray(b.recommend_features)? b.recommend_features : [],
            estimate_timeline: String(b.estimate_timeline||''),
            estimate_budget: String(b.estimate_budget||''),
            cta_label: String(b.cta_label||''),
            cta_url: String(b.cta_url||''),
          }))
        })
      });
      const j = await res.json();
      if (res.status === 409){
        alert('他のユーザーが先に保存しました。最新を読み込み直しください。');
        return;
      }
      if (!res.ok){ console.error(j); alert('保存に失敗しました'); return; }
      state.version = j.version || state.version+1;
      toast('保存しました');
    }catch(err){
      console.error(err);
      alert('保存中にエラーが発生しました');
    }
  }

  function exportJSON(){
    const blob = new Blob([JSON.stringify({result_bands: state.bands}, null, 2)], {type:'application/json'});
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'ouq-result-bands.ouq.json';
    a.click();
    URL.revokeObjectURL(a.href);
  }

  function importJSON(){
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = '.json,.ouq.json,application/json';
    input.onchange = async ()=>{
      const file = input.files?.[0];
      if (!file) return;
      try{
        const text = await file.text();
        const j = JSON.parse(text);
        if (!Array.isArray(j.result_bands)){ alert('フォーマットが不正です'); return; }
        state.bands = j.result_bands.map((b,i)=>({
          id: b.id || makeId(),
          order: i+1,
          min: parseInt(b.min,10)||0,
          max: parseInt(b.max,10)||0,
          title: String(b.title||''),
          summary: String(b.summary||''),
          recommend_pages: Array.isArray(b.recommend_pages)? b.recommend_pages : [],
          recommend_features: Array.isArray(b.recommend_features)? b.recommend_features : [],
          estimate_timeline: String(b.estimate_timeline||''),
          estimate_budget: String(b.estimate_budget||''),
          cta_label: String(b.cta_label||''),
          cta_url: String(b.cta_url||''),
        }));
        render();
      }catch(err){
        console.error(err);
        alert('読み込みに失敗しました');
      }
    };
    input.click();
  }

  function splitCsv(s){
    return String(s||'').split(',').map(x=>x.trim()).filter(Boolean);
  }
  function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/"/g,'&quot;'); }

})();
