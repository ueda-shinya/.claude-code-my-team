'use strict'

// -----------------------------------------------------------------------
// DOM 取得
// -----------------------------------------------------------------------
const form        = document.getElementById('contact-form')
const submitBtn   = document.getElementById('submit-btn')
const privacyCb   = document.getElementById('privacy')
// 注: ?error=1 経路では PHP 出力の error メッセージ要素を指す。
// 通常経路では hidden の空要素を指す。いずれも同じ id="form-message" で互換動作する。
const formMsg     = document.getElementById('form-message')
const zipcodeInput = document.getElementById('zipcode')
const zipcodeHint = document.getElementById('zipcode-hint')

// -----------------------------------------------------------------------
// 電話番号正規化
// 全角数字→半角変換、ハイフン各種・スペース除去して数字のみにする
// -----------------------------------------------------------------------

/**
 * 全角数字を半角数字に変換する。
 * @param {string} s
 * @returns {string}
 */
function fullwidthToHalf(s) {
  return s.replace(/[０-９]/g, (c) => String.fromCharCode(c.charCodeAt(0) - 0xFF10 + 0x30))
}

/**
 * 電話番号を正規化して数字のみにして返す。
 * 除去対象: ハイフン各種 (- － ー ‐ ‑ ‒ – —)、半角/全角スペース
 * @param {string} tel
 * @returns {string}
 */
function normalizeTelDigits(tel) {
  const half = fullwidthToHalf(tel)
  return half.replace(/[\-\－\ー\‐\‑\‒\–\—\s]/g, '')
}

/**
 * 電話番号バリデーション。
 * ハイフンあり/なし・全角数字・全角ハイフン・スペース対応。
 * 0120（フリーダイヤル）は10桁体系のため固定電話判定で通る（10桁必須）。
 * 0800（フリーダイヤル）は11桁体系のため携帯・IP電話グループで判定する。
 * @param {string} value  入力値（トリム済み）
 * @param {string} label  フィールドラベル（エラーメッセージに使用）
 * @param {boolean} required 必須かどうか
 * @returns {string} エラーメッセージ。問題なければ空文字列
 */
function validateTel(value, label, required) {
  if (value === '') return required ? `${label}を入力してください。` : ''
  const digits = normalizeTelDigits(value)
  if (!/^\d+$/.test(digits)) return `${label}の形式が正しくありません（数字のみで入力してください）。`
  if (/^(090|080|070|050|0800)/.test(digits)) {
    // 携帯・IP電話・フリーダイヤル(0800): 11桁必須
    return /^0\d{10}$/.test(digits) ? '' : `${label}（090/080/070/050/0800）は11桁で入力してください。`
  }
  // 固定電話・0120: 10桁必須
  return /^0\d{9}$/.test(digits) ? '' : `${label}は10桁で入力してください。`
}

// -----------------------------------------------------------------------
// 全角カタカナバリデーション
// -----------------------------------------------------------------------

/**
 * @param {string} value  入力値
 * @param {string} label  フィールドラベル
 * @param {boolean} required 必須かどうか
 * @returns {string} エラーメッセージ。問題なければ空文字列
 */
function validateKatakana(value, label, required) {
  if (value === '') return required ? `${label}は必須です。` : ''
  // 全角カタカナ・長音符のみ許容（スペース不可・A案）
  if (!/^[ァ-ヶー]+$/u.test(value)) return `${label}は全角カタカナで入力してください（スペース不可）。`
  return ''
}

// -----------------------------------------------------------------------
// バリデーション定義
// validate: 値を受け取り true（合格）/ string（エラーメッセージ）を返す関数
// -----------------------------------------------------------------------
const validators = {
  // 会社情報
  company: {
    validate: (v) => v !== '' || '会社名・サロン名は必須です。',
  },
  company_kana: {
    validate: (v) => validateKatakana(v, '会社名・サロン名カナ', true) || true,
  },
  // 代表者
  rep_lastname: {
    validate: (v) => v !== '' || '代表者の姓は必須です。',
  },
  rep_firstname: {
    validate: (v) => v !== '' || '代表者の名は必須です。',
  },
  rep_lastname_kana: {
    validate: (v) => validateKatakana(v, '代表者カナ（セイ）', true) || true,
  },
  rep_firstname_kana: {
    validate: (v) => validateKatakana(v, '代表者カナ（メイ）', true) || true,
  },
  // 担当者（任意）
  staff_lastname: {
    validate: () => true, // 文字数チェックはサーバー側
  },
  staff_firstname: {
    validate: () => true,
  },
  staff_lastname_kana: {
    validate: (v) => validateKatakana(v, '担当者カナ（セイ）', false) || true,
  },
  staff_firstname_kana: {
    validate: (v) => validateKatakana(v, '担当者カナ（メイ）', false) || true,
  },
  // 住所
  zipcode: {
    validate: (v) => /^\d{3}-?\d{4}$/.test(v) || '郵便番号は7桁（ハイフンありなし両対応）で入力してください。',
  },
  prefecture: {
    validate: (v) => v !== '' || '都道府県は必須です。',
  },
  city: {
    validate: (v) => v !== '' || '市区町村は必須です。',
  },
  street: {
    validate: (v) => v !== '' || '町域・番地は必須です。',
  },
  // 連絡先
  email: {
    validate: (v) => {
      if (v === '') return 'メールアドレスは必須です。'
      return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) || 'メールアドレスの形式が正しくありません。'
    },
  },
  tel: {
    validate: (v) => validateTel(v, '電話番号', true) || true,
  },
  mobile: {
    validate: (v) => validateTel(v, '携帯番号', false) || true,
  },
  // 業種・URL
  industry: {
    validate: (v) => v !== '' || '業態 / 業種は必須です。',
  },
  website: {
    validate: (v) => {
      if (v === '') return 'ホームページURLは必須です。'
      if (!/^https?:\/\/.+/i.test(v)) return 'ホームページURLは http:// または https:// で始まる形式で入力してください。'
      return true
    },
  },
  // 同意
  privacy: {
    validate: (v) => v === true || 'プライバシーポリシーへの同意が必要です。',
  },
}

