'use strict'

// -----------------------------------------------------------------------
// 設定
// -----------------------------------------------------------------------
const ZIPCLOUD_ENDPOINT = 'https://zipcloud.ibsnet.co.jp/api/search'
const ZIP_FETCH_TIMEOUT_MS = 5000 // L16: タイムアウト5秒

// -----------------------------------------------------------------------
// DOM取得
// -----------------------------------------------------------------------
const form       = document.getElementById('contact-form')
const submitBtn  = document.getElementById('submit-btn')
const privacyCb  = document.getElementById('privacy')
const zipInput   = document.getElementById('zip')
const zipSearch  = document.getElementById('zip-search')
const addressInput = document.getElementById('address')
const zipHint    = document.getElementById('zip-hint')
const formMsg    = document.getElementById('form-message')

// -----------------------------------------------------------------------
// プライバシーポリシー同意チェックで送信ボタン活性化
// -----------------------------------------------------------------------
privacyCb.addEventListener('change', () => {
  submitBtn.disabled = !privacyCb.checked
})

// -----------------------------------------------------------------------
// 郵便番号 → 住所自動反映
// -----------------------------------------------------------------------
async function fetchAddress(zip) {
  const normalized = zip.replace('-', '')
  if (!/^\d{7}$/.test(normalized)) {
    showZipHint('郵便番号は7桁で入力してください。', 'error')
    return
  }

  zipHint.textContent = '検索中...'

  // L16: AbortSignal.timeout でタイムアウト設定
  const signal = typeof AbortSignal !== 'undefined' && AbortSignal.timeout
    ? AbortSignal.timeout(ZIP_FETCH_TIMEOUT_MS)
    : undefined

  try {
    const fetchOptions = { signal }
    const res = await fetch(`${ZIPCLOUD_ENDPOINT}?zipcode=${normalized}`, fetchOptions)
    if (!res.ok) throw new Error(`HTTP ${res.status}`)

    const json = await res.json()

    if (json.status !== 200 || !json.results) {
      showZipHint('該当する住所が見つかりませんでした。', 'error')
      return
    }

    const r = json.results[0]
    const fullAddress = (r.address1 || '') + (r.address2 || '') + (r.address3 || '')
    addressInput.value = fullAddress
    clearFieldError(addressInput)
    showZipHint('住所を反映しました。番地・部屋番号を追記してください。', 'info')
  } catch (e) {
    showZipHint('住所検索に失敗しました。手動で入力してください。', 'error')
  }
}

function showZipHint(message, type) {
  zipHint.textContent = message
  zipHint.style.color = type === 'error' ? '#d9534f' : '#555'
}

zipSearch.addEventListener('click', () => fetchAddress(zipInput.value))

// Enterキーで検索（フォーム送信防止）
zipInput.addEventListener('keydown', (e) => {
  if (e.key === 'Enter') {
    e.preventDefault()
    fetchAddress(zipInput.value)
  }
})

// -----------------------------------------------------------------------
// クライアントサイドバリデーション
// -----------------------------------------------------------------------
const validators = {
  name: {
    validate: (v) => v !== '',
    message: 'お名前を入力してください。',
  },
  tel: {
    validate: (v) => {
      if (v === '') return false
      if (!/^[0-9\-]+$/.test(v)) return false
      const digits = v.replace(/-/g, '')
      // 先頭3-4桁で携帯・IP電話・フリーダイヤル(0800)か固定電話かを判定し、桁数を検証する
      if (/^(090|080|070|050|0800)/.test(digits)) {
        return /^0\d{10}$/.test(digits) // 携帯・IP電話・フリーダイヤル(0800): 11桁必須
      }
      return /^0\d{9}$/.test(digits) // 固定電話: 10桁必須
    },
    // 形式エラーと桁数エラーを分けて表示するため、メッセージは getErrorMessage で決定
    message: null,
    getErrorMessage: (v) => {
      if (v === '') return '電話番号を入力してください。'
      if (!/^[0-9\-]+$/.test(v)) return '電話番号の形式が正しくありません。'
      const digits = v.replace(/-/g, '')
      if (/^(090|080|070|050|0800)/.test(digits)) {
        return '携帯電話・フリーダイヤル(0800)（090, 080, 070, 050, 0800）は11桁で入力してください。'
      }
      return '固定電話番号は10桁で入力してください。'
    },
  },
  email: {
    validate: (v) => v !== '' && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v),
    message: '正しいメールアドレスを入力してください。',
  },
  zip: {
    validate: (v) => /^\d{3}-?\d{4}$/.test(v),
    message: '郵便番号は7桁（ハイフンありなし両対応）で入力してください。',
  },
  address: {
    validate: (v) => v !== '',
    message: '住所を入力してください。',
  },
  privacy: {
    validate: (v) => v === true,
    message: 'プライバシーポリシーへの同意が必要です。',
  },
}

/**
 * 1フィールドをバリデートして、エラー表示を更新する。
 * @param {HTMLInputElement} input
 * @returns {boolean}
 */
function validateField(input) {
  const id   = input.id
  const rule = validators[id]
  if (!rule) return true

  const value  = input.type === 'checkbox' ? input.checked : input.value.trim()
  const isValid = rule.validate(value)

  // getErrorMessage が定義されている場合は動的メッセージを使用（tel 等）
  const message = isValid
    ? ''
    : (rule.getErrorMessage ? rule.getErrorMessage(value) : rule.message)
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

// 入力時にリアルタイムでバリデーション
;['name', 'tel', 'email', 'zip', 'address'].forEach((id) => {
  const el = document.getElementById(id)
  el.addEventListener('blur', () => validateField(el))
  el.addEventListener('input', () => {
    if (el.getAttribute('aria-invalid') === 'true') validateField(el)
  })
})

privacyCb.addEventListener('change', () => validateField(privacyCb))

// -----------------------------------------------------------------------
// フォーム送信
// -----------------------------------------------------------------------
form.addEventListener('submit', async (e) => {
  e.preventDefault()

  // 全フィールドバリデーション
  const fields = ['name', 'tel', 'email', 'zip', 'address', 'privacy']
  const isAllValid = fields.every((id) => {
    const el = document.getElementById(id)
    return validateField(el)
  })

  if (!isAllValid) {
    showFormMessage('入力内容に誤りがあります。各項目を確認してください。', 'error')
    return
  }

  submitBtn.disabled = true
  submitBtn.textContent = '送信中...'
  hideFormMessage()

  try {
    const res = await fetch(form.action, {
      method:  'POST',
      body:    new FormData(form),
      headers: { 'X-Requested-With': 'XMLHttpRequest' }, // サーバー側 AJAX 判定に使用
    })
    const json = await res.json()

    if (json.success) {
      window.location.replace('./thanks.html') // ブラウザ履歴を汚さない（戻るボタンで再送信誘発を防止）
      return
    } else {
      showFormMessage(json.message, 'error')
      submitBtn.disabled = !privacyCb.checked
    }
  } catch (e) {
    showFormMessage('通信エラーが発生しました。しばらく時間をおいて再度お試しください。', 'error')
    submitBtn.disabled = !privacyCb.checked
  }

  submitBtn.textContent = '送信する'
})

function showFormMessage(message, type) {
  formMsg.textContent = message
  formMsg.className   = `c-form-message c-form-message--${type}`
  formMsg.hidden      = false
  formMsg.scrollIntoView({ behavior: 'smooth', block: 'nearest' })
}

function hideFormMessage() {
  formMsg.hidden    = true
  formMsg.textContent = ''
  formMsg.className   = 'c-form-message'
}
