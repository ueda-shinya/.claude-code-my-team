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
  zipHint.className = 'field-hint'

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
    validate: (v) => v !== '' && /^[\d\-０-９ー]+$/.test(v),
    message: '電話番号は数字とハイフンのみ入力してください。',
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

  setFieldError(input, isValid ? '' : rule.message)
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
      method: 'POST',
      body:   new FormData(form),
    })
    const json = await res.json()

    if (json.success) {
      showFormMessage(json.message, 'success')
      form.reset()
      submitBtn.disabled = true
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
  formMsg.className   = `form-message form-message--${type}`
  formMsg.hidden      = false
  formMsg.scrollIntoView({ behavior: 'smooth', block: 'nearest' })
}

function hideFormMessage() {
  formMsg.hidden    = true
  formMsg.textContent = ''
  formMsg.className   = 'form-message'
}
