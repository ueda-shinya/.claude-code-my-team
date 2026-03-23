// assets/js/frontend.js
/* OU Quiz Chat frontend — data-driven Q&A (defer) */
(function () {
  'use strict';

  const $  = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

  function meta(name){
    const el = document.querySelector(`meta[name="${name}"]`);
    return el ? el.getAttribute('content') : '';
  }

  const REST_ROOT = meta('ouq-rest-root') || '/wp-json/';
  const CONSENT_IN_PROGRESS = meta('ouq-consent-in-progress') === '1';
  function route(p){ return (REST_ROOT.replace(/\/+$/,'') + '/ouq/v1' + p); }
  function wpNonce(){ return meta('ouq-rest-nonce') || ''; }

  function createFromTpl(id){ const t=document.getElementById(id); return t? t.content.firstElementChild.cloneNode(true):null; }
  function scrollToBottom(stream){ stream.scrollTop = stream.scrollHeight; }

  function addBot(stream, html){
    const n = createFromTpl('tpl-bot'); if(!n) return;
    $('.c-msg__bubble', n).innerHTML = html;
    stream.appendChild(n); scrollToBottom(stream); return n;
  }
  function addUser(stream, html){
    const n = createFromTpl('tpl-user'); if(!n) return;
    $('.c-msg__bubble', n).innerHTML = html;
    stream.appendChild(n); scrollToBottom(stream); return n;
  }
  function showTyping(stream){
    const n = createFromTpl('tpl-typing'); if(!n) return;
    n.classList.add('js-typing'); stream.appendChild(n); scrollToBottom(stream); return n;
  }
  function hideTyping(stream){ const n = $('.js-typing', stream); if(n) n.remove(); }

  function makeChoices(buttons){
    const wrap = createFromTpl('tpl-choices'); if(!wrap) return null;
    buttons.forEach(b=>{
      const el = document.createElement('button');
      el.type = 'button';
      el.className = 'c-btn';
      el.textContent = b.label;
      if (b.primary) el.classList.add('c-btn--primary');
      el.addEventListener('click', ()=> b.onClick && b.onClick(el));
      wrap.appendChild(el);
    });
    return wrap;
  }

  function setProgress(count, total){
    const bar = $('.js-progress-bar');
    const now = total>0 ? Math.max(0, Math.min(100, Math.round(count/total*100))) : 0;
    if (bar){ bar.style.width = `${now}%`; bar.setAttribute('aria-valuenow', String(now)); }
    const qCount = $('.js-q-count'); if(qCount) qCount.textContent = `Q ${count}`;
    const qTotal = $('.js-q-total'); if(qTotal) qTotal.textContent = String(total);
  }

  // ---------- Data load ----------
  async function loadQuestions(){ const r=await fetch(route('/questions'), {credentials:'same-origin'}); const j=await r.json(); if(!r.ok) throw j; return j.questions || []; }
  async function loadResults(){ const r=await fetch(route('/results'),   {credentials:'same-origin'}); const j=await r.json(); if(!r.ok) throw j; return j.result_bands || []; }

  function decideBand(bands, score){
    let hit = null;
    for (const b of bands){
      if (score >= (parseInt(b.min,10)||0) && score <= (parseInt(b.max,10)||0)){ hit = b; break; }
    }
    return hit;
  }

  function escapeHTML(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;'); }
  function escapeAttr(s){ return String(s||'').replace(/"/g,'&quot;'); }

  // ---------- Chat flow ----------
  function initChat(root){
    const stream = $('.js-stream', root);
    const head   = $('.c-head__progress', root);

    // 同意を進捗に含めない（既定）→ consent中は非表示
    head.style.visibility = CONSENT_IN_PROGRESS ? 'visible' : 'hidden';

    let questions = [];
    let bands = [];
    let totalQ = 0;
    let idx = 0;
    let totalScore = 0;

    // 回答ログ（後でメールに添付したい場合の拡張用）
    const answerLog = []; // {qText, aLabel, score}

    (async function bootstrap(){
      try{
        const t = showTyping(stream);
        [questions, bands] = await Promise.all([loadQuestions(), loadResults()]);
        hideTyping(stream);

        questions = (questions||[]).map((q,i)=>({
          id: q.id, order: q.order || (i+1),
          text: String(q.text||''),
          type: (q.type==='multi' ? 'multi':'single'),
          required: !!q.required,
          score_cap: (q.score_cap===''||q.score_cap===null||q.score_cap===undefined)? null : (parseInt(q.score_cap,10)||0),
          choices: Array.isArray(q.choices) ? q.choices.map(c=>({
            id: c.id, label: String(c.label||''), score: parseInt(c.score,10)||0
          })) : []
        })).sort((a,b)=> (a.order-b.order));

        bands = (bands||[]).map(b=>({
          id: b.id,
          min: parseInt(b.min,10)||0,
          max: parseInt(b.max,10)||0,
          title: String(b.title||''),
          summary: String(b.summary||''),
          recommend_pages: Array.isArray(b.recommend_pages)? b.recommend_pages : [],
          recommend_features: Array.isArray(b.recommend_features)? b.recommend_features : [],
          estimate_timeline: String(b.estimate_timeline||''),
          estimate_budget: String(b.estimate_budget||''),
          cta_label: String(b.cta_label||''),
          cta_url: String(b.cta_url||'')
        })).sort((a,b)=> (a.min-b.min)|| (a.max-b.max));

        totalQ = questions.length;
        setProgress(0, totalQ);
        consentStep();

      }catch(err){
        console.error(err);
        addBot(stream, '読み込みに失敗しました。時間をおいて再度お試しください。');
      }
    })();

    function consentStep(){
      const policyLinkText = 'プライバシーポリシー';
      addBot(stream, `診断の前に、${policyLinkText}への同意をお願いします。<br><small>※別タブで開いてご確認ください。</small>`);
      const choices = makeChoices([
        {label:'同意する', primary:true, onClick:()=>onConsent(true)},
        {label:'同意しない', onClick:()=>onConsent(false)}
      ]);
      stream.appendChild(choices); scrollToBottom(stream);
    }

    function onConsent(ok){
      $$('.c-choices button', stream).slice(-2).forEach(b=> b.disabled = true);
      addUser(stream, ok?'同意する':'同意しない');
      if (!ok){
        const t = showTyping(stream);
        setTimeout(()=>{
          hideTyping(stream);
          addBot(stream, '同意が必要です。ご確認のうえ、同意いただける場合は続行してください。');
          const again = makeChoices([
            {label:'同意する', primary:true, onClick:()=>onConsent(true)},
            {label:'今回は中止する', onClick:()=> addBot(stream, 'またの機会にお願いします。')}
          ]);
          stream.appendChild(again); scrollToBottom(stream);
        }, 600);
        return;
      }
      head.style.visibility = 'visible';
      setProgress(0, totalQ);
      idx = 0;
      askNext();
    }

    function askNext(){
      if (idx >= totalQ){ return finish(); }
      const q = questions[idx];
      const t = showTyping(stream);
      setTimeout(()=>{
        hideTyping(stream);
        const box = addBot(stream, escapeHTML(q.text));
        if (!q || !Array.isArray(q.choices) || q.choices.length===0){
          idx++; askNext(); return;
        }

        if (q.type === 'single'){
          const choices = makeChoices(q.choices.map(c=>({
            label: c.label,
            primary: false,
            onClick: (btn)=> {
              const group = btn.parentElement; $$('button', group).forEach(b=> b.disabled = true);
              addUser(stream, escapeHTML(c.label));
              totalScore += (parseInt(c.score,10)||0);
              answerLog.push({qText:q.text, aLabel:c.label, score: (parseInt(c.score,10)||0)});
              idx++;
              setProgress(Math.min(idx, totalQ), totalQ);
              const t2 = showTyping(stream);
              setTimeout(()=>{ hideTyping(stream); askNext(); }, 450);
            }
          })));
          box.appendChild(choices); scrollToBottom(stream);
        } else {
          const selected = new Set();
          const wrap = document.createElement('div');
          const grp = makeChoices(q.choices.map(c=>({
            label: c.label,
            onClick: (btn)=>{
              const cid = c.id || c.label;
              if (selected.has(cid)){
                selected.delete(cid); btn.classList.remove('c-btn--primary');
              } else {
                selected.add(cid); btn.classList.add('c-btn--primary');
              }
            }
          })));
          const confirm = makeChoices([{label: 'この内容でOK', primary:true, onClick: ()=>{
            $$('button', grp).forEach(b=> b.disabled = true);
            $$('button', confirm).forEach(b=> b.disabled = true);
            const labels = q.choices.filter(c=> selected.has(c.id||c.label)).map(c=> c.label);
            addUser(stream, labels.length? labels.map(escapeHTML).join(' / ') : '（選択なし）');

            let sum = 0;
            q.choices.forEach(c=> { const cid=c.id||c.label; if (selected.has(cid)){ sum += (parseInt(c.score,10)||0); } });
            if (q.score_cap !== null && q.score_cap !== undefined){
              sum = Math.min(sum, parseInt(q.score_cap,10)||0);
            }
            totalScore += sum;

            // ログ（multiは結合表示）
            answerLog.push({qText:q.text, aLabel: labels.join(' / '), score: sum});

            idx++; setProgress(Math.min(idx, totalQ), totalQ);
            const t2 = showTyping(stream);
            setTimeout(()=>{ hideTyping(stream); askNext(); }, 450);
          }}]);

          wrap.appendChild(grp); wrap.appendChild(confirm);
          box.appendChild(wrap); scrollToBottom(stream);
        }
      }, 450);
    }

    function finish(){
      const t = showTyping(stream);
      setTimeout(()=>{
        hideTyping(stream);
        addBot(stream, `診断が完了しました。あなたのスコアは <strong>${totalScore}</strong> です。`);
        const b = decideBand(bands, totalScore);
        if (b){ renderResultCard(b); }
        setTimeout(()=>{ startContactFlow(b); }, 400);
      }, 500);
    }

    function renderResultCard(b){
      const lines = [];
      if (b.title)   lines.push(`<h3 class="c-result__title">${escapeHTML(b.title)}</h3>`);
      if (b.summary) lines.push(`<p class="c-result__summary">${escapeHTML(b.summary)}</p>`);
      if ((b.recommend_pages||[]).length){
        lines.push(`<div class="c-result__block"><strong>推奨ページ</strong><div class="c-result__chips">${b.recommend_pages.map(x=> `<span>${escapeHTML(x)}</span>`).join('')}</div></div>`);
      }
      if ((b.recommend_features||[]).length){
        lines.push(`<div class="c-result__block"><strong>推奨機能</strong><div class="c-result__chips">${b.recommend_features.map(x=> `<span>${escapeHTML(x)}</span>`).join('')}</div></div>`);
      }
      const meta = [];
      if (b.estimate_timeline) meta.push(`目安期間：${escapeHTML(b.estimate_timeline)}`);
      if (b.estimate_budget)   meta.push(`概算：${escapeHTML(b.estimate_budget)}`);
      if (meta.length) lines.push(`<p class="c-result__meta">${meta.join(' / ')}</p>`);
      if (b.cta_url && b.cta_label){
        lines.push(`<p class="u-mt8"><a class="c-btn c-btn--primary" href="${escapeAttr(b.cta_url)}" target="_blank" rel="noopener">${escapeHTML(b.cta_label)}</a></p>`);
      }
      addBot(stream, `<div class="c-result">${lines.join('')}</div>`);
    }

    // ====== 入力フロー ======
    async function startContactFlow(band){
      addBot(stream, '診断結果をメールでお送りします。続いてお名前とご連絡先の入力をお願いします。');

      const name = await askAndConfirm('お名前', 'お名前を入力してください。', val=>{
        if (!val) return 'お名前を入力してください。';
        if (val.length>100) return '100文字以内で入力してください。';
        return '';
      });
      if (!name) return cancelFlow();

      const company = await askAndConfirm('会社名（任意）', null, val=>{
        if (val && val.length>150) return '150文字以内で入力してください。';
        return '';
      }, '');

      const phone = await askAndConfirm('お電話番号（任意）', null, val=>{
        if (!val) return '';
        if (!/^\+?\d[\d\-\s]{6,}$/.test(val)) return '電話番号の形式をご確認ください。';
        return '';
      }, '');

      const email = await askAndConfirm('メールアドレス', 'メールアドレスを入力してください。', val=>{
        if (!val) return 'メールアドレスを入力してください。';
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) return 'メールアドレスの形式が正しくありません。';
        return '';
      });
      if (!email) return cancelFlow();

      const confirmNode = addBot(stream, '以上の内容で送信します。よろしいですか？');
      const choices = makeChoices([
        {label:'送信する', primary:true, onClick:()=> doSubmitContact({name, company, phone, email}, band)},
        {label:'やり直す', onClick:()=> addBot(stream, '修正は項目ごとの再入力UIを次工程で実装します。お手数ですがこのまま送信してください。')}
      ]);
      confirmNode.appendChild(choices);
      scrollToBottom(stream);
    }

    async function askAndConfirm(label, requiredMsg=null, validate=null, initial=''){
      const res = await openModal('お客様情報', label, initial, 'text', (v)=>{
        if (validate){ const e = validate(v); if (e) return e; }
        return '';
      });
      if (!res.ok) { addBot(stream, 'キャンセルされました。'); return null; }
      addUser(stream, `${label}：${res.value}`);

      const conf = await choiceConfirm(`${label}は「${escapeHTML(res.value)}」でよろしいですか？`);
      if (conf === 'yes') return res.value;
      if (conf === 'no')  return await askAndConfirm(label, requiredMsg, validate, res.value);
      return null;
    }

    function choiceConfirm(text){
      return new Promise(resolve=>{
        const node = addBot(stream, text);
        const choices = makeChoices([
          {label:'はい',  primary:true, onClick:()=> { disableGroup(choices); addUser(stream, 'はい'); resolve('yes'); }},
          {label:'いいえ', onClick:()=> { disableGroup(choices); addUser(stream, 'いいえ'); resolve('no');  }},
        ]);
        node.appendChild(choices); scrollToBottom(stream);
        function disableGroup(group){ $$('button', group).forEach(b=> b.disabled = true); }
      });
    }

    function cancelFlow(){
      addBot(stream, '入力が中断されました。再開する場合はページを更新してください。');
    }

    async function doSubmitContact(contact, band){
      addUser(stream, '送信する');
      const t = showTyping(stream);

      const payload = {
        name: contact.name,
        company: contact.company,
        phone: contact.phone,
        email: contact.email,
        consent: true,
        score: totalScore,
        band: band || {},
        answers: answerLog.map(x=> ({ q: x.qText, a: x.aLabel, score: x.score })),
        page_url: location.href,
        query: Object.fromEntries(new URL(location.href).searchParams.entries()),
      };

      try{
        const res = await fetch(route('/submit'), {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': wpNonce(),
          },
          body: JSON.stringify(payload)
        });
        const j = await res.json();
        hideTyping(stream);

        if (!res.ok || !j.ok){
          addBot(stream, '送信に失敗しました。時間をおいて再度お試しください。');
          return;
        }

        addBot(stream, '診断結果を送信しました。メールをご確認ください。届かない場合は迷惑メールもご確認ください。');
        if (band && band.cta_url && band.cta_label){
          addBot(stream, `<a class="c-btn c-btn--primary" href="${escapeAttr(band.cta_url)}" target="_blank" rel="noopener">${escapeHTML(band.cta_label)}</a>`);
        }
      } catch(err){
        hideTyping(stream);
        console.error(err);
        addBot(stream, '送信に失敗しました。通信状況をご確認ください。');
      }
    }

    // 簡易 openModal（テンプレがあればそちらを優先）
    function openModal(title, label, initial='', type='text', validator=null){
      const tpl = document.getElementById('tpl-modal');
      if (tpl){
        // ここで tpl-modal を使った重厚版実装に差し替え可能
      }
      // 簡易版：画面内モーダルを生成
      return new Promise(resolve=>{
        const overlay = document.createElement('div');
        overlay.style.position='fixed';
        overlay.style.inset='0';
        overlay.style.background='rgba(0,0,0,.35)';
        overlay.style.zIndex='9999';
        overlay.style.display='flex';
        overlay.style.alignItems='center';
        overlay.style.justifyContent='center';

        const box = document.createElement('div');
        box.style.background='#fff';
        box.style.border='1px solid #e2e8f0';
        box.style.borderRadius='12px';
        box.style.padding='16px';
        box.style.width='min(92vw, 520px)';
        box.innerHTML = `
          <div style="font-weight:700; margin-bottom:8px;">${escapeHTML(title)}</div>
          <label style="display:block; margin-bottom:8px; color:#334155;">${escapeHTML(label)}</label>
          <input class="js-inp" type="${escapeAttr(type)}" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:8px;" />
          <p class="js-err" style="color:#b91c1c; font-size:12px; min-height:18px; margin:6px 0 0;"></p>
          <div style="display:flex; gap:8px; justify-content:flex-end; margin-top:12px;">
            <button class="js-cancel c-btn">キャンセル</button>
            <button class="js-ok c-btn c-btn--primary">OK</button>
          </div>
        `;
        document.body.appendChild(overlay);
        overlay.appendChild(box);

        const inp = $('.js-inp', box);
        const err = $('.js-err', box);
        const btnOk = $('.js-ok', box);
        const btnCancel = $('.js-cancel', box);

        inp.value = initial || '';
        setTimeout(()=> inp.focus(), 50);

        function close(ok=false, value=''){
          overlay.remove();
          resolve({ok, value});
        }
        function validate(){
          if (!validator) return '';
          return validator(inp.value.trim());
        }
        btnOk.addEventListener('click', ()=>{
          const e = validate();
          if (e){ err.textContent = e; return; }
          close(true, inp.value.trim());
        });
        btnCancel.addEventListener('click', ()=> close(false,''));
        overlay.addEventListener('click', (ev)=> { if (ev.target === overlay) close(false,''); });
        inp.addEventListener('keydown', (ev)=>{ if(ev.key==='Enter'){ btnOk.click(); } });
      });
    }
  }

  document.addEventListener('DOMContentLoaded', ()=>{
    $$('.ouq-chat.js-ouq-root').forEach(initChat);
  });
})();
