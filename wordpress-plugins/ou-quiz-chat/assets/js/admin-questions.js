// admin-questions.js
// 管理UI：質問管理（Vanilla JS / defer）
(function(){
  'use strict';

  const apiRoot = (document.querySelector('meta[name="ouq-rest-root"]')||{}).content || (window.wpApiSettings && window.wpApiSettings.root) || '/wp-json/';
  const restNS  = 'ouq/v1';
  const nonce   = (document.querySelector('.js-nonce')||{}).value || '';

  const $  = (s, r=document)=>r.querySelector(s);
  const $$ = (s, r=document)=>Array.from(r.querySelectorAll(s));

  const state = {
    version: 0,
    questions: [],
  };

  function route(path){ return apiRoot.replace(/\/+$/,'') + '/' + restNS + path; }

  function toast(msg, type='success'){
    console[type==='error'?'error':'log'](msg);
  }

  function makeId(){ return (window.crypto?.randomUUID?.() || ('id-' + Math.random().toString(36).slice(2))); }

  // ---- タブ切替（簡易） ----
  document.addEventListener('click', (e)=>{
    const tab = e.target.closest('.ouq-tab');
    if (!tab) return;
    $$('.ouq-tab').forEach(t=>t.classList.remove('is-active'));
    tab.classList.add('is-active');
    const name = tab.dataset.tab;
    $$('.ouq-tabpanel').forEach(p=>p.classList.remove('is-active'));
    $('#ouq-tab-' + name).classList.add('is-active');
  });

  // ---- 初期ロード ----
  async function load(){
    try {
      const res = await fetch(route('/questions'), {credentials:'same-origin'});
      const j = await res.json();
      if (!res.ok) throw j;
      state.version   = j.version || 0;
      state.questions = Array.isArray(j.questions) ? j.questions : [];
      renderList();
    } catch(err){
      toast('読み込みに失敗しました', 'error');
      console.error(err);
    }
  }

  // ---- 画面描画 ----
  function renderList(){
    const root = $('.js-questions');
    root.innerHTML = '';
    if (state.questions.length === 0){
      const p = document.createElement('p');
      p.textContent = '質問がまだありません。「質問を追加」で作成してください。';
      root.appendChild(p);
      return;
    }
    state.questions.sort((a,b)=> (a.order||0) - (b.order||0));
    state.questions.forEach((q, idx)=>{
      const card = document.createElement('div');
      card.className = 'ouq-qcard';
      card.dataset.qid = q.id;

      const head = document.createElement('div');
      head.className = 'ouq-qcard__head';
      head.innerHTML = `
        <strong class="ouq-qcard__title">Q${idx+1}</strong>
        <div class="ouq-qcard__ops">
          <button type="button" class="button js-up">↑</button>
          <button type="button" class="button js-down">↓</button>
          <button type="button" class="button js-dup">複製</button>
          <button type="button" class="button button-link-delete js-del">削除</button>
        </div>
      `;

      const body = document.createElement('div');
      body.className = 'ouq-qcard__body';
      body.innerHTML = `
        <label class="ouq-row">
          <span class="ouq-label">質問文</span>
          <input type="text" class="regular-text js-q-text" value="${escapeAttr(q.text||'')}">
        </label>
        <div class="ouq-cols">
          <label><span class="ouq-label">タイプ</span>
            <select class="js-q-type">
              <option value="single"${q.type==='multi'?'':' selected'}>single（単一）</option>
              <option value="multi"${q.type==='multi'?' selected':''}>multi（複数）</option>
            </select>
          </label>
          <label><span class="ouq-label">必須</span>
            <input type="checkbox" class="js-q-req"${q.required?' checked':''}>
          </label>
          <label><span class="ouq-label">score cap</span>
            <input type="number" class="small-text js-q-cap" min="0" step="1" value="${q.score_cap??''}">
          </label>
        </div>

        <div class="ouq-choices">
          <div class="ouq-choices__head">
            <strong>選択肢</strong>
            <button type="button" class="button js-add-choice">＋ 追加</button>
          </div>
          <div class="ouq-choices__list js-choice-list"></div>
        </div>
      `;

      root.appendChild(card);
      card.appendChild(head);
      card.appendChild(body);

      // 選択肢描画
      const list = $('.js-choice-list', card);
      (q.choices||[]).forEach((c,i)=>{
        list.appendChild(renderChoiceRow(c, i));
      });

      // 操作イベント
      head.querySelector('.js-up').addEventListener('click', ()=>moveQuestion(idx, -1));
      head.querySelector('.js-down').addEventListener('click', ()=>moveQuestion(idx, +1));
      head.querySelector('.js-dup').addEventListener('click', ()=>duplicateQuestion(idx));
      head.querySelector('.js-del').addEventListener('click', ()=>deleteQuestion(idx));

      body.querySelector('.js-add-choice').addEventListener('click', ()=>{
        const c = { id: makeId(), label: '', score: 0 };
        q.choices = q.choices || [];
        q.choices.push(c);
        list.appendChild(renderChoiceRow(c, q.choices.length-1));
      });

      body.querySelector('.js-q-text').addEventListener('input', (e)=> q.text = e.target.value);
      body.querySelector('.js-q-type').addEventListener('change', (e)=> q.type = e.target.value === 'multi' ? 'multi':'single');
      body.querySelector('.js-q-req').addEventListener('change', (e)=> q.required = !!e.target.checked);
      body.querySelector('.js-q-cap').addEventListener('input', (e)=> q.score_cap = e.target.value === '' ? null : Math.max(0, parseInt(e.target.value,10)||0));
    });
  }

  function renderChoiceRow(c, idx){
    const row = document.createElement('div');
    row.className = 'ouq-choice';
    row.dataset.cid = c.id;
    row.innerHTML = `
      <input type="text" class="regular-text js-c-label" placeholder="選択肢ラベル" value="${escapeAttr(c.label||'')}">
      <input type="number" class="small-text js-c-score" placeholder="score" value="${c.score||0}">
      <button type="button" class="button js-c-up">↑</button>
      <button type="button" class="button js-c-down">↓</button>
      <button type="button" class="button button-link-delete js-c-del">削除</button>
    `;
    row.querySelector('.js-c-label').addEventListener('input', (e)=> c.label = e.target.value);
    row.querySelector('.js-c-score').addEventListener('input', (e)=> c.score = parseInt(e.target.value||'0',10)||0);
    row.querySelector('.js-c-up').addEventListener('click', ()=>{
      const q = findQuestionByChoiceId(c.id);
      if (!q) return;
      const i = q.choices.findIndex(x=>x.id===c.id);
      moveItem(q.choices, i, -1); renderList();
    });
    row.querySelector('.js-c-down').addEventListener('click', ()=>{
      const q = findQuestionByChoiceId(c.id);
      if (!q) return;
      const i = q.choices.findIndex(x=>x.id===c.id);
      moveItem(q.choices, i, +1); renderList();
    });
    row.querySelector('.js-c-del').addEventListener('click', ()=>{
      const q = findQuestionByChoiceId(c.id);
      if (!q) return;
      q.choices = q.choices.filter(x=>x.id!==c.id);
      renderList();
    });
    return row;
  }

  function findQuestionByChoiceId(cid){
    return state.questions.find(q => (q.choices||[]).some(c => c.id === cid));
  }

  function moveItem(arr, idx, delta){
    const ni = idx + delta;
    if (ni < 0 || ni >= arr.length) return;
    const t = arr[idx];
    arr[idx] = arr[ni];
    arr[ni] = t;
  }

  function moveQuestion(idx, delta){
    moveItem(state.questions, idx, delta);
    // orderを振り直し
    state.questions.forEach((q,i)=> q.order = i+1);
    renderList();
  }

  function duplicateQuestion(idx){
    const src = state.questions[idx];
    const dup = JSON.parse(JSON.stringify(src));
    dup.id = makeId();
    dup.choices = (dup.choices||[]).map(c => ({...c, id: makeId()}));
    state.questions.splice(idx+1, 0, dup);
    state.questions.forEach((q,i)=> q.order = i+1);
    renderList();
  }

  function deleteQuestion(idx){
    if (!confirm('この質問を削除します。よろしいですか？')) return;
    state.questions.splice(idx, 1);
    state.questions.forEach((q,i)=> q.order = i+1);
    renderList();
  }

  // ---- 検証 ----
  function validate(){
    if (!Array.isArray(state.questions) || state.questions.length===0){
      alert('質問が1件もありません。');
      return false;
    }
    for (const q of state.questions){
      if (!q.text || q.text.trim()===''){ alert('空の質問文があります'); return false; }
      if (!Array.isArray(q.choices) || q.choices.length===0){ alert('選択肢が1件もない質問があります'); return false; }
      for (const c of q.choices){
        if (!c.label || c.label.trim()===''){ alert('空の選択肢ラベルがあります'); return false; }
        if (Number.isNaN(parseInt(c.score,10))){ alert('scoreが数値でない選択肢があります'); return false; }
      }
    }
    return true;
  }

  // ---- 保存 ----
  async function save(){
    if (!validate()) return;
    try{
      const res = await fetch(route('/questions') + '?nonce=' + encodeURIComponent(nonce), {
        method: 'POST',
        credentials: 'same-origin',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({
          version: state.version,
          questions: state.questions.map((q,i)=>({
            id: q.id || makeId(),
            order: i+1,
            text: String(q.text||''),
            type: (q.type==='multi' ? 'multi' : 'single'),
            required: !!q.required,
            score_cap: (q.score_cap===''||q.score_cap===null||q.score_cap===undefined) ? null : parseInt(q.score_cap,10)||0,
            choices: (q.choices||[]).map(c=>({
              id: c.id || makeId(),
              label: String(c.label||''),
              score: parseInt(c.score,10)||0
            }))
          }))
        })
      });
      const j = await res.json();
      if (res.status === 409){
        alert('他のユーザーが先に保存しました。最新を読み込みなおしてから、再度保存してください。');
        return;
      }
      if (!res.ok){
        console.error(j);
        alert('保存に失敗しました。');
        return;
      }
      state.version = j.version || state.version+1;
      toast('保存しました');
    }catch(err){
      console.error(err);
      alert('保存中にエラーが発生しました。');
    }
  }

  // ---- JSONインポート/エクスポート ----
  function exportJSON(){
    const blob = new Blob([JSON.stringify({questions: state.questions}, null, 2)], {type:'application/json'});
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'ouq-questions.ouq.json';
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
        if (!Array.isArray(j.questions)) { alert('フォーマットが不正です'); return; }
        state.questions = j.questions.map((q,i)=>({
          id: q.id || makeId(),
          order: i+1,
          text: String(q.text||''),
          type: (q.type==='multi' ? 'multi' : 'single'),
          required: !!q.required,
          score_cap: (q.score_cap===''||q.score_cap===null||q.score_cap===undefined) ? null : parseInt(q.score_cap,10)||0,
          choices: (q.choices||[]).map(c=>({
            id: c.id || makeId(),
            label: String(c.label||''),
            score: parseInt(c.score,10)||0
          }))
        }));
        renderList();
      }catch(err){
        console.error(err);
        alert('読み込みに失敗しました。');
      }
    };
    input.click();
  }

  // ---- ヘルパ ----
  function escapeAttr(s){
    return String(s||'').replace(/"/g,'&quot;');
  }

  // ---- イベント結線 ----
  document.addEventListener('click', (e)=>{
    if (e.target.matches('.js-add-q')){
      state.questions.push({
        id: makeId(),
        order: state.questions.length+1,
        text: '',
        type: 'single',
        required: true,
        score_cap: null,
        choices: [{id: makeId(), label: '', score: 0}]
      });
      renderList();
    }
    if (e.target.matches('.js-save')) save();
    if (e.target.matches('.js-export')) exportJSON();
    if (e.target.matches('.js-import')) importJSON();
  });

  // 起動
  document.addEventListener('DOMContentLoaded', load);
})();