/**
 * 1フィールドをバリデートして、エラー表示を更新する。
 * @param {HTMLInputElement} input
 * @returns {boolean} 合格なら true
 */
function validateField(input) {
  const id   = input.id
  const rule = validators[id]
  if (!rule) return true

  const value   = input.type === 'checkbox' ? input.checked : input.value.trim()
  const result  = rule.validate(value)
  const isValid = result === true
  const message = isValid ? '' : result

  setFieldError(input, message)
  return isValid
}

function setFieldError(input, message) {
  const errorEl = document.getElementById(`${input.id}-error`)
  if (!errorEl) return
  errorEl.textContent = message
  input.setAttribute('aria-invalid', message ? 'true' : 'false')
}

function clearFieldError(input) {
  setFieldError(input, '')
}

// -----------------------------------------------------------------------
// 郵便番号 → 住所自動補完（YubinBango.js 連携）
// YubinBango.js は class="p-postal-code" の入力値を検知して自動で住所補完する。
// ただし入力が単一フィールドにハイフン混じりで入っている場合、
// 半角化・ハイフン除去してから value を上書きしてイベントを発火させる必要がある。
// -----------------------------------------------------------------------

/**
 * 郵便番号フィールドの値を YubinBango.js が認識できる形式に正規化してイベントを発火する。
 * YubinBango.js は keyup イベントのみ監視する（ライブラリソース実地確認済み）。
 */
function triggerYubinBango() {
  if (!zipcodeInput) return
  // 全角数字→半角、ハイフン除去して7桁純粋数字に揃える
  const raw        = zipcodeInput.value
  const normalized = fullwidthToHalf(raw).replace(/-/g, '')
  if (/^\d{7}$/.test(normalized)) {
    // YubinBango.js が認識できるようにハイフンなし7桁をセット
    zipcodeInput.value = normalized
    // keyup イベントで YubinBango.js を発火（ライブラリ仕様）
    zipcodeInput.dispatchEvent(new Event('keyup', { bubbles: true }))
    showZipcodeHint('住所を自動入力しました。番地などを確認・修正してください。', 'info')
  }
}

zipcodeInput && zipcodeInput.addEventListener('blur', () => {
  const v = zipcodeInput.value.replace(/-/g, '')
  if (/^\d{7}$/.test(fullwidthToHalf(v))) {
    triggerYubinBango()
  }
})

zipcodeInput && zipcodeInput.addEventListener('keydown', (e) => {
  if (e.key === 'Enter') {
    e.preventDefault()
    triggerYubinBango()
  }
})

function showZipcodeHint(message, type) {
  if (!zipcodeHint) return
  zipcodeHint.textContent = message
  zipcodeHint.style.color = type === 'error' ? '#d9534f' : '#555'
}

// -----------------------------------------------------------------------
// プライバシーポリシー同意チェックで送信ボタン活性化
// -----------------------------------------------------------------------
privacyCb && privacyCb.addEventListener('change', () => {
  submitBtn.disabled = !privacyCb.checked
})

// -----------------------------------------------------------------------
// リアルタイムバリデーション（blur / input）
// -----------------------------------------------------------------------
const allFieldIds = Object.keys(validators)
allFieldIds.forEach((id) => {
  const el = document.getElementById(id)
  if (!el) return
  el.addEventListener('blur', () => validateField(el))
  el.addEventListener('input', () => {
    if (el.getAttribute('aria-invalid') === 'true') validateField(el)
  })
})

// -----------------------------------------------------------------------
// プライバシーポリシー モーダル
// -----------------------------------------------------------------------
const modal        = document.getElementById('privacy-modal')
const modalContent = modal && modal.querySelector('.p-modal__content')
const modalOpen    = document.getElementById('privacy-modal-open')
const modalClose   = document.getElementById('privacy-modal-close')
const modalOverlay = document.getElementById('privacy-modal-overlay')

