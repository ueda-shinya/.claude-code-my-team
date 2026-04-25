document.addEventListener("DOMContentLoaded", () => {
  const contactForm = document.forms.contact;
  contactForm.postcode.addEventListener("input", (e) => {
    const zip = e.target.value.replace(/[^0-9]/g, "");
    if (zip.length === 7) {
      fetch(`https://zipcloud.ibsnet.co.jp/api/search?zipcode=${zip}`)
        .then((response) => response.json())
        .then((data) => {
          if (data.results) {
            contactForm.pref.value = data.results[0].address1;
            contactForm.city.value = data.results[0].address2;
            contactForm.address.value = data.results[0].address3;
          }
        })
        .catch((error) => console.log("住所取得エラー:", error));
    }
  });
});
