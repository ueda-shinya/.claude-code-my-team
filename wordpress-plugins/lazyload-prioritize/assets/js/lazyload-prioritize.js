document.addEventListener("DOMContentLoaded", () => {
  console.log("スクリプト開始: lazyload-prioritize.js");

  const lazyImages = document.querySelectorAll("img[loading='lazy']");
  const normalImages = document.querySelectorAll("img:not([loading='lazy'])");
  let normalImagesLoaded = 0;

  console.log(`通常画像の数: ${normalImages.length}`);
  console.log(`Lazy画像の数: ${lazyImages.length}`);

  function checkNormalImagesLoaded() {
    console.log(
      `読み込まれた通常画像数: ${normalImagesLoaded} / ${normalImages.length}`
    );

    if (normalImagesLoaded === normalImages.length) {
      console.log(
        "すべての通常画像が読み込まれました！Lazy画像の優先読み込みを開始します。"
      );
      prioritizeLazyImages();
    }
  }

  // 通常画像のロード完了をカウント
  normalImages.forEach((img) => {
    if (img.complete) {
      console.log(`すでに読み込まれている画像: ${img.src}`);
      normalImagesLoaded++;
    } else {
      img.onload = () => {
        normalImagesLoaded++;
        console.log(`読み込み完了: ${img.src}`);
        checkNormalImagesLoaded();
      };
      img.onerror = () => {
        console.warn(`読み込み失敗: ${img.src}`);
        normalImagesLoaded++; // 失敗してもカウントを進める
        checkNormalImagesLoaded();
      };
    }
  });

  // ビューポートに近いLazy画像から優先的に読み込む
  function prioritizeLazyImages() {
    if (lazyImages.length === 0) {
      console.log("Lazy画像がありません。処理を終了します。");
      return;
    }

    const distances = [];

    // すべてのLazy画像のビューポートからの距離を計算
    lazyImages.forEach((img) => {
      const rect = img.getBoundingClientRect();
      const distance = Math.abs(rect.top); // ビューポートの上からの距離
      distances.push({ img, distance });
    });

    // 距離が近い順にソート
    distances.sort((a, b) => a.distance - b.distance);

    // 距離が近い順に画像をロード
    distances.forEach(({ img }, index) => {
      setTimeout(() => {
        console.log(`優先読み込み開始: ${img.src}`);
        img.removeAttribute("loading"); // lazy を削除
        img.src = img.src; // 強制リロード
      }, index * 100); // 100ms 間隔で順番に読み込み
    });
  }

  // ページロード時に通常画像がすでに読み込まれているかチェック
  checkNormalImagesLoaded();
});
