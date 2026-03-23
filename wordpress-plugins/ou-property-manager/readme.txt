=== OU Property Manager ===
Contributors: officeueda
Tags: property, real-estate, custom-post-type, search, acf
Requires at least: 6.0
Tested up to: 6.x
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later


ACF PRO を用いた物件管理 + Ajax 絞り込み検索 + PDFプレビュー/自動JPG（環境対応時）。


== Installation ==
1. フォルダ `ou-property-manager` を `wp-content/plugins/` に配置し有効化
2. ACF PRO を有効化
3. 固定ページに `[oupm_search]` を挿入して公開
4. 物件を登録（媒体PDFを添付）。環境が許せば JPGが自動生成され一覧サムネイルに使用。


== Notes ==
- PDF→JPG はサーバ設定に依存（Imagick+Ghostscript+policy.xml）。不可の場合はPDF埋め込み表示で運用。
- 掲載期限が過ぎた物件は検索結果から除外。