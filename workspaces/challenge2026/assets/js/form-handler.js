// -----------------------------------------------------------------------
// tel バリデーション（サーバー側と同一ロジック）
// -----------------------------------------------------------------------
/**
 * 電話番号のクライアント側バリデーション
 * @param {string} value - 入力値
 * @returns {{ valid: boolean, message: string }}
 */
function validateTel(value) {
  if (value === '') {
    return { valid: false, message: '電話番号を入力してください。' }
  }
  if (!/^[0-9\-]+$/.test(value)) {
    return { valid: false, message: '電話番号の形式が正しくありません。' }
  }
  const digits = value.replace(/-/g, '')
  // 先頭3桁で携帯・IP電話か固定電話かを判定し、桁数を検証する
  if (/^(090|080|070|050|0800)/.test(digits)) {
    // 携帯・IP電話・フリーダイヤル(0800): 11桁必須
    if (!/^0\d{10}$/.test(digits)) {
      return { valid: false, message: '携帯電話・フリーダイヤル(0800)（090, 080, 070, 050, 0800）は11桁で入力してください。' }
    }
  } else {
    // 固定電話: 10桁必須
    if (!/^0\d{9}$/.test(digits)) {
      return { valid: false, message: '固定電話番号は10桁で入力してください。' }
    }
  }
  return { valid: true, message: '' }
}

/**
 * tel inputのエラー表示を更新する
 * @param {HTMLInputElement} telInput
 * @param {string} message - 空文字でクリア
 */
function setTelError(telInput, message) {
  let errorEl = document.getElementById('tel-error')
  if (!errorEl) {
    errorEl = document.createElement('span')
    errorEl.id = 'tel-error'
    errorEl.style.color = '#d9534f'
    errorEl.style.fontSize = '0.85em'
    errorEl.style.display = 'block'
    telInput.parentNode.appendChild(errorEl)
  }
  errorEl.textContent = message
  telInput.setAttribute('aria-invalid', message ? 'true' : 'false')
}

document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("contact-form");
  const csrfInput = form.querySelector('input[name="csrf_token"]');
  const telInput = form.querySelector('input[name="tel"]')

  // blur・input でリアルタイムバリデーション
  if (telInput) {
    telInput.addEventListener('blur', () => {
      const result = validateTel(telInput.value.trim())
      setTelError(telInput, result.valid ? '' : result.message)
    })
    telInput.addEventListener('input', () => {
      if (telInput.getAttribute('aria-invalid') === 'true') {
        const result = validateTel(telInput.value.trim())
        setTelError(telInput, result.valid ? '' : result.message)
      }
    })
  }

  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    // tel バリデーション（確認画面遷移前にブロック）
    if (telInput) {
      const result = validateTel(telInput.value.trim())
      setTelError(telInput, result.valid ? '' : result.message)
      if (!result.valid) {
        telInput.focus()
        return
      }
    }

    const formData = new FormData(form);

    try {
      const res = await fetch(form.action, {
        method: "POST",
        body: formData
      });

      const result = await res.json();

      // 成功時の処理
      if (result.status === "success" && res.ok) {
        // トークン更新（次回送信用）
        if (result.token) csrfInput.value = result.token;

        // サンクスページへ遷移
        window.location.href = result.redirect;
        return;
      }

      // エラー時の処理
      alert("送信エラー：" + (result.error || "不明なエラーが発生しました。"));
      if (result.token) csrfInput.value = result.token;

    } catch (error) {
      alert("通信エラーが発生しました。");
      console.error(error);
    }
  });
});
