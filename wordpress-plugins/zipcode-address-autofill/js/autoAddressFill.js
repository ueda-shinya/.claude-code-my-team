// HTML内の'id="contact-form"'というdivを取得
const contactFormDiv = document.getElementById('contact-form');

// Contact Form 7のショートコードが生成するフィールドを取得
const contactForm = {
  postcode: document.querySelector('[name="postcode"]'),
  prefecture: document.querySelector('[name="prefecture"]'),
  city: document.querySelector('[name="city"]'),
  town: document.querySelector('[name="town"]')
};

// 'postcode'フィールドで入力があった場合のイベントリスナを追加
contactForm.postcode.addEventListener('input', e => {

  // zipcloud APIにリクエストを行い、入力された郵便番号に対応する住所を取得
  fetch(`https://zipcloud.ibsnet.co.jp/api/search?zipcode=${e.target.value}`)
    
    // APIのレスポンスをJSONとして解析
    .then(response => response.json())
    
    // 解析したデータ（住所情報）をフォームの各フィールドに自動入力
    .then(data => {
      if (data.results && data.results.length > 0) {
        contactForm.prefecture.value = data.results[0].address1; // 都道府県
        contactForm.city.value = data.results[0].address2;       // 市区町村
        contactForm.town.value = data.results[0].address3;       // 町名・番地等
      }
    })
    
    // エラーが発生した場合、コンソールにエラーメッセージを出力
    .catch(error => console.log(error))
});