/** フォーカストラップ: モーダル内のフォーカス可能要素に閉じ込める */
function trapFocus(e) {
  if (!modal || modal.hidden) return
  const focusable = modal.querySelectorAll(
    'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
  )
  const first = focusable[0]
  const last  = focusable[focusable.length - 1]
  if (e.key === 'Tab') {
    if (e.shiftKey) {
      if (document.activeElement === first) { e.preventDefault(); last.focus() }
    } else {
      if (document.activeElement === last) { e.preventDefault(); first.focus() }
    }
  }
  if (e.key === 'Escape') closeModal()
}

function openModal() {
  if (!modal) return
  modal.hidden = false
  document.body.style.overflow = 'hidden' // スクロールロック
  modalContent && modalContent.focus()
  document.addEventListener('keydown', trapFocus)
}

function closeModal() {
  if (!modal) return
  modal.hidden = true
  document.body.style.overflow = ''
  document.removeEventListener('keydown', trapFocus)
  modalOpen && modalOpen.focus() // 閉じたらオープンボタンへフォーカスを戻す
}

modalOpen   && modalOpen.addEventListener('click', openModal)
modalClose  && modalClose.addEventListener('click', closeModal)
modalOverlay && modalOverlay.addEventListener('click', closeModal)

// -----------------------------------------------------------------------
// エラーメッセージ領域へのスクロール（バリデーションエラー共通処理）
// prefers-reduced-motion を尊重し、設定 ON の場合は即時スクロール（'auto'）に切り替える。
// getElementById が null を返す場合はスクロールをスキップする（null ガード）。
// -----------------------------------------------------------------------

/**
 * #form-message 要素までスクロールする。
 * AJAX 経路では PHP が出力する hidden 状態の <div id="form-message">、
 * 非AJAX 経路（?error=1）では PHP が出力する c-form-message--error 状態の <div id="form-message"> を対象とする。
 * 両経路で id="form-message" は DOM 上に1つだけ存在する（PHP 側で if/else 分岐済み）。
 * エラー表示後の描画タイミングを確保するため requestAnimationFrame でラップする。
 */
function scrollToFormMessage() {
  const formMessage = document.getElementById('form-message')
  if (!formMessage) return // 要素が存在しない場合はスキップ
  const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches
  requestAnimationFrame(() => {
    formMessage.scrollIntoView({ behavior: prefersReduced ? 'auto' : 'smooth', block: 'start' })
  })
}

// -----------------------------------------------------------------------
// フォーム送信
// -----------------------------------------------------------------------
form && form.addEventListener('submit', async (e) => {
  e.preventDefault()

  // 全フィールドバリデーション
  const isAllValid = allFieldIds.every((id) => {
    const el = document.getElementById(id)
    if (!el) return true // DOM にない項目はスキップ
    return validateField(el)
  })

  if (!isAllValid) {
    // クライアント側エラー: エラーサマリを表示してからフォームトップへスクロール
    showFormMessage('入力内容に誤りがあります。各項目を確認してください。', 'error')
    scrollToFormMessage()
    return
  }

  submitBtn.disabled    = true
  submitBtn.textContent = '送信中...'
  hideFormMessage()

  try {
    const res  = await fetch(form.action, {
      method:  'POST',
      body:    new FormData(form),
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
    const json = await res.json()

    if (json.success) {
      // ブラウザ履歴を汚さない（戻るボタンで再送信誘発を防止）
      window.location.replace('./thanks.html')
      return
    } else {
      // サーバー側エラー（AJAX）: エラーメッセージ表示後にフォームトップへスクロール
      showFormMessage(json.message, 'error')
      scrollToFormMessage()
      submitBtn.disabled    = !privacyCb.checked
      submitBtn.textContent = '送信する'
    }
  } catch (_e) {
    // 通信エラー: エラーメッセージ表示後にフォームトップへスクロール
    showFormMessage('通信エラーが発生しました。しばらく時間をおいて再度お試しください。', 'error')
    scrollToFormMessage()
    submitBtn.disabled    = !privacyCb.checked
    submitBtn.textContent = '送信する'
  }
})

// -----------------------------------------------------------------------
// 非AJAX環境フォールバック: ?error=1 でリダイレクトされてきた場合のスクロール
// submit.php が JS 無効環境で 303 リダイレクトする際、index.php?error=1 に戻ってくる。
// ページロード後に #form-message までスクロールしてエラーメッセージを視認させる。
// -----------------------------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {
  const params = new URLSearchParams(window.location.search)
  if (params.get('error') === '1') {
    scrollToFormMessage()
  }
})

function showFormMessage(message, type) {
  if (!formMsg) return
  formMsg.textContent = message
  formMsg.className   = `c-form-message c-form-message--${type}`
  formMsg.hidden      = false
}

function hideFormMessage() {
  if (!formMsg) return
  formMsg.hidden      = true
  formMsg.textContent = ''
  formMsg.className   = 'c-form-message'
}
