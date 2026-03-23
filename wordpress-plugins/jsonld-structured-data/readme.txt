=== JSON-LD Structured Data Output ===
Contributors: officeueda
Tags: json-ld, structured data, seo, schema.org, blogposting, rich results
Requires at least: 5.5
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

出力対象の投稿・固定ページに自動的に JSON-LD BlogPosting 構造化データを追加し、SEOとリッチリザルト対応を強化します。

== Description ==
このプラグインは、WordPress の投稿や固定ページに対して Google リッチリザルトに対応した JSON-LD 形式の構造化データ（schema.org/BlogPosting）を自動出力します。

**主な機能：**
* タイトル、URL、著者名、抜粋、画像、日付、ロゴ、運営者情報を自動出力
* 管理画面から出力内容をカスタマイズ可能
* `isAccessibleForFree` による有料/無料の明示的フラグ出力に対応
* アイキャッチがない場合のデフォルト画像指定機能
* 投稿タイプごとに出力有無を制御可能
* プラグイン削除時に設定を残すか削除するかの制御が可能

== Installation ==
1. このフォルダを `jsonld-structured-data` という名前で `wp-content/plugins/` にアップロードしてください。
2. 管理画面の「プラグイン」メニューから有効化します。
3. 「設定 > JSON-LD構造化データ設定」から設定を行ってください。

== Frequently Asked Questions ==
= Q. 構造化データの構造を変更したい場合は？ =
A. `jsonld_sd_structured_data` フィルターフックで `$data` を変更可能です。

== Screenshots ==
1. 管理画面の設定画面
2. 出力されたJSON-LDコードの例

== Changelog ==
= 1.0.0 =
* 初回リリース

== Upgrade Notice ==
= 1.0.0 =
最初の安定バージョンです。
